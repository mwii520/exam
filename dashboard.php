<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch spending data by category
$spending_sql = "SELECT category, SUM(amount) AS total_spent FROM expenses WHERE user_id = ? GROUP BY category";
$stmt = $conn->prepare($spending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_result = $stmt->get_result();

$categories = [];
$spent_totals = [];
while ($row = $spending_result->fetch_assoc()) {
    $categories[] = $row['category'];
    $spent_totals[$row['category']] = $row['total_spent'];
}
$stmt->close();

// Fetch budget data for each category
$budget_sql = "SELECT category, budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budget_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result();

$budget_totals = [];
while ($row = $budget_result->fetch_assoc()) {
    $budget_totals[$row['category']] = $row['budget'];
}
$stmt->close();

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
$daily_spending_sql = "SELECT DATE(date) AS date, SUM(amount) AS total_spent FROM expenses WHERE user_id = ? GROUP BY DATE(date) ORDER BY DATE(date) ASC";
$stmt = $conn->prepare($daily_spending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$daily_result = $stmt->get_result();

$daily_dates = [];
$daily_spent = [];
while ($row = $daily_result->fetch_assoc()) {
    $daily_dates[] = $row['date'];
    $daily_spent[] = $row['total_spent'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Font Awesome for icons -->
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

        /* Make the calendar card look good on small screens */
        .calendar-card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 250px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
        }

        #calendar {
            max-width: 230px;
            font-size: 14px;
            margin: 0 auto;
        }

        .charts-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .chart-box {
            width: 48%;
            margin-top: 20px;
        }

        .chart-box canvas {
            background-color: #f5f5f5;
            border-radius: 12px;
            padding: 10px;
        }

        /* Responsive Styling for Mobile */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
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

            /* Sidebar toggling on mobile */
            .sidebar {
                position: absolute;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }
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
        <h2 class="text-center text-white">Dashboard</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="welcome-message">
            Welcome, <?php echo $_SESSION['full_name']; ?>! <br>
            Here is an overview of your budget and spending.
        </div>

        <!-- Calendar Section -->
        <div class="card calendar-card">
            <h5 class="text-center">Your Calendar</h5>
            <div id="calendar"></div>
        </div>

        <!-- Category Balance Containers -->
        <div class="category-container">
            <?php foreach ($categories as $category) { 
                $spent = $chart_data[$category]['spent'];
                $budget = $chart_data[$category]['budget'];
                $remaining = $chart_data[$category]['remaining'];
            ?>
                <div class="category-box">
                    <h5><?php echo $category; ?></h5>
                    <p class="spent">Spent: $<?php echo number_format($spent, 2); ?></p>
                    <p class="budget">Budget: $<?php echo number_format($budget, 2); ?></p>
                    <p class="remaining">Remaining: $<?php echo number_format($remaining, 2); ?></p>
                </div>
            <?php } ?>
        </div>

        <!-- Charts Section (Budget vs Spent + Daily Spending Chart) -->
        <div class="charts-container">
            <!-- Bar Chart -->
            <div class="chart-box">
                <h5>Budget vs Spending Overview</h5>
                <canvas id="barChart"></canvas>
            </div>

            <!-- Line Chart (Daily Spending) -->
            <div class="chart-box">
                <h5>Daily Spending Overview</h5>
                <canvas id="lineChart"></canvas>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            $('#calendar').flatpickr({
                inline: true,
                locale: 'en',
                dateFormat: "Y-m-d",
            });
        });

        // Prepare data for Bar Chart
        const budgetData = <?php echo json_encode($chart_data); ?>;
        const labels = Object.keys(budgetData);
        const budgetValues = labels.map(category => budgetData[category].budget);
        const spentValues = labels.map(category => budgetData[category].spent);
        const remainingValues = labels.map(category => budgetData[category].remaining);

        const ctx = document.getElementById('barChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Budget',
                        data: budgetValues,
                        backgroundColor: '#36A2EB',
                    },
                    {
                        label: 'Spent',
                        data: spentValues,
                        backgroundColor: '#FF6384',
                    },
                    {
                        label: 'Remaining',
                        data: remainingValues,
                        backgroundColor: '#4BC0C0',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Prepare data for Line Chart
        const dailyDates = <?php echo json_encode($daily_dates); ?>;
        const dailySpent = <?php echo json_encode($daily_spent); ?>;

        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: dailyDates,
                datasets: [{
                    label: 'Daily Spending',
                    data: dailySpent,
                    fill: false,
                    borderColor: '#FF5733',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
