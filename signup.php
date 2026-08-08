<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Sign up</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body>

<div class="split-wrapper split-wrapper-v2">

  <a href="index.php" class="logo logo-lg logo-corner">Watchlist</a>

  <div class="split-left">
    <div class="split-left-inner">
      <h2>Sign up</h2>

      <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">
          <?php
            if ($_GET['error'] === 'exists') echo "An account with that username already exists.";
            elseif ($_GET['error'] === 'empty') echo "Please fill in all fields.";
            elseif ($_GET['error'] === 'short') echo "Password must be at least 8 characters.";
          ?>
        </div>
      <?php endif; ?>

      <form action="signup_process.php" method="POST">
        <div class="form-row">
          <div class="form-group">
            <label>First name</label>
            <input type="text" name="first_name" required>
          </div>
          <div class="form-group">
            <label>Last name</label>
            <input type="text" name="last_name" required>
          </div>
        </div>

        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-primary btn-pill">Create account</button>
      </form>

      <p class="bottom-text">Already have an account? <a href="login.php">Log in</a></p>
    </div>
  </div>

  <div class="split-right">
  </div>

</div>

</body>
</html>
