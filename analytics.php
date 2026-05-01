<?php
session_start();
require 'db_config.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch data for the Chart (Top 5 States Only)
$chart_query = "SELECT state_name, gold, silver, bronze FROM medals ORDER BY gold DESC, silver DESC LIMIT 5";
$chart_result = mysqli_query($conn, $chart_query);

// Process data into arrays for Chart.js
$states = [];
$golds = [];
$silvers = [];
$bronzes = [];

while($row = mysqli_fetch_assoc($chart_result)) {
    $states[] = $row['state_name'];
    $golds[] = $row['gold'];
    $silvers[] = $row['silver'];
    $bronzes[] = $row['bronze'];
}

// Convert PHP arrays into JSON format
$states_json = json_encode($states);
$golds_json = json_encode($golds);
$silvers_json = json_encode($silvers);
$bronzes_json = json_encode($bronzes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Dashboard - SIS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background-color: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="header">
    <h2>Performance Analytics</h2>
    <div>
        <a href="dashboard.php" style="background-color: #f8f9fa; color: #333; padding: 8px 15px; text-decoration: none; border-radius: 4px; border: 1px solid #ccc;">&larr; Back to Main Dashboard</a>
    </div>
</div>

<div class="container">
    <h3 style="text-align: center; color: #333; margin-bottom: 30px;">Top 5 Contingents (Gold-Weighted)</h3>
    
    <div style="width: 100%; max-width: 900px; margin: 0 auto;">
        <canvas id="medalChart"></canvas>
    </div>
</div>

<script>
    const labels = <?php echo $states_json; ?>;
    const goldData = <?php echo $golds_json; ?>;
    const silverData = <?php echo $silvers_json; ?>;
    const bronzeData = <?php echo $bronzes_json; ?>;

    const ctx = document.getElementById('medalChart').getContext('2d');
    const medalChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Gold',
                    data: goldData,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Silver',
                    data: silverData,
                    backgroundColor: 'rgba(192, 192, 192, 0.8)',
                    borderColor: 'rgba(192, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Bronze',
                    data: bronzeData,
                    backgroundColor: 'rgba(205, 127, 50, 0.8)',
                    borderColor: 'rgba(205, 127, 50, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });
</script>

</body>
</html>