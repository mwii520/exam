<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch budgets with currency
$sql = "SELECT b.category, b.budget, p.currency FROM budgets b
        LEFT JOIN preferences p ON b.user_id = p.user_id
        WHERE b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budgets_result = $stmt->get_result();

// Handle form submission for setting a budget
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['category'], $_POST['budget'])) {
        // Handle budget setting
        $category = $_POST['category'];
        $budget = $_POST['budget'];

        $sql = "INSERT INTO budgets (user_id, category, budget) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $category, $budget);

        if ($stmt->execute()) {
            $success_msg = "Budget set successfully.";
        } else {
            $error_msg = "Failed to set budget. Please try again.";
        }
        $stmt->close();
    } elseif (isset($_POST['currency'])) {
        // Handle currency setting
        $currency = !empty($_POST['custom_currency']) ? $_POST['custom_currency'] : $_POST['currency'];

        $query = "SELECT * FROM preferences WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $preferences_result = $stmt->get_result();

        if ($preferences_result->num_rows > 0) {
            // Update currency preference
            $update_query = "UPDATE preferences SET currency = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $currency, $user_id);
        } else {
            // Insert currency preference
            $insert_query = "INSERT INTO preferences (user_id, currency) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $user_id, $currency);
        }

        if ($stmt->execute()) {
            $success_msg = "Currency updated successfully.";
        } else {
            $error_msg = "Failed to update currency. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View and Set Budgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FFFEF6; /* Light beige background */
            font-family: 'Arial', sans-serif;
        }

        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2; /* Soft Teal */
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
            background-color: #C1E2DB; /* Soft Light Blue */
            border-radius: 5px;
        }

        /* Content styling */
        .content {
            margin-left: 250px;
            padding: 30px;
        }

        /* Card and Button styling */
        .card {
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
        }

        .btn-primary:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .form-control {
            border-radius: 8px;
        }

        .navbar {
            display: none;
        }

        /* Table styling */
        table {
            width: 100%;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #C1E2DB;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="add_expense.php">Add Expense</a>
        
        <a href="set_budget.php" class="active">Set Budget</a>
        
        <a href="reports.php">Reports</a>
        <a href="login.php" class="text-danger">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Success/Error Message -->
        <?php if (isset($success_msg)) { ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php } ?>

        <?php if (isset($error_msg)) { ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php } ?>

        <!-- Set Preferences Section -->
        <div class="card">
            <h5>Set Budget and Preferences</h5>

            <!-- Dropdown to toggle Preferences Form -->
            <button class="btn btn-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#preferencesForm" aria-expanded="false" aria-controls="preferencesForm">
                Edit Budget & Currency Preferences
            </button>

            <!-- Collapsible Form Section -->
            <div class="collapse mt-3" id="preferencesForm">
                <!-- Set Budget Form -->
                <div class="card p-3">
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
                            <label for="budget" class="form-label">Budget Amount</label>
                            <input type="number" class="form-control" id="budget" name="budget" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Set Budget</button>
                    </form>
                </div>

                <!-- Set Preferred Currency Form -->
                <div class="card p-3">
                    <h5>Set Preferred Currency</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Select Currency</label>
                            <select class="form-control" id="currency" name="currency">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="JPY">JPY - Japanese Yen</option>
                                <option value="UGX">UGX - Ugandan Shilling</option>
                                <option value="KES">KES - Kenyan Shilling</option>
                                <option value="NGN">NGN - Nigerian Naira</option>
                            </select>
                        </div>
                        <button type="button" id="showCustomCurrency" class="btn btn-secondary w-100">Other</button>
                        <div class="mb-3 hidden" id="customCurrencyContainer">
                            <label for="custom_currency" class="form-label">Add Custom Currency</label>
                            <input type="text" id="custom_currency" name="custom_currency" class="form-control" placeholder="Enter custom currency">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-2">Save Currency</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Budgets Section -->
        <div class="card">
            <h5>Your Budgets</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Budget</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $budgets_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['budget']; ?></td>
                        <td><?php echo $row['currency']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('showCustomCurrency').addEventListener('click', function () {
            document.getElementById('customCurrencyContainer').classList.remove('hidden');
        });
    </script>
</body>
</html>
