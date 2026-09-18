<?php
require_once "auth.php";
require_once "db.php";
require_once "partials/helpers.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$fid        = (int)($_GET['id'] ?? 0);

[$st] = friend_status($conn, $user_id, $fid);
if ($st !== 'friends') { header("Location: friends.php"); exit(); }

$friend = db_all($conn, "SELECT first_name, last_name, username FROM users WHERE user_id = ?", "i", $fid)[0];

$tabs = ['watched' => 'Watched', 'towatch' => 'To Watch', 'bookmarked' => 'Bookmarked', 'reviews' => 'Reviews'];
$tab  = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'watched';

$base = "SELECT m.media_id, m.title_name, m.release_year, m.poster_image, r.review_value
         FROM watchlist_entry we
         JOIN media m ON m.media_id = we.media_id
         LEFT JOIN rating r ON r.media_id = m.media_id AND r.user_id = we.user_id
         WHERE we.user_id = ? AND ";

if ($tab === 'reviews') {
    $items = db_all($conn,
        "SELECT r.*, m.title_name, m.release_year, m.poster_image
         FROM rating r JOIN media m ON m.media_id = r.media_id
         WHERE r.user_id = ? ORDER BY r.date_rated DESC", "i", $fid);
} else {
    $where = [
        'watched'    => "we.watch_status = 'Watched'",
        'towatch'    => "we.watch_status = 'To Watch'",
        'bookmarked' => "we.is_bookmarked = 1",
    ][$tab];
    $items = db_all($conn, $base . $where . " ORDER BY we.date_added DESC", "i", $fid);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo h($friend['first_name']); ?> - Watchlist</title>
<link rel="stylesheet" href="CSS/stylesheet.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="details-page-wrapper">
  <div class="details-header">
    <a href="friends.php" class="back-link">← Back to Friends</a>
    <div class="main-header-right"><?php require "partials/user_menu.php"; ?></div>
  </div>

  <div class="figma-card details-section">
    <div class="figma-title" style="font-size:32px;"><?php echo h($friend['first_name'] . ' ' . $friend['last_name']); ?></div>
    <div class="platform-text">@<?php echo h($friend['username']); ?></div>

    <div class="type-tabs" style="margin-top:20px;">
      <?php foreach ($tabs as $key => $label): ?>
        <a href="friend_profile.php?id=<?php echo $fid; ?>&tab=<?php echo $key; ?>" class="<?php echo $tab === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$items): ?><div class="platform-text">Nothing here yet.</div><?php endif; ?>

    <?php if ($tab === 'reviews'): ?>
      <?php foreach ($items as $rv): ?>
        <div class="review-item">
          <div class="review-top">
            <a href="media_details.php?id=<?php echo $rv['media_id']; ?>"><strong><?php echo h($rv['title_name']); ?></strong></a>
            <span class="platform-text"><?php echo h($rv['release_year']); ?></span>
            <?php echo stars_html($rv['review_value'], 16); ?>
          </div>
          <?php if ($rv['watched_on'] || $rv['watched_before']): ?>
            <div class="review-meta">
              <?php if ($rv['watched_on']): ?>Watched on <?php echo date('j M Y', strtotime($rv['watched_on'])); ?><?php endif; ?>
              <?php if ($rv['watched_before']): ?><?php echo $rv['watched_on'] ? ' · ' : ''; ?>↻ Watched before<?php echo $rv['watched_before_note'] ? ': ' . h($rv['watched_before_note']) : ''; ?><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($rv['review_note']): ?><div class="review-text"><?php echo nl2br(h($rv['review_note'])); ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fp-grid">
        <?php foreach ($items as $it): ?>
          <a class="fp-card" href="media_details.php?id=<?php echo $it['media_id']; ?>">
            <?php if (!empty($it['poster_image'])): ?>
              <img src="uploads/posters/<?php echo h($it['poster_image']); ?>" alt="">
            <?php else: ?>
              <div class="fp-fallback"><?php echo strtoupper(substr($it['title_name'], 0, 1)); ?></div>
            <?php endif; ?>
            <div class="fp-title"><?php echo h($it['title_name']); ?></div>
            <?php if ($it['review_value']): echo stars_html($it['review_value'], 14); endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>