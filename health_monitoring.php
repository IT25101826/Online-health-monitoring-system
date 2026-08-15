<?php

// Database connection parameters
$host     = "127.0.0.1";
$username = "root";
$password = "";
$database = "health_monitoring";
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Automatically create database if missing
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

// Automatically create tables matching system schema
$conn->query("CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(60) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) UNIQUE,
    license_no VARCHAR(30) UNIQUE NOT NULL,
    availability_status ENUM('available', 'on_leave', 'unavailable') DEFAULT 'available'
)");

$conn->query("CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('M', 'F', 'Other') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(200)
)");

$conn->query("CREATE TABLE IF NOT EXISTS health_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    diagnosis VARCHAR(255) NOT NULL,
    vitals VARCHAR(150),
    record_date DATE NOT NULL,
    remarks TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    record_id INT,
    generated_by INT,
    recommendation TEXT NOT NULL,
    report_date DATE NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES health_records(record_id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES doctors(doctor_id) ON DELETE SET NULL
)");

// Insert Sample Data if empty (so dropdowns work right away)
$check_patients = $conn->query("SELECT * FROM patients");
if ($check_patients->num_rows == 0) {
    $conn->query("INSERT INTO patients (full_name, dob, gender, phone, address) VALUES 
        ('John Doe', '1990-05-15', 'M', '0771234567', 'Colombo 03'),
        ('Jane Smith', '1985-08-22', 'F', '0719876543', 'Kandy')");
}

$check_doctors = $conn->query("SELECT * FROM doctors");
if ($check_doctors->num_rows == 0) {
    $conn->query("INSERT INTO doctors (full_name, specialization, phone, email, license_no) VALUES 
        ('Dr. Alan Grant', 'Cardiology', '0770001112', 'alan@medinova.com', 'LIC-1001'),
        ('Dr. Sarah Connor', 'General Medicine', '0770003334', 'sarah@medinova.com', 'LIC-1002')");
}


// ==========================
// ADD HEALTH RECORD
// ==========================

if (isset($_POST['add_health_record'])) {

    $patient_id  = $_POST['patient_id'];
    $doctor_id   = $_POST['doctor_id'];
    $diagnosis   = $_POST['diagnosis'];
    $vitals      = $_POST['vitals'];
    $record_date = $_POST['record_date'];
    $remarks     = $_POST['remarks'];

    try {
        $sql = "INSERT INTO health_records (patient_id, doctor_id, diagnosis, vitals, record_date, remarks) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", $patient_id, $doctor_id, $diagnosis, $vitals, $record_date, $remarks);

        if ($stmt->execute()) {
            $message = "Health record added successfully!";
        } else {
            $error_message = "Error adding record: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Failed: Selected Patient or Doctor ID does not exist in database.";
    }
}


// ==========================
// GENERATE REPORT
// ==========================

if (isset($_POST['generate_report'])) {

    $patient_id     = $_POST['rep_patient_id'];
    $record_id      = $_POST['rep_record_id'];
    $generated_by   = $_POST['generated_by'];
    $recommendation = $_POST['recommendation'];
    $report_date    = $_POST['report_date'];

    try {
        $sql = "INSERT INTO reports (patient_id, record_id, generated_by, recommendation, report_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $patient_id, $record_id, $generated_by, $recommendation, $report_date);

        if ($stmt->execute()) {
            $message = "Report generated successfully!";
        } else {
            $error_message = "Error generating report: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Failed to generate report: Make sure Patient ID, Record ID, and Doctor ID exist in database first.";
    }
}


// ==========================
// DELETE ACTIONS
// ==========================

if (isset($_GET['delete_record'])) {
    $record_id = $_GET['delete_record'];
    $stmt = $conn->prepare("DELETE FROM health_records WHERE record_id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $stmt->close();
    header("Location: health_monitoring.php");
    exit();
}

if (isset($_GET['delete_report'])) {
    $report_id = $_GET['delete_report'];
    $stmt = $conn->prepare("DELETE FROM reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
    header("Location: health_monitoring.php");
    exit();
}


// ==========================
// DATA RETRIEVAL
// ==========================

$patients_opt = $conn->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC");
$doctors_opt  = $conn->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name ASC");

$records_query = "SELECT r.*, p.full_name AS patient_name, d.full_name AS doctor_name 
                  FROM health_records r
                  LEFT JOIN patients p ON r.patient_id = p.patient_id
                  LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
                  ORDER BY r.record_id DESC";
$health_records_result = $conn->query($records_query);

$reports_query = "SELECT rep.*, p.full_name AS patient_name, d.full_name AS doctor_name 
                  FROM reports rep
                  LEFT JOIN patients p ON rep.patient_id = p.patient_id
                  LEFT JOIN doctors d ON rep.generated_by = d.doctor_id
                  ORDER BY rep.report_id DESC";
$reports_result = $conn->query($reports_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring & Report Management</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f7fb; }
        .app-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #102b3a, #1b5261); color: #ffffff; padding: 30px 20px; flex-shrink: 0; position: fixed; left: 0; top: 0; bottom: 0; }
        .sidebar .brand h1 { margin: 0; font-size: 30px; color: #fff; }
        .sidebar .brand h1 span { color: #75eaff; }
        .sidebar .brand p { margin: 10px 0 0; font-size: 12px; letter-spacing: 1px; color: #bfdee8; }
        .side-nav a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #d7eef2; padding: 12px 14px; border-radius: 8px; margin-bottom: 10px; font-size: 14px; }
        .side-nav a:hover, .side-nav a.active { background-color: #0ba6b7; color: white; }
        .main-content { flex: 1; min-width: 0; margin-left: 280px; }
        .container { width: 90%; max-width: 1200px; margin: 30px auto; }
        .sub-nav { border-radius: 10px; padding: 10px; margin-bottom: 20px; display: flex; background: linear-gradient(180deg, #102b3a, #1b5261); color: #ffffff; }
        .sub-nav a { text-decoration: none; color: #ffffff; padding: 10px 18px; margin-left: 10px; border-radius: 6px; }
        .sub-nav a.active, .sub-nav a:hover { background-color: #0ba6b7; }
        .card { background-color: white; padding: 25px; margin-bottom: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #0ba6b7; margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-group { margin-bottom: 10px; }
        .full-width { grid-column: span 2; }
        .btn { background-color: #0ba6b7; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 15px; }
        .btn:hover { background-color: #125ca5; }
        .table-wrapper { width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #d7e8f2; }
        table { width: 100%; min-width: 760px; border-collapse: collapse; }
        th { color: black; padding: 10px; background-color: #eef6f9; }
        td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; white-space: nowrap; }
        .delete-btn { background-color: #dc3545; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; }
        .message { background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 5px; }
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 5px; }
        @media (max-width: 768px) {
            .app-shell { display: block; }
            .sidebar { width: 100%; position: relative; }
            .main-content { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>

<body>

<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <h1>MEDI <span>NOVA</span>.</h1>
            <p>Health Monitoring System</p>
        </div>
        <nav class="side-nav">
            <a href="#health-record-form" class="active"><span>◆</span> <span>Health Monitoring</span></a>
            <a href="#report-generation"><span>◆</span> <span>Report Management</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="container">

            <div class="sub-nav">
                <a href="#health-record-form">Add Health Record</a>
                <a href="#health-history">Health History</a>
                <a href="#report-generation">Generate Report</a>
                <a href="#report-view">View Reports</a>
            </div>

            <?php if (isset($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="error-msg"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- ADD HEALTH RECORD FORM -->
            <div class="card" id="health-record-form">
                <h2>Add Health Record</h2>
                <form method="POST" action="health_monitoring.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Patient</label>
                            <select name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php 
                                if ($patients_opt && $patients_opt->num_rows > 0) {
                                    while ($p = $patients_opt->fetch_assoc()) {
                                        echo "<option value='".$p['patient_id']."'>".htmlspecialchars($p['full_name'])." (ID: ".$p['patient_id'].")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Attending Doctor</label>
                            <select name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php 
                                if ($doctors_opt && $doctors_opt->num_rows > 0) {
                                    while ($d = $doctors_opt->fetch_assoc()) {
                                        echo "<option value='".$d['doctor_id']."'>".htmlspecialchars($d['full_name'])." (ID: ".$d['doctor_id'].")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Diagnosis</label>
                            <input type="text" name="diagnosis" placeholder="e.g. Hypertension" required>
                        </div>

                        <div class="form-group">
                            <label>Vitals</label>
                            <input type="text" name="vitals" placeholder="e.g. BP: 120/80">
                        </div>

                        <div class="form-group">
                            <label>Record Date</label>
                            <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Clinical Remarks</label>
                            <textarea name="remarks" rows="3" placeholder="Clinical notes..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="add_health_record" class="btn">Save Health Record</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- HEALTH HISTORY TABLE -->
            <div class="card" id="health-history">
                <h2>Health History Page</h2>
                <div class="table-wrapper"
                    <table>
                        <tr>
                            <th>Record ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Vitals</th>
                            <th>Record Date</th>
                            <th>Action</th>
                        </tr>
                        <?php if ($health_records_result && $health_records_result->num_rows > 0): ?>
                            <?php while ($rec = $health_records_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rec['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rec['patient_name'] ?? 'ID: '.$rec['patient_id']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['doctor_name'] ?? 'ID: '.$rec['doctor_id']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['diagnosis']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['vitals'] ?? 'N/A'); ?></td>
                                    <td><?php echo $rec['record_date']; ?></td>
                                    <td><a href="health_monitoring.php?delete_record=<?php echo $rec['record_id']; ?>" class="delete-btn" onclick="return confirm('Delete?');">Delete</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No records found.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- GENERATE REPORT FORM -->
            <div class="card" id="report-generation">
                <h2>Generate Medical Report</h2>
                <form method="POST" action="health_monitoring.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Patient ID</label>
                            <input type="number" name="rep_patient_id" placeholder="Use ID: 1 or 2" required>
                        </div>

                        <div class="form-group">
                            <label>Health Record ID</label>
                            <input type="number" name="rep_record_id" placeholder="Enter Record ID from table above" required>
                        </div>

                        <div class="form-group">
                            <label>Generated By (Doctor ID)</label>
                            <input type="number" name="generated_by" placeholder="Use ID: 1 or 2" required>
                        </div>

                        <div class="form-group">
                            <label>Report Date</label>
                            <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Medical Recommendation</label>
                            <textarea name="recommendation" rows="3" placeholder="Enter medical advice..." required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="generate_report" class="btn">Generate & Save Report</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- REPORTS TABLE -->
            <div class="card" id="report-view">
                <h2>Generated Reports List</h2>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th>Report ID</th>
                            <th>Patient</th>
                            <th>Record ID</th>
                            <th>Doctor</th>
                            <th>Recommendation</th>
                            <th>Report Date</th>
                            <th>Action</th>
                        </tr>
                        <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                            <?php while ($rep = $reports_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rep['report_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rep['patient_name'] ?? 'ID: '.$rep['patient_id']); ?></td>
                                    <td><?php echo $rep['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rep['doctor_name'] ?? 'ID: '.$rep['generated_by']); ?></td>
                                    <td><?php echo htmlspecialchars($rep['recommendation']); ?></td>
                                    <td><?php echo $rep['report_date']; ?></td>
                                    <td><a href="health_monitoring.php?delete_report=<?php echo $rep['report_id']; ?>" class="delete-btn" onclick="return confirm('Delete?');">Delete</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No reports generated yet.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>
<?php $conn->close(); ?>