<?php

session_start();

// ==========================
// DATABASE CONNECTION
// ==========================

$host = "localhost";
$username = "root";
$password = "";
$database = "health_monitoring";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Show MySQL errors clearly while developing
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";
$message_type = "";


// ==========================
// GENERATE NEXT CLIENT ID
// Example: C001, C002, C003
// ==========================

function generateClientID($conn)
{
    $result = $conn->query("
        SELECT client_id
        FROM client
        ORDER BY client_id DESC
        LIMIT 1
    ");

    if ($result->num_rows == 0) {
        return "C001";
    }

    $row = $result->fetch_assoc();
    $last_id = $row['client_id'];

    // Extract number from ID
    $number = intval(substr($last_id, 1));
    $number++;

    return "C" . str_pad($number, 3, "0", STR_PAD_LEFT);
}


// ==========================
// GENERATE NEXT PATIENT ID
// Example: P001, P002, P003
// ==========================

function generatePatientID($conn)
{
    $result = $conn->query("
        SELECT patient_id
        FROM patient
        ORDER BY patient_id DESC
        LIMIT 1
    ");

    if ($result->num_rows == 0) {
        return "P001";
    }

    $row = $result->fetch_assoc();
    $last_id = $row['patient_id'];

    // Extract number from ID
    $number = intval(substr($last_id, 1));
    $number++;

    return "P" . str_pad($number, 3, "0", STR_PAD_LEFT);
}


// ==========================
// ADD PATIENT
// ==========================

if (isset($_POST['add_patient'])) {

    $user_name = trim($_POST['user_name']);
    $passcode = trim($_POST['passcode']);
    $email = trim($_POST['email']);

    $full_name = trim($_POST['full_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_no = trim($_POST['contact_no']);
    $address = trim($_POST['address']);

    // Your database has passcode VARCHAR(8)
    if (strlen($passcode) > 8) {

        $message = "Passcode must be 8 characters or less.";
        $message_type = "error";

    } else {

        try {

            // Start transaction
            $conn->begin_transaction();

            // Generate IDs
            $client_id = generateClientID($conn);
            $patient_id = generatePatientID($conn);

            // ==========================
            // CHECK USERNAME
            // ==========================

            $check_username = $conn->prepare("
                SELECT client_id
                FROM client
                WHERE user_name = ?
            ");

            $check_username->bind_param(
                "s",
                $user_name
            );

            $check_username->execute();

            $username_result = $check_username->get_result();

            if ($username_result->num_rows > 0) {

                throw new Exception("Username already exists.");

            }

            $check_username->close();


            // ==========================
            // INSERT INTO CLIENT
            // ==========================

            $sql_client = "
                INSERT INTO client
                (
                    client_id,
                    user_name,
                    passcode,
                    roll,
                    email
                )
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmt_client = $conn->prepare($sql_client);

            $role = "patient";

            $stmt_client->bind_param(
                "sssss",
                $client_id,
                $user_name,
                $passcode,
                $role,
                $email
            );

            $stmt_client->execute();

            $stmt_client->close();


            // ==========================
            // INSERT INTO PATIENT
            // ==========================

            $sql_patient = "
                INSERT INTO patient
                (
                    patient_id,
                    client_id,
                    full_name,
                    date_of_birth,
                    gender,
                    contact_no,
                    address
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt_patient = $conn->prepare($sql_patient);

            $stmt_patient->bind_param(
                "sssssss",
                $patient_id,
                $client_id,
                $full_name,
                $date_of_birth,
                $gender,
                $contact_no,
                $address
            );

            $stmt_patient->execute();

            $stmt_patient->close();


            // Complete transaction
            $conn->commit();

            $message = "Patient added successfully! Patient ID: " . $patient_id;
            $message_type = "success";

        } catch (Exception $e) {

            // Undo changes if something failed
            $conn->rollback();

            $message = "Error adding patient: " . $e->getMessage();
            $message_type = "error";
        }
    }
}


// ==========================
// UPDATE PATIENT
// Admin can update any patient. A patient can only update
// their own record (role check below).
// ==========================

if (isset($_POST['update_patient'])) {

    $patient_id = trim($_POST['patient_id']);
    $client_id  = trim($_POST['client_id']);

    $user_name   = trim($_POST['user_name']);
    $email       = trim($_POST['email']);
    $full_name   = trim($_POST['full_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender      = $_POST['gender'];
    $contact_no  = trim($_POST['contact_no']);
    $address     = trim($_POST['address']);

    // Passcode is optional on update — only change it if the
    // user actually typed a new one.
    $new_passcode = trim($_POST['passcode']);

    // ==========================
    // ROLE CHECK
    // ==========================

    $current_role      = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : "";
    $current_client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : "";

    if ($current_role !== "admin" && $current_client_id !== $client_id) {

        $message = "You are not allowed to update this patient's record.";
        $message_type = "error";

    } elseif ($new_passcode !== "" && strlen($new_passcode) > 8) {

        $message = "Passcode must be 8 characters or less.";
        $message_type = "error";

    } else {

        try {

            // Start transaction
            $conn->begin_transaction();


            // ==========================
            // CHECK USERNAME NOT TAKEN BY SOMEONE ELSE
            // ==========================

            $check_username = $conn->prepare("
                SELECT client_id
                FROM client
                WHERE user_name = ?
                  AND client_id != ?
            ");

            $check_username->bind_param(
                "ss",
                $user_name,
                $client_id
            );

            $check_username->execute();

            $username_result = $check_username->get_result();

            if ($username_result->num_rows > 0) {

                throw new Exception("Username already taken by another user.");

            }

            $check_username->close();


            // ==========================
            // UPDATE CLIENT
            // ==========================

            if ($new_passcode !== "") {

                $sql_client = "
                    UPDATE client
                    SET
                        user_name = ?,
                        passcode  = ?,
                        email     = ?
                    WHERE client_id = ?
                ";

                $stmt_client = $conn->prepare($sql_client);

                $stmt_client->bind_param(
                    "ssss",
                    $user_name,
                    $new_passcode,
                    $email,
                    $client_id
                );

            } else {

                $sql_client = "
                    UPDATE client
                    SET
                        user_name = ?,
                        email     = ?
                    WHERE client_id = ?
                ";

                $stmt_client = $conn->prepare($sql_client);

                $stmt_client->bind_param(
                    "sss",
                    $user_name,
                    $email,
                    $client_id
                );

            }

            $stmt_client->execute();

            $stmt_client->close();


            // ==========================
            // UPDATE PATIENT
            // ==========================

            $sql_patient = "
                UPDATE patient
                SET
                    full_name      = ?,
                    date_of_birth  = ?,
                    gender         = ?,
                    contact_no     = ?,
                    address        = ?
                WHERE patient_id = ?
            ";

            $stmt_patient = $conn->prepare($sql_patient);

            $stmt_patient->bind_param(
                "ssssss",
                $full_name,
                $date_of_birth,
                $gender,
                $contact_no,
                $address,
                $patient_id
            );

            $stmt_patient->execute();

            $stmt_patient->close();


            // Complete transaction
            $conn->commit();

            header("Location: patient.php?updated=1");
            exit();

        } catch (Exception $e) {

            // Undo changes if something failed
            $conn->rollback();

            $message = "Error updating patient: " . $e->getMessage();
            $message_type = "error";
        }
    }
}


// ==========================
// DELETE PATIENT
// ==========================

if (isset($_GET['delete'])) {

    $patient_id = $_GET['delete'];

    try {

        // Start transaction
        $conn->begin_transaction();


        // First find the client_id belonging to this patient
        $sql_find = "
            SELECT client_id
            FROM patient
            WHERE patient_id = ?
        ";

        $stmt_find = $conn->prepare($sql_find);

        $stmt_find->bind_param(
            "s",
            $patient_id
        );

        $stmt_find->execute();

        $result_find = $stmt_find->get_result();

        $patient = $result_find->fetch_assoc();

        $stmt_find->close();


        if (!$patient) {

            throw new Exception("Patient not found.");

        }


        $client_id = $patient['client_id'];


        // ==========================
        // DELETE PATIENT FIRST
        // ==========================

        // Patient must be deleted before client
        // because patient.client_id is a foreign key.

        $stmt_patient_delete = $conn->prepare("
            DELETE FROM patient
            WHERE patient_id = ?
        ");

        $stmt_patient_delete->bind_param(
            "s",
            $patient_id
        );

        $stmt_patient_delete->execute();

        $stmt_patient_delete->close();


        // ==========================
        // DELETE CLIENT
        // ==========================

        $stmt_client_delete = $conn->prepare("
            DELETE FROM client
            WHERE client_id = ?
        ");

        $stmt_client_delete->bind_param(
            "s",
            $client_id
        );

        $stmt_client_delete->execute();

        $stmt_client_delete->close();


        // Complete transaction
        $conn->commit();


        header("Location: patient.php");
        exit();


    } catch (Exception $e) {

        $conn->rollback();

        $message = "Error deleting patient: " . $e->getMessage();
        $message_type = "error";
    }
}


// ==========================
// FETCH PATIENT FOR EDIT
// Runs when the Edit link is clicked (?edit=P001), loads that
// patient's current data so the edit form can be pre-filled.
// ==========================

$edit_patient = null;

if (isset($_GET['edit'])) {

    $edit_id = $_GET['edit'];

    $sql_edit = "
        SELECT
            patient.patient_id,
            patient.client_id,
            client.user_name,
            client.email,
            patient.full_name,
            patient.date_of_birth,
            patient.gender,
            patient.contact_no,
            patient.address

        FROM patient

        INNER JOIN client
            ON patient.client_id = client.client_id

        WHERE patient.patient_id = ?
    ";

    $stmt_edit = $conn->prepare($sql_edit);

    $stmt_edit->bind_param(
        "s",
        $edit_id
    );

    $stmt_edit->execute();

    $edit_result = $stmt_edit->get_result();

    if ($edit_result->num_rows === 1) {
        $edit_patient = $edit_result->fetch_assoc();
    } else {
        $message = "Patient not found for editing.";
        $message_type = "error";
    }

    $stmt_edit->close();
}

if (isset($_GET['updated'])) {
    $message = "Patient updated successfully.";
    $message_type = "success";
}


// ==========================
// GET ALL PATIENTS
// ==========================

$result = $conn->query("
    SELECT
        patient.patient_id,
        patient.client_id,
        client.user_name,
        client.email,
        patient.full_name,
        patient.date_of_birth,
        patient.gender,
        patient.contact_no,
        patient.address

    FROM patient

    INNER JOIN client
        ON patient.client_id = client.client_id

    ORDER BY patient.patient_id DESC
");

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
            min-width: 1000px;
            border-collapse: collapse;
        }

        th {
            color: black;
            padding: 10px;
            background-color: #f1f7fa;
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

        .edit-btn {
            background-color: #0ba6b7;
            color: white;
            padding: 7px 12px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 6px;
            display: inline-block;
        }

        .edit-btn:hover {
            background-color: #125ca5;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
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

    <!-- ==========================
         SIDEBAR
         ========================== -->

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

            <a href="patient.php" class="active">

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

            <a href="#">

                <span class="icon">◆</span>

                <span>
                    Admin Dashboard
                </span>

            </a>

            <a href="#">

                <span class="icon">◆</span>

                <span>
                    System Management
                </span>

            </a>

        </nav>

    </aside>


    <!-- ==========================
         MAIN CONTENT
         ========================== -->

    <main class="main-content">

        <div class="container">

            <!-- Navigation -->

            <div class="sub-nav">

                <a href="#patient-management" class="active">
                    Patient Registration
                </a>

                <a href="#patient-list">
                    Patient List
                </a>

            </div>


            <!-- Message -->

            <?php if ($message != ""): ?>

                <div class="message <?php echo $message_type; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!-- ==========================
                 ADD PATIENT
                 ========================== -->

            <div class="card" id="patient-management">

                <h2>
                    Register New Patient
                </h2>

                <form method="POST" action="patient.php">

                    <div class="form-grid">


                        <!-- Username -->

                        <div class="form-group">

                            <label>
                                Username
                            </label>

                            <input
                                type="text"
                                name="user_name"
                                maxlength="10"
                                placeholder="Enter username"
                                required
                            >

                        </div>


                        <!-- Passcode -->

                        <div class="form-group">

                            <label>
                                Passcode
                            </label>

                            <input
                                type="password"
                                name="passcode"
                                maxlength="8"
                                placeholder="Maximum 8 characters"
                                required
                            >

                        </div>


                        <!-- Email -->

                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                maxlength="30"
                                placeholder="Enter email address"
                                required
                            >

                        </div>


                        <!-- Full Name -->

                        <div class="form-group">

                            <label>
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                maxlength="30"
                                placeholder="Enter full name"
                                required
                            >

                        </div>


                        <!-- Date of Birth -->

                        <div class="form-group">

                            <label>
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                name="date_of_birth"
                                required
                            >

                        </div>


                        <!-- Gender -->

                        <div class="form-group">

                            <label>
                                Gender
                            </label>

                            <select name="gender">

                                <option value="">
                                    Select Gender
                                </option>

                                <option value="Male">
                                    Male
                                </option>

                                <option value="Female">
                                    Female
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                        <!-- Contact Number -->

                        <div class="form-group">

                            <label>
                                Contact Number
                            </label>

                            <input
                                type="text"
                                name="contact_no"
                                maxlength="20"
                                placeholder="Enter contact number"
                            >

                        </div>


                        <!-- Address -->

                        <div class="form-group full-width">

                            <label>
                                Address
                            </label>

                            <input
                                type="text"
                                name="address"
                                maxlength="50"
                                placeholder="Enter address"
                            >

                        </div>


                        <!-- Button -->

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
                 UPDATE PATIENT
                 Only shows up when an Edit link was clicked
                 ========================== -->

            <?php if ($edit_patient): ?>

            <div class="card" id="edit-patient">

                <h2>
                    Update Patient — <?php echo htmlspecialchars($edit_patient['patient_id']); ?>
                </h2>

                <form method="POST" action="patient.php">

                    <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($edit_patient['patient_id']); ?>">
                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($edit_patient['client_id']); ?>">

                    <div class="form-grid">


                        <!-- Username -->

                        <div class="form-group">

                            <label>
                                Username
                            </label>

                            <input
                                type="text"
                                name="user_name"
                                maxlength="10"
                                required
                                value="<?php echo htmlspecialchars($edit_patient['user_name']); ?>"
                            >

                        </div>


                        <!-- Passcode -->

                        <div class="form-group">

                            <label>
                                New Passcode (leave blank to keep current)
                            </label>

                            <input
                                type="password"
                                name="passcode"
                                maxlength="8"
                                placeholder="Leave blank to keep current"
                            >

                        </div>


                        <!-- Email -->

                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                maxlength="30"
                                value="<?php echo htmlspecialchars($edit_patient['email']); ?>"
                            >

                        </div>


                        <!-- Full Name -->

                        <div class="form-group">

                            <label>
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                maxlength="30"
                                required
                                value="<?php echo htmlspecialchars($edit_patient['full_name']); ?>"
                            >

                        </div>


                        <!-- Date of Birth -->

                        <div class="form-group">

                            <label>
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                name="date_of_birth"
                                required
                                value="<?php echo htmlspecialchars($edit_patient['date_of_birth']); ?>"
                            >

                        </div>


                        <!-- Gender -->

                        <div class="form-group">

                            <label>
                                Gender
                            </label>

                            <select name="gender">

                                <option value="Male" <?php echo $edit_patient['gender'] === 'Male' ? 'selected' : ''; ?>>
                                    Male
                                </option>

                                <option value="Female" <?php echo $edit_patient['gender'] === 'Female' ? 'selected' : ''; ?>>
                                    Female
                                </option>

                                <option value="Other" <?php echo $edit_patient['gender'] === 'Other' ? 'selected' : ''; ?>>
                                    Other
                                </option>

                            </select>

                        </div>


                        <!-- Contact Number -->

                        <div class="form-group">

                            <label>
                                Contact Number
                            </label>

                            <input
                                type="text"
                                name="contact_no"
                                maxlength="20"
                                value="<?php echo htmlspecialchars($edit_patient['contact_no']); ?>"
                            >

                        </div>


                        <!-- Address -->

                        <div class="form-group full-width">

                            <label>
                                Address
                            </label>

                            <input
                                type="text"
                                name="address"
                                maxlength="50"
                                value="<?php echo htmlspecialchars($edit_patient['address']); ?>"
                            >

                        </div>


                        <!-- Buttons -->

                        <div class="form-group full-width">

                            <button
                                type="submit"
                                name="update_patient"
                                class="btn"
                            >
                                Save Changes
                            </button>

                            <a
                                href="patient.php"
                                class="btn"
                                style="background-color:#888; text-decoration:none; display:inline-block; margin-left:10px;"
                            >
                                Cancel
                            </a>

                        </div>

                    </div>

                </form>

            </div>

            <?php endif; ?>


            <!-- ==========================
                 PATIENT LIST
                 ========================== -->

            <div class="card" id="patient-list">

                <h2>
                    Registered Patients
                </h2>

                <div class="table-wrapper">

                    <table>

                        <tr>

                            <th>
                                Patient ID
                            </th>

                            <th>
                                Client ID
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Full Name
                            </th>

                            <th>
                                Date of Birth
                            </th>

                            <th>
                                Gender
                            </th>

                            <th>
                                Contact Number
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>


                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($patient = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['patient_id']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['client_id']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['user_name']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['full_name']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['date_of_birth']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['gender']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['contact_no']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['email']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $patient['address']
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <a
                                            href="patient.php?edit=<?php echo urlencode($patient['patient_id']); ?>"
                                            class="edit-btn"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="patient.php?delete=<?php echo urlencode($patient['patient_id']); ?>"
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

                                <td colspan="10">
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
