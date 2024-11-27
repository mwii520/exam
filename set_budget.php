<?php
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current currency preference
$currency_query = "SELECT currency FROM preferences WHERE user_id = ?";
$stmt = $conn->prepare($currency_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$currency_result = $stmt->get_result();
$current_currency = $currency_result->fetch_assoc()['currency'] ?? 'USD'; // Default to USD

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['category'], $_POST['budget'])) {
        $category = $_POST['category'];
        $budget = $_POST['budget'];

        // Insert the budget
        $sql = "INSERT INTO budgets (user_id, category, budget) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $category, $budget);

        if ($stmt->execute()) {
            $success_msg = "Budget set successfully.";

            // Add notification for the new budget
            $notification_message = "A new budget for '$category' has been added.";
            $notification_sql = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
            $notif_stmt = $conn->prepare($notification_sql);
            $notif_stmt->bind_param("is", $user_id, $notification_message);
            $notif_stmt->execute();
            $notif_stmt->close();
        } else {
            $error_msg = "Failed to set budget. Please try again.";
        }
        $stmt->close();
    } elseif (isset($_POST['currency'])) {
        $currency = !empty($_POST['custom_currency']) ? $_POST['custom_currency'] : $_POST['currency'];

        $query = "SELECT * FROM preferences WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $preferences_result = $stmt->get_result();

        if ($preferences_result->num_rows > 0) {
            $update_query = "UPDATE preferences SET currency = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $currency, $user_id);
        } else {
            $insert_query = "INSERT INTO preferences (user_id, currency) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $user_id, $currency);
        }

        if ($stmt->execute()) {
            $success_msg = "Currency updated successfully.";
            $current_currency = $currency;
        } else {
            $error_msg = "Failed to update currency. Please try again.";
        }
        $stmt->close();
    }
}
?>
<?php include 'header.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Budgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            margin-left: 270px;
            padding: 40px;
        }

        .card {
            border-radius: 12px;
            background-color: #FFFFFF;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            padding: 20px;
        }

        .btn-primary {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
        }

        .btn-primary:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #AEC6D2;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php" class="active"><i class="fas fa-dollar-sign"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <?php if (isset($success_msg)) { ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php } ?>

        <?php if (isset($error_msg)) { ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php } ?>

        <!-- Set Currency Section -->
        <div class="card">
            <h5>Set Preferred Currency</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="currency" class="form-label">Select Currency</label>
                    <select class="form-control" id="currency" name="currency">
                        <option value="USD" <?php echo $current_currency === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                        <option value="EUR" <?php echo $current_currency === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                        <option value="GBP" <?php echo $current_currency === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                        <option value="UGX" <?php echo $current_currency === 'UGX' ? 'selected' : ''; ?>>UGX - Ugandan Shilling</option>
                        <option value="KES" <?php echo $current_currency === 'KES' ? 'selected' : ''; ?>>KES - Kenyan Shilling</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3 hidden" id="customCurrencyContainer">
                    <label for="custom_currency" class="form-label">Enter Custom Currency</label>
                    <input type="text" id="custom_currency" name="custom_currency" class="form-control" placeholder="Custom Currency (e.g., INR)">
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Save Currency</button>
            </form>
        </div>

        <!-- Set Budget Section -->
        <div class="card">
            <h5>Set Budget</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="Food">Food</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Tuition">Tuition</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="budget" class="form-label">Budget Amount (<?php echo $current_currency; ?>)</label>
                    <input type="number" class="form-control" id="budget" name="budget" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Set Budget</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currencySelect = document.getElementById('currency');
        const customCurrencyContainer = document.getElementById('customCurrencyContainer');
        const customCurrencyInput = document.getElementById('custom_currency');

        currencySelect.addEventListener('change', function () {
            if (this.value === 'Other') {
                customCurrencyContainer.classList.remove('hidden');
                customCurrencyInput.required = true;
            } else {
                customCurrencyContainer.classList.add('hidden');
                customCurrencyInput.required = false;
            }
        });
    </script>
</body>
</html>
