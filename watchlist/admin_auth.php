<?php
session_start();

if (!isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}
?>
