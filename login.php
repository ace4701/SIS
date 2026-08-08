<?php
session_start();
require 'db_config.php'; // Pull in the database connection

$error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Prepare the SQL template
    $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
    
    if ($stmt) {
        // 2. Bind the data
        mysqli_stmt_bind_param($stmt, "s", $username);
        
        // 3. Execute the secure query
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // User found, now check if the hashed password matches
            if (password_verify($password, $row['password'])) {
                // Success! Store user info in the session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['username'] = $row['username'];
                
                mysqli_stmt_close($stmt); // Good memory cleanup
                
                // Send them to the dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
        
        // Close the statement if it didn't hit the successful exit
        mysqli_stmt_close($stmt);
        
    } else {
        $error = "System database error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS - Login | SUKMA Selangor 2026</title>
    <style>
        /* --- GLOBAL RESETS --- */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* --- SPLIT SCREEN LAYOUT --- */
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* --- LEFT SIDE: SLIDESHOW --- */
        .slideshow-section {
            flex: 6; 
            position: relative;
            background-color: #000; 
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out; 
            z-index: 1;
        }
        .slide.active {
            opacity: 1;
            z-index: 2;
        }

        .slideshow-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 100%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 50px;
            box-sizing: border-box;
            color: white;
        }

        .slideshow-overlay h1 {
            font-size: 3rem;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .slideshow-overlay p {
            font-size: 1.2rem;
            margin: 0;
            color: #ffcc00; 
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* --- RIGHT SIDE: FORM --- */
        .form-section {
            flex: 4; 
            display: flex;
            align-items: center;
            justify-content: center;
            
            /* Watermark Mascot Background */
            background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.50)), url('assets/maskot.jpg');
            background-size: 80%; 
            background-position: center bottom; 
            background-repeat: no-repeat;
            
            box-shadow: -10px 0 20px rgba(0,0,0,0.05);
            z-index: 4;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .sys-logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .sys-logo-area h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }

        .sys-logo-area span {
            color: #da251d; 
            font-weight: 900;
        }

        /* --- ERROR MESSAGE BOX --- */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        /* Form Controls */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 600;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: all 0.3s;
            background-color: #f8fafc;
        }

        .input-group input:focus {
            border-color: #da251d;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(218, 37, 29, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background-color: #da251d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 10px;
        }

        .login-btn:hover {
            background-color: #b01b15;
        }
        
        .login-btn:active {
            transform: scale(0.98);
        }

        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
            color: #777;
        }

        .form-footer a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .login-container { flex-direction: column; }
            .slideshow-section { flex: 3; }
            .form-section {
                flex: 7;
                border-radius: 20px 20px 0 0;
                margin-top: -20px;
            }
            .slideshow-overlay h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <!-- LEFT: Slideshow Area -->
        <div class="slideshow-section">
            <div class="slide active" style="background-image: url(assets/R1.jpg);"></div>
            <div class="slide" style="background-image: url(assets/R2.jpeg);"></div>
            <div class="slide" style="background-image: url(assets/R3.jpg);"></div>

            <div class="slideshow-overlay">
                <h1>SUKMA XXII</h1>
                <p>Selangor 2026 • Official Information System</p>
            </div>
        </div>

        <!-- RIGHT: Login Form Area -->
        <div class="form-section">
            <div class="form-wrapper">
                
                <div class="sys-logo-area">
                    <h2>SUKMA<br> <span> INFORMATION</span> SYSTEM</h2>
                    <p style="color: #888; font-size: 14px; margin-top: 5px;">Secure Management Framework</p>
                </div>

                <!-- NEW: Dynamic Error Message Display -->
                <?php if(!empty($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your credentials" required autocomplete="off">
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="login-btn">Secure Login</button>
                </form>

                <div class="form-footer">
                    <a href="register.php">Create an Account</a> &nbsp;|&nbsp; <a href="forgot_password.php">Forgot Password?</a>
                </div>

            </div>
        </div>

    </div>

    <!-- Slideshow Engine Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let slides = document.querySelectorAll('.slide');
            let currentSlide = 0;
            
            setInterval(function() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 5000); 
        });
    </script>

</body>
</html>