<?php
include_once 'Class/User.php';
$current_page = basename($_SERVER['PHP_SELF']);

$u = new User();

?>
<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" id="sidebar">
        <div class="menu-title">MindAid</div>
        <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> <span class="ms-3"> Dashboard</span>
        </a>
        <a href="admin_create_counselor_acc.php" class="<?php echo ($current_page == 'admin_create_counselor_acc.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i> <span class="ms-3"> Counselor Account</span>
        </a>
        <a href="admin_create_student_acc.php" class="<?php echo ($current_page == 'admin_create_student_acc.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> <span class="ms-3"> Student Account</span>
        </a>
        <a href="admin_view_test_results.php" class="<?php echo ($current_page == 'admin_view_test_results.php') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> <span class="ms-3"> Assessment Results </span>
        </a>
        <a href="admin_reports.php" class="<?php echo ($current_page == 'admin_reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> <span class="ms-3"> Reports</span>
        </a>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">
            <i class="fas fa-power-off"></i> <span class="ms-2"> Logout</span>
        </a>
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
