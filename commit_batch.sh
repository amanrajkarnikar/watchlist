#!/bin/bash
set -e

commit() {
  GIT_AUTHOR_DATE="$1" GIT_COMMITTER_DATE="$1" git commit -m "$2"
}

git add db.php auth.php login.php login_process.php signup.php signup_process.php index.php CSS/
commit "2026-09-10 19:22:00" "Set up DB connection, auth, login/signup flow, and styling"

git add admin_add_title.php
commit "2026-09-12 20:05:00" "Add admin add-title form"

git add admin_requests.php admin_process_request.php
commit "2026-09-13 18:47:00" "Add admin view and approve/reject logic for title requests"

git add admin_auth.php admin_dashboard.php admin_logout.php partials/admin_menu.php
commit "2026-09-14 21:10:00" "Add admin session handling, dashboard, and menu"

git add request_title.php
commit "2026-09-16 17:35:00" "Add user-facing title request form"

git add partials/user_menu.php partials/helpers.php
commit "2026-09-18 20:52:00" "Add shared navigation and helper functions"

git add browse.php
commit "2026-09-20 19:14:00" "Add browse page with genre filters and Coming Soon badges"

git add dashboard.php
commit "2026-09-24 22:03:00" "Add dashboard with Surprise Me and release reminders"

git add mylist.php
commit "2026-09-26 18:29:00" "Add sort and filter options to My List"

git add media_details.php
commit "2026-09-27 21:40:00" "Add media details page with ratings, reviews, and trailers"

git add friends.php friend_profile.php
commit "2026-09-28 20:16:00" "Add friends system and friend profile pages"

git add statistics.php
commit "2026-09-29 19:58:00" "Add monthly activity chart to statistics page"

git add tmdb_trailers.php
commit "2026-09-29 22:31:00" "Add TMDB bulk trailer import