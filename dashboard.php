<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if session variables exist
$full_name = $_SESSION['full_name'] ?? 'User';

// Fetch spending data by category
$categories = [];
$spent_totals = [];
$spending_sql = "SELECT category, SUM(amount) AS total_spent FROM expenses WHERE user_id = ? GROUP BY category";
$stmt = $conn->prepare($spending_sql);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $spending_result = $stmt->get_result();

    while ($row = $spending_result->fetch_assoc()) {
        $categories[] = $row['category'];
        $spent_totals[$row['category']] = $row['total_spent'];
    }
    $stmt->close();
}

// If no categories are found, set a default empty array
if (empty($categories)) {
    $categories = [];
}

// Fetch budget data for each category
$budget_totals = [];
$budget_sql = "SELECT category, budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budget_sql);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $budget_result = $stmt->get_result();

    while ($row = $budget_result->fetch_assoc()) {
        $budget_totals[$row['category']] = $row['budget'];
    }
    $stmt->close();
}

// Prepare data for the bar chart (Budget vs Spent)
$chart_data = [];
foreach ($categories as $category) {
    $spent = isset($spent_totals[$category]) ? $spent_totals[$category] : 0;
    $budget = isset($budget_totals[$category]) ? $budget_totals[$category] : 0;
    $remaining = $budget - $spent;
    $chart_data[$category] = [
        'spent' => $spent,
        'budget' => $budget,
        'remaining' => $remaining
    ];
}

// Fetch daily spending data
$daily_dates = [];
$daily_spent = [];
$daily_spending_sql = "SELECT DATE(date) AS date, SUM(amount) AS total_spent FROM expenses WHERE user_id = ? GROUP BY DATE(date) ORDER BY DATE(date) ASC";
$stmt = $conn->prepare($daily_spending_sql);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $daily_result = $stmt->get_result();

    while ($row = $daily_result->fetch_assoc()) {
        $daily_dates[] = $row['date'];
        $daily_spent[] = $row['total_spent'];
    }
    $stmt->close();
}

?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #F8F9FA;
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
        }

        .sidebar a:hover {
            background-color: #C1E2DB;
            border-radius: 5px;
        }

        .sidebar i {
            margin-right: 10px;
        }

        /* Content Styling */
        .content {
            margin-left: 270px;
            padding: 30px;
        }

        /* Responsive Sidebar for Mobile */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .menu-toggle {
                position: fixed;
                top: 10px;
                left: 10px;
                background-color: #AEC6D2;
                color: white;
                border: none;
                border-radius: 5px;
                padding: 10px;
                z-index: 1100;
            }

            .menu-toggle i {
                font-size: 20px;
            }
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-container canvas {
            background-color: #f5f5f5;
            border-radius: 12px;
        }
    </style>
</head>
<body>

    <!-- Mobile Sidebar Toggle Button -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container">
            <div class="welcome-message">
                <h4>Welcome, <?php echo $_SESSION['full_name']; ?>!</h4>
                <p>Here is an overview of your budget and spending.</p>
            </div>

            <!-- Budget Summary Section -->
            <div class="row mb-4">
                <?php foreach ($categories as $category): 
                    $spent = $chart_data[$category]['spent'];
                    $budget = $chart_data[$category]['budget'];
                    $remaining = $chart_data[$category]['remaining'];
                ?>
                <div class="col-md-4">
                    <div class="card p-3">
                        <h5><?php echo $category; ?></h5>
                        <p>Spent: <span class="text-danger">$<?php echo number_format($spent, 2); ?></span></p>
                        <p>Budget: <span class="text-success">$<?php echo number_format($budget, 2); ?></span></p>
                        <p>Remaining: <span class="text-warning">$<?php echo number_format($remaining, 2); ?></span></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Chart Section -->
            <div class="row chart-container">
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5>Budget vs Spending</h5>
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5>Daily Spending</h5>
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barData = <?php echo json_encode($chart_data); ?>;
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(barData),
                datasets: [
                    { label: 'Spent', data: Object.values(barData).map(d => d.spent), backgroundColor: '#FF6384' },
                    { label: 'Budget', data: Object.values(barData).map(d => d.budget), backgroundColor: '#36A2EB' }
                ]
            },
            options: { responsive: true }
        });

        // Line Chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const dailyDates = <?php echo json_encode($daily_dates); ?>;
        const dailySpent = <?php echo json_encode($daily_spent); ?>;
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: dailyDates,
                datasets: [{
                    label: 'Daily Spending',
                    data: dailySpent,
                    borderColor: '#FF5733',
                    tension: 0.1
                }]
            },
            options: { responsive: true }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
