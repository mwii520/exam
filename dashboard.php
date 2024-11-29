<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch total budget and total expenses with proper checks
$total_budget = 0;
$total_expenses = 0;
$remaining_budget = 0;
$spent_percentage = 0;
$remaining_percentage = 100;

// Fetch total budget
$total_budget_sql = "SELECT SUM(budget) AS total_budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($total_budget_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result()->fetch_assoc();
if ($budget_result && isset($budget_result['total_budget'])) {
    $total_budget = $budget_result['total_budget'];
}

// Fetch total spending
$total_expense_sql = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($total_expense_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_result = $stmt->get_result()->fetch_assoc();
if ($expense_result && isset($expense_result['total_expenses'])) {
    $total_expenses = $expense_result['total_expenses'];
}

// Calculate remaining budget
$remaining_budget = $total_budget - $total_expenses;

// Calculate percentages
if ($total_budget > 0) {
    $spent_percentage = ($total_expenses / $total_budget) * 100;
    $remaining_percentage = ($remaining_budget / $total_budget) * 100;
}

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

        .content {
            margin-left: 270px;
            padding: 20px;
            padding-top: 80px; /* Padding for the navbar */
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            margin-top: 20px;
            text-align: center;
        }

        .chart-container canvas {
            width: 90% !important;
            height: 35vh !important;
        }

        .carousel-inner {
            text-align: center;
        }

        .carousel-item {
            padding: 15px;
        }

        .category-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: #000;
        }

        /* Mobile Responsive Fix */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
                padding-top: 100px; /* Ensure navbar does not overlap content */
            }

            /* Sidebar toggle */
            .sidebar {
                display: none;
                position: absolute;
                top: 0;
                left: 0;
                width: 250px;
                height: 100%;
                background-color: #AEC6D2;
                padding-top: 20px;
                z-index: 1000;
            }

            .sidebar.active {
                display: block;
            }

            .sidebar a {
                padding: 12px;
                color: white;
                font-size: 18px;
            }

            .navbar-toggler {
                background-color: #AEC6D2;
                border: none;
                color: white;
            }

            .navbar-toggler-icon {
                background-color: white;
            }
        }
    </style>
</head>
<body>

    <!-- Mobile Sidebar Toggle Button -->
    <nav class="navbar navbar-expand-lg navbar-light d-lg-none">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="navbar-brand">Dashboard</span>
        </div>
    </nav>    

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2 class="text-center text-white">ZOi </h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container">
            <div id="categoryCarousel" class="carousel slide category-carousel" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="category-card">
                                <h5><?php echo $category; ?></h5>
                                <p>Spent: UGX <?php echo number_format($spent_totals[$category] ?? 0, 2); ?></p>
                                <p>Budget: UGX <?php echo number_format($budget_totals[$category] ?? 0, 2); ?></p>
                                <p>Remaining: UGX <?php echo number_format(($budget_totals[$category] ?? 0) - ($spent_totals[$category] ?? 0), 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#categoryCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#categoryCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>

            <!-- Budget Section -->
            <div class="row mb-4">
                <!-- Total Budget -->
                <div class="col-md-4">
                    <div class="card text-center">
                        <h5>Total Budget</h5>
                        <p>UGX <?php echo number_format($total_budget, 2); ?></p>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%; background-color: #36A2EB;" role="progressbar">100%</div>
                        </div>
                    </div>
                </div>

                <!-- Total Spending -->
                <div class="col-md-4">
                    <div class="card text-center">
                        <h5>Total Spending</h5>
                        <p>UGX <?php echo number_format($total_expenses, 2); ?></p>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $spent_percentage; ?>%; background-color: #FF5733;" role="progressbar">
                                <?php echo round($spent_percentage); ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remaining Budget -->
                <div class="col-md-4">
                    <div class="card text-center">
                        <h5>Remaining Budget</h5>
                        <p>UGX <?php echo number_format($remaining_budget, 2); ?></p>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $remaining_percentage; ?>%; background-color: #2ECC71;" role="progressbar">
                                <?php echo round($remaining_percentage); ?>%
                            </div>
                        </div>
                    </div>
                </div>
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
