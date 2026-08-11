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
// ADD DOCTOR
// ==========================

if (isset($_POST['add_doctor'])) {

    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $qualification = $_POST['qualification'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $available_days = $_POST['available_days'];
    $available_time = $_POST['available_time'];

    $sql = "INSERT INTO Doctor
            (name, specialization, qualification, phone, email, available_days, available_time)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssss",
        $name,
        $specialization,
        $qualification,
        $phone,
        $email,
        $available_days,
        $available_time
    );

    if ($stmt->execute()) {
        $message = "Doctor added successfully!";
    } else {
        $message = "Error adding doctor.";
    }

    $stmt->close();
}


// ==========================
// DELETE DOCTOR
// ==========================

if (isset($_GET['delete'])) {

    $doctor_id = $_GET['delete'];

    $sql = "DELETE FROM Doctor WHERE doctor_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);

    if ($stmt->execute()) {
        header("Location: doctor.php");
        exit();
    }

    $stmt->close();
}


// ==========================
// GET ALL Doctor
// ==========================

$result = $conn->query("SELECT * FROM Doctor ORDER BY doctor_id DESC");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Doctor Management</title>

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
            <h1>MEDI <span>NOVA</span>.</h1>
            <p>Health Monitoring System</p>
        </div>

        <nav class="side-nav">
            <a href="#" class="active">
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
        <a href="#doctor-management" class="active">Doctor Management</a>
        <a href="#doctor-list">Doctor List</a>
    </div>

    <?php if (isset($message)): ?>

        <div class="message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>


    <!-- ==========================
         ADD DOCTOR FORM
         ========================== -->

    <div class="card" id="doctor-management">

        <h2>Add New Doctor</h2>

        <form method="POST" action="doctor.php">

            <div class="form-grid">

                <div class="form-group">

                    <label>Doctor Name</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter doctor name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Specialization</label>

                    <select name="specialization" required>

                        <option value="">Select Specialization</option>

                        <option value="General Physician">
                            General Physician
                        </option>

                        <option value="Cardiologist">
                            Cardiologist
                        </option>

                        <option value="Dermatologist">
                            Dermatologist
                        </option>

                        <option value="Neurologist">
                            Neurologist
                        </option>

                        <option value="Pediatrician">
                            Pediatrician
                        </option>

                        <option value="Psychiatrist">
                            Psychiatrist
                        </option>

                        <option value="Dentist">
                            Dentist
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Qualification</label>

                    <input
                        type="text"
                        name="qualification"
                        placeholder="e.g. MBBS, MD"
                    >

                </div>


                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Enter phone number"
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

                    <label>Available Days</label>

                    <input
                        type="text"
                        name="available_days"
                        placeholder="e.g. Monday, Wednesday, Friday"
                    >

                </div>


                <div class="form-group full-width">

                    <label>Available Time</label>

                    <input
                        type="text"
                        name="available_time"
                        placeholder="e.g. 9:00 AM - 1:00 PM"
                    >

                </div>


                <div class="form-group full-width">

                    <button
                        type="submit"
                        name="add_doctor"
                        class="btn"
                    >
                        Add Doctor
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- ==========================
         DOCTOR LIST
         ========================== -->

    <div class="card" id="doctor-list">

        <h2>Registered Doctor</h2>

        <div class="table-wrapper">
        <table>

            <tr>

                <th>ID</th>
                <th>Name</th>
                <th>Specialization</th>
                <th>Qualification</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Available Days</th>
                <th>Available Time</th>
                <th colspan="2">Action</th>

            </tr>


            <?php if ($result->num_rows > 0): ?>

                <?php while ($doctor = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $doctor['doctor_id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['qualification']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['phone']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['email']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['available_days']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($doctor['available_time']); ?>
                        </td>

                        <td>

                            <a
                                href="doctor.php?delete=<?php echo $doctor['doctor_id']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this doctor?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="9">
                        No Doctor registered yet.
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