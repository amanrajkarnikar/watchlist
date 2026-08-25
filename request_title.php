<?php
require "auth.php";
require "db.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title_name']);
    $year  = $_POST['release_year'];
    $genre = trim($_POST['genre']);
    $type  = $_POST['media_type'];

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
        $stmt = mysqli_prepare($conn, "INSERT INTO media_requests (user_id, title_name, release_year, genre, media_type, poster_image) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isisss", $user_id, $title, $year, $genre, $type, $poster_filename);
        mysqli_stmt_execute($stmt);

        header("Location: request_title.php?sent=1");
        exit();
    }
}

$my_requests = mysqli_prepare($conn, "SELECT * FROM media_requests WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($my_requests, "i", $user_id);
mysqli_stmt_execute($my_requests);
$my_requests_result = mysqli_stmt_get_result($my_requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Request a title</title>
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
      <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
      <a href="mylist.php" class="nav-link">📋 My list</a>
      <a href="browse.php" class="nav-link">🔍 Browse</a>
            <a href="friends.php" class="nav-link">👥 Friends</a>
    </div>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1>Request a title</h1>
        <p>Can't find it in Browse? Ask the admin to add it</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/user_menu.php"; ?>
      </div>
    </div>

    <?php if (isset($_GET['sent'])): ?>
      <div class="error-msg" style="background:rgba(111,207,151,0.12); color:var(--green); border-color:var(--green);">Request sent to the admin.</div>
    <?php endif; ?>

    <div class="panel" style="max-width:480px; margin-bottom:26px;">
      <h3>New request</h3>
      <form action="request_title.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title_name" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Release year</label>
            <input type="number" name="release_year">
          </div>
          <div class="form-group">
            <label>Genre</label>
            <input type="text" name="genre">
          </div>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="media_type">
            <option value="Movie">Movie</option>
            <option value="TV">TV Show</option>
            <option value="Anime">Anime</option>
          </select>
        </div>
        <div class="form-group">
          <label>Poster image (optional)</label>
          <input type="file" name="poster_image" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <button type="submit" class="btn-primary">Send request</button>
      </form>
    </div>

    <div class="panel">
      <h3>Your requests</h3>
      <?php if (mysqli_num_rows($my_requests_result) === 0): ?>
        <p style="color:var(--text-gray); font-size:13px;">You haven't requested anything yet.</p>
      <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($my_requests_result)): ?>
          <div class="media-row">
            <div class="media-thumb" style="background:var(--accent); overflow:hidden; padding:0;">
              <?php if (!empty($row['poster_image'])): ?>
                <img src="uploads/posters/<?php echo htmlspecialchars($row['poster_image']); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
              <?php else: ?>
                <?php echo strtoupper(substr($row['title_name'], 0, 1)); ?>
              <?php endif; ?>
            </div>
            <div class="media-info">
              <div class="title"><?php echo htmlspecialchars($row['title_name']); ?></div>
              <div class="sub"><?php echo htmlspecialchars($row['genre']); ?> · <?php echo htmlspecialchars($row['media_type']); ?></div>
            </div>
            <span class="badge <?php echo $row['status'] === 'approved' ? 'watched' : ($row['status'] === 'rejected' ? '' : 'towatch'); ?>"
                  style="<?php echo $row['status'] === 'rejected' ? 'background:rgba(193,68,60,0.15); color:var(--red);' : ''; ?>">
              <?php echo ucfirst($row['status']); ?>
            </span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>