
<?php
date_default_timezone_set('Asia/Manila');
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Guidance Counselor') {
    header("Location: index.php");
    exit();
}

session_regenerate_id(true);

include_once 'counselor_sidebar.php';
include_once 'Class/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enableTest'])) {
    $student_id = $_POST['student_id'];

    $u = new User();
    $enabled = $u->enableTest($student_id);

    if ($enabled) {
        echo '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Success!",
                    text: "Test enabled successfully.",
                    icon: "success"
                }).then(function() {
                    window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                });
            </script>
        ';
        exit();
    } else {
        echo '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Student Not Found",
                    text: "The specified student could not be located in the records.",
                    icon: "error"
                }).then(function() {
                    window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                });
            </script>
        ';
        exit();
    }
}

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


require 'PHPMailer/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendRemarkEmail($email, $name, $remark, $result_id, $user) {
    $mail = new PHPMailer(true);
    try {
        // Existing code...

        // Fetch the test result
        $resultQuery = $user->getTestResultById($result_id);
        $result = $resultQuery; // It's already an array

        // Get DASS remarks
        $dassRemarks = getDASSRemark($result['depression_score'], $result['anxiety_score'], $result['stress_score']);

        $mail->Body = "
            <h3 style='color: #6a1b9a;'>Hello, $name</h3>
            <p>You have received a new test result and remark:</p>
            <p><strong>Date Taken:</strong> $dateTaken</p>
            <h4 style='color: #6a1b9a;'>Your Test Results:</h4>
            <p><strong>Depression:</strong> {$result['depression_score']} ({$result['depression_class']}) - {$dassRemarks['depression']}</p>
            <p><strong>Anxiety:</strong> {$result['anxiety_score']} ({$result['anxiety_class']}) - {$dassRemarks['anxiety']}</p>
            <p><strong>Stress:</strong> {$result['stress_score']} ({$result['stress_class']}) - {$dassRemarks['stress']}</p>
            <h4 style='color: #6a1b9a;'>Remarks:</h4>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 5px solid #6a1b9a;'>
                <strong>$remark</strong>
            </p>
            <p>Thank you,<br><strong>MindAid Counselor</strong></p>
        ";

        // Existing code...
    } catch (Exception $e) {
        // Existing error handling...
    }
}


// ✅ Handle updating of specific record only
if (isset($_POST['updateRemark'])) {
    $result_id = $_POST['result_id'];
    $customRemark = $_POST['customRemark'];

    // Ensure correct extraction of counselor name from session
    if (isset($_SESSION['user'])) {
        $counselor_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
    } else {
        $counselor_name = 'Unknown';
    }

    $u = new User();
    $student = $u->getStudentByResultId($result_id);

    if ($student) {
        $student_email = $student['email'];
        $student_name = $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name'];

        if (!empty($customRemark)) {
            $sent = sendRemarkEmail($student_email, $student_name, nl2br($customRemark), $result_id, $u);

            if ($sent === true) {
                $date_sent = date('Y-m-d H:i:s');
                if ($u->updateRemark($result_id, $customRemark, $date_sent, $counselor_name)) {
                    if ($u->updateRemarkStatus($result_id, 'Completed')) {
                        echo '
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                Swal.fire({
                                    title: "Success",
                                    text: "Remarks have been successfully recorded and sent to the student via email. The remark status has also been updated to Completed.",
                                    icon: "success"
                                }).then(() => {
                                    window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                                });
                            </script>';
                    } else {
                        echo '
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <script>
                            Swal.fire({
                                title: "Update Failed",
                                text: "The remark was saved, but updating the status to Completed was unsuccessful.",
                                icon: "error"
                            });
                        </script>';
                    }
                } else {
                    echo '
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        Swal.fire({
                            title: "Update Failed",
                            text: "An error occurred while attempting to update the remark.",
                            icon: "error"
                        });
                    </script>';
                }
            } else {
                echo '
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    Swal.fire({
                        title: "Email Dispatch Failed",
                        text: "The remark could not be sent via email. Error: '. addslashes($sent) .'",
                        icon: "error"
                    });
                </script>';
            }
        } else {
            echo '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Missing Remark",
                    text: "Please provide a remark before submitting.",
                    icon: "warning"
                });
            </script>';
        }
    } else {
        echo '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Student Not Found",
                text: "The specified student could not be located in the records.",
                icon: "error"
            });
        </script>';
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

        .btn-view, .btn-enable {
            background-color: #007bff;
            color: #fff;
            border-radius: 20px;
            padding: 5px 12px;
            transition: background-color 0.3s ease;
        }
        .btn-view:hover, .btn-enable:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            .badge-custom{
                margin-right:100px;
            }
        }

        .modal-btn {
            background: linear-gradient(135deg, #4b6cb7, #182848);
            color: #ffffff;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .modal-btn:hover {
            background: linear-gradient(135deg, #3a4a8b, #10162a);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .modal-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .tooltip {
            visibility: hidden;
            background-color: #1B263B;
            color: #FFFFFF;
            text-align: center;
            padding: 6px;
            border-radius: 4px;
            position: absolute;
            z-index: 10;
            bottom: 125%; /* Position above the button */
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }

        .modal-btn:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }

        .enable-test-btn {
            background: linear-gradient(135deg, #28a745, #1e7e34); /* Green-themed gradient */
        }

        .enable-test-btn:hover {
            background: linear-gradient(135deg, #218838, #155d27);
        }

        .enable-test-btn:active {
            box-shadow: 0 3px 8px rgba(40, 167, 69, 0.3);
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
        
        #resultTable {
            width: 100%;
            table-layout: auto; /* allow flexible column width */
            white-space: normal;
        }
    
        #resultTable th, #resultTable td {
            white-space: normal !important; /* allow wrapping inside cells */
            vertical-align: middle;
        }
    
        /* Optional: shrink font a little on smaller screens */
        @media (max-width: 768px) {
            #resultTable th, #resultTable td {
                font-size: 12px;
                padding: 8px;
            }
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
        <table class="table table-hover text-nowrap" id="resultTable">
            <thead>
                <tr class="text-nowrap">
                    <th>Student Info</th>
                    <th>Status</th>
                    <th>Date Taken</th>
                    <th>Remark Status</th>
                    <th> Remark </th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $u = new User();
                $data = $u->displayalltestresult();
                $latestTestResultIds = $u->getLatestTestResultIds();

                if ($data) {
                    while ($row = $data->fetch_assoc()) {
                        $result_id = htmlspecialchars($row['result_id'] ?? '');
                        $student_id = htmlspecialchars($row['student_id'] ?? '');
                        $first_name = htmlspecialchars($row['first_name'] ?? '');
                        $middle_name = htmlspecialchars($row['middle_name'] ?? '');
                        $last_name = htmlspecialchars($row['last_name'] ?? '');
                        $email = htmlspecialchars($row['email'] ?? '');
                        $course = htmlspecialchars($row['course'] ?? '');
                        $year_level = htmlspecialchars($row['year_level'] ?? '');
                        $date_taken = htmlspecialchars($row['date_taken'] ?? '');
                        $remark_status = htmlspecialchars($row['remark_status'] ?? 'Pending');

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
                                <medium class='text-nowrap'><b class='me-1'>Depression:</b> $status_icon_depression $depression_class</medium><br>
                                <medium class='text-nowrap'><b class='me-1'>Anxiety:</b> $status_icon_anxiety $anxiety_class</medium><br>
                                <medium class='text-nowrap'><b class='me-1'>Stress:</b> $status_icon_stress $stress_class</medium>
                            </td>
                            <td> $date_taken </td>
                            <td> $remark_status </td>
                            <td>
                                <button 
                                    class='modal-btn give-remark-btn'
                                    data-result-id='{$row['result_id']}'
                                    data-student-name='{$row['first_name']} {$row['last_name']}'
                                    data-depression='{$row['depression_class']}'
                                    data-anxiety='{$row['anxiety_class']}'
                                    data-stress='{$row['stress_class']}'
                                    data-email='{$row['email']}'
                                    data-remark-status='{$row['remark_status']}'>
                                    <i class='fas fa-sticky-note'></i>
                                    <span class='tooltip'>Give Remark</span>
                                </button>
                            </td>
                            <td>";
                            // ✅ Check if student_id exists in the latest test result array
                            if (isset($latestTestResultIds[$row['student_id']]) && $row['result_id'] == $latestTestResultIds[$row['student_id']] && $row['test_status'] != 'Enabled') {
                                echo "
                                <form method='POST'>
                                    <input type='hidden' name='student_id' value='$student_id'>
                                    <button type='submit' name='enableTest' class='modal-btn btn btn-enable enable-test-btn mt-3'>
                                        <i class='fas fa-check-circle'></i>
                                        <span class='tooltip'>Enable Test</span>
                                    </button>
                                </form>";
                            }
                            echo "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No Results Found!</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Give Remarks Modal -->
<div class="modal fade" id="giveRemarkModal" tabindex="-1" aria-labelledby="giveRemarkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="giveRemarkForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Give Remark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="result_id" id="result_id">
                    <p><strong>Student:</strong> <span id="studentName"></span></p>
                    <p><strong>Depression Level:</strong> <span id="depressionLevel"></span></p>
                    <p><strong>Anxiety Level:</strong> <span id="anxietyLevel"></span></p>
                    <p><strong>Stress Level:</strong> <span id="stressLevel"></span></p>
                    
                    <!-- DASS Remarks Section -->
                    <div id="dassRemarks" class="mb-3">
                        <h6><b>DASS Remarks:</b></h6>
                        <p id="dassDepressionRemark"></p>
                        <p id="dassAnxietyRemark"></p>
                        <p id="dassStressRemark"></p>
                    </div>

                    <div class="mb-3">
                        <label for="customRemark" class="form-label">Custom Remark:</label>
                        <textarea class="form-control" name="customRemark" id="customRemark" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRemarkBtn">Proceed</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Confirmation Modal -->
<div class="modal fade" id="confirmRemarkModal" tabindex="-1" aria-labelledby="confirmRemarkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="confirmRemarkForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Remark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Student:</strong> <span id="confirmStudentName"></span></p>
                    <p><strong>Depression Level:</strong> <span id="confirmDepressionLevel"></span></p>
                    <p><strong>Anxiety Level:</strong> <span id="confirmAnxietyLevel"></span></p>
                    <p><strong>Stress Level:</strong> <span id="confirmStressLevel"></span></p>
                    <p><strong>Custom Remark:</strong> <span id="confirmCustomRemark"></span></p>
                    <input type="hidden" name="result_id" id="confirmResultId">
                    <input type="hidden" name="customRemark" id="confirmCustomRemarkInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#giveRemarkModal">Cancel</button>
                    <button type="submit" class="btn btn-success" name="updateRemark">Send Remark</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DASS-42 Classification Reference Modal -->
<div class="modal fade" id="dassModal" tabindex="-1" aria-labelledby="dassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #00c6ff, #0072ff);">
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

    $(document).ready(function () {
        // Open "Give Remarks" modal with data
        $(document).on('click', '.give-remark-btn', function () {
            const resultId = $(this).data('result-id');
            const studentName = $(this).data('student-name');
            const depression = $(this).data('depression');
            const anxiety = $(this).data('anxiety');
            const stress = $(this).data('stress');
            const email = $(this).data('email');
            $('#result_id').val(resultId);
            $('#studentName').text(studentName);
            $('#depressionLevel').text(depression);
            $('#anxietyLevel').text(anxiety);
            $('#stressLevel').text(stress);
            // Calculate DASS remarks and categories
            const dassRemarks = getDASSRemark(depression, anxiety, stress);
            $('#dassDepressionRemark').text(`${dassRemarks.depression} (${dassRemarks.depression_class})`);
            $('#dassAnxietyRemark').text(`${dassRemarks.anxiety} (${dassRemarks.anxiety_class})`);
            $('#dassStressRemark').text(`${dassRemarks.stress} (${dassRemarks.stress_class})`);
            $('#giveRemarkModal').modal('show');
        });

        // Transfer data to "Confirmation" modal
        $('#confirmRemarkBtn').on('click', function () {
            // Get values from the first modal
            const studentName = $('#studentName').text();
            const depression = $('#depressionLevel').text();
            const anxiety = $('#anxietyLevel').text();
            const stress = $('#stressLevel').text();
            const customRemark = $('#customRemark').val();
            const resultId = $('#result_id').val();
    
            // Set values in the confirmation modal
            $('#confirmStudentName').text(studentName);
            $('#confirmDepressionLevel').text(depression);
            $('#confirmAnxietyLevel').text(anxiety);
            $('#confirmStressLevel').text(stress);
            $('#confirmCustomRemark').text(customRemark);
    
            $('#confirmResultId').val(resultId);
            $('#confirmCustomRemarkInput').val(customRemark);
    
            // Close the first modal and open the second one
            $('#giveRemarkModal').modal('hide');
            $('#confirmRemarkModal').modal('show');
        });
    });
    
function getDASSRemark(depression_score, anxiety_score, stress_score) {
    const remarks = {
        depression_class: '',
        anxiety_class: '',
        stress_class: ''
    };

    // Depression remarks
    if (depression_score <= 9) {
        remarks.depression = "Depression: You are doing well.";
        remarks.depression_class = "Normal";
    } else if (depression_score <= 13) {
        remarks.depression = "Depression: You may want to take care of your mental health.";
        remarks.depression_class = "Mild";
    } else if (depression_score <= 20) {
        remarks.depression = "Depression: Consider seeking support.";
        remarks.depression_class = "Moderate";
    } else if (depression_score <= 27) {
        remarks.depression = "Depression: It's important to talk to someone.";
        remarks.depression_class = "Severe";
    } else {
        remarks.depression = "Depression: Please seek professional help.";
        remarks.depression_class = "Extremely Severe";
    }

    // Anxiety remarks
    if (anxiety_score <= 7) {
        remarks.anxiety = "Anxiety: You are managing well.";
        remarks.anxiety_class = "Normal";
    } else if (anxiety_score <= 9) {
        remarks.anxiety = "Anxiety: A little stress is normal.";
        remarks.anxiety_class = "Mild";
    } else if (anxiety_score <= 14) {
        remarks.anxiety = "Anxiety: Consider relaxation techniques.";
        remarks.anxiety_class = "Moderate";
    } else if (anxiety_score <= 19) {
        remarks.anxiety = "Anxiety: It's advisable to seek help.";
        remarks.anxiety_class = "Severe";
    } else {
        remarks.anxiety = "Anxiety: Please consult a mental health professional.";
        remarks.anxiety_class = "Extremely Severe";
    }

    // Stress remarks
    if (stress_score <= 14) {
        remarks.stress = "Stress: Keep up the good work.";
        remarks.stress_class = "Normal";
    } else if (stress_score <= 18) {
        remarks.stress = "Stress: Try to manage your stress.";
        remarks.stress_class = "Mild";
    } else if (stress_score <= 25) {
        remarks.stress = "Stress: Consider stress management strategies.";
        remarks.stress_class = "Moderate";
    } else if (stress_score <= 33) {
        remarks.stress = "Stress: It's important to address your stress.";
        remarks.stress_class = "Severe";
    } else {
        remarks.stress = "Stress: Please seek professional guidance.";
        remarks.stress_class = "Extremely Severe";
    }

    return remarks;
}



    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.give-remark-btn').forEach(button => {
            if (button.getAttribute('data-remark-status') === 'Completed') {
                button.disabled = true;
            }
        });
    });
</script>

</body>
</html>