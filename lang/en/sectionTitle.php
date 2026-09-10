<?php

return [
    "about_company" => "About Company",
    "add_to_watchlist" => "Add to Watchlist",
    "best_in_tv" => "Best in Series This Week",
    "best_sellers" => "Best Sellers",
    "best_selling_categories" => "Best Selling Categories",
    "international_shows" => "Best of International Series",
    "continue_watching" => "Continue Watching",
    "from" => "From",
    "crew" => "Crew",
    "fresh_picks" => "Fresh Picks Just For You",
    "latest_movies" => "Latest Movies",
    "latest_series" => "Latest Series",
    "membership" => "Membership",
    "more_like_this" => "More Like This",
    "movie_genres" => "Movie Genres",
    "tv_genres" => "Series Genres",
    "video_genres" => "Video Genres",
    "movie_tag" => "Movie Tags",
    "tv_show_tag" => "Series Tags",
    "video_tag" => "Video Tags",
    "movies_recommended" => "Movies Recommended For You",
    "movies_to_watch" => "Movies to Watch",
    "newest_products" => "Newest Products",
    "newsletter" => "Subscribe Newsletter",
    "only_on_streamit" => "Only on Jambo",
    "payments" => "We Accept Payments",
    "popular" => "Popular",
    "popular_movies" => "Popular Movies",
    "popular_videos" => "Popular Videos",
    "privacy_policy" => "Privacy Policy",
    "quick_links" => "Quick Links",
    "recommended" => "Recommended",
    "recommended_for_you" => "Recommended for You",
    "smart_shuffle" => "AI Smart Shuffle",
    "recommended_movie" => "Recommended Movies",
    "related_movies" => "Related Movies",
    "related_products" => "Related Products",
    "related_videos" => "Related Videos",
    "recommended_tv_show" => "Recommended Series",
    "specials_latest_movies" => "Specials & Latest Movies",
    "starring" => "Starring",
    "suggested_block" => "Suggested For You",
    "shows_recommend" => "Series We Recommend",
    "popular_show" => "Popular Series",
    "specials_latest_videos" => "Specials & Latest Videos",
    "terms_of_use" => "Terms of Use",
    "terms_and_use" => "Terms and Use",
    "top_10_movies_to_watch" => "Top 10 Movies This Week",
    "top_10_tvshow_to_watch" => "Top 10 Series This Week",
    "top_10_video_to_watch" => "Top 10 Videos to Watch",
    "top_picks" => "Top Picks for You",
    "top_ten" => "Top 10 Movies This Week",
    /*
     * The two daily banners, named for the screen that arranges them.
     *
     * Neither surface draws these strings: the website's vertical slider and
     * tab slider are full-width banners with no shelf heading, and the app
     * sends no title for either. They exist so a row on /admin/home-sections
     * has something to say, which is why they are worded as the admin reads
     * them — "Top 10 Movies Today", not "#3 in Movies Today", which is the
     * per-slide badge and lives in streamMovies.
     */
    "top_movies_today" => "Top 10 Movies Today",
    "top_series_today" => "Top 10 Series Today",
    "top_trending" => "Top Trending",
    "tv_upcoming_title" => "Upcoming Series",
    "tv_popular_shows" => "Popular Series",
    "upcoming" => "Upcoming",
    // The homepage's Upcoming section asks for this key. It did not exist, so
    // the live site rendered the literal string "sectionTitle.upcoming_title"
    // as a heading whenever that rail had anything in it — invisible only
    // because the rail is currently empty. Added here rather than repointing
    // the Blade, because language files are a config point the template
    // provides and its layouts are not ours to edit.
    "upcoming_title" => "Upcoming",
    "upcoming_movies" => "Upcoming Movies",
    "upcoming_video" => "Upcoming Video",
    "view_all" => "View All",
    "videos_recommended" => "Videos Recommended For You",
    "your_favourite_personality" => "Your Favourite Personality",
    "remove_from_list" => "Remove from list",


    // tooltip

    "add_to_watchlist_tooltip" => "Add to Watchlist",
    "content_here" => "Content Here",
    "mark_as_read_tooltip" => "Mark as Read",
    "like_tooltip" => "Like",
    "unlike_tooltip" => "Unlike"
];