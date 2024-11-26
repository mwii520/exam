<?php
session_start();
require 'connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $access_number = $_POST['access_number'] ?? null;
    $registration_number = $_POST['registration_number'] ?? null;
    $age = $_POST['age'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $phone_number = $_POST['phone_number'] ?? null;
    $address = $_POST['address'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;

    if (!$full_name || !$email || !$password || !$access_number || !$registration_number) {
        $error_message = "All required fields must be filled.";
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $profile_picture_url = null;

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['profile_picture']['name']);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture_url = $target_file;
            }
        }

        try {
            $check_query = "SELECT * FROM Users WHERE email = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "This email is already registered.";
            } else {
                $insert_query = "INSERT INTO Users 
                    (access_number, registration_number, full_name, email, password_hash, age, gender, phone_number, address, date_of_birth, profile_picture_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param(
                    "sssssssssss",
                    $access_number, $registration_number, $full_name, $email, $password_hash,
                    $age, $gender, $phone_number, $address, $date_of_birth, $profile_picture_url
                );

                if ($stmt->execute()) {
                    $success_message = "Account created successfully! Please log in.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error_message = "Failed to create account: " . $stmt->error;
                }
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FFFEF6; /* Light beige background */
            font-family: 'Arial', sans-serif;
        }

        .form-container {
            background-color: #AEC6D2; /* Soft Teal */
            color: #364C84; /* Deep Blue */
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 50px auto;
        }

        h2 {
            color: #364C84;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #B2C5CE; /* Soft Teal */
            border: none;
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background-color: #3B4A58; /* Darker Teal on hover */
            color: #FFFFFF;
        }

        .form-label {
            color: #364C84;
            font-weight: bold;
        }

        .form-container input,
        .form-container select,
        .form-container textarea {
            border: 1px solid #CBCEEA; /* Soft Lavender */
            background-color: #FFFFFF;
            color: #364C84;
        }

        .form-container input:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #364C84; /* Deep Blue */
            box-shadow: 0 0 5px rgba(54, 76, 132, 0.5);
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #364C84;
        }

        .footer a {
            color: #5D6D7E; /* Soft Teal */
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container form-container">
    <h2>Create Your Account</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="access_number" class="form-label">Access Number</label>
            <input type="text" class="form-control" id="access_number" name="access_number" required>
        </div>
        <div class="mb-3">
            <label for="registration_number" class="form-label">Registration Number</label>
            <input type="text" class="form-control" id="registration_number" name="registration_number" required>
        </div>
        <div class="mb-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="age" class="form-label">Age</label>
            <input type="number" class="form-control" id="age" name="age">
        </div>
        <div class="mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-control" id="gender" name="gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address"></textarea>
        </div>
        <div class="mb-3">
            <label for="date_of_birth" class="form-label">Date of Birth</label>
            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
        </div>
        <div class="mb-3">
            <label for="profile_picture" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" id="profile_picture" name="profile_picture">
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
    </form>

    <div class="footer">
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
