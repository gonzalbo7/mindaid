<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Guidance Counselor') {
    header("Location: index.php");
    exit();
}

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

include_once 'Class/User.php';
include_once 'counselor_sidebar.php';
include_once 'generate_question_id.php';

$u = new User();
$current_page = basename($_SERVER['PHP_SELF']);
$questionid = generateQUESTIONID();

if (isset($_POST['btnadd'])) {
    // Count current number of questions
    $countResult = $u->count_questions();
    $count = 0;
    if ($countResult) {
        $row = $countResult->fetch_assoc();
        $count = (int)$row['total'];
    }

    if ($count >= 42) {
        echo '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Notice",
                    text: "You have reached the maximum limit of 42 questions for the DASS-42 questionnaire. Adding additional questions is not permitted.",
                    icon: "warning"
                }).then(() => {
                    window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                });
            </script>
        ';
    } else {
        $question_text = $_POST['question_text'];
        $category = $_POST['category'];
        $result = $u->save_questionnaire($questionid, $question_text, $category);
        echo '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Notification",
                    text: "' . $result . '",
                    icon: "success"
                }).then(() => {
                    window.location.href = window.location.href.split("?")[0] + "?refresh=1";
                });
            </script>
        ';
    }
}


if(isset($_POST['btnupdate'])){
    $questionid = $_POST['questionid'];
    $question_text = $_POST['question_text'];
    $category = $_POST['category'];
    $result = $u->updatequestions($questionid, $question_text, $category);
    echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $result .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
}

if(isset($_POST['btndelete'])){
    $questionid = $_POST['questionid'];
    $result = $u->deletequestion($questionid);
    echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $result .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .search-box { 
            margin-bottom: 15px; 
            width: 30%;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
            transition: margin-left 0.3s;
            width: calc(100% - 270px);
        }
        .toggle-btn {
            display: none;
            position: absolute;
            top: 15px;
            left: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        .container-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table-container {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }

    </style>
</head>
<body>

<div class="d-flex">
    <div class="content w-100 p-4">
        <h4 class="mb-4">Manage Assessment Questionnaire</h4>
        <div class="container-card p-4 mb-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question</label>
                            <textarea type="text" class="form-control" id="question_text" name="question_text" required> </textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Depression">Depression</option>
                                <option value="Anxiety">Anxiety</option>
                                <option value="Stress">Stress</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" name="btnadd" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <table class = "table table-hover" id="questionnaireTable">
            <thead>
                <tr class = "p-5">
                    <th>Questions</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    include_once 'Class/user.php';
                    $u = new User();
                    $data = $u->displayallquestions();
                    if($data){
                        while($row = $data->fetch_assoc()){
                            echo'
                                <tr class = "bg-white">
                                    <td>'.htmlspecialchars($row['questions']).'</td>
                                    <td>'.htmlspecialchars($row['category']).'</td>
                                    <td>
                                        <button type="button" class="bg-warning form-control" data-bs-toggle="modal" data-bs-target="#UpdateModal" onclick="displayquestions(&quot;'.$row['question_id'].'&quot;,&quot;'.$row['questions'].'&quot;,&quot;'.$row['category'].'&quot;)" >
                                        <i class="fas fa-pen-to-square" style="color: white;"></i> </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="questionid" value="'.$row['question_id'].'">
                                            <button type="submit" name="btndelete" class="bg-danger form-control" 
                                                onclick="return confirm(\'Are you sure you want to delete this question?\')">
                                                <i class="fas fa-trash" style="color: white;"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            ';
                        }
                    }else{
                         echo '<tr><td colspan="7">No Questions Found!</td></tr>';
                    }
                ?>
            </tbody>
        </table>

        <!-- Modal for Update Questionnaire -->
        <div class="modal fade" id="UpdateModal" tabindex="-1" aria-labelledby="UpdateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="updateForm">
                            <!-- Display Question ID without input field -->
                            <div class="mb-3">
                                <label class="form-label">Question ID:</label>
                                <span id="display_question_id" class="fw-bold"></span>
                                <input type="hidden" id="question_id" name="questionid">
                            </div>
                            
                            <div class="mb-3">
                                <label for="question_text" class="form-label">Question</label>
                                <textarea class="form-control" id="questions" name="question_text" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled selected> Select Category </option>
                                    <option value="Depression">Depression</option>
                                    <option value="Anxiety">Anxiety</option>
                                    <option value="Stress">Stress</option>
                                </select>
                            </div>

                            <button type="submit" name="btnupdate" class="btn btn-primary">Update Question</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Update Modal Code -->
    </div>
</div>

<script>
function displayquestions(question_id, questions, category) {
    // Use the correct variable "questionid" to display the question ID.
    document.getElementById("display_question_id").innerText = question_id;
    document.getElementById("question_id").value = question_id;
    document.getElementById("questions").value = questions;
    document.getElementById("category").value = category;
}

$(document).ready(function() {
    $('#questionnaireTable').DataTable({
        "order": [] // No initial sorting
    });
});
</script>

</body>
</html>