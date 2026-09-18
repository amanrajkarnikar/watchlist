<?php
require_once "auth.php";
require_once "db.php";
require_once "partials/helpers.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$back = "Location: friends.php";

if (isset($_GET['add'])) {
    $other = (int)$_GET['add'];
    if ($other && $other !== $user_id && db_all($conn, "SELECT 1 FROM users WHERE user_id = ?", "i", $other)) {
        [$st, $fid] = friend_status($conn, $user_id, $other);
        if ($st === 'none') {
            $s = mysqli_prepare($conn, "INSERT INTO friendships (requester_id, addressee_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($s, "ii", $user_id, $other);
            mysqli_stmt_execute($s);
        } elseif ($st === 'received') {
            $s = mysqli_prepare($conn, "UPDATE friendships SET status = 'accepted' WHERE friendship_id = ?");
            mysqli_stmt_bind_param($s, "i", $fid);
            mysqli_stmt_execute($s);
        }
    }
    header($back); exit();
}

if (isset($_GET['accept'])) {
    $s = mysqli_prepare($conn, "UPDATE friendships SET status = 'accepted' WHERE friendship_id = ? AND addressee_id = ? AND status = 'pending'");
    $fid = (int)$_GET['accept'];
    mysqli_stmt_bind_param($s, "ii", $fid, $user_id);
    mysqli_stmt_execute($s);
    header($back); exit();
}

if (isset($_GET['remove'])) {
    $s = mysqli_prepare($conn, "DELETE FROM friendships WHERE friendship_id = ? AND (requester_id = ? OR addressee_id = ?)");
    $fid = (int)$_GET['remove'];
    mysqli_stmt_bind_param($s, "iii", $fid, $user_id, $user_id);
    mysqli_stmt_execute($s);
    header($back); exit();
}

$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $results = db_all($conn,
        "SELECT user_id, first_name, last_name, username FROM users
         WHERE user_id != ? AND (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?) LIMIT 20",
        "isss", $user_id, $like, $like, $like);
}

$incoming = db_all($conn,
    "SELECT f.friendship_id, u.user_id, u.first_name, u.last_name, u.username
     FROM friendships f JOIN users u ON u.user_id = f.requester_id
     WHERE f.addressee_id = ? AND f.status = 'pending'", "i", $user_id);

$friends = db_all($conn,
    "SELECT f.friendship_id, u.user_id, u.first_name, u.last_name, u.username
     FROM friendships f JOIN users u ON u.user_id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
     WHERE f.status = 'accepted' AND (f.requester_id = ? OR f.addressee_id = ?)
     ORDER BY u.first_name", "iii", $user_id, $user_id, $user_id);

$outgoing = db_all($conn,
    "SELECT f.friendship_id, u.user_id, u.first_name, u.last_name, u.username
     FROM friendships f JOIN users u ON u.user_id = f.addressee_id
     WHERE f.requester_id = ? AND f.status = 'pending'", "i", $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Friends - Watchlist</title>
<link rel="stylesheet" href="CSS/stylesheet.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="details-page-wrapper">
  <div class="details-header">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <div class="main-header-right"><?php require "partials/user_menu.php"; ?></div>
  </div>

  <div class="figma-card details-section">
    <h3>Find people</h3>
    <form method="GET" action="friends.php" class="friend-search">
      <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Search by username or name">
      <button type="submit" class="btn-pill-solid" style="border:0; cursor:pointer;">Search</button>
    </form>
    <?php if ($q !== ''): ?>
      <?php if (!$results): ?><div class="platform-text" style="margin-top:14px;">No users found.</div><?php endif; ?>
      <?php foreach ($results as $u): [$st, $fid] = friend_status($conn, $user_id, $u['user_id']); ?>
        <div class="friend-row">
          <div><strong><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></strong> <span class="platform-text">@<?php echo h($u['username']); ?></span></div>
          <div>
            <?php if ($st === 'friends'): ?><a class="fr-btn" href="friend_profile.php?id=<?php echo $u['user_id']; ?>">View</a>
            <?php elseif ($st === 'sent'): ?><span class="platform-text">Request sent</span>
            <?php elseif ($st === 'received'): ?><a class="fr-btn primary" href="friends.php?accept=<?php echo $fid; ?>">Accept</a>
            <?php else: ?><a class="fr-btn primary" href="friends.php?add=<?php echo $u['user_id']; ?>">+ Add friend</a><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($incoming): ?>
  <div class="figma-card details-section">
    <h3>Friend requests</h3>
    <?php foreach ($incoming as $u): ?>
      <div class="friend-row">
        <div><strong><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></strong> <span class="platform-text">@<?php echo h($u['username']); ?></span></div>
        <div>
          <a class="fr-btn primary" href="friends.php?accept=<?php echo $u['friendship_id']; ?>">Accept</a>
          <a class="fr-btn" href="friends.php?remove=<?php echo $u['friendship_id']; ?>">Decline</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="figma-card details-section">
    <h3>Your friends (<?php echo count($friends); ?>)</h3>
    <?php if (!$friends): ?><div class="platform-text">No friends yet. Search for someone above.</div><?php endif; ?>
    <?php foreach ($friends as $u): ?>
      <div class="friend-row">
        <div><strong><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></strong> <span class="platform-text">@<?php echo h($u['username']); ?></span></div>
        <div>
          <a class="fr-btn primary" href="friend_profile.php?id=<?php echo $u['user_id']; ?>">View catalogue</a>
          <a class="fr-btn" href="friends.php?remove=<?php echo $u['friendship_id']; ?>" onclick="return confirm('Remove this friend?')">Remove</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($outgoing): ?>
  <div class="figma-card details-section">
    <h3>Sent requests</h3>
    <?php foreach ($outgoing as $u): ?>
      <div class="friend-row">
        <div><strong><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></strong> <span class="platform-text">@<?php echo h($u['username']); ?></span></div>
        <a class="fr-btn" href="friends.php?remove=<?php echo $u['friendship_id']; ?>">Cancel</a>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>