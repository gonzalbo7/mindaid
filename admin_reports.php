<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

session_regenerate_id(true);

include_once 'Class/User.php';
include_once 'admin_sidebar.php';

$u = new User();

$filters = [
    'course' => $_GET['course'] ?? '',
    'year_level' => $_GET['year_level'] ?? '',
    'depression_class' => $_GET['depression_class'] ?? '',
    'anxiety_class' => $_GET['anxiety_class'] ?? '',
    'stress_class' => $_GET['stress_class'] ?? '',
    'test_status' => $_GET['test_status'] ?? '',
    'remark_status' => $_GET['remark_status'] ?? '',
    'remarks' => $_GET['remarks'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? ''
];

$u->filters = $filters;
$results = $u->getFilteredResults();

$students = [];

foreach ($results as $entry) {
    $studentId = $entry['student_id'];
    if (!isset($students[$studentId])) {
        $students[$studentId] = [
            'info' => $entry,
            'results' => []
        ];
    }
    $students[$studentId]['results'][] = $entry;
}

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
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }

        .filter-form {
            margin: 20px 0;
            padding: 20px;
            border-radius: 15px;
            background: #f8f9fa;
        }
        
        .table-wrapper {
    overflow-x: auto;
    padding: 20px;
}

table {
    width: 100%;
    table-layout: fixed; /* Ensures the columns are the same width */
}

th, td {
    vertical-align: middle !important;
    white-space: nowrap;
    text-overflow: ellipsis; /* Truncate long text */
    overflow: hidden; /* Prevents text overflow */
}

.table-responsive {
    display: block;
    max-height: 500px; /* Set a max height for the table body */
    overflow-y: auto; /* Adds vertical scroll if content exceeds max height */
}


@media print {
    @page {
        margin: 2cm;
    }

    body * {
        visibility: hidden;
    }

    #formalReportContainer {
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .print-report, .print-report * {
        visibility: visible;
    }

    .print-report {
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        color: #000;
        line-height: 1.6;
        background-color: #fff;
        padding: 20px;
        width: 100%;
    }

    .print-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    .print-header h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }

    .print-header p {
        margin: 0;
        font-size: 14px;
    }

    .print-entry {
        margin-bottom: 40px;
        padding: 20px;
        border-radius: 10px;
        background: #fefefe;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
    }

    .print-entry h2 {
        font-size: 18px;
        margin-bottom: 10px;
        border-bottom: 1px solid #333;
        padding-bottom: 5px;
    }

    .print-entry table {
        table-layout: auto !important;
    }

    .print-entry th,
    .print-entry td {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: initial !important;
        word-wrap: break-word !important;
        font-size: 13px;
    }

    .print-entry th {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    hr {
        margin: 30px 0;
        border: none;
        border-top: 1px solid #bbb;
    }

    .no-print {
        display: none !important;
    }

    .dataTables_wrapper, .table-wrapper {
        display: none !important;
    }
}


    
    </style>
</head>
<body>
<div class="main-content">
    <div class="container-fluid no-print">
        <h2 class="mt-4">MindAid Test Results</h2>
        <form class="filter-form row g-3" method="get">
            <div class="col-md-4">
                <label for="start_date" class="form-label">From Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">To Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']) ?>">
            </div>
            <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>
        <button onclick="generateFormalReport()" class="btn btn-primary mb-3">
            🖨️ Print All (Filtered Results)
        </button>
    </div>

    <div class="container-fluid table-wrapper">
        <div class="table-responsive">
            <table id="resultsTable" class="table table-hover table-striped table-bordered nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Depression</th>
                        <th>Anxiety</th>
                        <th>Stress</th>
                        <th>Date Taken</th>
                        <th>Remarks</th>
                        <th>Remark By</th>
                        <th>Total Tests Taken</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
               <tbody>
                    <?php foreach ($students as $studentId => $student): ?>
                        <tr>
                            <td><?= $student['info']['student_id'] ?></td>
                            <td><?= $student['info']['first_name'] . ' ' . $student['info']['middle_name'] . ' ' . $student['info']['last_name'] ?></td>
                            <td><?= $student['info']['email'] ?></td>
                            <td><?= $student['info']['course'] ?></td>
                            <td><?= $student['info']['year_level'] ?></td>
                            <td><?= $student['info']['depression_class'] ?> (<?= $student['info']['depression_score'] ?>)</td>
                            <td><?= $student['info']['anxiety_class'] ?> (<?= $student['info']['anxiety_score'] ?>)</td>
                            <td><?= $student['info']['stress_class'] ?> (<?= $student['info']['stress_score'] ?>)</td>
                            <td><?= $student['info']['date_taken'] ?></td>
                            <td>
                                <?= $student['info']['remark_status'] == 'Completed' ? '✅' : '❌' ?>
                                <?= $student['info']['remarks'] || $student['info']['counselors_remarks'] ? ' - ' . trim($student['info']['remarks'] . ' ' . $student['info']['counselors_remarks']) : '' ?>
                            </td>
                            <td><?= $student['info']['remark_by'] ?></td>
                            <td><?= count($student['results']) ?></td>
                            <td class="no-print text-center">
                                <button 
                                    class="btn btn-sm btn-outline-secondary" 
                                    onclick='generateIndividualReport("<?= $studentId ?>", <?= json_encode($student["results"]) ?>)'>
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <!-- Progress Chart Button -->
                                <button 
                                    class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#progressChartModal"
                                    onclick='showProgressChart(<?= json_encode($student["results"]) ?>)'>
                                    <i class="fas fa-chart-line"></i> Progress Chart
                                </button>
                
                                <!-- All Results Button -->
                                <button 
                                    class="btn btn-sm btn-outline-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewAllResultsModal"
                                    onclick='viewAllResults(<?= json_encode($student["results"]) ?>)'>
                                    <i class="fas fa-file-alt"></i> All Results
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Printable report layout -->
<div class="print-report" id="formalReportContainer" style="display: none;"></div>

<!-- Modal for Chart -->
<div class="modal fade" id="progressChartModal" tabindex="-1" aria-labelledby="progressChartLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Progress Chart</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <canvas id="progressChartCanvas"></canvas>
      </div>
    </div>
  </div>
</div>



<!-- All Results Modal -->
<div class="modal fade" id="viewAllResultsModal" tabindex="-1" aria-labelledby="viewAllResultsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewAllResultsModalLabel">All Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="allResultsContent">
        <!-- Results will be inserted here -->
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function () {
        $('#resultsTable').DataTable({
            responsive: true,
            scrollX: true,
            "order": [] // No initial sorting
        });
    });
    
    //Progress Chart
    let progressChart;

function showProgressChart(results) {
    const labels = results.map(r => r.date_taken);
    const depressionData = results.map(r => ({ x: r.date_taken, y: r.depression_score, label: r.depression_class }));
    const anxietyData = results.map(r => ({ x: r.date_taken, y: r.anxiety_score, label: r.anxiety_class }));
    const stressData = results.map(r => ({ x: r.date_taken, y: r.stress_score, label: r.stress_class }));

    const ctx = document.getElementById('progressChartCanvas').getContext('2d');

    if (progressChart) {
        progressChart.destroy(); // Destroy old chart if it exists
    }

    progressChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Depression',
                    data: depressionData,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.3
                },
                {
                    label: 'Anxiety',
                    data: anxietyData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.3
                },
                {
                    label: 'Stress',
                    data: stressData,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    fill: false,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const point = context.raw;
                            return `${context.dataset.label}: ${point.y} (${point.label})`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Score'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date Taken'
                    }
                }
            }
        }
    });
}
    
    //Other Assessment Results
    function viewAllResults(results) {
        const container = document.getElementById('allResultsContent');
        container.innerHTML = '';

        if (results.length === 0) {
            container.innerHTML = '<p>No results found.</p>';
            return;
        }

        results.forEach(result => {
            const div = document.createElement('div');
            div.classList.add('mb-3', 'p-3', 'border', 'rounded');
            div.innerHTML = `
                <strong>Date Taken:</strong> ${result.date_taken}<br>
                <strong>Depression:</strong> ${result.depression_class} (${result.depression_score})<br>
                <strong>Anxiety:</strong> ${result.anxiety_class} (${result.anxiety_score})<br>
                <strong>Stress:</strong> ${result.stress_class} (${result.stress_score})<br>
                <strong>Remarks:</strong> ${result.remarks}<br>
                <strong>Remark By:</strong> ${result.remark_by}<br>
                <hr>
            `;
            container.appendChild(div);
        });
    }

    //All Print
    function generateFormalReport() {
        const studentsData = <?= json_encode($students) ?>;
        const container = document.getElementById('formalReportContainer');
        container.innerHTML = '';
    
        Object.values(studentsData).forEach(student => {
            const info = student.info;
            const results = student.results;
    
            let studentSection = `
                <div class="print-entry" style="page-break-after: always;">
                    <div class="print-header">
                        <h3>${info.first_name} ${info.middle_name} ${info.last_name}</h3>
                        <p><strong>Student ID:</strong> ${info.student_id}</p>
                        <p><strong>Email:</strong> ${info.email}</p>
                        <p><strong>Course:</strong> ${info.course}</p>
                        <p><strong>Year Level:</strong> ${info.year_level}</p>
                    </div>
                    <h5>📄 All Test Results:</h5>
                    <table style="width:100%; border-collapse: collapse;" border="1" cellpadding="8">
                        <thead>
                            <tr>
                                <th>Date Taken</th>
                                <th>Depression</th>
                                <th>Anxiety</th>
                                <th>Stress</th>
                                <th>Remarks</th>
                                <th>Remarked By</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
    
    
            results.forEach(result => {
                studentSection += `
                    <tr>
                        <td>${result.date_taken}</td>
                        <td>${result.depression_class} (${result.depression_score})</td>
                        <td>${result.anxiety_class} (${result.anxiety_score})</td>
                        <td>${result.stress_class} (${result.stress_score})</td>
                        <td>
                            ${result.remark_status === 'Completed' ? '✅' : '❌'}
                            ${result.remarks ? ' - ' + result.remarks : ''}
                        </td>
                        <td>${result.remark_by}</td>
                    </tr>
                `;
            });
    
            studentSection += `
                        </tbody>
                    </table>
                </div>
                <hr>
            `;
    
            container.innerHTML += studentSection;
        });
    
        window.print();
    }


    function generateIndividualReport(studentId, results) {
        const studentInfo = results[0]; // base info from first result
        const container = document.getElementById('formalReportContainer');
        container.innerHTML = `
            <div class="print-entry">
                <div class="print-header">
                    <h3>${studentInfo.first_name} ${studentInfo.middle_name} ${studentInfo.last_name}</h3>
                    <p><strong>Student ID:</strong> ${studentInfo.student_id}</p>
                    <p><strong>Email:</strong> ${studentInfo.email}</p>
                    <p><strong>Course:</strong> ${studentInfo.course}</p>
                    <p><strong>Year Level:</strong> ${studentInfo.year_level}</p>
                </div>
                <h5>📄 All Test Results:</h5>
                <table style="width:100%; border-collapse: collapse;" border="1" cellpadding="8">
                    <thead>
                        <tr>
                            <th>Date Taken</th>
                            <th>Depression</th>
                            <th>Anxiety</th>
                            <th>Stress</th>
                            <th>Remarks</th>
                            <th>Remarked By</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${results.map(result => `
                            <tr>
                                <td>${result.date_taken}</td>
                                <td>${result.depression_class} (${result.depression_score})</td>
                                <td>${result.anxiety_class} (${result.anxiety_score})</td>
                                <td>${result.stress_class} (${result.stress_score})</td>
                                <td>${result.remark_status === 'Completed' ? '✅ Completed' : '❌ Pending'}</td>
                                <td>${result.remark_by}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        window.print();
    }

</script>

</body>
</html>