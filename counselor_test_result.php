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
require 'fpdf186/fpdf.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MindAidPDF extends FPDF
{
    // Page header
    function Header()
    {
        // Blue border - thick
        $this->SetDrawColor(0, 102, 204); // Blue
        $this->SetLineWidth(5);
        $this->Rect(5, 5, $this->w - 10, $this->h - 10);

        // Yellow header background
        $this->SetFillColor(255, 204, 0); // Yellow
        $this->Rect(5, 5, $this->w - 10, 25, 'F'); // Header background

        // Header text MINDAID
        $this->SetFont('Arial', 'B', 28);
        $this->SetTextColor(255, 255, 255); // White
        $this->Cell(0, 25, 'MINDAID', 0, 1, 'C');

        // Optional subtitle line
        $this->SetFont('Arial', 'I', 14);
        $this->Cell(0, 10, 'Mental Health Assessment Results', 0, 1, 'C');

        // Reset Y for below header content
        $this->SetY(35);
        $this->SetTextColor(0, 0, 0);
    }

    // Page footer - can add if desired
    function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

function sendRemarkEmail($email, $name, $remark, $result_id, $user) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reciokathrine@gmail.com';
        $mail->Password   = 'dlll rdyx aaaf xbjm'; // Note: Never expose credentials in production
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('reciokathrine@gmail.com', 'MindAid Counselor');
        $mail->addAddress($email, $name);

        $user = new User();
        $result = $user->getTestResultById($result_id);

        $dateTaken = !empty($result['date_taken']) ? date('F d, Y h:i A', strtotime($result['date_taken'])) : "Unknown";

        // ✅ Generate PDF using MindAidPDF class
        $pdf = new MindAidPDF();
        $pdf->AddPage();

        // Main title below header
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(106, 27, 154); // Purple
        $pdf->Cell(0, 12, 'MindAid Assessment Result', 0, 1, 'C');
        $pdf->Ln(8);

        // User info
        $pdf->SetFont('Arial', '', 13);
        $pdf->SetTextColor(0, 0, 0); // Black
        $pdf->Cell(0, 10, "Name: $name", 0, 1);
        $pdf->Cell(0, 10, "Date Taken: $dateTaken", 0, 1);
        $pdf->Ln(5);

        // Test results heading
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Test Results:', 0, 1);
        $pdf->SetFont('Arial', '', 13);

        $pdf->Cell(0, 10, "Depression: {$result['depression_score']} ({$result['depression_class']})", 0, 1);
        $pdf->Cell(0, 10, "Anxiety: {$result['anxiety_score']} ({$result['anxiety_class']})", 0, 1);
        $pdf->Cell(0, 10, "Stress: {$result['stress_score']} ({$result['stress_class']})", 0, 1);
        $pdf->Ln(8);

        // Remark heading
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Remark:', 0, 1);
        $pdf->SetFont('Arial', '', 13);
        $pdf->MultiCell(0, 10, $remark);
        $pdf->Ln(10);

        // DASS-42 Reference Table Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 12, 'DASS-42 Interpretation Guide for Scores Table', 0, 1, 'C');
        $pdf->Ln(3);

        // Table Headers with blue fill
        $pdf->SetFillColor(0, 102, 204); // Blue
        $pdf->SetTextColor(255, 255, 255); // White
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 12, 'Severity', 1, 0, 'C', true);
        $pdf->Cell(45, 12, 'Depression', 1, 0, 'C', true);
        $pdf->Cell(45, 12, 'Anxiety', 1, 0, 'C', true);
        $pdf->Cell(50, 12, 'Stress', 1, 1, 'C', true);

        // Table Rows - alternating fill colors (white and light yellow)
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(0, 0, 0);

        $rows = [
            ['Normal', '0 to 9', '0 to 7', '0 to 14'],
            ['Mild', '10 to 13', '8 to 9', '15 to 18'],
            ['Moderate', '14 to 20', '10 to 14', '19 to 25'],
            ['Severe', '21 to 27', '15 to 19', '26 to 33'],
            ['Extremely Severe', '28+', '20+', '34+']
        ];

        $fill = false;
        foreach ($rows as $row) {
            $pdf->SetFillColor(255, 255, 224); // Light yellow
            $pdf->Cell(50, 10, $row[0], 1, 0, 'C', $fill);
            $pdf->Cell(45, 10, $row[1], 1, 0, 'C', $fill);
            $pdf->Cell(45, 10, $row[2], 1, 0, 'C', $fill);
            $pdf->Cell(50, 10, $row[3], 1, 1, 'C', $fill);
            $fill = !$fill;
        }
        
$pdf->AddPage();

// Add title
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Visual Representation of Severity Levels', 0, 1, 'C');
$pdf->Ln(2);

// Define severity levels and Y-axis
$levels = ['Extremely Severe', 'Severe', 'Moderate', 'Mild', 'Normal'];
$levelY = array_combine($levels, [60, 70, 80, 90, 100]);

$barHeightMax = 50;
$barWidth = 20;
$xBase = 70;

// Draw Y-axis grid and labels
$pdf->SetFont('Arial', '', 10);
foreach ($levelY as $label => $y) {
    $pdf->SetXY(25, $y - 4);
    $pdf->Cell(30, 8, $label, 0, 0, 'R');
    $pdf->Line(60, $y, 160, $y);
}

// Helper function
function getBarY($class, $levelY) {
    return $levelY[$class] ?? $levelY['Normal'];
}

// Severity-to-color map
$severityColors = [
    'Normal' => [144, 238, 144],          // Green
    'Mild' => [0, 102, 204],          // Blue
    'Moderate' => [255, 204, 0],      // Yellow
    'Severe' => [255, 128, 0],        // Orange
    'Extremely Severe' => [204, 0, 0] // Red
];

// Metrics and drawing
$metrics = [
    ['label' => 'Depression', 'class' => $result['depression_class']],
    ['label' => 'Anxiety', 'class' => $result['anxiety_class']],
    ['label' => 'Stress', 'class' => $result['stress_class']],
];

$xPos = $xBase;
$interpretationText = "";

foreach ($metrics as $metric) {
    $label = $metric['label'];
    $class = $metric['class'];
    list($r, $g, $b) = $severityColors[$class] ?? [0, 0, 0];
    $yTop = getBarY($class, $levelY);
    $barHeight = 100 - $yTop;

    $pdf->SetFillColor($r, $g, $b);
    $pdf->Rect($xPos, $yTop, $barWidth, $barHeight, 'F');

    $pdf->SetXY($xPos - 2, 105);
    $pdf->Cell($barWidth + 4, 5, $label, 0, 0, 'C');

    $interpretationText .= "- Your **$label** level is classified as **$class**.\n";

    $xPos += $barWidth + 25;
}

// 🧠 Graph Interpretation Paragraph
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetTextColor(106, 27, 154);
$pdf->Cell(0, 10, 'Interpretation of the Graph', 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);
$finalInterpretation = <<<EOT
This visual graph presents the severity classification of Depression, Anxiety, and Stress based on your DASS-42 assessment.

$interpretationText
Severity levels are represented by colored bars:
- Green: Normal
- Blue: Mild
- Yellow: Moderate
- Orange: Severe
- Red: Extremely Severe
The height of each bar corresponds to the intensity of symptoms. Taller bars towards the top of the chart indicate higher levels of psychological distress.
Please consider your current psychological state and reach out to a mental health professional if your levels fall under Severe or Extremely Severe categories.
EOT;

$pdf->MultiCell(0, 8, $finalInterpretation);



// Add a new page for scoring and interpretation
$pdf->AddPage();

// Define box dimensions
$margin = 10;
$boxX = $margin;
$boxY = 42;
$boxWidth = 190;  // A4 width - 2 * margin
$boxHeight = 230; // Approximate height

// Draw box with black border
$pdf->SetDrawColor(0, 0, 0);
$pdf->Rect($boxX, $boxY, $boxWidth, $boxHeight);

// Title inside the box
$pdf->SetXY($boxX, $boxY + 5);
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetTextColor(106, 27, 154);
$pdf->Cell($boxWidth, 10, 'Scoring and Interpretation Information', 0, 1, 'C');

// Reset for paragraph content
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($boxX + 5, $boxY + 20); // Indent inside box

$interpretation = <<<EOD
Scores are presented as a total score (between 0 and 126) and a score for the three subscales (between 0 and 42). In addition, percentiles are computed based on a community sample (Henry & Crawford, 2005; Lovibond & Lovibond, 1995).

Scores for each subscale are categorised into five severity ranges: normal, mild, moderate, severe, and extremely severe. The severity labels describe the full range of scores in the population. For example, ‘mild’ means that the person is above the population mean but probably still below the typical severity of someone seeking help. The severity levels are determined by the DASS manual (Lovibond & Lovibond, 1995).

Each of the three DASS-42 scales contains 7 items:
- Depression: Symptoms such as dysphoria, hopelessness, devaluation of life, self-deprecation, lack of interest/involvement, anhedonia, and inertia.
- Anxiety: Symptoms such as physiological arousal and fear components of anxiety. It assesses autonomic arousal typical of anxiety, such as trembling, sweating, feelings of panic, and the fear of losing control.
- Stress: Chronic symptoms of non-specific arousal. It assesses difficulty relaxing, nervous arousal, irritability, and impatience.

The primary difference between the stress and anxiety subscales lies in anxiety's focus on acute responses and stress's focus on chronic tension. Anxiety is about the immediate physiological response to perceived threats, while stress encompasses a broader, sustained response to ongoing demands that exceed an individual's resources.

Analysis of Results:
On first administration, a bar graph is presented showing the percentiles for general psychological distress (the total score, labelled as 'Total Distress') and the three subscales. When administered more than once, two graphs are produced showing change in symptoms over time. The DASS-42 total score is plotted to show change over time, emphasizing the visual representation of change for extremely severe levels of distress. The maximum and minimum values on the y-axis of this plot will change depending upon the scores, enhancing the practitioner's ability to observe change.

The subscale percentiles are also graphed over time, indicating the level of symptoms.
EOD;

// Print paragraph text inside the box
$pdf->MultiCell($boxWidth - 10, 6, $interpretation);



        // ✅ Save PDF temporarily
        $pdfFilePath = sys_get_temp_dir() . "/MindAid_Result_{$result_id}.pdf";
        $pdf->Output('F', $pdfFilePath);

        // ✅ Email Body
        $mail->isHTML(true);
        $mail->Subject = 'MindAid Assessment Test Results and Remark';
        $mail->Body = "
            <h3 style='color: #6a1b9a;'>Hello, $name</h3>
            <p>Please find attached your test result and counselor's remark in PDF format.</p>
            <p>Thank you,<br><strong>MindAid Counselor</strong></p>
        ";

        // ✅ Attach PDF
        $mail->addAttachment($pdfFilePath, "MindAid_Results_$name.pdf");

        // ✅ Send Email
        $mail->send();

        // ✅ Update database
        $date_sent = date('Y-m-d H:i:s');
        $remark_by = $_SESSION['user'] ?? 'Counselor'; // fallback
        $user->updateRemark($result_id, $remark, $date_sent, $remark_by);

        // ✅ Clean up PDF file
        unlink($pdfFilePath);

        return true;
    } catch (Exception $e) {
        return "Error sending email: {$mail->ErrorInfo}";
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
                    <div class="mb-3">
                        <label for="customRemark" class="form-label">Custom Remark:</label>
                        <textarea class="form-control" name="customRemark" id="customRemark" rows="3"></textarea>
                    </div>
&nbsp;
&nbsp;

                    <!-- DASS-42 Reference Table -->
                    <h6>DASS-42 Classification Reference Table</h6>
                    <table class="table table-bordered">
                        <thead>
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
&nbsp;
&nbsp;

                    <!-- DASS-42 Reference Table -->
                    <h6>DASS-42 Classification Reference Table</h6>
                    <table class="table table-bordered">
                        <thead>
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