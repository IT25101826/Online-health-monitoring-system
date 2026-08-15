<?php

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "health_monitoring";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


// ==========================
// ADD PATIENT 
// ==========================

if (isset($_POST['add_patient'])) {

    // Fields for the User table (login credentials)
    $client_name = $_POST['client_name'];
    $passcode = $_POST['passcode'];
    $email = $_POST['email'];
    $role = "patient";

    // Fields for the Patient table (profile info)
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    // Hash the passcode before storing (never store plain-text passwords)
    $hashed_passcode = password_hash($passcode, PASSWORD_DEFAULT);

    // Use a transaction so User + Patient are created together or not at all
    $conn->begin_transaction();

    try {

        // Step 1: insert into User table
        $sql_user = "INSERT INTO User
                (`User name`, password, Role, email)
                VALUES (?, ?, ?, ?)";

        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param(
            "ssss",
            $client_name,
            $hashed_passcode,
            $role,
            $email
        );
        $stmt_user->execute();

        $new_user_id = $conn->insert_id;
        $stmt_user->close();

        //  insert into Patient table, linked by the new User ID
        $sql_patient = "INSERT INTO Patient
                (`User ID`, `Full name`, DOB, gender, `Contact number`, address)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt_patient = $conn->prepare($sql_patient);
        $stmt_patient->bind_param(
            "isssss",
            $new_user_id,
            $full_name,
            $dob,
            $gender,
            $contact_number,
            $address
        );
        $stmt_patient->execute();
        $stmt_patient->close();

        $conn->commit();
        $message = "Patient added successfully!";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error adding patient: " . $e->getMessage();
    }
}


// ==========================
// DELETE PATIENT 
// ==========================

if (isset($_GET['delete'])) {

    $patient_id = $_GET['delete'];

    // First find the linked User ID
    $sql_find = "SELECT `User ID` FROM Patient WHERE `Patient ID` = ?";
    $stmt_find = $conn->prepare($sql_find);
    $stmt_find->bind_param("i", $patient_id);
    $stmt_find->execute();
    $find_result = $stmt_find->get_result();
    $row = $find_result->fetch_assoc();
    $stmt_find->close();

    if ($row) {

        $linked_user_id = $row['User ID'];

        $conn->begin_transaction();

        try {
            $stmt1 = $conn->prepare("DELETE FROM Patient WHERE `Patient ID` = ?");
            $stmt1->bind_param("i", $patient_id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM User WHERE `User-ID` = ?");
            $stmt2->bind_param("i", $linked_user_id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            header("Location: patient.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error deleting patient: " . $e->getMessage();
        }
    }
}


// ==========================
// GET ALL PATIENTS (joined with User for client_name/email)
// ==========================

$result = $conn->query(
    "SELECT Patient.`Patient ID`, User.`User name` AS client_name, User.email,
            Patient.`Full name`, Patient.DOB, Patient.gender,
            Patient.`Contact number`, Patient.address
     FROM Patient
     JOIN User ON Patient.`User ID` = User.`User-ID`
     ORDER BY Patient.`Patient ID` DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Patient Management</title>

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
            margin-left:10px;
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
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
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
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
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
            <a href="doctor.php">
                <span class="icon">◆</span>
                <span>Doctor Management</span>
            </a>
            <a href="patient.php" class="active">
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
            <a href="#">
                <span class="icon">◆</span>
                <span>Health Monitoring</span>
            </a>
            <a href="#">
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

<div class="header">

</div>


<div class="container">

    <div class="sub-nav">
        <a href="#patient-management" class="active">Patient Management</a>
        <a href="#patient-list">Patient List</a>
    </div>

    <?php if (isset($message)): ?>

        <div class="message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>


    <!-- ==========================
         ADD PATIENT FORM
         (creates a User row for login + a Patient row for profile)
         ========================== -->

    <div class="card" id="patient-management">

        <h2>Register New Patient</h2>

        <form method="POST" action="patient.php">

            <div class="form-grid">

                <div class="form-group">

                    <label>Client Name</label>

                    <input
                        type="text"
                        name="client_name"
                        placeholder="Enter client name (used to log in)"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Passcode</label>

                    <input
                        type="password"
                        name="passcode"
                        placeholder="Enter passcode"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter email address"
                    >

                </div>


                <div class="form-group">

                    <label>Full Name</label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter full name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Date of Birth</label>

                    <input
                        type="date"
                        name="dob"
                    >

                </div>


                <div class="form-group">

                    <label>Gender</label>

                    <select name="gender">

                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="contact_number"
                        placeholder="Enter contact number"
                    >

                </div>


                <div class="form-group full-width">

                    <label>Address</label>

                    <input
                        type="text"
                        name="address"
                        placeholder="Enter address"
                    >

                </div>


                <div class="form-group full-width">

                    <button
                        type="submit"
                        name="add_patient"
                        class="btn"
                    >
                        Add Patient
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- ==========================
         PATIENT LIST
         ========================== -->

    <div class="card" id="patient-list">

        <h2>Registered Patients</h2>

        <div class="table-wrapper">
        <table>

            <tr>

                <th>ID</th>
                <th>Client Name</th>
                <th>Full Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th colspan="2">Action</th>

            </tr>


            <?php if ($result->num_rows > 0): ?>

                <?php while ($patient = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $patient['Patient ID']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['client_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['Full name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['DOB']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['gender']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['Contact number']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['email']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($patient['address']); ?>
                        </td>

                        <td>

                            <a
                                href="patient.php?delete=<?php echo $patient['Patient ID']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this patient?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="9">
                        No patients registered yet.
                    </td>

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
