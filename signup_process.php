<?php

session_start();
require "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];

    if (empty($first_name) || empty($last_name) || empty($username) || empty($password)) {
        header("Location: signup.php?error=empty");
        exit();
    }

    if (strlen($password) < 8) {
        header("Location: signup.php?error=short");
        exit();
    }

    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($check, "s", $username);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        header("Location: signup.php?error=exists");
        exit();
    }
    mysqli_stmt_close($check);

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users (first_name, last_name, username, password) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $first_name, $last_name, $username, $hashed_password);
    mysqli_stmt_execute($stmt);

    $_SESSION['user_id']    = mysqli_insert_id($conn);
    $_SESSION['first_name'] = $first_name;

    header("Location: dashboard.php");
    exit();
}
?>
