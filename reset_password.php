<?php
session_start();
include_once 'Class/User.php';

$u = new User();
$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $user = $u->findUserByToken($token);
    
    if ($user) {
        if (isset($_POST['submit_reset'])) {
            $new_password = $_POST['new_password'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
            $userId = $user['user_type'] === 'counselor' 
                ? $user['user_data']['counselor_id'] 
                : $user['user_data']['student_id'];
            $user_type = $user['user_type'];

            if ($u->updateUserPassword($userId, $hashed_password, $user_type)) {
                $message = "Password reset successful. Redirecting to login...";
                header("refresh:3;url=index.php");
            } else {
                $message = "Error resetting password.";
            }            
        }
    } else {
        $message = "Invalid or expired token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Reset Password | MindAid</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --yellow-gold: #FFD700;
            --deep-navy: #1A1A40;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }


        body {
            background-color: var(--deep-navy);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .reset-container {
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 40px 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(255, 215, 0, 0.15);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .reset-container::before,
        .reset-container::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.1;
            background-color: rgba(255, 255, 255, 0.1);
            animation: waves 8s infinite linear;
            top: -150px;
            left: -150px;
        }

        .reset-container::after {
            animation-delay: 3s;
            top: auto;
            bottom: -150px;
            right: -150px;
            left: auto;
        }

        h3 {
            color: var(--yellow-gold);
            font-size: clamp(1.5rem, 4vw, 1.8rem);
            margin-bottom: 25px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid var(--white);
            border-radius: 8px;
            background: var(--white);
            color: var(--deep-navy);
            font-size: 1rem;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: var(--yellow-gold);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        button {
            background-color: var(--yellow-gold);
            color: var(--deep-navy);
            font-weight: bold;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        button:hover {
            background-color: #e5c100;
        }

        .message {
            margin-top: 20px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        @keyframes waves {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .reset-container {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 25px 15px;
            }
            h3 {
                font-size: 1.4rem;
            }
            input, button {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <form action="" method="POST">
            <h3>Reset Password</h3>
            <input type="password" name="new_password" placeholder="Enter new password" required>
            <button type="submit" name="submit_reset">Reset Password</button>
        </form>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
