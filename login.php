<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
if (isset($_SESSION['is_admin'])) {
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Log in</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body>

<div class="split-wrapper split-wrapper-v2">

  <a href="index.php" class="logo logo-lg logo-corner">Watchlist</a>

  <div class="split-left">
    <div class="split-left-inner">
      <h2>Login</h2>

      <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">
          <?php
            if ($_GET['error'] === 'invalid') echo "Username or password is incorrect.";
            elseif ($_GET['error'] === 'empty') echo "Please fill in both fields.";
          ?>
        </div>
      <?php endif; ?>

      <form action="login_process.php" method="POST">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-primary btn-pill">Login</button>
      </form>

      <p class="bottom-text">Don't have an account? <a href="signup.php">Sign up free</a></p>
    </div>
  </div>

  <div class="split-right">
  </div>

</div>

</body>
</html>
