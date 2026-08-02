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

<div class="split-wrapper">

  <div class="split-left">
    <div class="logo">Watchlist</div>
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
          <input type="text" name="first_name" placeholder="First name" required>
        </div>
        <div class="form-group">
          <label>Last name</label>
          <input type="text" name="last_name" placeholder="Last name" required>
        </div>
      </div>

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Pick a username" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 8 characters" required>
      </div>

      <button type="submit" class="btn-primary">Create account</button>
    </form>

    <p class="bottom-text">Already have an account? <a href="login.php">Log in</a></p>
  </div>

  <div class="split-right">
    <div class="split-right-text">
      <h1>Start your watchlist today.</h1>
      <p>Add movies and shows, mark what you've watched, and see your stats build up over time.</p>
    </div>
  </div>

</div>

</body>
</html>
