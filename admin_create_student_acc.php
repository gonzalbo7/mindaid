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
include 'generate_student_id.php';

$u = new User();
$current_page = basename($_SERVER['PHP_SELF']);
$studentid = generateSTUDENTID();

function generatePassword($length = 7) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

require 'PHPMailer/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($email, $username, $password, $isReset = false) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reciokathrine@gmail.com';
        $mail->Password   = 'dlll rdyx aaaf xbjm'; // Use env or config in production
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('reciokathrine@gmail.com', 'MindAid Admin');
        $mail->addAddress($email);

        $mail->isHTML(true);
        
        // Dynamic subject and body based on purpose
        if ($isReset) {
            $mail->Subject = 'Your Student Account Credentials';
            $mail->Body    = "
                Hello,<br><br>Your student account has been created. Here are your login details:<br><br>
                Your password has been successfully reset. Here are your updated login credentials:<br><br>
                <strong>Username:</strong> $username<br>
                <strong>New Password:</strong> $password<br><br>
                Please log in to your account via the following link: <a href='https://mindaid.site/index.php'>MindAid Login</a>. Once logged in, we recommend updating your password for security purposes.<br><br>
                Regards,<br>
                <strong>MindAid Admin</strong>
            ";
        } else {
            $mail->Subject = 'Your Student Account Credentials';
            $mail->Body    = "
               Hello,<br><br>Your student account has been created. Here are your login details:<br><br>
                <strong>Username:</strong> $username<br>
                <strong>Password:</strong> $password<br><br>
                Please log in to your account via the following link: <a href='https://mindaid.site/index.php'>MindAid Login</a>. Once logged in, we recommend updating your password for security purposes.<br><br>
                Regards,<br>
                <strong>MindAid Admin</strong>
            ";
        }

        $mail->send();

        return $isReset 
            ? "Password updated successfully. New credentials sent to student email." 
            : "Student account created successfully. Login credentials sent to student email.";
    } catch (Exception $e) {
        return "Email could not be sent. Error: " . $mail->ErrorInfo;
    }
}

// **Fixed Create Student Account Function with Prepared Statements**
if (isset($_POST['btncreate'])) {
    $firstName   = $_POST['firstName'];
    $middleName  = $_POST['middleName'];
    $lastName    = $_POST['lastName'];
    $contact     = $_POST['contact'];
    $email       = $_POST['email'];
    $course      = $_POST['course'];
    $year_level  = $_POST['year_level'];
    $username    = $studentid;
    $password    = generatePassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $message = $u->create_student_acc($studentid, $firstName, $middleName, $lastName, $contact, $email, $course, $year_level, $username, $hashedPassword);
    $emailMessage = sendEmail($email, $username, $password);
    echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $message .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
}


if (isset($_POST['btnupdate'])) {
    // Note: the name of the hidden input in the update modal is now "student_id"
    $studentid  = $_POST['student_id'];
    $firstName  = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName   = $_POST['lastName'];
    $contact    = $_POST['contact'];
    $email      = $_POST['email'];
    $course     = $_POST['course'];
    $year_level = $_POST['year_level'];
    $username    = $studentid;
    $password    = generatePassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $message = $u->update_student_acc($studentid, $firstName, $middleName, $lastName, $contact, $email, $course, $year_level, $username, $hashedPassword);
    $message = sendEmail($email, $username, $password, true);

    echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $message .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
}

if (isset($_POST['btnblock'])) {
    $student_id = $_POST['student_id'];
    $message = $u->blocked_student_acc($student_id);
    
    echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $message .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
}

//RECOVER STUDENT ACCOUNT AND TEST RESULTS
if (isset($_POST['btnrecover'])) {
    if (isset($_POST['recover_student_id'])) {
        $student_id = $_POST['recover_student_id'];
        
        $message = $u->recover_student_account($student_id);
        
        echo'
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: "Notification",
                text: "'. $message .'",
                icon: "success"
            }).then(() => {
                window.location.href = window.location.href.split("?")[0] + "?refresh=1";
            });
        </script>
    ';
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .content {
            padding: 20px;
            width:75%;
            margin-left:300px;
        }
        .container-card, .table-container {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .content{
                margin-left:100px;
                width:80%;
            }
            
            .table td button {
                width: 50%;
                padding: 6px 10px;
                margin-top:10px;
                margin-left:8px;
            }
        }

        /* Loading Overlay */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.8);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        /* Spinner Animation */
        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #007bff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 15px;
            font-size: 18px;
            color: #007bff;
            font-weight: bold;
        }

    </style>
</head>
<body>
<div class="d-flex">
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Creating Student Account. Please wait...</div>
    </div>

    <div class="content">
        <div class="container-card mb-4">
            <h4>Create Student Account</h4>
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" name="firstName" id="first_name" class="form-control" placeholder="First Name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="text" name="middleName" id="middle_name" class="form-control" placeholder="Middle Name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="text" name="lastName" id="last_name" class="form-control" placeholder="Last Name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" name="contact" id="contact_number" class="form-control" placeholder="Contact Number" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" required>
                    </div>
                </div>
                <div class="row">
                        <div class="col-md-4 mb-3">
                            <select class="form-select" id="course" name="course" required>
                                <option value="" disabled selected> Select Course </option>
                                <option> Bachelor of Science in Criminology </option>
                                <option> Bachelor of Science in Management Accounting </option>
                                <option> Bachelor of Public Administration </option>
                                <option> Bachelor of Science in Computer Science </option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="" disabled selected>Select Year Level</option>
                                <option> 1st Year </option>
                                <option> 2nd Year </option>
                                <option> 3rd Year </option>
                                <option> 4th Year </option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="btncreate" class="btn btn-primary">Create Account</button>
            </form>
        </div>

        <div class="table-responsive text-nowrap">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>List of Student</h4>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deletedAccountsModal">
                    View Deleted Accounts
                </button>
            </div>
            <table class="table table-hover" id="studentTable">
                <thead>
                    <tr class="text-nowrap">
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th> Action </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $data = $u->display_student_acc();
                    if ($data) {
                        while ($row = $data->fetch_assoc()) {
                            echo '<tr class="bg-white">'
                                . '<td>' . htmlspecialchars($row['student_id']) . '</td>'
                                . '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) . '</td>'
                                . '<td>' . htmlspecialchars($row['contact_number']) . '</td>'
                                . '<td>' . htmlspecialchars($row['email']) . '</td>'
                                . '<td>' . htmlspecialchars($row['course']) . '</td>'
                                . '<td>' . htmlspecialchars($row['year_level']) . '</td>'
                                . '<td>'
                                
                                . '<button type="button" class="btn btn-warning btn-sm ms-4" data-bs-toggle="modal" data-bs-target="#UpdateModal" onclick="displaystudent(&quot;'.$row['student_id'].'&quot;, &quot;'.$row['first_name'].'&quot;, &quot;'.$row['middle_name'].'&quot;, &quot;'.$row['last_name'].'&quot;, &quot;'.$row['contact_number'].'&quot;, &quot;'.$row['email'].'&quot;, &quot;'.$row['course'].'&quot;, &quot;'.$row['year_level'].'&quot;)">'
                                . '<i class="fas fa-edit"></i>'
                                . '</button>'

                                // Block/Delete Button
                                . '<form method="POST" style="display:inline;">'
                                . '<input type="hidden" name="student_id" value="' . htmlspecialchars($row['student_id']) . '">'
                                . '<button type="submit" class="btn btn-danger btn-sm ms-4 mt-2" name="btnblock" onclick="return confirm(\'Are you sure you want to move this account to deleted accounts?\')">'
                                . '<i class="fas fa-ban"></i>'
                                . '</button>'
                                . '</form>'

                                . '</td>'
                                . '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7">No Counselor Information Found!</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Modal for Update Student Account Information -->
        <div class="modal fade" id="UpdateModal" tabindex="-1" aria-labelledby="UpdateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Student Account Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <!-- Display Student ID without input field -->
                            <div class="mb-3">
                                <label class="form-label">Student ID:</label>
                                <span id="display_student_id" class="fw-bold"></span>
                                <input type="hidden" id="upd_student_id" name="student_id">
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label> First Name </label>
                                    <input type="text" name="firstName" id="upd_first_name" class="form-control" placeholder="First Name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label> Middle Name </label>
                                    <input type="text" name="middleName" id="upd_middle_name" class="form-control" placeholder="Middle Name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label> Last Name </label>
                                    <input type="text" name="lastName" id="upd_last_name" class="form-control" placeholder="Last Name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label> Contact Number </label>
                                    <input type="text" name="contact" id="upd_contact_number" class="form-control" placeholder="Contact Number" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label> Email </label>
                                    <input type="email" name="email" id="upd_email" class="form-control" placeholder="Email Address" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label> Select Course </label>
                                    <select class="form-select" id="upd_course" name="course" required>
                                        <option value="" disabled selected> Select Course </option>
                                        <option> Bachelor of Science in Criminology </option>
                                        <option> Bachelor of Science in Management Accounting </option>
                                        <option> Bachelor of Public Administration </option>
                                        <option> Bachelor of Science in Computer Science </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label> Select Year Level </label>
                                    <select class="form-select" id="upd_year_level" name="year_level" required>
                                        <option value="" disabled selected> Select Year Level </option>
                                        <option> 1st Year </option>
                                        <option> 2nd Year </option>
                                        <option> 3rd Year </option>
                                        <option> 4th Year </option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="btnupdate" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Update Modal Code -->

       <!-- Recovered Accounts Modal -->
        <div class="modal fade" id="deletedAccountsModal" tabindex="-1" aria-labelledby="deletedAccountsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deletedAccountsModalLabel">Deleted Student Accounts</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Search Bar -->
                         <div class="col-md-4">
                            <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search....">
                        </div>

                        <?php
                        $deletedAccounts = $u->get_deleted_student_accounts(); // Only returns students
                        if ($deletedAccounts && $deletedAccounts->num_rows > 0) {
                            echo '<div class="table-responsive"><table class="table table-bordered">';
                            echo '<thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>';
                            while ($student = $deletedAccounts->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($student['user_id']) . '</td>';
                                echo '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($student['email']) . '</td>';
                                echo '<td>
                                        <form method="POST" class="recover-form" style="display:inline;">
                                            <input type="hidden" name="recover_student_id" value="' . htmlspecialchars($student['user_id']) . '">
                                            <button type="submit" name="btnrecover" class="btn btn-success btn-sm">Recover</button>
                                        </form>
                                    </td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>No deleted student accounts found.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Deleted Accounts End Modal -->

    </div>
</div>

<script>
    $(document).ready(function() {
        $('#studentTable').DataTable({
            "order": [] // No initial sorting
        });
    });

    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
    
    document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function () {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#deletedAccountsModal tbody tr');

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchTerm) ? '' : 'none';
        });
    });
});

    function displaystudent(student_id, first_name, middle_name, last_name, contact_number, email, course, year_level) {
        // Populate the update modal with the row data.
        document.getElementById("display_student_id").innerText = student_id;
        document.getElementById("upd_student_id").value = student_id;
        document.getElementById("upd_first_name").value = first_name;
        document.getElementById("upd_middle_name").value = middle_name;
        document.getElementById("upd_last_name").value = last_name;
        document.getElementById("upd_contact_number").value = contact_number;
        document.getElementById("upd_email").value = email;
        document.getElementById("upd_course").value = course;
        document.getElementById("upd_year_level").value = year_level;
    }
    
    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    });
</script>

</body>
</html>
