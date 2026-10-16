// --- 2. SECURE PASSWORD CHANGE (CSRF + Prepared Statement) ---
if (isset($_POST['change_password']) && isset($_SESSION['user'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<div class='alert'>CSRF Attack Detected! Request blocked.</div>");
    }

    $new_pass = $_POST['new_password'];
    $current_user = $_SESSION['user'];

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_pass, $current_user);
    $stmt->execute();
    $stmt->close();

    $message = "<div class='alert success'>Password safely updated to: " . htmlspecialchars($new_pass, ENT_QUOTES, 'UTF-8') . "</div>";
}
