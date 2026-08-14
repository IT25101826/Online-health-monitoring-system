<?php

// Database connection parameters
$host     = "127.0.0.1";
$username = "root";
$password = "";
$database = "health_monitoring";

// Connect to MySQL server
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
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    record_id INT,
    generated_by INT,
    recommendation TEXT NOT NULL,
    report_date DATE NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (record_id) REFERENCES health_records(record_id),
    FOREIGN KEY (generated_by) REFERENCES doctors(doctor_id)
)");


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

    $sql = "INSERT INTO health_records
            (patient_id, doctor_id, diagnosis, vitals, record_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iissss",
        $patient_id,
        $doctor_id,
        $diagnosis,
        $vitals,
        $record_date,
        $remarks
    );

    if ($stmt->execute()) {
        $message = "Health record added successfully!";
    } else {
        $message = "Error adding health record: " . $stmt->error;
    }

    $stmt->close();
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

    $sql = "INSERT INTO reports
            (patient_id, record_id, generated_by, recommendation, report_date)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiiss",
        $patient_id,
        $record_id,
        $generated_by,
        $recommendation,
        $report_date
    );

    if ($stmt->execute()) {
        $message = "Report generated successfully!";
    } else {
        $message = "Error generating report: " . $stmt->error;
    }

    $stmt->close();
}


// ==========================
// DELETE HEALTH RECORD
// ==========================

if (isset($_GET['delete_record'])) {

    $record_id = $_GET['delete_record'];

    $sql = "DELETE FROM health_records WHERE record_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $record_id);

    if ($stmt->execute()) {
        header("Location: health_monitoring.php");
        exit();
    }

    $stmt->close();
}


// ==========================
// DELETE REPORT
// ==========================

if (isset($_GET['delete_report'])) {

    $report_id = $_GET['delete_report'];

    $sql = "DELETE FROM reports WHERE report_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        header("Location: health_monitoring.php");
        exit();
    }

    $stmt->close();
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f7fb;
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #102b3a, #1b5261);
            color: #ffffff;
            padding: 30px 20px;
            flex-shrink: 0;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .sidebar .brand {
            padding-bottom: 30px;
        }

        .sidebar .brand h1 {
            margin: 0;
            font-size: 30px;
            color: #fff;
        }

        .sidebar .brand h1 span {
            color: #75eaff;
        }

        .sidebar .brand p {
            margin: 10px 0 0;
            font-size: 12px;
            letter-spacing: 1px;
            color: #bfdee8;
        }

        .side-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #d7eef2;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .side-nav a:hover,
        .side-nav a.active {
            background-color: #0ba6b7;
            color: white;
        }

        .side-nav a .icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background-color: rgba(255,255,255,0.08);
        }

        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 280px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .sub-nav {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            background: linear-gradient(180deg, #102b3a, #1b5261);
            color: #ffffff;
        }

        .sub-nav a {
            text-decoration: none;
            color: #ffffff;
            padding: 10px 18px;
            margin-left: 10px;
            border-radius: 6px;
        }

        .sub-nav a.active,
        .sub-nav a:hover {
            background-color: #0ba6b7;
            color: white;
        }

        .card {
            background-color: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2 {
            color: #0ba6b7;
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: inherit;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .full-width {
            grid-column: span 2;
        }

        .btn {
            background-color: #0ba6b7;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn:hover {
            background-color: #125ca5;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            border: 1px solid #d7e8f2;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        th {
            color: black;
            padding: 10px;
            background-color: #eef6f9;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            white-space: nowrap;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            padding: 7px 12px;
            text-decoration: none;
            border-radius: 4px;
        }

        .delete-btn:hover {
            background-color: #b02a37;
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .app-shell {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
                height: auto;
            }

            .main-content {
                width: 100%;
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            table {
                font-size: 12px;
            }

            .container {
                width: 95%;
            }
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
            <a href="#">
                <span class="icon">◆</span>
                <span>Doctor Management</span>
            </a>
            <a href="#">
                <span class="icon">◆</span>
                <span>Patient Management</span>
            </a>
            <a href="#">
                <span class="icon">◆</span>
                <span>User Authentication</span>
            </a>
            <a href="#">
                <span class="icon">◆</span>
                <span>Appointment Management</span>
            </a>
            <a href="#health-record-form" class="active">
                <span class="icon">◆</span>
                <span>Health Monitoring</span>
            </a>
            <a href="#report-generation">
                <span class="icon">◆</span>
                <span>Report Management</span>
            </a>
            <a href="#">
                <span class="icon">◆</span>
                <span>Admin Dashboard</span>
            </a>
            <a href="#">
                <span class="icon">◆</span>
                <span>System Management</span>
            </a>
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
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>


            <!-- ==========================
                 HEALTH RECORD FORM
                 ========================== -->

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
                            <input type="text" name="diagnosis" placeholder="e.g. Hypertension - Stage 1" required>
                        </div>

                        <div class="form-group">
                            <label>Vitals</label>
                            <input type="text" name="vitals" placeholder="e.g. BP: 120/80, Pulse: 72bpm">
                        </div>

                        <div class="form-group">
                            <label>Record Date</label>
                            <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Clinical Remarks</label>
                            <textarea name="remarks" rows="3" placeholder="Enter additional clinical notes..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="add_health_record" class="btn">
                                Save Health Record
                            </button>
                        </div>

                    </div>
                </form>
            </div>


            <!-- ==========================
                 HEALTH HISTORY PAGE
                 ========================== -->

            <div class="card" id="health-history">
                <h2>Health History Page</h2>

                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th>Record ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Vitals</th>
                            <th>Record Date</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>

                        <?php if ($health_records_result && $health_records_result->num_rows > 0): ?>
                            <?php while ($rec = $health_records_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rec['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient #'.$rec['patient_id']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['doctor_name'] ?? 'Doctor #'.$rec['doctor_id']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['diagnosis']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['vitals'] ?? 'N/A'); ?></td>
                                    <td><?php echo $rec['record_date']; ?></td>
                                    <td><?php echo htmlspecialchars($rec['remarks'] ?? '-'); ?></td>
                                    <td>
                                        <a href="health_monitoring.php?delete_record=<?php echo $rec['record_id']; ?>" 
                                           class="delete-btn" 
                                           onclick="return confirm('Delete this health record?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No health records found.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>


            <!-- ==========================
                 REPORT GENERATION
                 ========================== -->

            <div class="card" id="report-generation">
                <h2>Generate Medical Report</h2>

                <form method="POST" action="health_monitoring.php">
                    <div class="form-grid">

                        <div class="form-group">
                            <label>Patient ID</label>
                            <input type="number" name="rep_patient_id" placeholder="Enter Patient ID" required>
                        </div>

                        <div class="form-group">
                            <label>Source Health Record ID</label>
                            <input type="number" name="rep_record_id" placeholder="Enter Health Record ID" required>
                        </div>

                        <div class="form-group">
                            <label>Generated By (Doctor ID)</label>
                            <input type="number" name="generated_by" placeholder="Enter Doctor ID" required>
                        </div>

                        <div class="form-group">
                            <label>Report Date</label>
                            <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Medical Advice / Recommendation</label>
                            <textarea name="recommendation" rows="3" placeholder="Enter medical advice and next steps..." required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="generate_report" class="btn">
                                Generate & Save Report
                            </button>
                        </div>

                    </div>
                </form>
            </div>


            <!-- ==========================
                 REPORT VIEW PAGE
                 ========================== -->

            <div class="card" id="report-view">
                <h2>Generated Reports List</h2>

                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th>Report ID</th>
                            <th>Patient</th>
                            <th>Record ID</th>
                            <th>Generated By</th>
                            <th>Recommendation</th>
                            <th>Report Date</th>
                            <th>Action</th>
                        </tr>

                        <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                            <?php while ($rep = $reports_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $rep['report_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rep['patient_name'] ?? 'Patient #'.$rep['patient_id']); ?></td>
                                    <td><?php echo $rep['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rep['doctor_name'] ?? 'Doctor #'.$rep['generated_by']); ?></td>
                                    <td><?php echo htmlspecialchars($rep['recommendation']); ?></td>
                                    <td><?php echo $rep['report_date']; ?></td>
                                    <td>
                                        <a href="health_monitoring.php?delete_report=<?php echo $rep['report_id']; ?>" 
                                           class="delete-btn" 
                                           onclick="return confirm('Delete this medical report?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No reports generated yet.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

        </div>

    </main>
</div>

</body>
</html>

<?php
$conn->close();
?>