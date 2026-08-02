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

<div class="split-wrapper">

  <div class="split-left">
    <div class="logo">Watchlist</div>
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
        <input type="text" name="username" placeholder="amanrjkr" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <div class="remember-row">
        <label><input type="checkbox" name="remember"> Keep me logged in</label>
      </div>

      <button type="submit" class="btn-primary">Login</button>
    </form>

    <p class="bottom-text">Don't have an account? <a href="signup.php">Sign up free</a></p>
  </div>

  <div class="split-right">
    <div class="split-right-text">
      <h1>Every movie night, tracked.</h1>
      <p>Keep a running list of what you've watched, what's next, and what you loved — all in one place.</p>
    </div>
  </div>

</div>

</body>
</html>
