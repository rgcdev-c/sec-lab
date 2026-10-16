if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Step 1: Send the SQL template with ? placeholders
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    // Step 2: Bind inputs strictly as Strings ("ss")
    $stmt->bind_param("ss", $username, $password);
    // Step 3: Execute and get result
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user'] = $row['username'];
    } else {
        $message = "<div class='alert'>Invalid username or password.</div>";
    }
    $stmt->close();
}
