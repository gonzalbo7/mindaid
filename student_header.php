 <?php
include_once 'Class/User.php';

$u = new User();

// Check if student_id is set in the session
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];

    // Example of checking if the student has already completed the test
    $test_results = $u->is_test_completed($student_id);

    // Fetch student information based on logged-in student_id
    $result = $u->display_student_acc_by_id($student_id);
    if ($result) {
        $student = $result->fetch_assoc();
    } else {
        $student = null;
    }
} else {
    $student_id = null;
    $student = null;
}

// Upload Avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];

    $file_name = $_FILES['avatar']['name'];
    $file_tmp = $_FILES['avatar']['tmp_name'];
    $file_size = $_FILES['avatar']['size'];
    $file_error = $_FILES['avatar']['error'];

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed) && $file_error === 0) {
        if ($file_size <= 2 * 1024 * 1024) { // 2MB limit
            $new_file_name = 'uploads/' . uniqid('avatar_', true) . '.' . $file_ext;

            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (move_uploaded_file($file_tmp, $new_file_name)) {
                $upload_result = $u->update_student_avatar($student_id, $new_file_name);

                echo '
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <title>Uploading...</title>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    </head>
                    <body>
                        <script>
                            Swal.fire({
                                title: "Success!",
                                text: "Profile Picture Updated Successfully!",
                                icon: "success",
                                confirmButtonText: "OK"
                            }).then(() => {
                                window.location.href = "' . $_SERVER['PHP_SELF'] . '?refresh=1";
                            });
                        </script>
                    </body>
                    </html>
                ';
                exit(); // Important to stop further execution
            } else {
                $error_message = "Failed to move uploaded file.";
            }
        } else {
            $error_message = "File size exceeds 2MB limit.";
        }
    } else {
        $error_message = "Invalid file type or upload error.";
    }

    // Show error if something went wrong
    if (isset($error_message)) {
        
        echo '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Uploading...</title>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        title: "Upload Failed",
                        text: "' . $error_message . '",
                        icon: "error",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "' . $_SERVER['PHP_SELF'] . '?refresh=1";
                    });
                </script>
            </body>
            </html>
        ';
        exit();
    }
}

// CHANGE PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_password_change'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$student) {
        $_SESSION['error'] = 'Student not found.';
    } elseif (!password_verify($current_password, $student['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New password and confirmation do not match.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if ($u->update_student_password($student_id, $hashed_password)) {
            $_SESSION['success'] = 'Password changed successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update password.';
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

?>

<!-- Load Poppins Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<?php
    $current_page = basename($_SERVER['PHP_SELF']); // Get current page for active link
?>
<!-- Navbar Styling -->
<style>
    body {
        font-family: 'Poppins', sans-serif;
    }

    .navbar {
        background-color: #1A2B3C; /* Darker background for a more realistic look */
        padding: 14px 18px;
        box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3); /* Subtle shadow for depth */
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar .navbar-nav .nav-link {
        color: #ffffff !important;
        font-size: 16px; /* Smaller font size */
        font-weight: 500;
        margin-left:30px;
        padding: 10px 15px; /* Slightly reduced padding */
        border-radius: 8px; /* Rounded corners for links */
        transition: color 0.3s ease, background-color 0.3s ease, transform 0.3s ease;
    }

    .navbar .navbar-nav .nav-link:hover {
        color: #ffffff !important;
        background-color: #0D6EFD !important; /* Hover effect with blue background */
        transform: scale(1.05);
        box-shadow: 0px 4px 10px rgba(13, 110, 253, 0.3); /* Hover shadow effect */
    }

    .navbar .navbar-nav .nav-link.active {
        background-color: #0D6EFD;
        color: #ffffff !important;
        font-weight: 600;
        transform: scale(1.1); /* Slightly enlarge active link */
        box-shadow: 0px 4px 10px rgba(13, 110, 253, 0.5); /* Active shadow effect */
    }


    /* Profile button */
    .profile-btn img {
        border: 2px solid #0D6EFD;
        border-radius: 50%;
        transition: transform 0.3s ease, border 0.3s ease;
    }

    .profile-btn img:hover {
        transform: scale(1.1);
        border-color: #1A2B3C; /* Subtle border change on hover */
    }

    /* Mobile Menu Button */
    .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.7) !important;
        color: #ffffff !important;
        font-size: 24px;
        padding: 8px 10px;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .navbar-toggler:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.9);
        transform: rotate(90deg); /* Slight rotation effect */
    }

    /* Dropdown Menu */
    .dropdown-menu {
        top: 10px !important;
        right: 0 !important;
        left: auto !important;
        width: 370px;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        background-color: #FFFFFF;
        border: none;
        z-index: 1050;
    }

    .dropdown-item {
        padding: 10px 15px;
        transition: background 0.3s ease, transform 0.3s ease;
        border-radius: 8px;
    }

    .dropdown-item:hover {
        background-color: #f1f1f1;
        transform: translateX(5px); /* Slight movement on hover */
    }

    .dropdown-item strong {
        flex-shrink: 0;
        color: #0D1B2A;
        font-weight: bold;
    }

    .dropdown-item span {
        color: #6c757d;
        font-size: 14px;
    }
    
    @media (max-width: 576px) {
    .dropdown-menu {
        width: 100vw;
        max-width: 100%;
        left: 0 !important;
        right: 0 !important;
        top: 60px !important;
        border-radius: 0;
        padding: 10px;
    }

    .dropdown-header img {
        width: 60px !important;
        height: 60px !important;
    }

    .dropdown-header h5 {
        font-size: 16px;
    }

    .dropdown-header p {
        font-size: 13px;
    }
}

</style>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <!-- Toggle button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mt-4 mt-md-0 mb-3 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'student_test.php') ? 'active' : ''; ?>" href="student_test.php">Take Test</a>
                </li>
                <li class="nav-item mb-3 mb-md-0">
                    <a class="nav-link <?php echo ($current_page == 'student_view_results.php') ? 'active' : ''; ?>" href="student_view_results.php">View All Results</a>
                </li>
            </ul>
           <!-- Profile Dropdown -->
           <?php if ($student): ?>
            <div class="dropdown ms-auto">
                <button class="profile-btn border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($student['avatar'] ?? 'path/to/default-avatar.png') ?>" 
                        alt="Profile" class="rounded-circle" 
                        style="width: 40px; height: 40px; object-fit: cover;">
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header text-center">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="file" name="avatar" id="avatar" style="display: none;" accept="image/*" onchange="this.form.submit();">
                            <button class="profile-btn border-0 bg-transparent" type="button" onclick="document.getElementById('avatar').click();">
                                <img src="<?= htmlspecialchars($student['avatar'] ?? 'path/to/default-avatar.png') ?>" 
                                    alt="Profile" class="rounded-circle" 
                                    style="width: 80px; height: 80px; object-fit: cover;">
                            </button>
                        </form>
                        <h5 class="mb-0"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($student['email']) ?></p>
                    </li>
                    <li>
                        <div class="dropdown-item">
                            <i class="fas fa-id-card me-2 text-primary"></i>
                            <strong>Student ID:</strong> <br> <span><?= htmlspecialchars($student['student_id']) ?></span>
                        </div>
                    </li>
                    <li>
                        <div class="dropdown-item">
                            <i class="fas fa-graduation-cap me-2 mt-3 text-success"></i>
                            <strong>Course:</strong> <br> <span><?= htmlspecialchars($student['course']) ?></span>
                        </div>
                    </li>
                    <li>
                        <div class="dropdown-item">
                            <i class="fas fa-layer-group me-2 mt-3 text-info"></i>
                            <strong>Year Level:</strong> <br> <span><?= htmlspecialchars($student['year_level']) ?></span>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item text-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            Change Password
                        </button>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger text-center" href="logout.php" onclick="return confirmLogout();">
                            <i class="fas fa-sign-out-alt me-2"></i>Log Out
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            <!-- End Profile Dropdown -->
        </div>
    </div>
</nav>
<!-- End Navbar -->

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
            <button type="submit" name="submit_password_change" class="btn btn-primary">Save</button>
            </div>
        </div>
        </form>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                customClass: 'custom-tooltip'
            });
        });
    });

    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>
