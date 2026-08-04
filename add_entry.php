<?php
require "auth.php";
require "db.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title_name']);
    $year       = $_POST['release_year'];
    $genre      = $_POST['genre'];
    $type       = $_POST['media_type'];
    $status     = $_POST['watch_status'];
    $bookmarked = isset($_POST['is_bookmarked']) ? 1 : 0;

    if (!empty($title)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO media (title_name, release_year, genre, media_type, added_by_user_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sissi", $title, $year, $genre, $type, $user_id);
        mysqli_stmt_execute($stmt);

        $media_id = mysqli_insert_id($conn);

        $stmt2 = mysqli_prepare($conn, "INSERT INTO watchlist_entry (user_id, media_id, watch_status, is_bookmarked) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "iisi", $user_id, $media_id, $status, $bookmarked);
        mysqli_stmt_execute($stmt2);

        header("Location: mylist.php?added=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Add Entry</title>
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
        <h1>Add a new entry</h1>
        <p>Track a movie, show, or anime</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <div class="panel" style="max-width:500px;">
      <form action="add_entry.php" method="POST">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title_name" placeholder="e.g. Inception" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Release year</label>
            <input type="number" name="release_year" placeholder="2010">
          </div>
          <div class="form-group">
            <label>Genre</label>
            <input type="text" name="genre" placeholder="Sci-Fi">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Type</label>
            <select name="media_type" style="width:100%; padding:10px; background:var(--bg-input); color:var(--text-white); border:1px solid var(--border-color); border-radius:8px;">
              <option value="Movie">Movie</option>
              <option value="TV">TV Show</option>
              <option value="Anime">Anime</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="watch_status" style="width:100%; padding:10px; background:var(--bg-input); color:var(--text-white); border:1px solid var(--border-color); border-radius:8px;">
              <option value="To Watch">To Watch</option>
              <option value="Watched">Watched</option>
            </select>
          </div>
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="is_bookmarked" id="is_bookmarked" value="1">
          <label for="is_bookmarked" style="margin:0;">🔖 Bookmark for later</label>
        </div>
        <button type="submit" class="btn-primary">Add to watchlist</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
