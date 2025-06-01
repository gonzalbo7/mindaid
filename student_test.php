<?php
ob_start();
date_default_timezone_set('Asia/Manila');
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Student') {
    header("Location: web_login.php");
    exit();
}

session_regenerate_id(true);

include_once 'student_header.php';
include_once 'Class/User.php';
include_once 'generate_result_id.php';

$u = new User();
$resultid = generateRESULTID();

$student_id = $_SESSION['student_id'] ?? 0;
$hasAcceptedTerms = $u->hasAcceptedTerms($student_id); // Correct variable name

// Accept terms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_terms'])) {
    $message = $u->updateTermsAcceptance($student_id);
    $_SESSION['has_accepted_terms'] = true;
}

if (!$student_id) {
    echo "<div class='alert alert-danger'>Invalid student information.</div>";
    exit();
}

if (isset($_POST['start_assessment'])) {
    $_SESSION['has_started_assessment'] = true;
}
$hasStarted = isset($_SESSION['has_started_assessment']) || isset($_POST['btnnext']) || isset($_POST['btnsubmit']);

$questions = $u->displayallquestions();

if (!$questions || $questions->num_rows == 0) {
    echo "<div class='alert alert-warning'>No test questions found. Please contact the administrator.</div>";
    exit();
}

$categories = ['Depression', 'Anxiety', 'Stress'];
$questionsByCategory = [];

if ($questions) {
    while ($row = $questions->fetch_assoc()) {
        $questionsByCategory[$row['category']][] = $row;
    }
}

$categoryIndex = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$currentCategory = $categories[$categoryIndex];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['responses']) && is_array($_POST['responses'])) {
        foreach ($_POST['responses'] as $category => $responses) {
            foreach ($responses as $question_id => $score) {
                $_SESSION['responses'][$category][$question_id] = (int)$score;
            }
        }
    }    

    if (isset($_POST['btnnext'])) {
        $nextCategory = $categoryIndex + 1;
        if ($nextCategory < count($categories)) {
            header("Location: ?category=" . $nextCategory);
            exit();
        }
    } elseif (isset($_POST['btnsubmit'])) {
        $depression_score = array_sum($_SESSION['responses']['Depression'] ?? []);
        $anxiety_score = array_sum($_SESSION['responses']['Anxiety'] ?? []);
        $stress_score = array_sum($_SESSION['responses']['Stress'] ?? []);

        $depression_class = classifyScore($depression_score, 'Depression');
        $anxiety_class = classifyScore($anxiety_score, 'Anxiety');
        $stress_class = classifyScore($stress_score, 'Stress');

        $saveResult = $u->student_save_test_result(
            $resultid,
            $student_id,
            $_SESSION['user']['first_name'],
            $_SESSION['user']['middle_name'],
            $_SESSION['user']['last_name'],
            $_SESSION['user']['email'],
            $_SESSION['user']['course'],
            $_SESSION['user']['year_level'],
            $depression_score,
            $depression_class,
            $anxiety_score,
            $anxiety_class,
            $stress_score,
            $stress_class,
            'Completed'
        );

        if ($saveResult) {
            $message = $u->updateTestStatus($student_id, 'Disabled');
            echo '
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    Swal.fire({
                        title: "Notification",
                        text: "' . $message . '",
                        icon: "success"
                    }).then(() => {
                        window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                    });
                </script>
            ';
        } else {
            echo "<div class='alert alert-danger'>Failed to save test results. Please try again.</div>";
        }
    }
}

function classifyScore($score, $category) {
    if ($category === 'Depression') {
        if ($score <= 9) return 'Normal';
        if ($score <= 13) return 'Mild';
        if ($score <= 20) return 'Moderate';
        if ($score <= 27) return 'Severe';
        return 'Extremely Severe';
    } elseif ($category === 'Anxiety') {
        if ($score <= 7) return 'Normal';
        if ($score <= 9) return 'Mild';
        if ($score <= 14) return 'Moderate';
        if ($score <= 19) return 'Severe';
        return 'Extremely Severe';
    } elseif ($category === 'Stress') {
        if ($score <= 14) return 'Normal';
        if ($score <= 18) return 'Mild';
        if ($score <= 25) return 'Moderate';
        if ($score <= 33) return 'Severe';
        return 'Extremely Severe';
    }
    return 'Unknown';
}

// Fetch the latest test date from the test_results table
$latestTestDate = $u->getLastTestDate($student_id);
$retakeDate = $latestTestDate ? date('F j, Y', strtotime($latestTestDate . ' +1 month')) : "N/A";

$result = $u->getStudentTestStatus($student_id);
$testStatus = $result['test_status'] ?? 'Disabled';
$testDisabled = $testStatus === 'Disabled';

if ($testStatus === 'Enabled') {
    $testDisabled = false;
} else {
    if ($latestTestDate) {
        $oneMonthLater = strtotime($latestTestDate . ' +1 month');
        $currentDate = time();
        if ($currentDate >= $oneMonthLater) {
            $testDisabled = false;
        }
    }
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f7fa;
        color: #333;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    .container {
        max-width: 800px;
        margin: auto;
        padding: 20px;
        margin-top: 20px;
    }
    h4 {
        font-size: 24px;
        color: #0D6EFD;
        font-weight: 700;
        text-align: center;
        margin-bottom: 20px;
    }
    .card {
        background: #f9fafb;
        padding: 15px;
        border: 1px solid #eaeaea;
        border-radius: 12px;
        margin-bottom: 15px;
    }
    .btn-submit {
        background-color: #0D6EFD;
        color: #fff;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        display: block;
        width: 100%;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }
    .btn-submit:hover {
        background-color: #025CE2;
    }
    .progress-bar-container {
        background: #eaeaea;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .progress-bar {
        height: 10px;
        background-color: #0D6EFD;
        width: calc(<?php echo (($categoryIndex + 1) / count($categories)) * 100; ?>%);
        transition: width 0.4s ease;
    }

    .title {
        font-size: 20px;
        font-weight: bold;
        color: #0072ff;
        cursor: pointer;
        display: inline-block;
        transition: text-decoration 0.3s;
    }
    .title:hover {
        text-decoration: underline;
    }
    
    .instructions-box {
        background: linear-gradient(135deg, #e3f2fd, #90caf9);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .instructions-box:hover {
        transform: translateY(-2px);
    }
    .questionnaire {
        animation: fadeIn 0.8s ease-in;
    }
    .form-check-label {
        margin-left: 10px;
    }
    .card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: none
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .progress-bar-container {
        margin-bottom: 20px;
    }
    .progress-bar {
        height: 20px;
        background-color: #42a5f5;
    }
    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(10px);}
        to {opacity: 1; transform: translateY(0);}
    }
    
    .modal-content {
    border-radius: 15px; /* Rounded corners for a softer look */
}

.modal-header {
    border-bottom: 2px solid #0D6EFD; /* Bottom border for separation */
}

.modal-body {
    font-size: 16px; /* Increase font size for better readability */
    line-height: 1.6; /* Improve line spacing */
    color: #333; /* Darker text for better contrast */
}

.modal-footer {
    border-top: 2px solid #0D6EFD; /* Top border for separation */
}

.btn-success {
    background-color: #28a745; /* Green color for the button */
    border: none; /* Remove border */
}

.btn-success:hover {
    background-color: #218838; /* Darker green on hover */
}


    </style>

</head>
<body>
    <div class="container">
        <?php if (!$hasAcceptedTerms): ?>
            <!-- TERMS AND CONDITIONS MODAL -->
            <div class="modal show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); font-family: 'Poppins', sans-serif;">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content shadow-lg rounded-4">
                        <div class="modal-header bg-primary text-white rounded-top-4">
                            <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Terms and Conditions</h5>
                        </div>
                        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                            <h6 class="font-weight-bold">Welcome to the <strong>DASS 42 Self-Assessment Test</strong></h6>
                            <p>Please carefully review the following <strong>Terms and Conditions</strong> regarding <strong>data privacy</strong>:</p>
                            <ol>
                                <li>The information you provide during this assessment is <strong>confidential</strong> and will be used <strong>solely</strong> for the purpose of monitoring your personal well-being.</li>
                                <li>The results generated from this assessment are not intended to serve as a formal diagnosis. We recommend consulting a qualified professional for any necessary evaluations.</li>
                                <li>You are advised not to manipulate your responses in any manner that could distort the accuracy of your results.</li>
                                <li>By clicking "<strong>I Agree</strong>," you consent to our data privacy policy, which outlines how your personal information and assessment data will be collected, stored, and utilized.</li>
                                <li>Your data will be handled in accordance with applicable data protection laws and regulations, ensuring your privacy and security.</li>
                            </ol>
                            <p>If you agree to the above <strong>terms and conditions</strong>, please click "<strong>I Agree</strong>" to continue.</p>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <form method="POST">
                                <button type="submit" name="accept_terms" class="btn btn-success px-4 py-2 rounded-pill shadow-sm">
                                    <i class="fas fa-check-circle me-2"></i>I Agree
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($testDisabled): ?>
            <div class="alert alert-warning text-center p-4" style="border-radius: 12px; background: linear-gradient(135deg, #FFF8E1, #FFE0B2); color: #5D4037;">
                <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color: #8D6E63;"></i>
                <h5><strong>Test Access Restricted</strong></h5>
                <p>You have already completed this test. You will be eligible to retake it on <strong><?= $retakeDate; ?></strong>.</p>
                <p>Additionally, if your guidance counselor enables the test before the retake date, you may gain access to it sooner. Thank you for your understanding.</p>
            </div>
        <?php else: ?>

        <?php if (!$hasStarted): ?>
            <!-- INSTRUCTION FIRST -->
            <div class="card shadow-lg p-4 mt-4 mx-auto" style="max-width: 750px; border-radius: 20px; background: linear-gradient(135deg, #E3F2FD, #BBDEFB); font-family: 'Poppins', sans-serif;">
                <div class="text-center mb-3">
                    <i class="fas fa-clipboard-list fa-3x text-primary mb-2"></i>
                    <h3 class="fw-bold">DASS 42 Assessment</h3>
                    <p class="text-muted">Depression, Anxiety, and Stress Self-Assessment</p>
                </div>
                <div class="mb-3">
                    <h5 class="fw-bold"><i class="fas fa-info-circle me-2 text-secondary"></i>Instructions:</h5>
                    <p>Please read each statement carefully and answer based on how much the statement applied to you over the past week.</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold text-secondary">Response Scale:</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent">0 - Did not apply to me at all</li>
                        <li class="list-group-item bg-transparent">1 - Applied to me to some degree, or some of the time</li>
                        <li class="list-group-item bg-transparent">2 - Applied to me a considerable degree, or a good part of the time</li>
                        <li class="list-group-item bg-transparent">3 - Applied to me very much, or most of the time</li>
                    </ul>
                </div>
                <div class="alert alert-warning mt-3" style="border-left: 6px solid #f0ad4e;">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    This test is a self-assessment tool and is not a substitute for professional diagnosis.
                </div>
                <form method="POST" class="text-center mt-4">
                    <input type="hidden" name="start_assessment" value="1">
                    <button class="btn btn-primary px-4 py-2 rounded-pill shadow-sm" type="submit">
                        <i class="fas fa-play-circle me-2"></i>Start Assessment
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- QUESTIONNAIRE SECTION -->
            <div class="questionnaire mt-4" style="font-family: 'Poppins', sans-serif;">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark">Take the Test: <span class="text-primary"><?= htmlspecialchars($currentCategory); ?></span></h3>
                </div>

                <div class="progress mb-4" style="height: 25px; border-radius: 30px; background-color: #e3f2fd;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: <?= (($categoryIndex + 1) / count($categories)) * 100; ?>%; font-weight: bold;">
                        <?= round((($categoryIndex + 1) / count($categories)) * 100); ?>%
                    </div>
                </div>

                <form method="POST">
                    <?php if (!empty($questionsByCategory[$currentCategory])): ?>
                        <?php foreach ($questionsByCategory[$currentCategory] as $index => $question): ?>
                            <div class="card mb-4 shadow-sm" style="border-left: 6px solid #2196F3; border-radius: 15px;">
                                <div class="card-body">
                                    <p class="fw-semibold mb-3 text-dark">
                                        <?= ($index + 1) ?>. <?= htmlspecialchars($question['questions']); ?>
                                    </p>
                                    <?php for ($j = 0; $j <= 3; $j++): ?>
                                        <div class="form-check mb-2 ps-4">
                                            <input class="form-check-input" type="radio"
                                                name="responses[<?= $question['category']; ?>][<?= $question['id']; ?>]"
                                                id="q<?= $question['id']; ?>_<?= $j; ?>"
                                                value="<?= $j; ?>" required>
                                            <label class="form-check-label text-muted" for="q<?= $question['id']; ?>_<?= $j; ?>">
                                                <strong><?= $j; ?></strong> — <?= match($j) {
                                                    0 => 'Did not apply to me at all',
                                                    1 => 'Applied to me to some degree, or some of the time',
                                                    2 => 'Applied to me a considerable degree, or a good part of the time',
                                                    3 => 'Applied to me very much, or most of the time',
                                                }; ?>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end">
                        <?php if ($categoryIndex < count($categories) - 1): ?>
                            <button type="submit" name="btnnext" class="btn btn-success rounded-pill px-4 py-2 shadow-sm">
                                Next <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="btnsubmit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                                Submit <i class="fas fa-check-circle ms-2"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

<script>
       // Ensure all functions and statements are properly closed
    function toggleInstructions() {
        var instructions = document.getElementById("instructions");
        if (instructions.style.display === "none" || instructions.style.display === "") {
           instructions.style.display = "block";
        } else {
            instructions.style.display = "none";
        }
    }
</script>

</body>
</html>

