<?php
require "auth.php";
require "db.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM watchlist_entry WHERE user_id = $user_id");
$total_entries = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM watchlist_entry WHERE user_id = $user_id AND watch_status = 'Watched'");
$watched = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM watchlist_entry WHERE user_id = $user_id AND watch_status = 'To Watch'");
$to_watch = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT AVG(review_value) AS average FROM rating WHERE user_id = $user_id");
$row = mysqli_fetch_assoc($result);
$avg_rating = $row['average'] ? round($row['average'], 1) : 0;

$genre_sql = "SELECT m.genre, COUNT(*) AS total
              FROM watchlist_entry we
              JOIN media m ON we.media_id = m.media_id
              WHERE we.user_id = $user_id
              GROUP BY m.genre
              ORDER BY total DESC";
$genre_result = mysqli_query($conn, $genre_sql);

$genre_rows = [];
$max_genre_count = 1;
while ($row = mysqli_fetch_assoc($genre_result)) {
    $genre_rows[] = $row;
    if ($row['total'] > $max_genre_count) {
        $max_genre_count = $row['total'];
    }
}

$genre_colors = ['#7c6ef0', '#2dd4bf', '#fbbf24', '#f472b6', '#4ade80'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Statistics</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body>

<div class="dash-wrapper">

  <div class="sidebar">
    <div class="logo">Watchlist</div>

    <div class="sidebar-nav-scroll">
      <form class="sidebar-search" action="browse.php" method="GET">
        <input type="text" name="search" placeholder="Search...">
        <button type="submit" title="Search">🔍</button>
      </form>

      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="mylist.php" class="nav-link">My list</a>
      <a href="browse.php" class="nav-link">Browse</a>
    </div>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1>Statistics</h1>
        <p>Your watchlist numbers, at a glance</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <div class="stats-grid stats-grid-4">
      <div class="stat-card">
        <div class="stat-value"><?php echo $total_entries; ?></div>
        <div class="stat-label">Total entries</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $watched; ?></div>
        <div class="stat-label">Watched</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $to_watch; ?></div>
        <div class="stat-label">To watch</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $avg_rating; ?></div>
        <div class="stat-label">Avg rating</div>
      </div>
    </div>

    <div class="panel">
      <h3>Genre breakdown</h3>
      <?php if (count($genre_rows) === 0): ?>
        <p style="color:var(--text-gray); font-size:13px;">Nothing to show yet.</p>
      <?php else: ?>
        <?php foreach ($genre_rows as $index => $genre): ?>
          <?php
            $bar_percent = ($genre['total'] / $max_genre_count) * 100;
            $bar_color   = $genre_colors[$index % count($genre_colors)];
          ?>
          <div class="genre-row">
            <div class="genre-label">
              <span><?php echo htmlspecialchars($genre['genre']); ?></span>
              <span><?php echo $genre['total']; ?></span>
            </div>
            <div class="genre-bar-bg">
              <div class="genre-bar-fill" style="width: <?php echo $bar_percent; ?>%; background: <?php echo $bar_color; ?>;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>
