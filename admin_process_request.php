<?php
require "admin_auth.php";
require "db.php";

$request_id = (int) ($_GET['id'] ?? 0);
$action     = $_GET['action'] ?? '';

if ($request_id && in_array($action, ['approve', 'reject'])) {

    $stmt = mysqli_prepare($conn, "SELECT * FROM media_requests WHERE id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($request) {
        if ($action === 'approve') {
            $insert = mysqli_prepare($conn, "INSERT INTO media (title_name, release_year, genre, media_type, poster_image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "sisss", $request['title_name'], $request['release_year'], $request['genre'], $request['media_type'], $request['poster_image']);
            mysqli_stmt_execute($insert);
            $new_media_id = mysqli_insert_id($conn);

            $link = mysqli_prepare($conn, "INSERT INTO watchlist_entry (user_id, media_id, watch_status) VALUES (?, ?, 'To Watch')");
            mysqli_stmt_bind_param($link, "ii", $request['user_id'], $new_media_id);
            mysqli_stmt_execute($link);

            $update = mysqli_prepare($conn, "UPDATE media_requests SET status = 'approved' WHERE id = ?");
            mysqli_stmt_bind_param($update, "i", $request_id);
            mysqli_stmt_execute($update);
        } else {
            $update = mysqli_prepare($conn, "UPDATE media_requests SET status = 'rejected' WHERE id = ?");
            mysqli_stmt_bind_param($update, "i", $request_id);
            mysqli_stmt_execute($update);
        }
    }
}

header("Location: admin_requests.php");
exit();