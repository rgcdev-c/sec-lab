// --- 3. SECURE GUESTBOOK INSERT ---
if (isset($_POST['submit_comment']) && isset($_SESSION['user'])) {
    $name = $_SESSION['user'];
    $comment = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO comments (name, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $comment);
    $stmt->execute();
    $stmt->close();
}
