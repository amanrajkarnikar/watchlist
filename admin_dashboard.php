<?php
require "admin_auth.php";
require "db.php";

$tab = ($_GET['tab'] ?? '') === 'featured' ? 'featured' : 'manage';
$genre_filter = $_GET['genre'] ?? '';
$search = trim($_GET['search'] ?? '');

if (isset($_GET['delete'])) {
    $media_id = (int) $_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM media WHERE media_id = ? AND added_by_user_id IS NULL");
    mysqli_stmt_bind_param($stmt, "i", $media_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_GET['toggle_featured'])) {
    $media_id = (int) $_GET['toggle_featured'];
    $stmt = mysqli_prepare($conn, "UPDATE media SET is_featured = NOT is_featured WHERE media_id = ? AND added_by_user_id IS NULL");
    mysqli_stmt_bind_param($stmt, "i", $media_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?tab=featured");
    exit();
}

// Pending request count, shown as a badge in the admin menu
$pending_count_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM media_requests WHERE status = 'pending'"));
$pending_count = $pending_count_row['total'];

// Genre list for the filter bar
$genres_res = mysqli_query($conn, "SELECT DISTINCT genre FROM media WHERE added_by_user_id IS NULL AND genre IS NOT NULL ORDER BY genre");
$all_genres = [];
while ($g = mysqli_fetch_assoc($genres_res)) $all_genres[] = $g['genre'];

$where = "added_by_user_id IS NULL";
if (!empty($genre_filter)) {
    $safe_genre = mysqli_real_escape_string($conn, $genre_filter);
    $where .= " AND genre = '$safe_genre'";
}
if ($search !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where .= " AND title_name LIKE '%$safe_search%'";
}

$catalog = mysqli_query($conn, "SELECT * FROM media WHERE $where ORDER BY media_id DESC");
$featured_count_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM media WHERE added_by_user_id IS NULL AND is_featured = 1"));
$featured_count = $featured_count_row['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Admin</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body class="admin-theme">

<div class="dash-wrapper">
  <div class="sidebar">
    <div class="logo">Watchlist Admin</div>
    <a href="admin_dashboard.php" class="nav-link <?php echo $tab === 'manage' ? 'active' : ''; ?>">Manage catalog</a>
    <a href="admin_dashboard.php?tab=featured" class="nav-link <?php echo $tab === 'featured' ? 'active' : ''; ?>">Featured releases</a>
    <a href="admin_requests.php" class="nav-link">Requests<?php if ($pending_count > 0) echo ' <span class="admin-menu-badge">' . $pending_count . '</span>'; ?></a>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1><?php echo $tab === 'featured' ? 'Featured releases' : 'Manage the catalog'; ?></h1>
        <p>
          <?php if ($search !== ''): ?>
            Showing matches for "<?php echo htmlspecialchars($search); ?>"
          <?php elseif ($tab === 'featured'): ?>
            Pick which titles show in the "Newly Released" spotlight (<?php echo $featured_count; ?> featured)
          <?php else: ?>
            Titles added here show up in Browse for every user
          <?php endif; ?>
        </p>
      </div>
      <div class="main-header-right">
        <a href="admin_add_title.php"><button class="btn-primary" style="width:auto; padding:10px 20px;">+ Add a new title</button></a>
        <?php require "partials/admin_menu.php"; ?>
      </div>
    </div>

    <div class="content-search">
      <form action="admin_dashboard.php" method="GET">
        <?php if ($tab === 'featured'): ?><input type="hidden" name="tab" value="featured"><?php endif; ?>
        <input type="text" name="search" placeholder="Search titles..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" title="Search">🔍</button>
      </form>
    </div>

    <div class="genre-filter-bar">
      <a href="admin_dashboard.php?<?php echo $tab === 'featured' ? 'tab=featured' : ''; ?><?php echo $search !== '' ? ($tab === 'featured' ? '&' : '') . 'search=' . urlencode($search) : ''; ?>" class="genre-btn <?php echo empty($genre_filter) ? 'active' : ''; ?>">All</a>
      <?php foreach ($all_genres as $g): ?>
        <a href="admin_dashboard.php?genre=<?php echo urlencode($g); ?><?php echo $tab === 'featured' ? '&tab=featured' : ''; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"
           class="genre-btn <?php echo $genre_filter === $g ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($g); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($tab === 'manage'): ?>

      <div class="panel">
        <h3>Catalog (<?php echo mysqli_num_rows($catalog); ?>)</h3>
        <?php if (mysqli_num_rows($catalog) === 0): ?>
          <p style="color:var(--text-gray); font-size:13px;">No titles found — <a href="admin_add_title.php" style="color:var(--accent);">add one</a>.</p>
        <?php else: ?>
          <div class="poster-grid">
            <?php while ($item = mysqli_fetch_assoc($catalog)): ?>
              <div class="poster-card">
                <div class="poster-card-image">
                  <div class="poster-tag"><?php echo htmlspecialchars($item['media_type']); ?></div>
                  <div class="poster-actions">
                    <a href="admin_add_title.php?edit=<?php echo $item['media_id']; ?>" title="Edit">Edit</a>
                    <a href="admin_dashboard.php?delete=<?php echo $item['media_id']; ?>" title="Delete" onclick="return confirm('Delete this title for every user?')">🗑️</a>
                  </div>
                  <?php if (!empty($item['poster_image'])): ?>
                    <img src="uploads/posters/<?php echo htmlspecialchars($item['poster_image']); ?>" alt="<?php echo htmlspecialchars($item['title_name']); ?>">
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

    <?php else: ?>

      <?php if (mysqli_num_rows($catalog) === 0): ?>
        <p style="color:var(--text-gray); font-size:13px;">No titles found — <a href="admin_add_title.php" style="color:var(--accent);">add one</a> in Manage catalog first.</p>
      <?php else: ?>
        <div class="poster-grid">
          <?php while ($item = mysqli_fetch_assoc($catalog)): ?>
            <div class="poster-card">
              <div class="poster-card-image">
                <div class="poster-tag"><?php echo htmlspecialchars($item['media_type']); ?></div>
                <?php if (!empty($item['poster_image'])): ?>
                  <img src="uploads/posters/<?php echo htmlspecialchars($item['poster_image']); ?>" alt="<?php echo htmlspecialchars($item['title_name']); ?>">
                <?php else: ?>
                  <div class="poster-fallback"><?php echo strtoupper(substr($item['title_name'], 0, 1)); ?></div>
                <?php endif; ?>
              </div>
              <div class="poster-card-caption">
                <div class="poster-title"><?php echo htmlspecialchars($item['title_name']); ?></div>
                <div class="poster-sub"><?php echo htmlspecialchars($item['genre']); ?> · <?php echo $item['release_year']; ?></div>
                <a href="admin_dashboard.php?toggle_featured=<?php echo $item['media_id']; ?>&tab=featured"
                   class="toggle-status-btn <?php echo $item['is_featured'] ? 'is-watched' : ''; ?>">
                  <?php echo $item['is_featured'] ? 'Featured — remove' : 'Feature this'; ?>
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

</body>
</html>