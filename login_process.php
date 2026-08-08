<?php
session_start();
require "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    // 1. Check the admins table using secure password_verify()
    $admin_stmt = mysqli_prepare($conn, "SELECT id, password FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($admin_stmt, "s", $username);
    mysqli_stmt_execute($admin_stmt);
    $admin_res = mysqli_stmt_get_result($admin_stmt);
    $admin_user = mysqli_fetch_assoc($admin_res);

    if ($admin_user && password_verify($password, $admin_user['password'])) {
        $_SESSION['is_admin'] = true;
        header("Location: admin_dashboard.php");
        exit();
    }

    // 2. Otherwise check a normal user account using password_verify()
    $stmt = mysqli_prepare($conn, "SELECT user_id, first_name, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($password, $user['password'])) {
        header("Location: login.php?error=invalid");
        exit();
    }

    // Set user session variables
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];

    header("Location: dashboard.php");
    exit();
}
?>