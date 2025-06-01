<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

include_once 'student_header.php';
include_once 'Class/User.php';

$u = new User();

$studentId = $_SESSION['student_id'] ?? '';
$results = $u->getResultsByStudentId($studentId);
$latest = $results[0] ?? null;

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- ADD THIS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
<div class="container py-4">

    <?php if ($latest): ?>
    <!-- Latest Result Card -->
    <div class="card shadow mb-4 border-start border-primary border-4">
        <div class="card-body">
            <h5 class="card-title text-primary mb-3">📌 Latest Test Result</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= $latest['first_name'] . ' ' . $latest['last_name'] ?></p>
                    <p><strong>Course:</strong> <?= $latest['course'] ?></p>
                    <p><strong>Year Level:</strong> <?= $latest['year_level'] ?></p>
                    <p><strong>Test Date:</strong> <?= $latest['date_taken'] ?? 'N/A' ?></p>
                    <p><strong>Depression:</strong> <?= $latest['depression_score'] ?> (<?= $latest['depression_class'] ?>)</p>
                    <p><strong>Anxiety:</strong> <?= $latest['anxiety_score'] ?> (<?= $latest['anxiety_class'] ?>)</p>
                    <p><strong>Stress:</strong> <?= $latest['stress_score'] ?> (<?= $latest['stress_class'] ?>)</p>
                    <p><strong>Remarks:</strong> <?= $latest['remarks'] ?? 'No remarks' ?></p>
                    <p><strong>Remarked By:</strong> <?= $latest['remark_by'] ?? 'N/A' ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">No test results found.</div>
    <?php endif; ?>


    <!-- Past Results -->
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-3">🗂️ Past Results</h5>
            <div class="row" style="max-height: 500px; overflow-y: auto;">
                <?php foreach (array_slice($results, 1) as $r): ?>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h6><?= $r['course'] ?> (<?= $r['year_level'] ?>)</h6>
                            <p class="text-muted mb-2"><?= $r['first_name'] ?> <?= $r['last_name'] ?> | <?= $r['date_taken'] ?? 'No date' ?></p>
                            <ul class="mb-0">
                                <li><strong>Depression:</strong> <?= $r['depression_score'] ?> (<?= $r['depression_class'] ?>)</li>
                                <li><strong>Anxiety:</strong> <?= $r['anxiety_score'] ?> (<?= $r['anxiety_class'] ?>)</li>
                                <li><strong>Stress:</strong> <?= $r['stress_score'] ?> (<?= $r['stress_class'] ?>)</li>
                                <li><strong>Remarks:</strong> <?= $r['remarks'] ?? 'No remarks' ?></li>
                                <li><strong>Remarked By:</strong> <?= $r['remark_by'] ?? 'N/A' ?></li>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


</div>

</body>
</html>