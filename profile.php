<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
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
    die("<div class='alert alert-danger text-center'>User not found. Please ensure your account exists or contact support.</div>");
}

// Fetch total budget and remaining balances
$total_budget_sql = "SELECT SUM(budget) AS total_budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($total_budget_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result()->fetch_assoc();
$total_budget = $budget_result['total_budget'] ?? 0;

$total_expense_sql = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($total_expense_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_result = $stmt->get_result()->fetch_assoc();
$total_expenses = $expense_result['total_expenses'] ?? 0;

$remaining_budget = $total_budget - $total_expenses;

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
                $user['profile_picture_url'] = $profile_picture_url;
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
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #F4F6F9;
            font-family: 'Arial', sans-serif;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2;
            padding-top: 20px;
            z-index: 1000;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #C1E2DB;
            border-radius: 5px;
        }

        .sidebar i {
            margin-right: 10px;
        }

        /* Content styling */
        .content {
            margin-left: 270px;
            padding: 30px;
        }

        /* Profile Styling */
        .profile-header img {
            border-radius: 50%;
            width: 180px;
            height: 180px;
            border: 5px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .profile-header img:hover {
            transform: scale(1.1);
        }

        .card {
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
            margin-bottom: 30px;
        }

        .btn-update {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
            width: 100%;
        }

        .btn-update:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .summary-card {
            background-color: #E8F6EF;
            color: #5A8A8B;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .info-item strong {
            color: #5A8A8B;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #AEC6D2;
        }

        .card-header {
            background-color: #F0F8FF;
            border-bottom: 1px solid #B2C5CE;
            font-weight: bold;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(54, 76, 132, 0.5);
            border-color: #B2C5CE;
        }

        /* Custom Styling for Summary Boxes */
        .summary-box {
            background-color: #E8F6EF;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .summary-box h5 {
            font-size: 18px;
            color: #5A8A8B;
        }

        .summary-box p {
            font-size: 22px;
            color: #2E8B57;
            font-weight: bold;
        }

        /* Container Styles */
        .container-fluid {
            padding: 30px;
            border-radius: 10px;
            background: linear-gradient(135deg, #C1E2DB, #F4F6F9);
        }

        /* Header Message */
        .header-message {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2A3D45;
            margin-bottom: 30px;
        }

        .container-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .container-summary .summary-box {
            width: 48%;
        }

        /* Responsive Sidebar */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }

            .sidebar {
                position: absolute;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .category-box {
                width: 100%;
                margin-bottom: 20px;
            }

            .charts-container {
                flex-direction: column;
            }

            .chart-box {
                width: 100%;
                margin-bottom: 20px;
            }

            /* Navbar */
            .navbar {
                background-color: #AEC6D2;
            }

            .navbar-toggler {
                border: none;
            }

            .navbar-brand {
                color: white;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar for Mobile -->
    <nav class="navbar navbar-expand-lg navbar-light d-lg-none">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="navbar-brand">Dashboard</span>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="mobileSidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="profile.php"><i class="fas fa-user"></i>Profile</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i>Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-dollar-sign"></i>Set Budget</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i>Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="welcome-message">
            Welcome, <?php echo $_SESSION['full_name']; ?>! <br>
            Here is an overview of your budget and spending.
        </div>

        <!-- Profile Header -->
        <div class="card text-center p-4">
            <div class="profile-header">
                <img src="<?php echo $user['profile_picture_url'] ?: 'default-profile.jpg'; ?>" alt="Profile Picture">
                <h3 class="mt-3"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="card p-4">
            <h5>Profile Information</h5>
            <div class="info-container">
                <div class="info-item"><strong>Access Number:</strong> <?php echo htmlspecialchars($user['access_number'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Registration Number:</strong> <?php echo htmlspecialchars($user['registration_number'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Age:</strong> <?php echo htmlspecialchars($user['age'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['date_of_birth'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- Update Profile Picture -->
        <div class="card text-center p-4">
            <h5>Update Profile Picture</h5>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" name="profile_picture" id="profile_picture" class="form-control">
                </div>
                <button type="submit" class="btn btn-update">Update Picture</button>
            </form>
        </div>

        <!-- Summary Section -->
        <div class="container-summary">
            <div class="summary-box">
                <h5>Total Income</h5>
                <p><?php echo number_format($total_budget, 2); ?> UGX</p>
            </div>
            <div class="summary-box">
                <h5>Remaining Budget</h5>
                <p><?php echo number_format($remaining_budget, 2); ?> UGX</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Students Finance. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
