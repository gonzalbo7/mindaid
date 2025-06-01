<?php
ob_start();
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

session_regenerate_id(true);

include_once 'student_header.php';
include_once 'Class/User.php';
include_once 'generate_result_id.php';

$u = new User();
$resultid = generateRESULTID();

$student_id = $_SESSION['student_id'] ?? 0;

if (!$student_id) {
    echo "<div class='alert alert-danger'>Invalid student information.</div>";
    exit();
}

$result = $u->getStudentTestStatus($student_id);
$testStatus = $result['test_status'] ?? 'Enabled';

$testDisabled = ($testStatus === 'Disabled');

$questions = $u->displayallquestions();
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
    // Store responses to session
    foreach ($_POST['responses'] as $category => $responses) {
        foreach ($responses as $question_id => $score) {
            $_SESSION['responses'][$category][$question_id] = (int)$score;
        }
    }

    if (isset($_POST['btnnext'])) {
        // Move to next category
        $nextCategory = $categoryIndex + 1;
        if ($nextCategory < count($categories)) {
            header("Location: ?category=" . $nextCategory);
            exit();
        }
    } elseif (isset($_POST['btnsubmit'])) {
        // Calculate scores from session data
        $depression_score = array_sum($_SESSION['responses']['Depression'] ?? []);
        $anxiety_score = array_sum($_SESSION['responses']['Anxiety'] ?? []);
        $stress_score = array_sum($_SESSION['responses']['Stress'] ?? []);

        $depression_class = classifyScore($depression_score, 'Depression');
        $anxiety_class = classifyScore($anxiety_score, 'Anxiety');
        $stress_class = classifyScore($stress_score, 'Stress');

        // Save results to the database
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
            $u->updateTestStatus($student_id, 'Disabled');
            echo "<script>
                alert('Test completed successfully! You can retake the test after 1 month.');
                window.location.href = 'student_test.php';
            </script>";
            exit;
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
<?php if ($testDisabled): ?>
    <div class="alert alert-warning">
        The test is currently disabled. You can retake the test after 1 month.
    </div>
<?php else: ?>
    <h4>Take the Test - <?= htmlspecialchars($currentCategory); ?></h4>

    <div class="progress-bar-container">
        <div class="progress-bar" style="width: <?= (($categoryIndex + 1) / count($categories)) * 100; ?>%;"></div>
    </div>

    <form method="POST">
        <?php if (!empty($questionsByCategory[$currentCategory])): ?>
            <?php foreach ($questionsByCategory[$currentCategory] as $question): ?>
                <div class="card">
                    <p><strong><?= htmlspecialchars($question['questions']); ?></strong></p>
                    <?php for ($j = 0; $j <= 3; $j++): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                name="responses[<?= $question['category']; ?>][<?= $question['id']; ?>]" 
                                value="<?= $j; ?>" required>
                            <label class="form-check-label">
                                <?= $j; ?> - <?= ($j == 0) ? 'Did not apply to me at all' : 
                                    (($j == 1) ? 'Applied to me to some degree, or some of the time.' : 
                                    (($j == 2) ? 'Applied to me a considerable degree, or a good part of the time.' : 
                                    'Applied to me very much, or most of the time.')); ?>
                            </label>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($categoryIndex < count($categories) - 1): ?>
                <button type="submit" name="btnnext" class="btn-submit">Next</button>
            <?php else: ?>
                <button type="submit" name="btnsubmit" class="btn-submit">Submit Final Result</button>
            <?php endif; ?>
        <?php else: ?>
            <p>No questions available.</p>
        <?php endif; ?>
    </form>
<?php endif; ?>


</div>

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
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
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
    </style>
</body>
</html>