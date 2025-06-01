<?php
include_once 'Class/User.php';
$current_page = basename($_SERVER['PHP_SELF']);

$u = new User();

// Check if counselor_id is set in the session
if (isset($_SESSION['counselor_id'])) {
    $counselor_id = $_SESSION['counselor_id'];

    // Fetch counselor information based on logged-in counselor_id
    $result = $u->display_counselor_acc_by_id($counselor_id);
    if ($result) {
        $counselor = $result->fetch_assoc();
    } else {
        $counselor = null;
    }
} else {
    $student_id = null;
    $counselor = null;
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_ext) && $file_error === 0) {
        if ($file_size <= 2 * 1024 * 1024) { // Max size: 2MB
            $new_file_name = 'uploads/' . uniqid('', true) . '.' . $file_ext;

            // Create the uploads directory if not exists
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (move_uploaded_file($file_tmp, $new_file_name)) {
                // Save file path to database
                $update_result = $u->update_counselor_avatar($counselor_id, $new_file_name);

                if ($update_result) {
                    $_SESSION['success'] = 'Profile picture updated successfully!';
                    $_SESSION['avatar'] = $new_file_name; // Update session avatar
                    header("Location: " . $_SERVER['PHP_SELF']); // Refresh to reflect changes
                    exit();
                } else {
                    $_SESSION['error'] = 'Failed to update profile picture.';
                }
            } else {
                $_SESSION['error'] = 'Failed to move uploaded file.';
            }
        } else {
            $_SESSION['error'] = 'File size exceeds 2MB limit.';
        }
    } else {
        $_SESSION['error'] = 'Invalid file type or upload error.';
    }
}

// CHANGE PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_password_change'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$counselor) {
        $_SESSION['error'] = 'Counselor not found.';
    } elseif (!password_verify($current_password, $counselor['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New password and confirmation do not match.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if ($u->update_counselor_password($counselor_id, $hashed_password)) {
            $_SESSION['success'] = 'Password changed successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update password.';
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>


<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="menu-title">MindAid</div>
        <a href="counselor_dashboard.php" class="<?php echo ($current_page == 'counselor_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        <a href="counselor_crude_questionnaire.php" class="<?php echo ($current_page == 'counselor_crude_questionnaire.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> <span>Assessment Questionnaire</span>
        </a>
        <a href="counselor_test_result.php" class="<?php echo ($current_page == 'counselor_test_result.php') ? 'active' : ''; ?>">
            <i class="fas fa-poll"></i> <span>Assessment Results</span>
        </a>
        <a href="counselor_reports.php" class="<?php echo ($current_page == 'counselor_reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> <span class="ms-3"> Reports</span>
        </a>
    </div>

    <!-- Profile Dropdown -->
    <?php if ($counselor): ?>
        <div class="dropdown ms-auto mt-3 me-3">
            <button class="profile-btn border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?= htmlspecialchars($counselor['avatar'] ?? 'path/to/default-avatar.png') ?>" 
                    alt="Profile" class="rounded-circle" 
                    style="width: 40px; height: 40px; object-fit: cover;">
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header text-center">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="file" name="avatar" id="avatar" style="display: none;" accept="image/*" onchange="this.form.submit();">
                        <button class="profile-btn border-0 bg-transparent" type="button" onclick="document.getElementById('avatar').click();">
                            <img src="<?= htmlspecialchars($counselor['avatar'] ?? 'path/to/default-avatar.png') ?>" 
                                alt="Profile" class="rounded-circle" 
                                style="width: 80px; height: 80px; object-fit: cover;">
                        </button>
                    </form>
                    <h5 class="mb-0"><?= htmlspecialchars($counselor['first_name'] . ' ' . $counselor['last_name']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars($counselor['email']) ?></p>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button class="dropdown-item text-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </li>
                <li>
                    <a class="dropdown-item text-danger" href="logout.php" onclick="return confirmLogout();">
                        <i class="fas fa-sign-out-alt me-2"></i>Log Out
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
    <!-- End Profile Dropdown -->
</div>

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

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #F4F4F9;
        margin: 0;
        padding: 0;
    }
    
    .sidebar {
        background-color: #0D1B2A;
        height: 100vh;
        padding: 20px;
        width: 260px;
        position: fixed;
        left: 0;
        top: 0;
        transition: width 0.3s ease;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .sidebar .menu-title {
        font-size: 24px;
        font-weight: 600;
        color: #F4F4F9;
        margin-bottom: 30px;
        padding-left: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sidebar a {
        color: #EAB308;
        font-size: 20px;
        padding: 12px 15px;
        display: flex;
        align-items: center;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-bottom: 10px;
        text-decoration: none; /* Remove the underline */
    }

    .sidebar a i {
        font-size: 20px;
        margin-right: 12px;
        transition: margin-right 0.3s ease;
    }

    .sidebar span {
        margin-left:10px;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: #1B263B;
        color: #FFFFFF;
        text-decoration: none; /* Remove the underline */
    }

    .sidebar .logout-btn {
        margin-top: auto;
        color: #E74C3C;
    }

    .sidebar .logout-btn:hover {
        background-color: #B03A2E;
        color: #FFFFFF;
    }

    .content {
        margin-left: 280px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }

    .toggle-btn {
        position: fixed;
        top: 15px;
        left: 15px;
        background-color: transparent;
        border: none;
        color: #0D1B2A;
        font-size: 24px;
        cursor: pointer;
        z-index: 100;
        transition: color 0.3s ease;
    }

    .toggle-btn:hover {
        color: #1B263B;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 10px;
        }

        .sidebar .menu-title {
            font-size: 16px;
            text-align: center;
            padding: 0;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar a {
            justify-content: center;
            padding: 10px;
            font-size: 14px;
        }

        .sidebar a i {
            margin-right: 0;
        }

        .sidebar a span {
            display: none;
        }

        .content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        .toggle-btn {
            left: 10px;
        }
    }

    @media (max-width: 480px) {
        .sidebar {
            width: 60px;
            padding: 8px;
        }

        .sidebar a {
            font-size: 12px;
            padding: 8px;
        }

        .sidebar a i {
            font-size: 18px;
        }

        .content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        .toggle-btn {
            font-size: 20px;
            left: 8px;
        }
    }
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.content');

    if (sidebar.style.width === '260px' || sidebar.style.width === '') {
        sidebar.style.width = '70px';
        content.style.marginLeft = '80px';
        document.querySelectorAll('.sidebar a span').forEach(span => {
            span.style.display = 'none';
        });
        document.querySelectorAll('.sidebar i').forEach(icon => {
            icon.style.marginRight = '0';
        });
    } else {
        sidebar.style.width = '260px';
        content.style.marginLeft = '280px';
        document.querySelectorAll('.sidebar a span').forEach(span => {
            span.style.display = 'inline';
        });
        document.querySelectorAll('.sidebar i').forEach(icon => {
            icon.style.marginRight = '12px';
        });
    }
}

function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>
