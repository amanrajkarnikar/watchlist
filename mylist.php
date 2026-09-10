<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

if (isset($_GET['toggle'])) {
    $entry_id = (int) $_GET['toggle'];
    $stmt = mysqli_prepare($conn, "SELECT watch_status FROM watchlist_entry WHERE entry_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) {
        $new_status = $row['watch_status'] === 'Watched' ? 'To Watch' : 'Watched';
        $update = mysqli_prepare($conn, "UPDATE watchlist_entry SET watch_status = ? WHERE entry_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($update, "sii", $new_status, $entry_id, $user_id);
        mysqli_stmt_execute($update);
    }
    header("Location: mylist.php" . (!empty($_GET['back']) ? "?filter=" . urlencode($_GET['back']) : ""));
    exit();
}

if (isset($_GET['bookmark'])) {
    $entry_id = (int) $_GET['bookmark'];
    $stmt = mysqli_prepare($conn, "UPDATE watchlist_entry SET is_bookmarked = NOT is_bookmarked WHERE entry_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: mylist.php" . (!empty($_GET['back']) ? "?filter=" . urlencode($_GET['back']) : ""));
    exit();
}

if (isset($_GET['favorite'])) {
    $entry_id = (int) $_GET['favorite'];
    $stmt = mysqli_prepare($conn, "UPDATE watchlist_entry SET is_favorite = NOT is_favorite WHERE entry_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: mylist.php" . (!empty($_GET['back']) ? "?filter=" . urlencode($_GET['back']) : ""));
    exit();
}

if (isset($_GET['rate'])) {
    $media_id = (int) $_GET['rate'];
    $value    = (int) ($_GET['value'] ?? 0);

    if ($value >= 1 && $value <= 5) {
        $check = mysqli_prepare($conn, "SELECT rating_id FROM rating WHERE user_id = ? AND media_id = ?");
        mysqli_stmt_bind_param($check, "ii", $user_id, $media_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $update = mysqli_prepare($conn, "UPDATE rating SET review_value = ? WHERE user_id = ? AND media_id = ?");
            mysqli_stmt_bind_param($update, "iii", $value, $user_id, $media_id);
            mysqli_stmt_execute($update);
        } else {
            $insert = mysqli_prepare($conn, "INSERT INTO rating (user_id, media_id, review_value) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insert, "iii", $user_id, $media_id, $value);
            mysqli_stmt_execute($insert);
        }
    }
    header("Location: mylist.php" . (!empty($_GET['back']) ? "?filter=" . urlencode($_GET['back']) : ""));
    exit();
}

if (isset($_GET['delete'])) {
    $entry_id = (int) $_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM watchlist_entry WHERE entry_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $entry_id, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: mylist.php" . (!empty($_GET['back']) ? "?filter=" . urlencode($_GET['back']) : ""));
    exit();
}

$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? 'recent';
$where  = "we.user_id = $user_id";
if ($filter === 'watched')    $where .= " AND we.watch_status = 'Watched'";
if ($filter === 'towatch')    $where .= " AND we.watch_status = 'To Watch'";
if ($filter === 'bookmarked') $where .= " AND we.is_bookmarked = 1";
if ($filter === 'favorites')  $where .= " AND we.is_favorite = 1";
if ($search !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where .= " AND m.title_name LIKE '%$safe_search%'";
}

$sort_map = [
    'recent'    => 'we.date_added DESC',
    'rating'    => 'r.review_value DESC, we.date_added DESC',
    'year'      => 'm.release_year DESC',
    'title'     => 'm.title_name ASC',
];
$order_by = $sort_map[$sort] ?? $sort_map['recent'];

$page_titles = [
    ''           => ['My list',     'Everything you\'re tracking'],
    'watched'    => ['Watched',     'Titles you\'ve finished'],
    'towatch'    => ['To Watch',    'Your backlog'],
    'bookmarked' => ['Bookmarked',  'Saved for later'],
    'favorites'  => ['Favorites',   'Titles you love'],
];
[$page_title, $page_sub] = $page_titles[$filter] ?? $page_titles[''];
if ($search !== '') {
    $page_title = 'Search results';
    $page_sub   = 'Showing matches for "' . $search . '"';
}

$sql = "SELECT we.entry_id, we.watch_status, we.is_bookmarked, we.is_favorite,
               m.media_id, m.title_name, m.genre, m.release_year, m.media_type, m.poster_image,
               r.review_value
        FROM watchlist_entry we
        JOIN media m ON we.media_id = m.media_id
        LEFT JOIN rating r ON m.media_id = r.media_id AND r.user_id = $user_id
        WHERE $where
        ORDER BY $order_by";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - <?php echo $page_title; ?></title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body>

<div class="dash-wrapper">
  <div class="sidebar">
    <a href="dashboard.php" class="logo" style="text-decoration:none;">Watchlist</a>
    <div class="sidebar-nav-scroll">
      <form class="sidebar-search" action="browse.php" method="GET">
        <input type="text" name="search" placeholder="Search...">
        <button type="submit" title="Search">🔍</button>
      </form>
      <a href="dashboard.php" class="nav-link">📊Dashboard</a>
      <a href="mylist.php" class="nav-link <?php echo $filter === '' ? 'active' : ''; ?>">📋My list</a>
      <a href="browse.php" class="nav-link">🔍Browse</a>
      <a href="friends.php" class="nav-link">👥Friends</a>
    </div>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1><?php echo $page_title; ?></h1>
        <p><?php echo $page_sub; ?></p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <div class="genre-filter-bar" style="justify-content:space-between; display:flex; flex-wrap:wrap; gap:10px;">
      <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <a href="mylist.php?sort=<?php echo urlencode($sort); ?>" class="genre-btn <?php echo $filter === '' ? 'active' : ''; ?>">All</a>
        <a href="mylist.php?filter=watched&sort=<?php echo urlencode($sort); ?>" class="genre-btn <?php echo $filter === 'watched' ? 'active' : ''; ?>">Watched</a>
        <a href="mylist.php?filter=towatch&sort=<?php echo urlencode($sort); ?>" class="genre-btn <?php echo $filter === 'towatch' ? 'active' : ''; ?>">To watch</a>
        <a href="mylist.php?filter=bookmarked&sort=<?php echo urlencode($sort); ?>" class="genre-btn <?php echo $filter === 'bookmarked' ? 'active' : ''; ?>">Bookmarked</a>
        <a href="mylist.php?filter=favorites&sort=<?php echo urlencode($sort); ?>" class="genre-btn <?php echo $filter === 'favorites' ? 'active' : ''; ?>">Favorites</a>
      </div>
      <select onchange="location = 'mylist.php?filter=<?php echo urlencode($filter); ?>&sort=' + this.value;" style="background:var(--bg-dark); color:var(--text-white); border:1px solid var(--border-color, #333); border-radius:8px; padding:8px 12px;">
        <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Recently added</option>
        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>My rating</option>
        <option value="year" <?php echo $sort === 'year' ? 'selected' : ''; ?>>Release year</option>
        <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
      </select>
    </div>

    <?php if (mysqli_num_rows($result) === 0): ?>
      <p style="color:var(--text-gray); font-size:13px;">Nothing here yet — <a href="browse.php" style="color:var(--accent);">browse the catalog</a> to add something.</p>
    <?php else: ?>
      <div class="poster-grid">
        <?php while ($row = mysqli_fetch_assoc($result)):
          $back = urlencode($filter);
        ?>
          <div class="poster-card">
            
            <div class="poster-card-image">
              <div class="poster-tag <?php echo $row['watch_status'] === 'Watched' ? 'tag-watched' : 'tag-towatch'; ?>" style="z-index: 10;">
                <?php echo $row['watch_status'] === 'Watched' ? 'Watched' : 'To Watch'; ?>
              </div>

              <div class="poster-actions" style="z-index: 10;">
                <a href="mylist.php?bookmark=<?php echo $row['entry_id']; ?>&back=<?php echo $back; ?>"
                   title="<?php echo $row['is_bookmarked'] ? 'Remove bookmark' : 'Bookmark'; ?>">
                  <?php echo $row['is_bookmarked'] ? '🔖' : '🏷️'; ?>
                </a>
                <a href="mylist.php?favorite=<?php echo $row['entry_id']; ?>&back=<?php echo $back; ?>"
                   title="<?php echo $row['is_favorite'] ? 'Remove favorite' : 'Favorite'; ?>">
                  <?php echo $row['is_favorite'] ? '❤️' : '🤍'; ?>
                </a>
                <a href="mylist.php?delete=<?php echo $row['entry_id']; ?>&back=<?php echo $back; ?>"
                   title="Remove from list"
                   onclick="return confirm('Remove this entry?');">🗑️</a>
              </div>

              <a href="media_details.php?id=<?php echo $row['media_id']; ?>" style="display:block; position:absolute; inset:0; z-index:1;">
                <?php if (!empty($row['poster_image'])): ?>
                  <img src="uploads/posters/<?php echo htmlspecialchars($row['poster_image']); ?>"
                       alt="<?php echo htmlspecialchars($row['title_name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                  <div class="poster-fallback" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;"><?php echo strtoupper(substr($row['title_name'], 0, 1)); ?></div>
                <?php endif; ?>
              </a>
            </div>

            <div class="poster-card-caption">
              <a href="media_details.php?id=<?php echo $row['media_id']; ?>" style="text-decoration:none; color:inherit;">
                <div class="poster-title"><?php echo htmlspecialchars($row['title_name']); ?></div>
              </a>
              <div class="poster-sub"><?php echo htmlspecialchars($row['genre']); ?> · <?php echo $row['release_year']; ?></div>
              <div class="rate-stars">
                <?php for ($star = 1; $star <= 5; $star++): ?>
                  <a href="mylist.php?rate=<?php echo $row['media_id']; ?>&value=<?php echo $star; ?>&back=<?php echo $back; ?>"
                     class="rate-star <?php echo $star <= (int) $row['review_value'] ? 'filled' : ''; ?>"
                     title="Rate <?php echo $star; ?> star<?php echo $star > 1 ? 's' : ''; ?>">★</a>
                <?php endfor; ?>
              </div>
              <a href="mylist.php?toggle=<?php echo $row['entry_id']; ?>&back=<?php echo $back; ?>"
                 class="toggle-status-btn <?php echo $row['watch_status'] === 'Watched' ? 'is-watched' : 'is-towatch'; ?>">
                <?php echo $row['watch_status'] === 'Watched' ? 'Mark To Watch' : 'Mark Watched'; ?>
              </a>
            </div>

          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>