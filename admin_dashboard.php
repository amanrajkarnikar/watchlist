<?php
require "admin_auth.php";
require "db.php";

$tab = ($_GET['tab'] ?? '') === 'featured' ? 'featured' : 'manage';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title_name']);
    $year  = $_POST['release_year'];
    $genre = trim($_POST['genre']);
    $type  = $_POST['media_type'];
    $editing_id = $_POST['media_id'] ?? '';

    $poster_filename = null;

    if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['poster_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $poster_filename = uniqid('poster_') . '.' . $ext;
            move_uploaded_file($_FILES['poster_image']['tmp_name'], "uploads/posters/" . $poster_filename);
        }
    }

    if (!empty($title)) {
        if (!empty($editing_id)) {
            if ($poster_filename) {
                $stmt = mysqli_prepare($conn, "UPDATE media SET title_name = ?, release_year = ?, genre = ?, media_type = ?, poster_image = ? WHERE media_id = ? AND added_by_user_id IS NULL");
                mysqli_stmt_bind_param($stmt, "sisssi", $title, $year, $genre, $type, $poster_filename, $editing_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE media SET title_name = ?, release_year = ?, genre = ?, media_type = ? WHERE media_id = ? AND added_by_user_id IS NULL");
                mysqli_stmt_bind_param($stmt, "sissi", $title, $year, $genre, $type, $editing_id);
            }
            mysqli_stmt_execute($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO media (title_name, release_year, genre, media_type, poster_image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sisss", $title, $year, $genre, $type, $poster_filename);
            mysqli_stmt_execute($stmt);
        }
    }

    header("Location: admin_dashboard.php");
    exit();
}

$editing_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM media WHERE media_id = ? AND added_by_user_id IS NULL");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $editing_item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$catalog = mysqli_query($conn, "SELECT * FROM media WHERE added_by_user_id IS NULL ORDER BY media_id DESC");
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
<body>

<div class="dash-wrapper">
  <div class="sidebar">
    <div class="logo">Watchlist Admin</div>
    <a href="admin_dashboard.php" class="nav-link <?php echo $tab === 'manage' ? 'active' : ''; ?>">Manage catalog</a>
    <a href="admin_dashboard.php?tab=featured" class="nav-link <?php echo $tab === 'featured' ? 'active' : ''; ?>">Featured releases</a>
    <div class="sidebar-user">
      <div class="avatar">A</div>
      <div>
        <div class="name">Admin</div>
        <div class="username"><a href="admin_logout.php">Log out</a></div>
      </div>
    </div>
  </div>

  <div class="main-content">

    <?php if ($tab === 'manage'): ?>

      <div class="main-header">
        <div>
          <h1>Manage the catalog</h1>
          <p>Titles added here show up in Browse for every user</p>
        </div>
      </div>

      <div class="panel" style="max-width:480px; margin-bottom:26px;">
        <h3><?php echo $editing_item ? 'Edit title' : 'Add a new title'; ?></h3>

        <form action="admin_dashboard.php" method="POST" enctype="multipart/form-data">
          <?php if ($editing_item): ?>
            <input type="hidden" name="media_id" value="<?php echo $editing_item['media_id']; ?>">
          <?php endif; ?>

          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title_name" required
                   value="<?php echo $editing_item ? htmlspecialchars($editing_item['title_name']) : ''; ?>">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Release year</label>
              <input type="number" name="release_year"
                     value="<?php echo $editing_item ? $editing_item['release_year'] : ''; ?>">
            </div>
            <div class="form-group">
              <label>Genre</label>
              <input type="text" name="genre"
                     value="<?php echo $editing_item ? htmlspecialchars($editing_item['genre']) : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Type</label>
            <?php $current_type = $editing_item ? $editing_item['media_type'] : 'Movie'; ?>
            <select name="media_type">
              <option value="Movie" <?php if ($current_type === 'Movie') echo 'selected'; ?>>Movie</option>
              <option value="TV" <?php if ($current_type === 'TV') echo 'selected'; ?>>TV Show</option>
              <option value="Anime" <?php if ($current_type === 'Anime') echo 'selected'; ?>>Anime</option>
            </select>
          </div>

          <div class="form-group">
            <label>Poster image<?php echo $editing_item ? ' (leave empty to keep current)' : ''; ?></label>
            <input type="file" name="poster_image" accept=".jpg,.jpeg,.png,.webp">
          </div>

          <button type="submit" class="btn-primary">
            <?php echo $editing_item ? 'Save changes' : 'Add title'; ?>
          </button>
          <?php if ($editing_item): ?>
            <a href="admin_dashboard.php" style="display:block; text-align:center; margin-top:10px; color:var(--text-gray); font-size:13px;">Cancel edit</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="panel">
        <h3>Catalog (<?php echo mysqli_num_rows($catalog); ?>)</h3>
        <?php if (mysqli_num_rows($catalog) === 0): ?>
          <p style="color:var(--text-gray); font-size:13px;">No titles yet — add one above.</p>
        <?php else: ?>
          <div class="poster-grid">
            <?php while ($item = mysqli_fetch_assoc($catalog)): ?>
              <div class="poster-card">
                <div class="poster-card-image">
                  <div class="poster-tag"><?php echo htmlspecialchars($item['media_type']); ?></div>
                  <div class="poster-actions">
                    <a href="admin_dashboard.php?edit=<?php echo $item['media_id']; ?>" title="Edit">Edit</a>
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

      <div class="main-header">
        <div>
          <h1>Featured releases</h1>
          <p>Pick which titles show in the "Newly Released" spotlight on every user's dashboard (<?php echo $featured_count; ?> featured)</p>
        </div>
      </div>

      <?php
        mysqli_data_seek($catalog, 0);
      ?>
      <?php if (mysqli_num_rows($catalog) === 0): ?>
        <p style="color:var(--text-gray); font-size:13px;">No titles in the catalog yet — add one in Manage catalog first.</p>
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
