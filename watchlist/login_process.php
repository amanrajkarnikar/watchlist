<?php
session_start();
require "db.php";

$ADMIN_USERNAME = "admin";
$ADMIN_PASSWORD = "admin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        header("Location: admin_dashboard.php");
        exit();
    }

    $stmt = mysqli_prepare($conn, "SELECT user_id, first_name, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($password, $user['password'])) {
        header("Location: login.php?error=invalid");
        exit();
    }

    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];

    header("Location: dashboard.php");
    exit();
}
?>
