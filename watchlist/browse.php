<?php
require "auth.php";
require "db.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

if (isset($_GET['add'])) {
    $media_id = (int) $_GET['add'];
    $check = mysqli_prepare($conn, "SELECT entry_id FROM watchlist_entry WHERE user_id = ? AND media_id = ?");
    mysqli_stmt_bind_param($check, "ii", $user_id, $media_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) === 0) {
        $insert = mysqli_prepare($conn, "INSERT INTO watchlist_entry (user_id, media_id, watch_status) VALUES (?, ?, 'To Watch')");
        mysqli_stmt_bind_param($insert, "ii", $user_id, $media_id);
        mysqli_stmt_execute($insert);
    }
    $back = !empty($_GET['genre']) ? "?added=1&genre=" . urlencode($_GET['genre']) : "?added=1";
    header("Location: browse.php" . $back);
    exit();
}

// Genre filter
$genre_filter = trim($_GET['genre'] ?? '');
$search = trim($_GET['search'] ?? '');
$where = "m.added_by_user_id IS NULL";
if (!empty($genre_filter)) {
    $safe_genre = mysqli_real_escape_string($conn, $genre_filter);
    $where .= " AND m.genre = '$safe_genre'";
}
if ($search !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where .= " AND m.title_name LIKE '%$safe_search%'";
}

// Fetch all genres for the filter bar
$genres_res = mysqli_query($conn, "SELECT DISTINCT genre FROM media WHERE added_by_user_id IS NULL AND genre IS NOT NULL ORDER BY genre");
$all_genres = [];
while ($g = mysqli_fetch_assoc($genres_res)) $all_genres[] = $g['genre'];

// Fetch catalog — left join rating so we can show the user's own rating on each card
$catalog = mysqli_query($conn,
    "SELECT m.*, r.review_value
     FROM media m
     LEFT JOIN rating r ON m.media_id = r.media_id AND r.user_id = $user_id
     WHERE $where
     ORDER BY m.media_id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Browse</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body>

<div class="dash-wrapper">
  <div class="sidebar">
    <div class="logo">Watchlist</div>
    <div class="sidebar-nav-scroll">
      <form class="sidebar-search" action="browse.php" method="GET">
        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" title="Search">🔍</button>
      </form>
      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="mylist.php" class="nav-link">My list</a>
      <a href="browse.php" class="nav-link active">Browse</a>    </div>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1>Browse</h1>
        <p><?php echo $search !== '' ? 'Showing matches for "' . htmlspecialchars($search) . '"' : "Everything in the catalog — add anything to your list"; ?></p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <?php if (isset($_GET['added'])): ?>
      <div class="error-msg" style="background:rgba(111,207,151,0.12); color:var(--green); border-color:var(--green);">Added to your list.</div>
    <?php endif; ?>

    <!-- Genre filter bar -->
    <div class="genre-filter-bar">
      <a href="browse.php" class="genre-btn <?php echo empty($genre_filter) ? 'active' : ''; ?>">All</a>
      <?php foreach ($all_genres as $g): ?>
        <a href="browse.php?genre=<?php echo urlencode($g); ?>"
           class="genre-btn <?php echo $genre_filter === $g ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($g); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (mysqli_num_rows($catalog) === 0): ?>
      <p style="color:var(--text-gray); font-size:13px; margin-top:10px;">No titles found for this genre.</p>
    <?php else: ?>
      <div class="poster-grid">
        <?php while ($item = mysqli_fetch_assoc($catalog)): ?>
          <div class="poster-card">
            <div class="poster-card-image">
              <div class="poster-tag"><?php echo htmlspecialchars($item['media_type']); ?></div>
              <div class="poster-actions">
                <a href="browse.php?add=<?php echo $item['media_id']; ?><?php echo !empty($genre_filter) ? '&genre=' . urlencode($genre_filter) : ''; ?>"
                   title="Add to my list">Add</a>
              </div>
              <?php if (!empty($item['poster_image'])): ?>
                <img src="uploads/posters/<?php echo htmlspecialchars($item['poster_image']); ?>"
                     alt="<?php echo htmlspecialchars($item['title_name']); ?>">
              <?php else: ?>
                <div class="poster-fallback"><?php echo strtoupper(substr($item['title_name'], 0, 1)); ?></div>
              <?php endif; ?>
            </div>
            <div class="poster-card-caption">
              <div class="poster-title"><?php echo htmlspecialchars($item['title_name']); ?></div>
              <div class="poster-sub"><?php echo htmlspecialchars($item['genre']); ?> · <?php echo $item['release_year']; ?></div>
              <?php if (!empty($item['review_value'])): ?>
                <div class="poster-rating"><?php echo $item['review_value']; ?> <span style="color:var(--text-gray); font-weight:400;">your rating</span></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
