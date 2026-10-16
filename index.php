<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php'; 

$message = "";

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- 1. VULNERABLE LOGIN LOGIC (SQL Injection) ---
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // DANGER: Directly pasting user input into the SQL command!
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if (!$result) {
        $message = "<div class='alert'>SQL Error: " . $conn->error . "</div>";
    } elseif ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user'] = $row['username']; // Save login state
    } else {
        $message = "<div class='alert'>Invalid username or password.</div>";
    }
}

// --- 2. VULNERABLE PASSWORD CHANGE (CSRF) ---
if (isset($_POST['change_password']) && isset($_SESSION['user'])) {
    $new_pass = $_POST['new_password'];
    $current_user = $_SESSION['user'];

    $sql = "UPDATE users SET password = '$new_pass' WHERE username = '$current_user'";
    $conn->query($sql);
    $message = "<div class='alert success'>Password updated to: $new_pass</div>";
}

// --- 3. VULNERABLE GUESTBOOK LOGIC (XSS) ---
if (isset($_POST['submit_comment']) && isset($_SESSION['user'])) {
    $name = $_SESSION['user']; // Automatically uses logged-in username
    $comment = $_POST['message'];

    $sql = "INSERT INTO comments (name, message) VALUES ('$name', '$comment')";
    $conn->query($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Web Security Seminar Lab</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Web Security Lab Portal</h1>
    <?php echo $message; ?>

    <?php if (!isset($_SESSION['user'])): ?>
        <!-- VIEW 1: LOGGED OUT (Show ONLY Login Form) -->
        <div class="box">
            <h2>Portal Login</h2>
            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

    <?php else: ?>
        <!-- VIEW 2: LOGGED IN (Show Logout Button, Settings, and Guestbook) -->
        <div class="top-bar">
            <span>Welcome, <strong><?php echo $_SESSION['user']; ?></strong>!</span>
            <a href="index.php?logout=1" class="btn-logout">Logout</a>
        </div>

        <!-- SECTION 1: ACCOUNT SETTINGS (CSRF Demo) -->
        <div class="box">
            <h2>1. Account Settings (Change Password)</h2>
            <form method="POST" action="index.php">
                <input type="text" name="new_password" placeholder="Enter New Password" required>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>

        <!-- SECTION 2: SEMINAR GUESTBOOK (XSS Demo) -->
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
                    // DANGER: Printing raw database text directly to the browser!
                    echo "<p><strong>" . $row['name'] . ":</strong> " . $row['message'] . "</p><hr>";
                }
            }
            ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
