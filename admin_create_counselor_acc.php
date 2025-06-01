<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

include_once 'admin_sidebar.php';
include_once 'Class/User.php';
include 'generate_counselor_id.php';

$u = new User();
$current_page = basename($_SERVER['PHP_SELF']);
$counselorid = generateCOUNSELORID();

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
            $mail->Subject = 'Your Guidance Counselor Account Credentials';
            $mail->Body    = "
                Hello Counselor,<br><br>
                Your password has been successfully reset. Here are your updated login credentials:<br><br>
                <strong>Username:</strong> $username<br>
                <strong>New Password:</strong> $password<br><br>
                Please log in to your account via the following link: <a href='https://mindaid.site/index.php'>MindAid Login</a>. Once logged in, we recommend updating your password for security purposes.<br><br>
                Regards,<br>
                <strong>MindAid Admin</strong>
            ";
        } else {
            $mail->Subject = 'Your Guidance Counselor Account Credentials';
            $mail->Body    = "
                Hello Counselor,<br><br>
                Your Guidance Counselor account has been created. Below are your login credentials:<br><br>
                <strong>Username:</strong> $username<br>
                <strong>Password:</strong> $password<br><br>
                Please log in to your account via the following link: <a href='https://mindaid.site/index.php'>MindAid Login</a>. Once logged in, we recommend updating your password for security purposes.<br><br>
                Regards,<br>
                <strong>MindAid Admin</strong>
            ";
        }

        $mail->send();

        return $isReset 
            ? "Password updated successfully. New credentials sent to counselor email." 
            : "Guidance Counselor account created successfully. Login credentials sent to counselor email.";
    } catch (Exception $e) {
        return "Email could not be sent. Error: " . $mail->ErrorInfo;
    }
}


if (isset($_POST['btncreate'])) {
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $username = $counselorid;
    $password = generatePassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $message = $u->create_counselor_acc($counselorid, $firstName, $middleName, $lastName, $contact, $email, $username, $hashedPassword);
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
    // Note: the name of the hidden input in the update modal is now "counselor_id"
    $counselorid  = $_POST['counselor_id'];
    $firstName  = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName   = $_POST['lastName'];
    $contact    = $_POST['contact'];
    $email      = $_POST['email'];
    $username = $counselorid;
    $password = generatePassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $message = $u->update_counselor_acc($counselorid, $firstName, $middleName, $lastName, $contact, $email, $username, $hashedPassword);
    
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
    $counselor_id = $_POST['counselor_id'];
    $message = $u->blocked_counselor_acc($counselor_id);
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
            margin-bottom: 20px;
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
    </style>
</head>
<body>
<div class="d-flex">
    <div class="content">
        <div class="container-card mb-4">
            <h4>Create Counselor Account</h4>
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
                <button type="submit" name="btncreate" class="btn btn-primary">Create Account</button>
            </form>
        </div>
        
        <div class="table-responsive text-nowrap">
            <h4> List of Counselor </h4>
            <table class="table table-hover" id="counselorTable">
                <thead>
                    <tr class="text-nowrap">
                        <th>Counselor ID</th>
                        <th>Full Name</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th> Action </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $data = $u->display_counselor_acc();
                    if ($data) {
                        while ($row = $data->fetch_assoc()) {
                            echo '<tr class="bg-white">'
                                . '<td>' . htmlspecialchars($row['counselor_id']) . '</td>'
                                . '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) . '</td>'
                                . '<td>' . htmlspecialchars($row['contact_number']) . '</td>'
                                . '<td>' . htmlspecialchars($row['email']) . '</td>'
                                . '<td>'
                                
                                . '<button type="button" class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#UpdateModal" onclick="displaycounselor(&quot;'.$row['counselor_id'].'&quot;, &quot;'.$row['first_name'].'&quot;, &quot;'.$row['middle_name'].'&quot;, &quot;'.$row['last_name'].'&quot;, &quot;'.$row['contact_number'].'&quot;, &quot;'.$row['email'].'&quot;)">'
                                . '<i class="fas fa-edit"></i>'
                                . '</button>'

                                // Block/Delete Button
                                . '<form method="POST" style="display:inline;">'
                                . '<input type="hidden" name="counselor_id" value="' . htmlspecialchars($row['counselor_id']) . '">'
                                . '<button type="submit" class="btn btn-danger btn-sm ms-2" name="btnblock" onclick="return confirm(\'Are you sure you want to block this account and add it to the blocked accounts list?\')">'
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
    
    <!-- Modal for Update Counselor Account Information -->
    <div class="modal fade" id="UpdateModal" tabindex="-1" aria-labelledby="UpdateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Counselor Account Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <!-- Display Counselor ID without input field -->
                            <div class="mb-3">
                                <label class="form-label">Counselor ID:</label>
                                <span id="display_counselor_id" class="fw-bold"></span>
                                <input type="hidden" id="upd_counselor_id" name="counselor_id">
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
                            <button type="submit" name="btnupdate" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Update Modal Code -->
    </div>
</div>

<script>

$(document).ready(function() {
        $('#counselorTable').DataTable({
            "order": [] // No initial sorting
        });
    });

    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }

    function displaycounselor(counselor_id, first_name, middle_name, last_name, contact_number, email) {
        // Populate the update modal with the row data.
        document.getElementById("display_counselor_id").innerText = counselor_id;
        document.getElementById("upd_counselor_id").value = counselor_id;
        document.getElementById("upd_first_name").value = first_name;
        document.getElementById("upd_middle_name").value = middle_name;
        document.getElementById("upd_last_name").value = last_name;
        document.getElementById("upd_contact_number").value = contact_number;
        document.getElementById("upd_email").value = email;
    }
</script>

</body>
</html>