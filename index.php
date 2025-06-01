<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include_once 'Class/User.php';

$u = new User();


if (isset($_POST['btnlogin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check admin login
    $data = $u->admin_login($username, $password);
    if (!empty($data)) {
        $_SESSION['user'] = $data;
        $_SESSION['role'] = 'Admin';
        sleep(2); // ⏳ Delay for 2 seconds to show loading
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check counselor login
    $data = $u->counselor_login($username, $password);
    if (!empty($data)) {
        $_SESSION['user'] = $data;
        $_SESSION['role'] = 'Guidance Counselor';
        $_SESSION['counselor_id'] = $data['counselor_id'];
        sleep(2); // ⏳ Delay for 2 seconds to show loading
        header("Location: counselor_dashboard.php");
        exit();
    }

    // Check student login
    $data = $u->student_login($username, $password);
    if (!empty($data)) {
        $_SESSION['user'] = $data;
        $_SESSION['role'] = 'Student';
        $_SESSION['student_id'] = $data['student_id'];
        sleep(2); // ⏳ Delay for 2 seconds to show loading
        header("Location: student_test.php");
        exit();
    }

    // If no match found
    $error = "Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>MindAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* Colors */
        :root {
            --yellow-gold: #FFD700;
            --deep-navy: #1A1A40;
            --white: #FFFFFF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--deep-navy);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            width: 100%;
            position: fixed;
        }

        h3 {
            color: var(--yellow-gold);
            font-size: 1.8rem;
            margin-bottom: 25px;
        }


        .login {
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 40px;
            width: 400px;
            box-shadow: 0 8px 32px rgba(255, 215, 0, 0.15);
            text-align: center;
            position: relative;
        }

        /* Animated Background */
        .login::before, .login::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-top-left-radius: 40%;
            border-top-right-radius: 45%;
            border-bottom-left-radius: 35%;
            border-bottom-right-radius: 40%;
            z-index: -1;
            opacity: 0.3;
        }

        .login::before {
            left: 40%;
            bottom: -130%;
            background-color: rgba(255, 255, 255, 0.15);
            animation: waves 6s infinite linear;
        }

        .login::after {
            left: 35%;
            bottom: -125%;
            background-color: rgba(255, 255, 255, 0.2);
            animation: waves 7s infinite linear;
        }

        /* Inputs */
        .login input {
            font-family: 'Poppins', sans-serif;
            display: block;
            border-radius: 5px;
            font-size: 16px;
            background: var(--white);
            color: var(--deep-navy);
            width: 100%;
            padding: 10px 10px;
            margin: 15px 0;
            outline: none;
        }

        .login input:focus {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        /* Button */
        .login button {
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            color: var(--yellow-gold);
            font-size: 16px;
            text-transform: uppercase;
            width: 100%;
            padding: 10px 0;
            margin-top: 10px;
            border-radius: 5px;
            background-color: var(--deep-navy);
            transition: all 300ms ease-in-out;
        }

        .login button:hover {
            background-color: var(--yellow-gold);
            color: var(--deep-navy);
            border-color: var(--deep-navy);
        }

        /* Fixing Keyframe Animation */
        @keyframes waves {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        a {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            position: absolute;
            right: 10px; 
            bottom: 10px;
            font-size: 12px;
        }

         /* Error Message */
         .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Updated Spinner with Gradient and Smoother Look */
        .spinner {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#007bff 10%, #f3f3f3 10% 100%);
            animation: spin 1s linear infinite;
        }

        /* Spinner Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smooth Fade-In for Loading Overlay */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.85);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: opacity 0.5s ease;
        }

        /* Loading Text with Animated Dots */
        .loading-text {
            margin-top: 20px;
            font-size: 18px;
            color: #007bff;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
        }

        .loading-dots::after {
            content: '';
            display: inline-block;
            width: 1em;
            text-align: left;
            animation: dots 1.5s steps(3, end) infinite;
        }

        @keyframes dots {
            0% { content: ''; }
            33% { content: '.'; }
            66% { content: '..'; }
            100% { content: '...'; }
        }

        .forgot-password {
            display: block;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            color: #007bff;
            font-size: 16px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Mobile adjustments */
        @media (max-width: 576px) {
            .login {
                padding: 30px 20px;
            }

            .login input {
                font-size: 14px;
                padding: 8px 10px;
            }

            .login button {
                font-size: 14px;
                padding: 8px 0;
            }

            .forgot-password {
                font-size: 14px;
            }

            .loading-text {
                font-size: 16px;
            }
        }

    </style>
</head>
<body>
<div class="d-flex">
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Logging In<span class="loading-dots"></span></div>
    </div>
</div>
    <form class="login" action="" method="POST">
        <h3>Login</h3>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="mb-3" name="btnlogin">Login</button>
        <a href="forgot_password.php" class="forgot-password">🔒 Forgot your password?</a>
    </form>

<script>

    // Optional: Disable going back to dashboard after logout
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.pushState(null, null, location.href);
    };

    document.querySelector('form').addEventListener('submit', function () {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = 'flex';
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.opacity = '1';
        }, 50);
    });
</script>


</body>
</html>