<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "mindaid");

$allowedCourses = [
    "Bachelor of Science in Criminology",
    "Bachelor of Science in Management Accounting",
    "Bachelor of Public Administration",
    "Bachelor of Science in Computer Science"
];

$selectedCourse = $_GET['course'] ?? $allowedCourses[0];
if (!in_array($selectedCourse, $allowedCourses)) {
    $selectedCourse = $allowedCourses[0];
}

$counts = ["1st Year" => 0, "2nd Year" => 0, "3rd Year" => 0, "4th Year" => 0];

$sql = "SELECT year_level, COUNT(*) as total 
        FROM user_student
        WHERE course = '" . $conn->real_escape_string($selectedCourse) . "' 
        GROUP BY year_level";

$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $counts[$row['year_level']] = (int)$row['total'];
}

echo json_encode($counts);
?>