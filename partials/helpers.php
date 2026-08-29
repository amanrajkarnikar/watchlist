<?php
function h($s) { return htmlspecialchars((string)$s); }

function db_all($conn, $sql, $types = '', ...$params) {
    $s = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($s, $types, ...$params);
    mysqli_stmt_execute($s);
    return mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
}

function stars_html($v, $size = 18) {
    $v = (float)$v;
    $o = '<span class="hs-static" style="font-size:' . (int)$size . 'px">';
    for ($i = 1; $i <= 5; $i++) {
        $w = $v >= $i ? 100 : ($v >= $i - 0.5 ? 50 : 0);
        $o .= '<span class="hs"><i class="hs-bg">★</i><i class="hs-fg" style="width:' . $w . '%">★</i></span>';
    }
    return $o . '</span>';
}

function fmt_rating($v) { return rtrim(rtrim(number_format((float)$v, 1), '0'), '.'); }

function youtube_id($url) {
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', (string)$url, $m)) return $m[1];
    return null;
}

function friend_status($conn, $me, $other) {
    $r = db_all($conn,
        "SELECT friendship_id, requester_id, status FROM friendships
         WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)",
        "iiii", $me, $other, $other, $me)[0] ?? null;
    if (!$r) return ['none', 0];
    if ($r['status'] === 'accepted') return ['friends', (int)$r['friendship_id']];
    return [$r['requester_id'] == $me ? 'sent' : 'received', (int)$r['friendship_id']];
}