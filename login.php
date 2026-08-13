<?php

session_start();

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
// HANDLE LOGIN FORM SUBMIT
// ==========================

$message = "";

if (isset($_POST['login'])) {

    $client_name = $_POST['client_name'];
    $passcode = $_POST['passcode'];

    // Look up the user by client_name (stored in the `User name` column)
    $sql = "SELECT * FROM User WHERE `User name` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $client_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Compare the typed passcode against the hashed one in the database
        if (password_verify($passcode, $user['password'])) {

            // Correct login — store details in the session
            $_SESSION['user_id'] = $user['User-ID'];
            $_SESSION['client_name'] = $user['User name'];
            $_SESSION['role'] = $user['Role'];

            // Redirect based on role
            if ($user['Role'] === 'admin') {
                header("Location: admin.php");
            } elseif ($user['Role'] === 'doctor') {
                header("Location: doctor-dashboard.php");
            } else {
                header("Location: patient-dashboard.php");
            }

            exit();

        } else {
            $message = "Incorrect passcode. Please try again.";
        }

    } else {
        $message = "No account found with that client name.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Authentication</title>

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

        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
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
            <a href="patient.php">
                <span class="icon">◆</span>
                <span>Patient Management</span>
            </a>
            <a href="login.php" class="active">
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
        <a href="#login" class="active">Login</a>
    </div>

    <?php if ($message): ?>

        <div class="error-message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>


    <!-- ==========================
         LOGIN FORM
         ========================== -->

    <div class="card" id="login">

        <h2>User Login</h2>

        <form method="POST" action="login.php">

            <div class="form-grid">

                <div class="form-group">

                    <label>Client Name</label>

                    <input
                        type="text"
                        name="client_name"
                        placeholder="Enter client name"
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


                <div class="form-group full-width">

                    <button
                        type="submit"
                        name="login"
                        class="btn"
                    >
                        Login
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

    </main>
</div>

</body>

</html>

<?php

$conn->close();

?>
