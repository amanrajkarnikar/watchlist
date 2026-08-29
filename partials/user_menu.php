<div class="user-menu">
  <button type="button" class="user-menu-trigger" onclick="this.parentElement.classList.toggle('open')">
    <span class="user-menu-name"><?php echo htmlspecialchars($first_name); ?></span>
    <span class="user-menu-caret">▾</span>
  </button>
  <div class="user-menu-dropdown">
    <a href="friends.php">Friends</a>
    <a href="statistics.php">Statistics</a>
    <a href="request_title.php">Request a title</a>
    <div class="user-menu-divider"></div>
    <a href="logout.php">Log out</a>
  </div>
</div>

<script>
document.addEventListener('click', function (e) {
  document.querySelectorAll('.user-menu.open').forEach(function (menu) {
    if (!menu.contains(e.target)) menu.classList.remove('open');
  });
});
</script>