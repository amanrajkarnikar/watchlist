<?php
require "auth.php";
require "db.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

$type_filter = $_GET['type'] ?? '';
$safe_type = null;
if (in_array($type_filter, ['Movie', 'TV', 'Anime'])) {
    $safe_type = mysqli_real_escape_string($conn, $type_filter);
}

// Featured / newly released — curated by the admin (media.is_featured)
$featured_sql = "SELECT m.media_id, m.title_name, m.genre, m.release_year, m.media_type, m.poster_image,
                         we.entry_id AS in_list
                  FROM media m
                  LEFT JOIN watchlist_entry we ON we.media_id = m.media_id AND we.user_id = $user_id
                  WHERE m.added_by_user_id IS NULL AND m.is_featured = 1
                  ORDER BY m.media_id DESC
                  LIMIT 6";
$featured_result = mysqli_query($conn, $featured_sql);
$featured_items = [];
if ($featured_result) {
    while ($row = mysqli_fetch_assoc($featured_result)) {
        $featured_items[] = $row;
    }
}

// Recent movie (right-hand panel) — only titles the user has actually rated
$recent_where = "we.user_id = $user_id AND r.review_value IS NOT NULL";
if ($safe_type) {
    $recent_where .= " AND m.media_type = '$safe_type'";
}
$recent_sql = "SELECT m.title_name, m.genre, m.release_year, m.media_type, m.poster_image,
                      we.entry_id, we.watch_status,
                      r.review_value
               FROM watchlist_entry we
               JOIN media m ON we.media_id = m.media_id
               JOIN rating r ON m.media_id = r.media_id AND r.user_id = $user_id
               WHERE $recent_where
               ORDER BY r.date_rated DESC
               LIMIT 4";
$recent_result = mysqli_query($conn, $recent_sql);

// You might like — catalog titles the user hasn't added yet
$suggest_where = "m.added_by_user_id IS NULL AND m.media_id NOT IN (SELECT media_id FROM watchlist_entry WHERE user_id = $user_id)";
if ($safe_type) {
    $suggest_where .= " AND m.media_type = '$safe_type'";
}
$suggest_sql = "SELECT m.*, r.review_value
                 FROM media m
                 LEFT JOIN rating r ON m.media_id = r.media_id AND r.user_id = $user_id
                 WHERE $suggest_where
                 ORDER BY m.media_id DESC
                 LIMIT 8";
$suggest_result = mysqli_query($conn, $suggest_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Dashboard</title>
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

      <a href="dashboard.php" class="nav-link active">Dashboard</a>
      <a href="mylist.php" class="nav-link">My list</a>
      <a href="browse.php" class="nav-link">Browse</a>

    </div>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1>Good evening, <?php echo htmlspecialchars($first_name); ?></h1>
        <p>Here's your watchlist overview</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <div class="type-tabs">
      <a href="dashboard.php" class="<?php echo $type_filter === '' ? 'active' : ''; ?>">All</a>
      <a href="dashboard.php?type=Movie" class="<?php echo $type_filter === 'Movie' ? 'active' : ''; ?>">Movies</a>
      <a href="dashboard.php?type=TV" class="<?php echo $type_filter === 'TV' ? 'active' : ''; ?>">TV Shows</a>
      <a href="dashboard.php?type=Anime" class="<?php echo $type_filter === 'Anime' ? 'active' : ''; ?>">Anime</a>
    </div>

    <div class="dash-top-row">
      <!-- Spotlight: newly released titles, curated by the admin -->
      <div class="spotlight" id="spotlight">
        <?php if (empty($featured_items)): ?>
          <div class="spotlight-empty">No newly released titles yet — check back soon!</div>
        <?php else: ?>
          <?php foreach ($featured_items as $index => $item): ?>
            <div class="spotlight-slide <?php echo $index === 0 ? 'active' : ''; ?>"
                 data-index="<?php echo $index; ?>"
                 style="<?php echo !empty($item['poster_image']) ? "background-image:url('uploads/posters/" . htmlspecialchars($item['poster_image']) . "');" : ''; ?>">
              <div class="spotlight-tag">Newly Released</div>
              <div class="spotlight-overlay">
                <h2><?php echo htmlspecialchars($item['title_name']); ?></h2>
                <p class="spotlight-meta">
                  <?php echo htmlspecialchars($item['genre']); ?> · <?php echo htmlspecialchars($item['release_year']); ?> · <?php echo htmlspecialchars($item['media_type']); ?>
                </p>
                <div class="spotlight-buttons">
                  <?php if ($item['in_list']): ?>
                    <a href="mylist.php"><button class="btn-primary">In My List</button></a>
                  <?php else: ?>
                    <a href="browse.php?add=<?php echo $item['media_id']; ?>"><button class="btn-primary">Add to My List</button></a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (count($featured_items) > 1): ?>
            <button type="button" class="spotlight-nav prev" onclick="spotlightMove(-1)">&#8249;</button>
            <button type="button" class="spotlight-nav next" onclick="spotlightMove(1)">&#8250;</button>
            <div class="spotlight-dots">
              <?php foreach ($featured_items as $index => $item): ?>
                <button type="button" class="spotlight-dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="spotlightGoTo(<?php echo $index; ?>)"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="panel recent-panel">
        <h3>Recent Movie</h3>
        <?php if (mysqli_num_rows($recent_result) === 0): ?>
          <p style="color:var(--text-gray); font-size:13px;">Rate something in My List to see it here.</p>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
            <div class="media-row">
              <?php if (!empty($row['poster_image'])): ?>
                <img class="media-thumb" src="uploads/posters/<?php echo htmlspecialchars($row['poster_image']); ?>" alt="<?php echo htmlspecialchars($row['title_name']); ?>">
              <?php else: ?>
                <div class="media-thumb" style="background:var(--accent);"><?php echo strtoupper(substr($row['title_name'], 0, 1)); ?></div>
              <?php endif; ?>
              <div class="media-info">
                <div class="title"><?php echo htmlspecialchars($row['title_name']); ?></div>
                <div class="sub"><?php echo htmlspecialchars($row['genre']); ?> · <?php echo htmlspecialchars($row['media_type']); ?></div>
                <div class="rate-stars">
                  <?php for ($star = 1; $star <= 5; $star++): ?>
                    <span class="rate-star <?php echo $star <= (int) $row['review_value'] ? 'filled' : ''; ?>">★</span>
                  <?php endfor; ?>
                </div>
              </div>
              <span class="badge <?php echo $row['watch_status'] === 'Watched' ? 'watched' : 'towatch'; ?>">
                <?php echo $row['watch_status'] === 'Watched' ? 'Watched' : 'To Watch'; ?>
              </span>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- You might like -->
    <div class="section-header">
      <h3>🎬 You might like</h3>
      <a href="browse.php">See all movies</a>
    </div>
    <?php if (mysqli_num_rows($suggest_result) === 0): ?>
      <p style="color:var(--text-gray); font-size:13px;">You're all caught up with the catalog!</p>
    <?php else: ?>
      <div class="poster-grid">
        <?php while ($item = mysqli_fetch_assoc($suggest_result)): ?>
          <div class="poster-card">
            <div class="poster-card-image">
              <div class="poster-tag"><?php echo htmlspecialchars($item['media_type']); ?></div>
              <div class="poster-actions">
                <a href="browse.php?add=<?php echo $item['media_id']; ?>" title="Add to my list">Add</a>
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
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
(function () {
  var slides = document.querySelectorAll('.spotlight-slide');
  var dots = document.querySelectorAll('.spotlight-dot');
  var current = 0;

  window.spotlightGoTo = function (index) {
    if (!slides.length) return;
    slides[current].classList.remove('active');
    if (dots.length) dots[current].classList.remove('active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('active');
    if (dots.length) dots[current].classList.add('active');
  };

  window.spotlightMove = function (delta) {
    spotlightGoTo(current + delta);
  };
})();
</script>

</body>
</html>
