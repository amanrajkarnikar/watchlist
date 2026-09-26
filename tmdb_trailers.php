<?php
require_once __DIR__ . '/db.php';
set_time_limit(0);

$key = '38cb23adfa9a834bdf28b6121a1d9bee';

function tmdb($path, $params, $key) {
    $params['api_key'] = $key;
    $ch = curl_init('https://api.themoviedb.org/3/' . $path . '?' . http_build_query($params));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 20]);
    $r = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $r ?: [];
}

function find_trailer($title, $year, $kinds, $key) {
    foreach ($kinds as $kind) {
        foreach ([true, false] as $useYear) {
            $p = ['query' => $title];
            if ($useYear && $year) $p[$kind === 'movie' ? 'year' : 'first_air_date_year'] = $year;
            $res = tmdb("search/$kind", $p, $key)['results'] ?? [];
            if (!$res) continue;
            $vids = tmdb("$kind/{$res[0]['id']}/videos", [], $key)['results'] ?? [];
            $best = null;
            foreach ($vids as $v) {
                if ($v['site'] !== 'YouTube' || $v['type'] !== 'Trailer') continue;
                if (!$best || (!empty($v['official']) && empty($best['official']))) $best = $v;
            }
            if ($best) return 'https://www.youtube.com/watch?v=' . $best['key'];
        }
    }
    return null;
}

$rows = mysqli_query($conn, "SELECT media_id, title_name, release_year, media_type FROM media WHERE trailer_url IS NULL OR trailer_url = ''");
$upd = mysqli_prepare($conn, "UPDATE media SET trailer_url = ? WHERE media_id = ?");
echo "<pre>";
while ($m = mysqli_fetch_assoc($rows)) {
    $kinds = $m['media_type'] === 'Movie' ? ['movie'] : ($m['media_type'] === 'TV' ? ['tv'] : ['tv', 'movie']);
    $url = find_trailer($m['title_name'], $m['release_year'], $kinds, $key);
    if ($url) {
        mysqli_stmt_bind_param($upd, "si", $url, $m['media_id']);
        mysqli_stmt_execute($upd);
        echo "OK    {$m['title_name']} -> $url\n";
    } else {
        echo "MISS  {$m['title_name']}\n";
    }
    flush();
}
echo "done</pre>";