<?php 
// 1. Error reporting and crash prevention for PHP 8.1+
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

session_start();
include 'db.php'; 

// Generate CSRF Token for the session if it doesn't exist yet
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- 1. SECURE LOGIN LOGIC (reCAPTCHA + Prepared Statement + Password Hashing) ---
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Google's Official Test Secret Key (Pairs with Test Site Key)
    $recaptcha_secret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    if (empty($recaptcha_response)) {
        $message = "<div class='alert'>Please check the 'I am not a robot' box!</div>";
    } else {
        $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}";
        $verify_reply = file_get_contents($verify_url);
        $response_data = json_decode($verify_reply);

        if ($response_data && $response_data->success) {
            // Prepared Statement to prevent SQL Injection
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                // Securely verify hashed password
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user'] = $row['username'];
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "<div class='alert'>Invalid username or password.</div>";
                }
            } else {
                $message = "<div class='alert'>Invalid username or password.</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='alert'>Bot verification failed!</div>";
        }
    }
}

// --- 2. SECURE PASSWORD CHANGE (CSRF Token + Prepared Statement + Hashing) ---
if (isset($_POST['change_password']) && isset($_SESSION['user'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<div class='alert'>CSRF Attack Detected! Request blocked.</div>");
    }

    $new_pass_raw = $_POST['new_password'];
    $new_pass_hashed = password_hash($new_pass_raw, PASSWORD_DEFAULT);
    $current_user = $_SESSION['user'];

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_pass_hashed, $current_user);
    $stmt->execute();
    $stmt->close();

    $message = "<div class='alert success'>Password safely updated and hashed!</div>";
}

// --- 3. SECURE GUESTBOOK INSERT (Prepared Statement + Redirect) ---
if (isset($_POST['submit_comment']) && isset($_SESSION['user'])) {
    $name = $_SESSION['user'];
    $comment = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO comments (name, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $comment);
    $stmt->execute();
    $stmt->close();

    // Prevent browser refresh POST resubmission loops
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Web Security Seminar Lab</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google reCAPTCHA API Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<div class="container">
    <h1>Web Security Lab Portal</h1>
    <?php echo $message; ?>

    <?php if (!isset($_SESSION['user'])): ?>
        <!-- ================= VIEW 1: LOGGED OUT ================= -->
        <div class="box">
            <h2>Portal Login</h2>
            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                
                <!-- Google Official Localhost Test Site Key -->
                <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" style="margin: 10px 0;"></div>
                
                <button type="submit" name="login">Login</button>
            </form>
        </div>

    <?php else: ?>
        <!-- ================= VIEW 2: LOGGED IN ================= -->
        <div class="top-bar">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></strong>!</span>
            <a href="index.php?logout=1" class="btn-logout">Logout</a>
        </div>

        <!-- SECTION 1: ACCOUNT SETTINGS (CSRF Protected) -->
        <div class="box">
            <h2>1. Account Settings (Change Password)</h2>
            <form method="POST" action="index.php">
                <!-- Hidden Anti-CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="text" name="new_password" placeholder="Enter New Password" required>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>

        <!-- SECTION 2: SEMINAR GUESTBOOK (XSS Protected) -->
        <div class="box">
            <h2>2. Seminar Guestbook</h2>
            <form method="POST" action="index.php">
                <textarea name="message" placeholder="Leave a comment..." required></textarea>
                <button type="submit" name="submit_comment">Post Comment</button>
            </form>

            <h3>Recent Comments:</h3>
            <?php
            $result = $conn->query("SELECT * FROM comments ORDER BY id DESC");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // XSS Output Encoding using htmlspecialchars
                    $safe_name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                    $safe_message = htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
                    echo "<p><strong>" . $safe_name . ":</strong> " . $safe_message . "</p><hr>";
                }
            }
            ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
