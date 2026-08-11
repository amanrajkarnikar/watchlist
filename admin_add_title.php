<?php
require "admin_auth.php";
require "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title_name']);
    $year  = $_POST['release_year'];
    $genre = trim($_POST['genre']);
    $type  = $_POST['media_type'];
    $editing_id = $_POST['media_id'] ?? '';

    $synopsis  = trim($_POST['synopsis'] ?? '') ?: null;
    $platforms = trim($_POST['platforms'] ?? '') ?: null;
    $trailer   = trim($_POST['trailer_url'] ?? '');
    $trailer   = preg_match('~^https?://~i', $trailer) ? $trailer : null;

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
                $stmt = mysqli_prepare($conn, "UPDATE media SET title_name = ?, release_year = ?, genre = ?, media_type = ?, synopsis = ?, platforms = ?, trailer_url = ?, poster_image = ? WHERE media_id = ? AND added_by_user_id IS NULL");
                mysqli_stmt_bind_param($stmt, "sissssssi", $title, $year, $genre, $type, $synopsis, $platforms, $trailer, $poster_filename, $editing_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE media SET title_name = ?, release_year = ?, genre = ?, media_type = ?, synopsis = ?, platforms = ?, trailer_url = ? WHERE media_id = ? AND added_by_user_id IS NULL");
                mysqli_stmt_bind_param($stmt, "sisssssi", $title, $year, $genre, $type, $synopsis, $platforms, $trailer, $editing_id);
            }
            mysqli_stmt_execute($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO media (title_name, release_year, genre, media_type, synopsis, platforms, trailer_url, poster_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sissssss", $title, $year, $genre, $type, $synopsis, $platforms, $trailer, $poster_filename);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - <?php echo $editing_item ? 'Edit title' : 'Add a new title'; ?></title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body class="admin-theme">

<div class="dash-wrapper">
  <div class="sidebar">
    <div class="logo">Watchlist Admin</div>
    <a href="admin_dashboard.php" class="nav-link">Manage catalog</a>
    <a href="admin_dashboard.php?tab=featured" class="nav-link">Featured releases</a>
    <a href="admin_requests.php" class="nav-link">Requests</a>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1><?php echo $editing_item ? 'Edit title' : 'Add a new title'; ?></h1>
        <p>This shows up in Browse for every user once saved</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/admin_menu.php"; ?>
      </div>
    </div>

    <div class="panel" style="max-width:480px;">
      <form action="admin_add_title.php" method="POST" enctype="multipart/form-data">
        <?php if ($editing_item): ?>
          <input type="hidden" name="media_id" value="<?php echo $editing_item['media_id']; ?>">
        <?php endif; ?>

                <div class="form-group">
          <label>Description / synopsis</label>
          <textarea name="synopsis" rows="4"><?php echo $editing_item ? htmlspecialchars($editing_item['synopsis'] ?? '') : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label>Platforms (comma separated)</label>
          <input type="text" name="platforms" placeholder="Netflix, Prime Video"
                 value="<?php echo $editing_item ? htmlspecialchars($editing_item['platforms'] ?? '') : ''; ?>">
        </div>

        <div class="form-group">
          <label>Trailer URL (YouTube)</label>
          <input type="text" name="trailer_url" placeholder="https://www.youtube.com/watch?v=..."
                 value="<?php echo $editing_item ? htmlspecialchars($editing_item['trailer_url'] ?? '') : ''; ?>">
        </div>
        
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
        <a href="admin_dashboard.php" style="display:block; text-align:center; margin-top:10px; color:var(--text-gray); font-size:13px;">Cancel</a>
      </form>
    </div>
  </div>
</div>

</body>
</html>