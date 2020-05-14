=== WordPress Crosspost ===
Contributors: maymay
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=WP%20Crosspost%20WordPress%20Plugin&amp;item_number=wp-crosspost&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: WordPress.com, post, crosspost, publishing
Requires at least: 3.1
Tested up to: 4.4.2
Stable tag: 0.4.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress Crosspost cross-posts content from your self-hosted WordPress blogs to your WordPress.com sites. Updates are crossposted, too.

== Description ==

WordPress Crosspost posts to your WordPress.com (or self-hosted [JetPack](http://jetpack.me/)-enabled) blog of your choice whenever you hit the "Publish" (or "Save Draft") button. It can import your reblogs and other posts on WordPress.com. It even downloads the media attachments in your WordPress.com posts and saves them in your self-hosted WordPress Media Library.

**Transform your self-hosted WordPress website into a back-end for your WordPress.com-hosted website. Create original posts on your local computer, but publish them to WordPress.com. Import your WordPress.com reblogs. [Always have a portable copy (a running copy) of your entire WordPress.com blog](https://maymay.net/blog/2014/02/17/keep-a-running-backup-of-your-tumblr-reblogs-with-tumblr-crosspostr/).**

This plugin uses [WordPress.com's REST API](http://developer.wordpress.com/docs/api/) to keep posts in sync; when you edit your WordPress post, it updates your crossposted post. Private WordPress posts stay private on the remote site, deleting a post from WordPress that you've previously cross-posted deletes it from the remote site, too, and so on. Scheduling a WordPress post to be published any time in the future will add it to the remote site's future publication schedule, too. See the [Other Notes](https://wordpress.org/plugins/wp-crosspost/other_notes/) page for a complete listing of features.

WP-Crosspost is very lightweight. It just requires you to connect to your WordPress.com account from the plugin options screen. After that, you're ready to cross-post!

Other options and features enable tweaking additional metadata from your WordPress entry (notably categories and tags) to the remote site, switching comments and pingbacks on or off, and more.

WP-Crosspost transforms your self-hosted WordPress website into a back-end for your WordPress.com-hosted website. Create your posts locally on your own computer's WordPress, but publish to WordPress.com's servers. This means you'll always have a portable copy of your entire blog, and you can stop worrying about whether your backups are up to date. Create new content locally, then move them to the server automatically, instead of the other way around!

> Servers no longer serve, they possess. We should call them possessors.

--[Ward Cunningham](https://twitter.com/WardCunningham/status/289875660246220800)

Learn more about how you can use this plugin to own your own data in conjunction with [the "Bring Your Own Content" self-hosted Web publishing virtual appliance](https://maymay.net/blog/2014/03/13/bring-your-own-content-virtual-self-hosting-web-publishing/).

== Installation ==

1. Download the plugin file.
1. Unzip the file into your 'wp-content/plugins/' directory.
1. Go to your WordPress administration panel and activate the plugin.
1. Go to WordPress Crosspost Settings (from the Settings menu) and either create or enter your WordPress.com OAuth client id and client secret. Then click "Save Changes."
1. Once you've entered your client id and client secret, a "Connect to WordPress.com" button will appear. Click that to be redirected to WordPress.com's authorization page.
1. Click "Authorize" to grant access to your blog from WP-Crosspost.
1. Start posting!!!

See also the [Screenshots](https://wordpress.org/plugins/wp-crosspost/screenshots/) section for a visual walk through of this process.

= Installation notes and troubleshooting =

If you are having trouble installing or using WP-Crosspost, first make sure have the following necessary components installed on your server:

* PHP 5.2.17 or later.
* [PHP cURL extension](https://secure.php.net/manual/book.curl.php) installed and enabled.
* [PHP OpenSSL extension](https://secure.php.net/manual/book.openssl.php) installed and enabled.

Moreover, WP-Crosspost makes use of [Manuel Lemos's `oauth_client_class`](http://www.phpclasses.org/package/7700-PHP-Authorize-and-access-APIs-using-OAuth.html) for some core functions. Most systems have the required packages installed already, but if you notice any errors upon plugin activation, first check to ensure your system's [PHP include path](http://php.net/manual/ini.core.php#ini.include-path) is set correctly. The `lib` directory and its required files look like this:

    lib
    ├── OAuthWP.php
    ├── OAuthWP_WordPressDotCom.php
    ├── WPCrosspostAPIClient.php
    ├── httpclient
    │   ├── LICENSE.txt
    │   └── http.php
    └── oauth_api
        ├── LICENSE
        └── oauth_client.php

It's also possible that your system administrator will apply updates to one or more of the core system packages this plugin uses without your knowledge. If this happens, and the updated packages contain backward-incompatible changes, the plugin may begin to issue errors. Should this occur, please [file a bug report on the WP-Crosspost project's issue tracker](https://github.com/fabacab/wp-crosspost/issues/new).

== Frequently Asked Questions ==

= Can I specify a post's tags or categories? =

Yes. WordPress's tags and categories are also crossposted to your other WordPress sites. If you'd like to keep your local WordPress tags or categories separate from your crossposted ones, be certain you've enabled the "Do not send post tags in crossposts" or "Do not send post categories in crossposts" setting.

Additionally, the "Automatically add these tags to all crossposts" setting lets you enter a comma-separated list of tags that will always be applied to your crossposts.

= Can I crosspost older WordPress posts? =

Yes. Go edit the desired post, verify the crosspost option is set to `Yes`, and update the post. WP-Crosspost will keep the original post date.

= What if I edit a post that has been cross-posted? =

If you edit or delete a post, changes will appear on the remote site accordingly.

= Can I cross-post Private posts? =

Yes. WP-Crosspost respects the WordPress post visibility setting and supports cross-posting private posts. Editing the visibility setting of your WordPress post will update your remote site's cross-posted entry with the new setting, as well.

= Is WP-Crosspost available in languages other than English? =

Not yet, but with your help it can be. To help translate the plugin into your language, please [sign up as a translator on WP-Crosspost's Transifex project page](https://www.transifex.com/projects/p/wp-crosspost/).

== Screenshots ==

1. When you first install WP-Crosspost, you'll need to connect it to your WordPress.com account before you can start crossposting. This screenshot shows how its options screen first appears after you activate the plugin.

2. Once you create and enter your client ID and secret, click "Save Changes." The options screen prompts you to connect to WordPress.com with another button. Press the "Click here to connect to WordPress.com" button to begin the OAuth connection process.

3. After allowing WP-Crosspost access to your WordPress.com account, you'll find you're able to access the remainder of the options page. You must choose at least one default WordPress site to send your crossposts to, so this option is highlighted if it is not yet set. Set your cross-posting preferences and click "Save Changes." You're now ready to start crossposting!

4. You can optionally choose not to crosspost individual WordPress posts from the WP-Crosspost custom post editing box. This box also enables you to send a specific post to a WordPress.com site other than the default one you selected in the previous step, and crosspost the post's excerpt rather than its main body.

5. Get help where you need it from WordPress's built-in "Help" system.

== Changelog ==

= Version 0.4.2 =

* [Bugfix](https://github.com/fabacab/wp-crosspost/issues/5): Enable crossposting via XML-RPC or REST API.

= Version 0.4.1 =

* Feature: Support cross-posting image attachments in posts. (This is in addition to a post's "featured image.")

= Version 0.4 =

* Feature: Cross-post featured images ([Post Thumbnails](https://codex.wordpress.org/Post_Thumbnails)). This works both for uploading and importing. Featured images you add to your local WordPress Media Library will be added to your site's Media Library on WordPress.com.
* [Feature](https://github.com/fabacab/wp-crosspost/issues/4): Automatically convert local audio embeds into remote audio embeds.
* Bugfix: Prevent duplicate importing of pages and custom post types during sync routines. (Regular posts were already de-duped.)
* Bugfix: Prevent WP-Cron invocations from duplicating sync schedules.
* Developer: New filter hook `wp_crosspost_prepared_post` lets other plugin authors customize the data about to be crossposted.

= Version 0.3.3 =

* Bugfix: Handle several PHP `E_NOTICE` errors in strict environments.

= Version 0.3.2 =

* Feature: Support [`rel-syndication` IndieWeb pattern](https://indiewebcamp.com/rel-syndication) as implemented by the recommended [Syndication Links](https://indiewebcamp.com/rel-syndication#How_to_link_from_WordPress) plugin.
    * `rel-syndication` is an IndieWeb best practice recommendation that provides a way to automatically link to crossposted copies (called "POSSE'd copies" in the jargon) of your posts to improve the discoverability and usability of your posts. For Tumblr Crosspostr's `rel-syndication` to work, you must also install a compatible WordPress syndication links plugin, such as the [Syndication Links](https://wordpress.org/plugins/syndication-links/) plugin, but the absence of such a plugin will not cause any problems, either.
* Bugfix: Consistent post meta field names resolve several issues where previously-crossposted entries were not found or had incorrect syndication links associated with them.

= Version 0.3.1 =

* [Bugfix](https://wordpress.org/support/topic/seems-to-work-better-than-other-plugins-but): Respect "Do not send post categories in crossposts" option. Also fixes issues with category-based crossposting exclusion.

= Version 0.3 =

* Feature: [Publicize](https://support.wordpress.com/publicize/) integration enables you to broadcast a link to your crossposted post on your Facebook, LinkedIn, Google+, Tumblr, Twitter, or Path account. Simply connect the service of your choice to your WordPress.com blog and crosspost as usual.
    * Attention **Tumblr users,** I strongly recommend using the [Tumblr Crosspostr](https://wordpress.org/plugins/tumblr-crosspostr/) plugin instead. It provides more seamless integration, greater customization, better attribution options, and doesn't rely on the third-party cloud services that Publicize does.
* Feature: Option to set global default value for Publicize integration. Useful for multi-author blogs and customized editorial workflows. (You can still override this on a per-post basis.)
* Feature: "Crosspost-ify Everything!" tool enables one-click crossposting of your entire blog archive.
* Feature: [Post stickiness](https://codex.wordpress.org/Sticky_Posts) is now cross-posted, too.
* Feature: Post categories are now imported when sync'ing.
* Feature: Show "View post on WordPress.com" link in Posts listing screen, and in Post Edit screen inside WordPress Crosspost custom metabox.
* Bugfix: Remove sync schedules on plugin deactivation. (This improves performance, security, and prevents errors by ensuring any WordPress.com synchronization routines are not invoked if you have deactivated but not deleted WP-Crosspost.)

= Version 0.2.1 =

* Feature: Crosspost password protected posts with their password, too.

= Version 0.2 =

* Feature: "Sync posts from WordPress.com" will import posts you create on your WordPress.com blog(s) into your self-hosted WordPress blog, along with their metadata, tags, post formats, geolocation data, and attachments. This is useful for creating an automatic backup of the conversations you have in reblog threads on WordPress.com.
    * When first activated, your entire WordPress.com blog archive will be copied (including private posts and custom post types).
    * Once every 24 hours, WordPress Crosspost will fetch up to the most recent 200 posts on your WordPress.com blog to see if you have reblogged anything on the service. If you have, WordPress Crosspost will import those posts to your self-hosted WordPress blog.
    * Posts you created on WordPress.com using WordPress Crosspost will not be duplicated.
    * Once imported to your self-hosted WordPress blog, edits you make on WordPress.com are not retrieved, but edits you make on your self-hosted WordPress blog are sent back to WordPress.com, so prefer using your self-hosted WordPress blog to edit and update your imported posts.
    * **This feature is experimental.** Please make sure you have a backup of your WordPress website before you enable sync'ing from WordPress.com
* Security: Improved protection for OAuth access tokens.
* Bugfix: Ensure sanitization routines do not corrupt OAuth access tokens.
* Minor code cleanup.

= Verson 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=WP%20Crosspost%20WordPress%20Plugin&amp;item_number=wp-crosspost&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted) for your use of the plugin or, better yet, contributing directly to [my's Cyberbusking fund](http://Cyberbusking.org/). Your support is appreciated!

This plugin is inspired by and based on [Tumblr Crosspostr](https://wordpress.org/plugins/tumblr-crosspostr/).

**Full feature list**

WP-Crosspost turns your self-hosted WordPress blog into the back-end of one of your WordPress.com sites. Crossposting between self-hosted WordPress blogs is *theoretically* supported (but untested) if you have [JetPack](https://jetpack.me) installed and enabled on the remote self-hosted WordPress blog.

You can crosspost (push):

* Posts (of [*any* type](https://codex.wordpress.org/Post_Types), including [Pages](https://codex.wordpress.org/Pages) and custom post types). Pushed data includes the post's:
    * date
    * title
    * status and visibility settings (including the [post password](https://codex.wordpress.org/Using_Password_Protection) if set and whether or not the post is currently in the trash)
    * format
    * tags
    * categories
    * slug
    * whether comments are on or off
    * whether [pingbacks](https://codex.wordpress.org/Glossary#Pingback) are on or off
    * whether the post is [sticky](https://codex.wordpress.org/Sticky_Posts)
    * featured image,
    * content
    * [excerpt](https://codex.wordpress.org/Excerpt),
    * image attachments, along with the actual image media file itself

Some notes on crossposting (push-posting):

* If you update a post on the remote site (such as on WordPress.com), the change will not be pulled back automatically, so always prefer to use your self-hosted WordPress blog to make changes.
* When you upload a file to your Media Library, it will not be crossposted to the remote site's Media Library until and unless you attach it to a post and save the post.
* The URLs of images and other media files will be automatically rewritten to reference the remote site's copy, so always use *local* URLs in your posts. (Let the plugin handle media URLs itself.)

When sync'ing is enabled, you will import (pull):

* Posts (of any type, including pages and custom post types), including the post's:
    * date
    * title
    * content
    * excerpt
    * status and visibility settings (including the [post password](https://codex.wordpress.org/Using_Password_Protection) if set and whether or not the post is currently in the trash),
    * whether comments are on or off
    * whether [pingbacks](https://codex.wordpress.org/Glossary#Pingback) are on or off
    * [geolocation](https://support.wordpress.com/geotagging/)
    * featured images
    * media attachments, along with the actual media file itself

Wondering if WP-Crosspost can do something you don't see on this list? Ask about or search for it in the [WP-Crosspost plugin support forum](https://wordpress.org/support/plugin/wp-crosspost)! Also, I'm just one guy working on this whenever I get the time to, so I prioritize the most often requested features. If your feature isn't high on my list, please be patient or, better yet, [donate to support my work on this plugin](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=WP%20Crosspost%20WordPress%20Plugin&amp;item_number=wp-crosspost&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted) so that I have more time to devote to this work. Thanks! :)
