<?php
session_start();

include_once 'Class/User.php';
require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$u = new User();
$message = "";

if (isset($_POST['submit_email'])) {
    $email = $_POST['email'];

    // Check if the email belongs to a counselor or student
    $user = $u->findUserByEmail($email);
    
    if ($user) {
        // Generate a password reset token
        $token = bin2hex(random_bytes(50)); // generate a random token

        // Store the token and expiry in the database for the appropriate user type
        if ($u->storeResetToken($email, $token, $user['user_type'])) {
            $reset_link = "http://mindaid.site/reset_password.php?token=$token";

            // Send the reset link via email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'reciokathrine@gmail.com';
                $mail->Password   = 'dlll rdyx aaaf xbjm';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('reciokathrine@gmail.com', 'MindAid');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Please click <a href='$reset_link'>this link</a> to proceed with resetting your password.";

                $mail->send();
                $message = 'Password reset link has been sent to your email.';
            } catch (Exception $e) {
                $message = 'Mailer Error: ' . $mail->ErrorInfo;
            }
        }
    } else {
        $message = "Email does not exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | MindAid</title>
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

        body {
            background-color: var(--deep-navy);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .forgot-container {
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 40px;
            width: 400px;
            box-shadow: 0 8px 32px rgba(255, 215, 0, 0.15);
            text-align: center;
            position: relative;
        }

        .forgot-container::before,
        .forgot-container::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-top-left-radius: 40%;
            border-top-right-radius: 45%;
            border-bottom-left-radius: 35%;
            border-bottom-right-radius: 40%;
            z-index: -1;
            opacity: 0.15;
            background-color: rgba(255, 255, 255, 0.1);
            animation: waves 8s infinite linear;
        }

        .forgot-container::after {
            animation-delay: 2s;
        }

        h3 {
            color: var(--yellow-gold);
            font-size: 1.8rem;
            margin-bottom: 25px;
        }

        input[type="email"] {
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

        input[type="email"]:focus {
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

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: var(--yellow-gold);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: #e5c100;
        }

    </style>
</head>
<body>
    <div class="forgot-container">
        <form action="" method="POST">
            <h3>Forgot Password?</h3>
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="submit_email">Send Reset Link</button>
        </form>
        <a href="index.php" class="back-btn">← Back to Login</a>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>

            <?php if ($message === 'Password reset link has been sent to your email.'): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 4000); // Redirect after 4 seconds
                </script>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
