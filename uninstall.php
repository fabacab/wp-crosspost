<?php
/**
 * WP-Crosspost uninstaller
 *
 * @package plugin
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

// Delete options.
delete_option('wp_crosspost_settings');
delete_option('_wp_crosspost_admin_notices');
delete_option('wp_crosspost_access_token');

delete_post_meta_by_key('wp_crosspost_crosspost');
delete_post_meta_by_key('wp_crosspost_use_excerpt');
/**
 * TODO: Should we really delete this post meta?
 *       That'll wipe Tumblr post IDs and blog hostnames. :\
 *       We need these to be able to re-associate WordPress posts
 *       with the Tumblr posts that they were cross-posted to.
 */
//delete_post_meta_by_key('wp_crosspost_post_id');
//delete_post_meta_by_key('wp_crosspost_destination');
