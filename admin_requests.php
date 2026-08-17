<?php
require "admin_auth.php";
require "db.php";

$requests = mysqli_query($conn, "
    SELECT mr.*, u.username, u.first_name
    FROM media_requests mr
    JOIN users u ON mr.user_id = u.user_id
    ORDER BY mr.status = 'pending' DESC, mr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist - Requests</title>
<link rel="stylesheet" href="CSS/stylesheet.css">
</head>
<body class="admin-theme">

<div class="dash-wrapper">
  <div class="sidebar">
    <div class="logo">Watchlist Admin</div>
    <a href="admin_dashboard.php" class="nav-link">Manage catalog</a>
    <a href="admin_dashboard.php?tab=featured" class="nav-link">Featured releases</a>
    <a href="admin_requests.php" class="nav-link active">Requests</a>
  </div>

  <div class="main-content">
    <div class="main-header">
      <div>
        <h1>Title requests</h1>
        <p>Approve to add straight to the catalog, or reject</p>
      </div>
      <div class="main-header-right">
        <?php require "partials/admin_menu.php"; ?>
      </div>
    </div>

    <div class="panel">
      <?php if (mysqli_num_rows($requests) === 0): ?>
        <p style="color:var(--text-gray); font-size:13px;">No requests yet.</p>
      <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($requests)): ?>
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
              <div class="sub"><?php echo htmlspecialchars($row['genre']); ?> · <?php echo htmlspecialchars($row['media_type']); ?> · requested by <?php echo htmlspecialchars($row['first_name']); ?></div>
            </div>
            <?php if ($row['status'] === 'pending'): ?>
              <div class="request-actions">
                <a href="admin_process_request.php?id=<?php echo $row['id']; ?>&action=approve"
                   class="approve-link"
                   onclick="return confirm('Add this to the catalog?')">Approve</a>
                <a href="admin_process_request.php?id=<?php echo $row['id']; ?>&action=reject"
                   class="reject-link"
                   onclick="return confirm('Reject this request?')">Reject</a>
              </div>
            <?php else: ?>
              <span class="badge <?php echo $row['status'] === 'approved' ? 'watched' : ''; ?>"
                    style="<?php echo $row['status'] === 'rejected' ? 'background:rgba(193,68,60,0.15); color:var(--red);' : ''; ?>">
                <?php echo ucfirst($row['status']); ?>
              </span>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>