<?php
session_start();
require 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data from the Users table
$query = "SELECT * FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    if ($_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $profile_picture_url = $upload_dir . basename($_FILES['profile_picture']['name']);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture_url)) {
            // Update profile picture in the database
            $update_query = "UPDATE Users SET profile_picture_url = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $profile_picture_url, $user_id);
            if ($stmt->execute()) {
                $user['profile_picture_url'] = $profile_picture_url; // Update the session profile picture
            } else {
                $error_message = "Failed to upload profile picture.";
            }
        } else {
            $error_message = "Failed to upload file.";
        }
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F8F9FA;
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2; 
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .sidebar a:hover {
            background-color: #C1E2DB;
            border-radius: 5px;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .profile-header img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            border: 3px solid #B2C5CE; 
        }

        .card {
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
            margin-bottom: 30px;
        }

        .btn-update {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
        }

        .btn-update:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .table th, .table td {
            padding: 15px;
            text-align: left;
        }

        .table-bordered th {
            background-color: #AEC6D2;
            color: #fff;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #AEC6D2;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(54, 76, 132, 0.5);
            border-color: #B2C5CE;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="profile.php">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_expense.php">Add Expense</a>
        <a href="set_budget.php">Set Budget</a>
        <a href="reports.php">Reports</a>
        <a href="login.php" class="text-danger">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="card">
            <div class="profile-header text-center">
                <img src="<?php echo $user['profile_picture_url'] ?: 'default-profile.jpg'; ?>" alt="Profile Picture">
                <h3 class="mt-3"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div class="profile-info-section mt-4">
                <h5>Profile Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Access Number</th>
                        <td><?php echo htmlspecialchars($user['access_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Registration Number</th>
                        <td><?php echo htmlspecialchars($user['registration_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Age</th>
                        <td><?php echo htmlspecialchars($user['age']); ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo htmlspecialchars($user['gender']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone Number</th>
                        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td><?php echo htmlspecialchars($user['date_of_birth']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="profile-picture-section mt-4 text-center">
                <h5>Update Profile Picture</h5>
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Choose New Profile Picture</label>
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-update">Update Picture</button>
                </form>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2024 Students Finance. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
