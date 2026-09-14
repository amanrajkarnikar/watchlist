<?php
require_once "auth.php";
require_once "db.php";
require_once "partials/helpers.php";

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$media_id   = (int)($_GET['id'] ?? 0);

$media = db_all($conn,
    "SELECT m.*, we.entry_id, we.watch_status, we.is_bookmarked, we.is_favorite
     FROM media m
     LEFT JOIN watchlist_entry we ON we.media_id = m.media_id AND we.user_id = ?
     WHERE m.media_id = ?", "ii", $user_id, $media_id)[0] ?? null;

if (!$media) { header("Location: dashboard.php"); exit(); }

if (isset($_GET['action'])) {
    $a = $_GET['action'];
    if (in_array($a, ['add', 'watch', 'bookmark', 'favorite'])) {
        $eid = $media['entry_id'];
        if (empty($eid)) {
            $i = mysqli_prepare($conn, "INSERT INTO watchlist_entry (user_id, media_id, watch_status) VALUES (?, ?, 'To Watch')");
            mysqli_stmt_bind_param($i, "ii", $user_id, $media_id);
            mysqli_stmt_execute($i);
            $eid = mysqli_insert_id($conn);
        }
        $sqls = [
            'watch'    => "UPDATE watchlist_entry SET watch_status = IF(watch_status='Watched','To Watch','Watched') WHERE entry_id = ?",
            'bookmark' => "UPDATE watchlist_entry SET is_bookmarked = NOT is_bookmarked WHERE entry_id = ?",
            'favorite' => "UPDATE watchlist_entry SET is_favorite = NOT is_favorite WHERE entry_id = ?",
        ];
        if (isset($sqls[$a])) {
            $u = mysqli_prepare($conn, $sqls[$a]);
            mysqli_stmt_bind_param($u, "i", $eid);
            mysqli_stmt_execute($u);
        }
    }
    header("Location: media_details.php?id=" . $media_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['delete'])) {
        $d = mysqli_prepare($conn, "DELETE FROM rating WHERE user_id = ? AND media_id = ?");
        mysqli_stmt_bind_param($d, "ii", $user_id, $media_id);
        mysqli_stmt_execute($d);
    } else {
        $rating = round(((float)($_POST['rating'] ?? 0)) * 2) / 2;
        if ($rating >= 0.5 && $rating <= 5) {
            $note = trim($_POST['review_note'] ?? '');
            $note = $note === '' ? null : $note;

            $watched_on = null;
            $wo = $_POST['watched_on'] ?? '';
            if (!empty($_POST['watched_check']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $wo) && $wo <= date('Y-m-d')) {
                $watched_on = $wo;
            }

            $before = !empty($_POST['watched_before']) ? 1 : 0;
            $before_note = null;
            if ($before) {
                $bn = trim($_POST['watched_before_note'] ?? '');
                $before_note = $bn === '' ? null : mb_substr($bn, 0, 255);
            }
            $like = !empty($_POST['like']) ? 1 : 0;

            $s = mysqli_prepare($conn,
                "INSERT INTO rating (user_id, media_id, review_value, review_note, watched_on, watched_before, watched_before_note)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE review_value = VALUES(review_value), review_note = VALUES(review_note),
                   watched_on = VALUES(watched_on), watched_before = VALUES(watched_before),
                   watched_before_note = VALUES(watched_before_note), date_rated = CURRENT_TIMESTAMP");
            mysqli_stmt_bind_param($s, "iidssis", $user_id, $media_id, $rating, $note, $watched_on, $before, $before_note);
            mysqli_stmt_execute($s);

            if (!empty($media['entry_id'])) {
                $u = mysqli_prepare($conn, "UPDATE watchlist_entry SET watch_status = 'Watched', is_favorite = ? WHERE entry_id = ?");
                mysqli_stmt_bind_param($u, "ii", $like, $media['entry_id']);
                mysqli_stmt_execute($u);
            } else {
                $u = mysqli_prepare($conn, "INSERT INTO watchlist_entry (user_id, media_id, watch_status, is_favorite) VALUES (?, ?, 'Watched', ?)");
                mysqli_stmt_bind_param($u, "iii", $user_id, $media_id, $like);
                mysqli_stmt_execute($u);
            }
        }
    }
    header("Location: media_details.php?id=" . $media_id . "#reviews");
    exit();
}

$my  = db_all($conn, "SELECT * FROM rating WHERE user_id = ? AND media_id = ?", "ii", $user_id, $media_id)[0] ?? null;
$agg = db_all($conn, "SELECT AVG(review_value) AS a, COUNT(*) AS c FROM rating WHERE media_id = ?", "i", $media_id)[0];
$reviews = db_all($conn,
    "SELECT r.*, u.first_name, u.last_name,
       EXISTS(SELECT 1 FROM friendships f WHERE f.status = 'accepted'
              AND ((f.requester_id = ? AND f.addressee_id = r.user_id) OR (f.addressee_id = ? AND f.requester_id = r.user_id))) AS is_friend
     FROM rating r JOIN users u ON u.user_id = r.user_id
     WHERE r.media_id = ? AND r.review_note IS NOT NULL
     ORDER BY is_friend DESC, r.date_rated DESC LIMIT 50", "iii", $user_id, $user_id, $media_id);

$platforms = !empty($media['platforms']) ? explode(',', $media['platforms']) : [];
$yt        = youtube_id($media['trailer_url'] ?? '');
$liked     = !empty($media['is_favorite']) ? 1 : 0;
$is_watched = ($media['watch_status'] ?? '') === 'Watched';
$my_rating = $my ? (float)$my['review_value'] : 0;
$has_date  = $my ? !empty($my['watched_on']) : true;
$date_val  = !empty($my['watched_on']) ? $my['watched_on'] : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo h($media['title_name']); ?> - Watchlist</title>
<link rel="stylesheet" href="CSS/stylesheet.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="details-page-wrapper">

  <div class="details-header">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <div class="main-header-right">
      <?php require "partials/user_menu.php"; ?>
    </div>
  </div>

  <div class="figma-layout">

    <div class="figma-poster-container">
      <?php if (!empty($media['poster_image'])): ?>
        <img class="figma-poster" src="uploads/posters/<?php echo h($media['poster_image']); ?>" alt="Poster">
      <?php else: ?>
        <div class="figma-poster figma-poster-fallback"><?php echo strtoupper(substr($media['title_name'], 0, 1)); ?></div>
      <?php endif; ?>
    </div>

    <div class="figma-card synopsis-card">
      <div class="figma-title"><?php echo h($media['title_name']); ?></div>
      <div class="figma-year"><?php echo h($media['release_year']); ?> • <?php echo h($media['genre']); ?> • <?php echo h($media['media_type']); ?></div>
      <div class="figma-synopsis">
        <?php echo !empty($media['synopsis']) ? nl2br(h($media['synopsis'])) : 'Synopsis not available. Check back later.'; ?>
      </div>
    </div>

    <div class="figma-sidebar">

      <div class="figma-card figma-side-card">
        <h3>Platforms:</h3>
        <?php if ($platforms): foreach ($platforms as $p): ?>
          <div class="platform-text">• <?php echo h(trim($p)); ?></div>
        <?php endforeach; else: ?>
          <div class="platform-text">Currently not streaming</div>
        <?php endif; ?>
      </div>

      <div class="figma-card figma-side-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
          <h3 style="margin:0;">Rating</h3>
          <?php if ($my): echo stars_html($my['review_value'], 26); else: ?>
            <span class="platform-text">Not rated</span>
          <?php endif; ?>
        </div>
        <button type="button" class="btn-pill-solid" data-open-review
                style="width:100%; border:0; cursor:pointer; margin-bottom:20px;">
          ✎ <?php echo $my ? 'Edit your review' : 'Rate & review'; ?>
        </button>

        <a href="media_details.php?id=<?php echo $media_id; ?>&action=favorite" class="action-row">
          <span class="action-icon" style="color:<?php echo $liked ? 'var(--accent)' : 'inherit'; ?>"><?php echo $liked ? '♥' : '♡'; ?></span>
          <span style="color:<?php echo $liked ? 'var(--accent)' : 'inherit'; ?>"><?php echo $liked ? 'Liked' : 'Like'; ?></span>
        </a>

        <a href="media_details.php?id=<?php echo $media_id; ?>&action=bookmark" class="action-row">
          <span class="action-icon" style="color:<?php echo !empty($media['is_bookmarked']) ? 'var(--accent)' : 'inherit'; ?>"><?php echo !empty($media['is_bookmarked']) ? '🔖' : '⚑'; ?></span>
          <span style="color:<?php echo !empty($media['is_bookmarked']) ? 'var(--accent)' : 'inherit'; ?>"><?php echo !empty($media['is_bookmarked']) ? 'Bookmarked' : 'Bookmark'; ?></span>
        </a>

        <a href="media_details.php?id=<?php echo $media_id; ?>&action=watch" class="action-row">
          <span class="action-icon" style="color:<?php echo $is_watched ? 'var(--accent)' : 'inherit'; ?>"><?php echo $is_watched ? '👁️' : '👁‍🗨'; ?></span>
          <span style="color:<?php echo $is_watched ? 'var(--accent)' : 'inherit'; ?>"><?php echo $is_watched ? 'Watched' : 'Mark as Watched'; ?></span>
        </a>

        <?php if (empty($media['entry_id'])): ?>
          <a href="media_details.php?id=<?php echo $media_id; ?>&action=add" class="btn-pill-solid" style="display:inline-block; margin-top:15px; width:100%; text-align:center;">➕ Add to My List</a>
        <?php else: ?>
          <span style="display:block; margin-top:25px; font-size:15px; color:var(--text-gray);">
            Status: <strong style="color:var(--text-white);"><?php echo h($media['watch_status']); ?></strong>
          </span>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <?php if ($yt): ?>
  <div class="figma-card details-section">
    <h3>Trailer</h3>
    <div class="trailer-frame">
      <iframe src="https://www.youtube-nocookie.com/embed/<?php echo h($yt); ?>" title="Trailer"
              allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
  <?php endif; ?>

  <?php
$similar = db_all($conn,
    "SELECT m.media_id, m.title_name, m.poster_image, AVG(r.review_value) avg_r
     FROM media m LEFT JOIN rating r ON r.media_id = m.media_id
     WHERE m.genre = ? AND m.media_id != ?
     GROUP BY m.media_id
     ORDER BY avg_r DESC, m.rating_score DESC LIMIT 6", "si", $media['genre'], $media_id);
?>
<?php if ($similar): ?>
<div class="figma-card details-section">
  <h3>Similar titles</h3>
  <div class="poster-grid">
    <?php foreach ($similar as $s): ?>
      <a href="media_details.php?id=<?php echo $s['media_id']; ?>" class="poster-card" style="text-decoration:none; color:inherit;">
        <div class="poster-card-image">
          <?php if (!empty($s['poster_image'])): ?>
            <img src="uploads/posters/<?php echo h($s['poster_image']); ?>" alt="<?php echo h($s['title_name']); ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <div class="poster-fallback" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;"><?php echo strtoupper(substr($s['title_name'], 0, 1)); ?></div>
          <?php endif; ?>
        </div>
        <div class="poster-card-caption">
          <div class="poster-title"><?php echo h($s['title_name']); ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

  <div class="figma-card details-section" id="reviews">
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px; flex-wrap:wrap;">
      <h3 style="margin:0;">Reviews</h3>
      <?php if ($agg['c'] > 0): ?>
        <?php echo stars_html($agg['a'], 18); ?>
        <span class="platform-text"><?php echo fmt_rating(round($agg['a'] * 2) / 2); ?> avg · <?php echo (int)$agg['c']; ?> rating<?php echo $agg['c'] == 1 ? '' : 's'; ?></span>
      <?php endif; ?>
    </div>

    <?php if (!$reviews): ?>
      <div class="platform-text">No written reviews yet. Be the first!</div>
    <?php endif; ?>

    <?php foreach ($reviews as $rv): ?>
      <div class="review-item">
        <div class="review-top">
          <strong><?php echo h($rv['first_name'] . ' ' . $rv['last_name']); ?></strong>
          <?php if ($rv['user_id'] == $user_id): ?><span class="badge towatch">You</span>
          <?php elseif ($rv['is_friend']): ?><span class="badge watched">Friend</span><?php endif; ?>
          <?php echo stars_html($rv['review_value'], 16); ?>
        </div>
        <?php if ($rv['watched_on'] || $rv['watched_before']): ?>
          <div class="review-meta">
            <?php if ($rv['watched_on']): ?>Watched on <?php echo date('j M Y', strtotime($rv['watched_on'])); ?><?php endif; ?>
            <?php if ($rv['watched_before']): ?><?php echo $rv['watched_on'] ? ' · ' : ''; ?>↻ Watched before<?php echo $rv['watched_before_note'] ? ': ' . h($rv['watched_before_note']) : ''; ?><?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="review-text"><?php echo nl2br(h($rv['review_note'])); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<dialog id="reviewDialog" class="review-dialog">
  <form method="POST" action="media_details.php?id=<?php echo $media_id; ?>" id="reviewForm">
    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $my_rating; ?>">
    <input type="hidden" name="like" id="likeInput" value="<?php echo $liked; ?>">

    <div class="rd-head">
      <span><?php echo $my ? 'Edit review' : 'Rate & review'; ?></span>
      <button type="button" class="rd-close" aria-label="Close">×</button>
    </div>

    <div class="rd-body">
      <div class="rd-poster">
        <?php if (!empty($media['poster_image'])): ?>
          <img src="uploads/posters/<?php echo h($media['poster_image']); ?>" alt="">
        <?php else: ?><div></div><?php endif; ?>
      </div>

      <div class="rd-main">
        <div class="rd-title"><?php echo h($media['title_name']); ?> <span><?php echo h($media['release_year']); ?></span></div>

        <div class="rd-row">
          <label class="rd-check"><input type="checkbox" name="watched_check" id="watchedCheck" <?php echo $has_date ? 'checked' : ''; ?>> Watched on</label>
          <input type="date" name="watched_on" id="watchedOn" value="<?php echo h($date_val); ?>" max="<?php echo date('Y-m-d'); ?>">
          <label class="rd-check"><input type="checkbox" name="watched_before" id="beforeCheck" <?php echo !empty($my['watched_before']) ? 'checked' : ''; ?>> I've watched this before</label>
        </div>

        <input type="text" class="rd-note" name="watched_before_note" id="beforeNote" maxlength="255"
               placeholder="When or how many times? (optional)"
               value="<?php echo h($my['watched_before_note'] ?? ''); ?>" <?php echo !empty($my['watched_before']) ? '' : 'hidden'; ?>>

        <textarea name="review_note" placeholder="Add a review..."><?php echo h($my['review_note'] ?? ''); ?></textarea>

        <div class="rd-foot-row">
          <div>
            <div class="rd-label">Rating <span id="ratingLabel"><?php echo $my_rating ? fmt_rating($my_rating) . ' out of 5' : 'Not rated'; ?></span></div>
            <div class="hs-input" id="hsInput">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="hs"><i class="hs-bg">★</i><i class="hs-fg" style="width:0"></i><b class="l"></b><b class="r"></b></span>
              <?php endfor; ?>
            </div>
          </div>
          <div>
            <div class="rd-label">Like</div>
            <button type="button" class="rd-like <?php echo $liked ? 'on' : ''; ?>" id="likeBtn" aria-label="Like">♥</button>
          </div>
        </div>
      </div>
    </div>

    <div class="rd-actions">
      <?php if ($my): ?>
        <button type="submit" name="delete" value="1" class="rd-btn" formnovalidate onclick="return confirm('Delete your review and rating?')">Delete</button>
      <?php endif; ?>
      <button type="submit" class="rd-btn save">Save</button>
    </div>
  </form>
</dialog>

<script>
(function () {
  var dlg = document.getElementById('reviewDialog');
  var form = document.getElementById('reviewForm');
  var ri = document.getElementById('ratingInput');
  var lbl = document.getElementById('ratingLabel');
  var stars = document.querySelectorAll('#hsInput .hs');

  document.querySelectorAll('[data-open-review]').forEach(function (b) { b.onclick = function () { dlg.showModal(); }; });
  dlg.querySelector('.rd-close').onclick = function () { dlg.close(); };
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  function paint(v) {
    stars.forEach(function (s, i) {
      var n = i + 1;
      s.querySelector('.hs-fg').style.width = (v >= n ? 100 : v >= n - 0.5 ? 50 : 0) + '%';
    });
  }
  function setLabel(v) { lbl.textContent = v > 0 ? v + ' out of 5' : 'Not rated'; }

  stars.forEach(function (s, i) {
    var n = i + 1;
    [['.l', n - 0.5], ['.r', n]].forEach(function (p) {
      var el = s.querySelector(p[0]);
      el.onmouseenter = function () { paint(p[1]); };
      el.onclick = function () { ri.value = p[1]; paint(p[1]); setLabel(p[1]); };
    });
  });
  document.getElementById('hsInput').onmouseleave = function () { paint(parseFloat(ri.value) || 0); };
  paint(parseFloat(ri.value) || 0);

  var likeBtn = document.getElementById('likeBtn'), likeIn = document.getElementById('likeInput');
  likeBtn.onclick = function () { likeIn.value = likeIn.value === '1' ? '0' : '1'; likeBtn.classList.toggle('on', likeIn.value === '1'); };

  var wc = document.getElementById('watchedCheck'), wo = document.getElementById('watchedOn');
  function syncDate() { wo.disabled = !wc.checked; }
  wc.onchange = syncDate; syncDate();

  var bc = document.getElementById('beforeCheck'), bn = document.getElementById('beforeNote');
  bc.onchange = function () { bn.hidden = !bc.checked; if (bc.checked) bn.focus(); };

  form.addEventListener('submit', function (e) {
    if (e.submitter && e.submitter.name === 'delete') return;
    if (!(parseFloat(ri.value) > 0)) { e.preventDefault(); lbl.textContent = 'Pick a star rating first'; }
  });
})();
</script>

</body>
</html>