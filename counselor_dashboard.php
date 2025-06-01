<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Guidance Counselor') {
    header("Location: index.php");
    exit();
}

session_regenerate_id(true);

include_once 'Class/User.php';
include_once 'counselor_sidebar.php';

$u = new User();

$filters = [
    'course' => $_GET['course'] ?? 'All',
    'year_level' => $_GET['year_level'] ?? 'All'
];

$data = $u->getDashboardData($filters);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        font-family: 'Poppins', sans-serif;
    }

    .main-content {
        padding: 2rem;
        margin-left: 0;
    }

    @media (min-width: 768px) {
        .main-content {
            margin-left: 250px;
        }
    }

    .dashboard-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 1rem;
        padding: 1.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }

    .dashboard-card h5 {
        font-size: 1.1rem;
        color: #555;
    }

    .dashboard-card h3 {
        font-size: 2.2rem;
        color: #2c3e50;
    }

    .card-title {
        font-weight: 700;
    }

    @media (max-width: 768px) {
      .main-content{
        margin-left:100px;
        width:80%;
      }

      form select, form button {
          min-height: 45px;
      }
    }

    .chart-container {
    position: relative;
    width: 100%;
    height: auto;
}

.chart-container canvas {
    width: 100% !important;
    height: auto !important;
}

</style>

</head>
<body class="bg-light">

<!-- This div assumes your sidebar is included in admin_sidebar.php -->
<div class="main-content">
    <h1 class="mb-4 text-center"> MindAid Counselor Dashboard</h1>

    <div class="row mb-4 text-center">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card">
                <h5>Total Questionnaire</h5>
                <h3><?= $data['questionnaire'] ?></h3>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="dashboard-card">
                <h5>Total Students</h5>
                <h3><?= $data['totalStudents'] ?></h3>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-10">
            <select name="year_level" class="form-select">
                <option value="All" <?= $data['filters']['year_level'] == 'All' ? 'selected' : '' ?>>All Year Levels</option>
                <option value="1st Year" <?= $data['filters']['year_level'] == '1st Year' ? 'selected' : '' ?>>1st Year</option>
                <option value="2nd Year" <?= $data['filters']['year_level'] == '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3rd Year" <?= $data['filters']['year_level'] == '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4th Year" <?= $data['filters']['year_level'] == '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Apply</button>
        </div>
    </form>

    <div class="card-body">
        <h5 class="card-title text-center mb-4">Assessment Results</h5>
        <div class="chart-container">
            <div style="text-align: center; margin-bottom: 15px;">
                <span style="background: #d0f0c0; padding: 4px 8px; border-radius: 4px; margin-right: 5px;">🟢 Depression</span>
                <span style="background: #bbdefb; padding: 4px 8px; border-radius: 4px; margin-right: 5px;">🔵 Anxiety</span>
                <span style="background: #e1bee7; padding: 4px 8px; border-radius: 4px;">🟣 Stress</span>
            </div>
            <canvas id="resultChart"></canvas>
        </div>
    </div>
</div>

<script>
    const chartData = <?= json_encode($data['chartData']) ?>;
    const ctx = document.getElementById('resultChart').getContext('2d');
    
    if (chartData.length > 0) {
        const labels = chartData.map(item => item.label);
        const datasets = [
            // Depression (Green Shades)
            { label: 'Depression - Normal', data: chartData.map(item => item.Depression_Normal || 0), backgroundColor: '#d0f0c0', stack: 'Depression' },
            { label: 'Depression - Mild', data: chartData.map(item => item.Depression_Mild || 0), backgroundColor: '#a5d6a7', stack: 'Depression' },
            { label: 'Depression - Moderate', data: chartData.map(item => item.Depression_Moderate || 0), backgroundColor: '#81c784', stack: 'Depression' },
            { label: 'Depression - Severe', data: chartData.map(item => item.Depression_Severe || 0), backgroundColor: '#4caf50', stack: 'Depression' },
            { label: 'Depression - Extremely Severe', data: chartData.map(item => item.Depression_Extremely_Severe || 0), backgroundColor: '#2e7d32', stack: 'Depression' },
    
            // Anxiety (Blue Shades)
            { label: 'Anxiety - Normal', data: chartData.map(item => item.Anxiety_Normal || 0), backgroundColor: '#bbdefb', stack: 'Anxiety' },
            { label: 'Anxiety - Mild', data: chartData.map(item => item.Anxiety_Mild || 0), backgroundColor: '#64b5f6', stack: 'Anxiety' },
            { label: 'Anxiety - Moderate', data: chartData.map(item => item.Anxiety_Moderate || 0), backgroundColor: '#42a5f5', stack: 'Anxiety' },
            { label: 'Anxiety - Severe', data: chartData.map(item => item.Anxiety_Severe || 0), backgroundColor: '#2196f3', stack: 'Anxiety' },
            { label: 'Anxiety - Extremely Severe', data: chartData.map(item => item.Anxiety_Extremely_Severe || 0), backgroundColor: '#1565c0', stack: 'Anxiety' },
    
            // Stress (Purple Shades)
            { label: 'Stress - Normal', data: chartData.map(item => item.Stress_Normal || 0), backgroundColor: '#e1bee7', stack: 'Stress' },
            { label: 'Stress - Mild', data: chartData.map(item => item.Stress_Mild || 0), backgroundColor: '#ce93d8', stack: 'Stress' },
            { label: 'Stress - Moderate', data: chartData.map(item => item.Stress_Moderate || 0), backgroundColor: '#ba68c8', stack: 'Stress' },
            { label: 'Stress - Severe', data: chartData.map(item => item.Stress_Severe || 0), backgroundColor: '#ab47bc', stack: 'Stress' },
            { label: 'Stress - Extremely Severe', data: chartData.map(item => item.Stress_Extremely_Severe || 0), backgroundColor: '#6a1b9a', stack: 'Stress' }
        ];
    
        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    },
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
    }

</script>


</body>
</html>
