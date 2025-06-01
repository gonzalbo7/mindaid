<?php
date_default_timezone_set('Asia/Manila');
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

session_regenerate_id(true);

include_once 'admin_sidebar.php';
include_once 'Class/User.php';

function getStatusIcon($status) {
    switch ($status) {
        case 'Normal':
            return '<i class="fas fa-circle status-icon status-normal"></i>';
        case 'Mild':
            return '<i class="fas fa-circle status-icon status-mild"></i>';
        case 'Moderate':
            return '<i class="fas fa-circle status-icon status-moderate"></i>';
        case 'Severe':
            return '<i class="fas fa-circle status-icon status-severe"></i>';
        case 'Extremely Severe':
            return '<i class="fas fa-circle status-icon status-extremely-severe"></i>';
        default:
            return '<i class="fas fa-circle status-icon"></i>';
    }
}

$pendingCount = $u->getPendingRemarkCount();
$completedCount = $u->getCompletedRemarkCount();

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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
            color: #333;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
            transition: margin-left 0.3s;
        }
        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .status-icon {
            font-size: 18px;
            margin-right: 5px;
        }
        .status-normal { color: #28a745; }
        .status-mild { color: #17a2b8; }
        .status-moderate { color: #ffc107; }
        .status-severe { color: #fd7e14; }
        .status-extremely-severe { color: #dc3545; }

        @media (max-width: 768px) {
            .content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            .badge-custom{
                margin-right:100px;
            }
        }

        .badge-custom {
            font-size: 16px;
            padding: 6px 14px;
            border-radius: 20px;
            background: linear-gradient(135deg, #ff6a00, #ee0979); /* Gradient color */
            color: #fff;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Soft shadow for depth */
            transition: transform 0.2s ease-in-out;
        }

        .badge-custom:hover {
            transform: translateY(-2px); /* Lift effect on hover */
        }

        .btn-dass {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease-in-out;
            border: none;
        }

        .btn-dass:hover {
            background: linear-gradient(135deg, #0072ff, #0059b3);
            transform: scale(1.05);
            color: #fff;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1rem 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

    </style>
</head>
<body>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap"> 
        <h2 class="mb-2">Assessment Test Results</h2>

        <div>
            <?php if ($pendingCount > 0): ?>
                <span class="badge-custom me-2">
                    <?= $pendingCount ?> Pending
                </span>
            <?php endif; ?>

            <?php if ($completedCount > 0): ?>
                <span class="badge-custom">
                    <?= $completedCount ?> Completed
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3 text-end w-100">
        <button type="button" class="btn btn-dass shadow-sm" data-bs-toggle="modal" data-bs-target="#dassModal">
            <i class="fas fa-book-open me-2"></i> View DASS-42 Reference
        </button>
    </div>


    <div class="table-responsive">
        <table class="table table-hover" id="resultTable">
            <thead>
                <tr class="text-nowrap">
                    <th>Student Info</th>
                    <th>Status</th>
                    <th>Date Taken</th>
                    <th>Remark Status</th>
                    <th>Remarks</th>
                    <th>Remark By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $data = $u->displayalltestresult();

                if ($data) {
                    while ($row = $data->fetch_assoc()) {
                        $student_id = htmlspecialchars($row['student_id'] ?? '');
                        $first_name = htmlspecialchars($row['first_name'] ?? '');
                        $middle_name = htmlspecialchars($row['middle_name'] ?? '');
                        $last_name = htmlspecialchars($row['last_name'] ?? '');
                        $email = htmlspecialchars($row['email'] ?? '');
                        $course = htmlspecialchars($row['course'] ?? '');
                        $year_level = htmlspecialchars($row['year_level'] ?? '');
                        $date_taken = htmlspecialchars($row['date_taken'] ?? '');
                        $remark_status = htmlspecialchars($row['remark_status'] ?? 'Pending');
                        $remark = htmlspecialchars($row['remarks'] ?? '');
                        $remark_by = htmlspecialchars($row['remark_by'] ?? '');

                        $depression_class = htmlspecialchars($row['depression_class'] ?? 'N/A');
                        $anxiety_class = htmlspecialchars($row['anxiety_class'] ?? 'N/A');
                        $stress_class = htmlspecialchars($row['stress_class'] ?? 'N/A');

                        $depression_score = htmlspecialchars($row['depression_score'] ?? 'N/A');
                        $anxiety_score = htmlspecialchars($row['anxiety_score'] ?? 'N/A');
                        $stress_score = htmlspecialchars($row['stress_score'] ?? 'N/A');

                        $status_icon_depression = getStatusIcon($depression_class);
                        $status_icon_anxiety = getStatusIcon($anxiety_class);
                        $status_icon_stress = getStatusIcon($stress_class);


                        $full_name = trim("$first_name $middle_name $last_name");

                        echo "
                        <tr class='text-nowrap flex-wrap'>
                            <td>
                                <strong>$full_name</strong><br>
                                <medium><b>Student ID:</b> $student_id</medium><br>
                                <medium><b>Email:</b> $email</medium><br>
                                <medium><b>Course:</b> $course</medium><br>
                                <medium><b>Year Level:</b> $year_level</medium>
                            </td>
                            <td>
                                <medium><b class='me-1'>Depression:</b> $status_icon_depression $depression_class ($depression_score)</medium><br>
                                <medium><b class='me-1'>Anxiety:</b> $status_icon_anxiety $anxiety_class ($anxiety_score)</medium><br>
                                <medium><b class='me-1'>Stress:</b> $status_icon_stress $stress_class ($stress_score)</medium>
                            </td>
                            <td>$date_taken</td>
                            <td>$remark_status</td>
                            <td>$remark</td>
                            <td>$remark_by</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No Results Found!</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DASS-42 Classification Reference Modal -->
<div class="modal fade" id="dassModal" tabindex="-1" aria-labelledby="dassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="dassModalLabel"><b>DASS-42 Classification Reference Table</b></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                <tr>
                    <th>Severity</th>
                    <th>Depression</th>
                    <th>Anxiety</th>
                    <th>Stress</th>
                </tr>
                </thead>
                <tbody>
                <tr><td>Normal</td><td>0 – 9</td><td>0 – 7</td><td>0 – 14</td></tr>
                <tr><td>Mild</td><td>10 – 13</td><td>8 – 9</td><td>15 – 18</td></tr>
                <tr><td>Moderate</td><td>14 – 20</td><td>10 – 14</td><td>19 – 25</td></tr>
                <tr><td>Severe</td><td>21 – 27</td><td>15 – 19</td><td>26 – 33</td></tr>
                <tr><td>Extremely Severe</td><td>28+</td><td>20+</td><td>34+</td></tr>
                </tbody>
            </table>
            </div>
        </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('#resultTable').DataTable({
            "order": [] // No initial sorting
        });
    });
</script>


</body>
</html>