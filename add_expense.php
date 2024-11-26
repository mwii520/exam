<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle deleting an expense
if (isset($_GET['delete_expense_id'])) {
    $expense_id = $_GET['delete_expense_id'];

    $delete_sql = "DELETE FROM expenses WHERE expense_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $expense_id, $user_id);

    if ($stmt->execute()) {
        $delete_msg = "Expense deleted successfully.";
    } else {
        $delete_msg = "Failed to delete the expense. Please try again.";
    }
    $stmt->close();
}

// Handle adding an expense
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Check if budget is set for the category
    $budget_sql = "SELECT budget FROM budgets WHERE user_id = ? AND category = ?";
    $stmt = $conn->prepare($budget_sql);
    $stmt->bind_param("is", $user_id, $category);
    $stmt->execute();
    $budget_result = $stmt->get_result();
    $stmt->close();

    if ($budget_result->num_rows > 0) {
        // Budget found, check if the expense is within the budget
        $budget = $budget_result->fetch_assoc()['budget'];
        if ($amount > $budget) {
            $error_msg = "Expense amount exceeds the set budget for this category.";
        } else {
            // Insert expense if it's within the budget
            $sql = "INSERT INTO expenses (user_id, category, amount, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $user_id, $category, $amount, $description);

            if ($stmt->execute()) {
                $success_msg = "Expense added successfully.";
            } else {
                $error_msg = "Failed to add expense. Please try again.";
            }
            $stmt->close();
        }
    } else {
        // No budget found for the selected category
        $error_msg = "Please set a budget for this category before adding an expense.";
    }
}

// Fetch expenses for this user
$sql = "SELECT * FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9; /* Light gray background */
            font-family: 'Arial', sans-serif;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2; /* Soft Teal */
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
            background-color: #C1E2DB; /* Soft Light Blue */
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

        /* Card Styling */
        .card {
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
            margin-bottom: 30px;
        }

        .category-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .category-box {
            background-color: #D1E0DC;
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            width: 48%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .category-box h5 {
            color: #364C84;
        }

        .category-box p {
            font-size: 16px;
        }

        .category-box .remaining {
            color: #FF5733;
            font-weight: bold;
        }

        .category-box .spent {
            color: #36A2EB;
        }

        .category-box .budget {
            color: #4BC0C0;
        }

        /* Form Styling */
        .form-control {
            border-radius: 8px;
        }

        .btn-primary {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
        }

        .btn-primary:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        /* Sidebar and Navbar for Mobile */
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
        <a href="add_expense.php" class="active"><i class="fas fa-plus-circle"></i>Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-dollar-sign"></i>Set Budget</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i>Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="card">
            <h2 class="text-center">Add Expense</h2>
            
            <!-- Display success or error messages -->
            <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if (isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

            <form method="POST">
                <!-- Category Field -->
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

                <!-- Amount Field -->
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" required>
                </div>

                <!-- Description Field -->
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">Add Expense</button>
            </form>
        </div>

        <!-- Toggle Button for Viewing Expenses -->
        <div class="card">
            <h2 class="text-center">View Expenses</h2>
            <button class="btn btn-primary w-100" data-bs-toggle="collapse" data-bs-target="#expenseTable" aria-expanded="false" aria-controls="expenseTable">
                Toggle Expense Overview
            </button>

            <!-- Expense Table (Collapsible) -->
            <div class="collapse mt-3" id="expenseTable">
                <?php if ($result->num_rows > 0) { ?>
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td>
                                        <a href="add_expense.php?delete_expense_id=<?php echo $row['expense_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this expense?');">
                                           <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="text-center text-muted">No expenses found.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
