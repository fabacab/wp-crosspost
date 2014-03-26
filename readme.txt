=== WordPress Crosspost ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=WP%20Crosspost%20WordPress%20Plugin&item_number=wp%2dcrosspost&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: WordPress.com, post, crosspost, publishing
Requires at least: 3.1
Tested up to: 3.8.1
Stable tag: 0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress Crosspost cross-posts content from your self-hosted WordPress blogs to your WordPress.com sites. Changes to your WordPress posts are reflected in your WordPress.com sites.

== Description ==

WordPress Crosspost crossposts to the WordPress.com (or [JetPack](http://jetpack.me/)-enabled) blog of your choice whenever you hit the "Publish" (or "Save Draft") button. It uses WordPress.com's simple API to keep posts in sync; when you edit your WordPress post, it updates your crossposted post. Private WordPress posts stay private on the remote site, deleting a post from WordPress that you've previously cross-posted deletes it from the remote site, too, and so on. Scheduling a WordPress post to be published any time in the future will add it to the remote site's future publication schedule, too.

WP-Crosspost is very lightweight. It just requires you to connect to your WordPress.com account from the plugin options screen. After that, you're ready to cross-post!

Other options and features enable tweaking additional metadata from your WordPress entry (notably categories and tags) to the remote site, switching comments and pingbacks on or off, and more.

WP-Crosspost transforms your self-hosted WordPress website into a back-end for your WordPress.com-hosted website. Create your posts locally WordPress, but publish to WordPress.com's servers. This means you'll always have a portable copy of your entire blog, and you can stop worrying about whether your backups are up to date. Create new content locally, then move it to the server automatically, instead of the other way around!

> Servers no longer serve, they possess. We should call them possessors.

--[Ward Cunningham](https://twitter.com/WardCunningham/status/289875660246220800)

Learn more about how you can use this plugin to own your own data in conjunction with [the "Bring Your Own Content" self-hosted Web publishing virtual appliance](http://maymay.net/blog/2014/03/13/bring-your-own-content-virtual-self-hosting-web-publishing/).

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

WP-Crosspost makes use of Manuel Lemos's `oauth_client_class` for some core functions. Most systems have the required packages installed already, buti f you notice any errors upon plugin activation, first check to ensure your system's [PHP include path](http://php.net/manual/ini.core.php#ini.include-path) is set correctly. The `lib` directory and its required files look like this:

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

It's also possible that your system administrator will apply updates to one or more of the core system packages this plugin uses without your knowledge. If this happens, and the updated packages contain backward-incompatible changes, the plugin may begin to issue errors. Should this occur, please [file a bug report on the WP-Crosspost project's issue tracker](https://github.com/meitar/wp-crosspost/issues/new).

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

= Verson 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) for your use of the plugin, [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or, better yet, contributing directly to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). (Publishing royalties ain't exactly the lucrative income it used to be, y'know?) Your support is appreciated!

This plugin is inspired by and based on [Tumblr Crosspostr](https://wordpress.org/plugins/tumblr-crosspostr/).
