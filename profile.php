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
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #F4F6F9;
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
            z-index: 1000;
        }

        .sidebar a {
            color: white;
            padding: 12px;
            text-decoration: none;
            display: block;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background-color: #C1E2DB;
            border-radius: 4px;
        }

        .content {
            margin-left: 270px;
            padding: 30px;
        }

        /* Profile Styling */
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #FFFFFF;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-update {
            background-color: #B2C5CE;
            border: none;
            width: 100%;
        }

        .btn-update:hover {
            background-color: #C8E6CF;
        }

        .summary-box {
            text-align: center;
            padding: 20px;
            background-color: #E8F6EF;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .summary-box h5 {
            font-size: 18px;
            color: #5A8A8B;
        }

        .summary-box p {
            font-size: 22px;
            font-weight: bold;
            color: #2E8B57;
        }

        .container-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

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

            .container-summary {
                flex-direction: column;
            }

            .container-summary .summary-box {
                width: 100%;
                margin-bottom: 20px;
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
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <!-- Profile Header -->
            <div class="profile-header text-center mb-4">
                <img src="<?php echo $user['profile_picture_url'] ?: 'default-profile.jpg'; ?>" alt="Profile Picture">
                <h3 class="mt-3"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <!-- Profile Information -->
                    <div class="card p-4">
                        <h5>Profile Information</h5>
                        <div class="info-container">
                            <div class="info-item mb-3"><strong>Access Number:</strong> <?php echo htmlspecialchars($user['access_number'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Registration Number:</strong> <?php echo htmlspecialchars($user['registration_number'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Age:</strong> <?php echo htmlspecialchars($user['age'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></div>
                            <div class="info-item mb-3"><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['date_of_birth'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Budget Summary -->
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
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar on mobile view
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
