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
// GET ADMIN INFORMATION
// ==========================

// Get total administrators
$admin_result = $conn->query(
    "SELECT COUNT(*) AS total FROM admin"
);

$total_admins = $admin_result->fetch_assoc()['total'];


// Get total doctors
$doctor_result = $conn->query(
    "SELECT COUNT(*) AS total FROM doctor"
);

$total_doctors = $doctor_result->fetch_assoc()['total'];


// Get total patients
$patient_result = $conn->query(
    "SELECT COUNT(*) AS total FROM patient"
);

$total_patients = $patient_result->fetch_assoc()['total'];


// Get total clients
$client_result = $conn->query(
    "SELECT COUNT(*) AS total FROM client"
);

$total_clients = $client_result->fetch_assoc()['total'];


// Get total appointments
$appointment_result = $conn->query(
    "SELECT COUNT(*) AS total FROM appointments"
);

$total_appointments = $appointment_result->fetch_assoc()['total'];


// Get total health records
$health_result = $conn->query(
    "SELECT COUNT(*) AS total FROM health_record"
);

$total_health_records = $health_result->fetch_assoc()['total'];


// Get total reports
$report_result = $conn->query(
    "SELECT COUNT(*) AS total FROM report"
);

$total_reports = $report_result->fetch_assoc()['total'];


// Get total medicines
$medicine_result = $conn->query(
    "SELECT COUNT(*) AS total FROM prescribed_medicine"
);

$total_medicines = $medicine_result->fetch_assoc()['total'];


// ==========================
// GET ALL ADMINISTRATORS
// ==========================

$result = $conn->query(
    "SELECT admin_id, admin_name, admin_address, date_of_birth
     FROM admin
     ORDER BY admin_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Management</title>

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

            <h1>
                MEDI <span>NOVA</span>.
            </h1>

            <p>
                Health Monitoring System
            </p>

        </div>

        <nav class="side-nav">

            <a href="doctor.php">

                <span class="icon">◆</span>

                <span>
                    Doctor Management
                </span>

            </a>


            <a href="patient.php">

                <span class="icon">◆</span>

                <span>
                    Patient Management
                </span>

            </a>


            <a href="#">

                <span class="icon">◆</span>

                <span>
                    User Authentication
                </span>

            </a>


            <a href="#">

                <span class="icon">◆</span>

                <span>
                    Appointment Management
                </span>

            </a>


            <a href="#">

                <span class="icon">◆</span>

                <span>
                    Health Monitoring
                </span>

            </a>


            <a href="#">

                <span class="icon">◆</span>

                <span>
                    Report Management
                </span>

            </a>


            <a href="admin.php" class="active">

                <span class="icon">◆</span>

                <span>
                    Admin Dashboard
                </span>

            </a>


            <a href="#system-management">

                <span class="icon">◆</span>

                <span>
                    System Management
                </span>

            </a>

        </nav>

    </aside>


    <main class="main-content">

        <div class="header">

        </div>


        <div class="container">


            <div class="sub-nav">

                <a href="#admin-management" class="active">
                    Admin Management
                </a>

                <a href="#admin-list">
                    Admin List
                </a>

                <a href="#system-management">
                    System Management
                </a>

            </div>


            <!-- ==========================
                 ADMIN MANAGEMENT
                 ========================== -->

            <div class="card" id="admin-management">

                <h2>
                    Administration Dashboard
                </h2>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Total Administrators
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_admins; ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Total Clients
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_clients; ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Total Doctors
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_doctors; ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Total Patients
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_patients; ?>"
                            readonly
                        >

                    </div>


                </div>

            </div>


            <!-- ==========================
                 ADMIN LIST
                 ========================== -->

            <div class="card" id="admin-list">

                <h2>
                    Registered Administrators
                </h2>


                <div class="table-wrapper">

                    <table>

                        <tr>

                            <th>
                                Admin ID
                            </th>

                            <th>
                                Admin Name
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Date of Birth
                            </th>

                        </tr>


                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($admin = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $admin['admin_id']
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $admin['admin_name']
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $admin['admin_address']
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $admin['date_of_birth']
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="4">
                                    No administrators registered yet.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </table>

                </div>

            </div>


            <!-- ==========================
                 SYSTEM MANAGEMENT
                 ========================== -->

            <div class="card" id="system-management">

                <h2>
                    System Management
                </h2>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Doctor Management
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_doctors; ?> Doctors Registered"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Patient Management
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_patients; ?> Patients Registered"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            User Authentication
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_clients; ?> Client Accounts"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Appointment Management
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_appointments; ?> Appointments"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Health Monitoring
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_health_records; ?> Health Records"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Report Management
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_reports; ?> Reports"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Medicine Management
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_medicines; ?> Medicines"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Administration
                        </label>

                        <input
                            type="text"
                            value="<?php echo $total_admins; ?> Administrators"
                            readonly
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            System Status
                        </label>

                        <input
                            type="text"
                            value="System is active"
                            readonly
                        >

                    </div>


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