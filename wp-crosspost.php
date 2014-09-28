<?php
/**
 * Plugin Name: WordPress Crosspost
 * Plugin URI: https://github.com/meitar/wp-crosspost/#readme
 * Description: Automatically crossposts to your WordPress.com site when you publish a post on your (self-hosted) WordPress blog.
 * Version: 0.3.1
 * Author: Meitar Moscovitz
 * Author URI: http://maymay.net/
 * Text Domain: wp-crosspost
 * Domain Path: /languages
 */

class WP_Crosspost {
    private $wpcom; //< WordPress.com API manipulation wrapper.
    private $prefix = 'wp_crosspost'; //< String to prefix plugin options, settings, etc.

    public function __construct () {
        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('init', array($this, 'setSyncSchedules'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'registerAdminScripts'));
        add_action('admin_head', array($this, 'doAdminHeadActions'));
        add_action('add_meta_boxes', array($this, 'addMetaBox'));
        add_action('save_post', array($this, 'savePost'));

        add_action($this->prefix . '_sync_content', array($this, 'syncFromWordPressBlog'));

        add_filter('post_row_actions', array($this, 'addWordPressPermalinkRowAction'), 10, 2);

        // Both 'trash_post' and 'before_delete_post' to mimic moving to trash.
        add_action('trash_post', array($this, 'removeFromService'));
        add_action('before_delete_post', array($this, 'removeFromService'));

        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        $options = get_option($this->prefix . '_settings');
        // Initialize consumer if we can, set up authroization flow if we can't.
        require_once 'lib/WPCrosspostAPIClient.php';
        if (isset($options['consumer_key']) && isset($options['consumer_secret'])) {
            $this->wpcom = new WP_Crosspost_API_Client($options['consumer_key'], $options['consumer_secret']);
            if (get_option($this->prefix . '_access_token')) {
                $this->wpcom->client->access_token = get_option($this->prefix . '_access_token');
            }
        } else {
            $this->wpcom = new WP_Crosspost_API_Client;
            add_action('admin_notices', array($this, 'showMissingConfigNotice'));
        }

        if (isset($options['debug'])) {
            $this->wpcom->client->debug = 1;
            $this->wpcom->client->debug_http = 1;
        }

        // OAuth connection workflow.
        if (isset($_GET[$this->prefix . '_oauth_authorize'])) {
            add_action('init', array($this, 'authorizeApp'));
        } else if (isset($_GET[$this->prefix . '_callback']) && !empty($_GET['code'])) {
            // Unless we're just saving the options, hook the final step in OAuth authorization.
            if (!isset($_GET['settings-updated'])) {
                add_action('init', array($this, 'completeAuthorization'));
            }
        }
    }

    public function authorizeApp () {
        check_admin_referer('wordpress-authorize');
        $this->wpcom->authorize(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_callback'));
    }

    public function completeAuthorization () {
        $tokens = $this->wpcom->completeAuthorization(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_callback'));
        update_option($this->prefix . '_access_token', $tokens['value']);
    }

    public function showMissingConfigNotice () {
        $screen = get_current_screen();
        if ($screen->base === 'plugins') {
?>
<div class="updated">
    <p><a href="<?php print admin_url('options-general.php?page=' . $this->prefix . '_settings');?>" class="button"><?php esc_html_e('Connect to WordPress.com', 'wp-crosspost');?></a> &mdash; <?php esc_html_e('Almost done! Connect your blog to WordPress.com to begin crossposting with WP-Crosspost.', 'wp-crosspost');?></p>
</div>
<?php
        }
    }

    private function showError ($msg) {
?>
<div class="error">
    <p><?php print esc_html($msg);?></p>
</div>
<?php
    }

    private function showNotice ($msg) {
?>
<div class="updated">
    <p><?php print $msg; // No escaping because we want links, so be careful. ?></p>
</div>
<?php
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-size: larger; width: 70%; margin: 0 auto;"><?php print sprintf(
esc_html__('WordPress Crosspost is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'wp-crosspost'),
'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=WP%20Crosspost%20WordPress%20Plugin&amp;item_number=wp%2dcrosspost&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">' . esc_html__('making a donation', 'wp-crosspost') . '</a>',
'<a href="http://maymay.net/" target="_blank">' . esc_html__('houseless, jobless, nomadic developer', 'wp-crosspost') . '</a>'
);?></p>
</div>
<?php
    }

    public function registerL10n () {
        load_plugin_textdomain('wp-crosspost', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerSettings () {
        register_setting(
            $this->prefix . '_settings',
            $this->prefix . '_settings',
            array($this, 'validateSettings')
        );
    }

    public function registerAdminMenu () {
        add_options_page(
            __('WordPress Crosspost Settings', 'wp-crosspost'),
            __('WordPress Crosspost', 'wp-crosspost'),
            'manage_options',
            $this->prefix . '_settings',
            array($this, 'renderOptionsPage')
        );

        add_management_page(
            __('WP-Crosspost-ify Archives', 'wp-crosspost'),
            __('WP-Crosspost-ify Archives', 'wp-crosspost'),
            'manage_options',
            $this->prefix . '_crosspost_archives',
            array($this, 'dispatchCrosspostArchivesPages')
        );
    }

    public function registerAdminScripts () {
        wp_register_style('wp-crosspost', plugins_url('wp-crosspost.css', __FILE__));
        wp_enqueue_style('wp-crosspost');
    }

    public function addMetaBox ($post) {
        add_meta_box(
            'wp-crosspost-meta-box',
            __('WordPress Crosspost', 'wp-crosspost'),
            array($this, 'renderMetaBox'),
            'post',
            'side'
        );
    }

    public function doAdminHeadActions () {
        $this->registerContextualHelp();
        $this->showAdminNotices();
    }

    private function replacePlaceholders ($str, $post_id) {
        $placeholders = array(
            '%permalink%',
            '%the_title%',
            '%blog_url%',
            '%blog_name%'
        );
        foreach ($placeholders as $x) {
            if (0 === strpos($x, '%blog_')) {
                $arg = substr($x, 6, -1);
                $str = str_replace($x, get_bloginfo($arg), $str);
            } else {
                $func = 'get_' . substr($x, 1, -1);
                $valid_funcs = array(
                    'get_permalink',
                    'get_the_title'
                );
                if (in_array($func, $valid_funcs, true)) {
                    $str = str_replace($x, call_user_func($func, $post_id), $str);
                }
            }
        }
        return $str;
    }

    private function registerContextualHelp () {
        $screen = get_current_screen();
        if ($screen->id !== 'post') { return; }
        $html = '<p>' . esc_html__('You can automatically copy this post to your WordPress.com site:', 'wp-crosspost') . '</p>'
        . '<ol>'
        . '<li>' . esc_html__('Compose your post in this blog as you normally would.', 'wp-crosspost') . '</li>'
        . '<li>' . sprintf(
            esc_html__('In %sthe WordPress Crosspost box%s, ensure the "Send this post to WordPress.com?" option is set to "Yes." (You can set it to "No" if you do not want to copy this post to WordPress.com.)', 'wp-crosspost'),
            '<a href="#wp-crosspost-meta-box">', '</a>'
            ) . '</li>'
        . '<li>' . esc_html__('If you have more than one WordPress.com site, choose the one you want to send this post to from the "Send to my site" list.', 'wp-crosspost') . '</li>'
        . '</ol>'
        . '<p>' . esc_html__('When you are done, click "Publish" (or "Save Draft"), and WP-Crosspost will send your post to the WordPress.com site you chose.', 'wp-crosspost') . '</p>'
        ;
        ob_start();
        $this->showDonationAppeal();
        $x = ob_get_contents();
        ob_end_clean();
        $html .= $x;
        $screen->add_help_tab(array(
            'id' => $this->prefix . '-' . $screen->base . '-help',
            'title' => __('Crossposting to WordPress.com', 'wp-crosspost'),
            'content' => $html
        ));

        $x = esc_html__('WP-Crosspost:', 'wp-crosspost');
        $y = esc_html__('WP-Crosspost support forum', 'wp-crosspost');
        $z = esc_html__('Donate to WP-Crosspost', 'wp-crosspost');
        $sidebar = <<<END_HTML
<p><strong>$x</strong></p>
<p><a href="https://wordpress.org/support/plugin/wp-crosspost" target="_blank">$y</a></p>
<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=WP%20Crosspost%20WordPress%20Plugin&amp;item_number=wp%2dcrosspost&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">&hearts; $z &hearts;</a></p>
END_HTML;
        $screen->set_help_sidebar($screen->get_help_sidebar() . $sidebar);
    }

    public function addWordPressPermalinkRowAction ($actions, $post) {
        $wpcom_id = get_post_meta($post->ID, 'wordpress_post_id', true);
        if ($wpcom_id) {
            $base_hostname = get_post_meta($post->ID, 'wp_crosspost_destination', true);
            if (empty($base_hostname)) { // fallback to default blog domain
                $options = get_option($this->prefix . '_settings');
                $base_hostname = $options['default_hostname'];
            }
            $actions['view_on_wordpress'] = '<a href="https://' . $base_hostname . '/?p=' . $wpcom_id . '">' . esc_html__('View post on WordPress.com', 'wp-crosspost') . '</a>';
        }
        return $actions;
    }

    /**
     * Translates a WordPress post status to a WordPress.com API post status.
     *
     * @param string $status The WordPress post status to translate.
     * @return mixed The translated WordPress.com post status or false if the WordPress status has no equivalently compatible state in the API.
     */
    private function WordPressStatus2WordPressDotComStatus ($status) {
        switch ($status) {
            case 'publish':
            case 'private':
            case 'draft':
            case 'pending':
                $state = $status;
                break;
            default:
                $state = false;
        }
        return $state;
    }

    /**
     * Issues a WordPress.com API call.
     *
     * @param string $blog The WordPress.com blog's base hostname (or site ID).
     * @param array @params Any additional parameters for the request.
     * @param int $pid The post ID of a specific post on the remote service (only needed if editing or deleting this post).
     * @param bool $deleting Whether or not to delete, rather than to edit, a specific post.
     * @return array The service's decoded JSON response.
     */
    private function crosspostToWordPressDotCom ($blog, $params, $pid = false, $deleting = false) {
        // TODO: Smoothen this deleting thing.
        //       Cancel WordPress deletions if WordPress.com deletions aren't working?
        if ($deleting === true && $pid) {
            return $this->wpcom->deleteFromService($blog, $pid, $params);
        } else if ($pid) {
            return $this->wpcom->editOnService($blog, $pid, $params);
        } else {
            return $this->wpcom->postToService($blog, $params);
        }
    }
    
    private function isPostCrosspostable ($post_id) {
        $options = get_option($this->prefix . '_settings');
        $crosspostable = true;

        // Do not crosspost if this post is excluded by a certain category.
        if (isset($options['exclude_categories']) && in_category($options['exclude_categories'], $post_id)) {
            $crosspostable = false;
        }

        // Do not crosspost if this specific post was excluded.
        if ('N' === get_post_meta($post_id, $this->prefix . '_crosspost', true)) {
            $crosspostable = false;
        }

        // Do not crosspost unsupported post states.
        if (!$this->WordPressStatus2WordPressDotComStatus(get_post_status($post_id))) {
            $crosspostable = false;
        }

        return $crosspostable;
    }

    /**
     * Translates a WordPress post data for WordPress.com's API.
     *
     * @param int $post_id The ID number of the WordPress post.
     * @return mixed A simple object representing data to crosspost or FALSE if the given post should not be crossposted.
     */
    private function prepareForWordPressDotCom ($post_id) {
        if (!$this->isPostCrosspostable($post_id)) { return false; }

        $options = get_option($this->prefix . '_settings');
        $custom = get_post_custom($post_id);

        $prepared_post = new stdClass();

        // Set the post's destination.
        $base_hostname = false;
        if (!empty($_POST[$this->prefix . '_destination'])) {
            $base_hostname = sanitize_text_field($_POST[$this->prefix . '_destination']);
        } else if (!empty($custom[$this->prefix . '_destination'][0])) {
            $base_hostname = sanitize_text_field($custom[$this->prefix . '_destination'][0]);
        } else {
            $base_hostname = sanitize_text_field($options['default_hostname']);
        }
        update_post_meta($post_id, $this->prefix . '_destination', $base_hostname);
        $prepared_post->base_hostname = $base_hostname;

        $tags = array();
        if ($t = get_the_tags($post_id)) {
            foreach ($t as $tag) {
                // Decode manually so that's the ONLY decoded entity.
                $tags[] = str_replace('&amp;', '&', $tag->name);
            }
        }
        $categories = array();
        if ($c = wp_get_post_categories($post_id)) {
            foreach ($c as $cat) {
                $category = get_category($cat);
                $categories[] = str_replace('&amp;', '&', $category->name);
            }
        }

        $common_params = array(
            'date' => get_post_time('c', true, $post_id),
            'title' => get_the_title($post_id),
            'status' => get_post_status($post_id),
            'password' => get_post_field('post_password', $post_id),
            // TODO: Figure out how to find approriate remote parent id.
            //'parent' => get_post_field('post_parent', $post_id),
            'type' => get_post_type($post_id),
            'format' => get_post_format($post_id),
            'tags' => implode(',', $tags),
            'categories' => implode(',', $categories),
            'slug' => get_post_field('post_name', $post_id),
            'comments_open' => comments_open($post_id),
            'pings_open' => pings_open($post_id),
            'sticky' => is_sticky($post_id)
            // 'publicize' is handled directly in savePost()
            // TODO: 'featured_image'
            // TODO: 'media' => 
            // TODO: 'metadata' => 
            // TODO: 'sharing_enabled' =>
        );

        if (!empty($options['exclude_tags'])) { unset($common_params['tags']); }
        if (!empty($options['crosspost_categories'])) { unset($common_params['categories']); }

        if (!empty($options['additional_tags'])) {
            if (!isset($common_params['tags'])) {
                $common_params['tags'] = '';
            }
            $common_params['tags'] = implode(',', array_merge(explode(',', $common_params['tags']), $options['additional_tags']));
        }

        $post_body = get_post_field('post_content', $post_id);
        $post_excerpt = get_post_field('post_excerpt', $post_id);
        // Mimic wp_trim_excerpt() without The Loop.
        if (empty($post_excerpt)) {
            $text = $post_body;
            $text = strip_shortcodes($text);
            $text = apply_filters('the_content', $text);
            $text = str_replace(']]>', ']]&gt;', $text);
            $text = wp_trim_words($text);
            $post_excerpt = $text;
        }
        $post_body = apply_filters('the_content', $post_body);

        $e = $this->getSettingForPost('use_excerpt', $post_id); // Use excerpt?
        if ($e) {
            $common_params['content'] = $post_excerpt;
        } else {
            $common_params['content'] = $post_body;
            $common_params['excerpt'] = $post_excerpt;
        }

        if (!empty($options['additional_markup'])) {
            $html = $this->replacePlaceholders($options['additional_markup'], $post_id);
            foreach ($common_params as $k => $v) {
                switch ($k) {
                    case 'content':
                    case 'excerpt':
                        $common_params[$k] = $v . $html; // append
                        break;
                }
            }
        }

        $prepared_post->params = $common_params;

        $pid = get_post_meta($post_id, $this->prefix . '_post_id', true); // Will be empty if none exists.
        $prepared_post->wpcom_pid = (empty($pid)) ? false : $pid;

        return $prepared_post;
    }

    public function savePost ($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!isset($_POST[$this->prefix . '_meta_box_nonce']) || !wp_verify_nonce($_POST[$this->prefix . '_meta_box_nonce'], 'editing_' . $this->prefix)) {
            return;
        }
        if (!$this->isConnectedToService()) { return; }

        if (isset($_POST[$this->prefix . '_use_excerpt'])) {
            update_post_meta($post_id, $this->prefix . '_use_excerpt', 1);
        } else {
            delete_post_meta($post_id, $this->prefix . '_use_excerpt');
        }

        if ('N' === $_POST[$this->prefix . '_crosspost']) {
            update_post_meta($post_id, $this->prefix . '_crosspost', 'N'); // 'N' means "no"
            return;
        } else {
            delete_post_meta($post_id, $this->prefix . '_crosspost', 'N');
        }

        if ($prepared_post = $this->prepareForWordPressDotCom($post_id)) {
            // TODO: For some reason array values to `publicize` aren't working. :(
            //       Use booleans only for now.
            if (isset($_POST[$this->prefix . '_publicize'])) {
//                $prepared_post->params['publicize'] = array_map('sanitize_text_field', $_POST[$this->prefix . '_publicize']);
//                $prepared_post->params['publicize_message'] = sanitize_text_field($_POST[$this->prefix . '_publicize_message']);
                $prepared_post->params['publicize'] = true;
            } else {
                $prepared_post->params['publicize'] = false;
            }
            $data = $this->crosspostToWordPressDotCom($prepared_post->base_hostname, $prepared_post->params, $prepared_post->wpcom_pid);
            if (empty($data->ID)) {
                $msg = esc_html__('Crossposting to WordPress.com failed.', 'wp-crosspost');
                if (isset($data->meta)) {
                    $msg .= esc_html__(' Remote service said:', 'wp-crosspost');
                    $msg .= '<blockquote>';
                    $msg .= esc_html__('Response code:', 'wp-crosspost') . " {$data->meta->status}<br />";
                    $msg .= esc_html__('Response message:', 'wp-crosspost') . " {$data->meta->msg}<br />";
                    $msg .= '</blockquote>';
                }
                switch ($data->meta->status) {
                    case 401:
                        $msg .= ' ' . $this->maybeCaptureDebugOf($data);
                        $msg .= sprintf(
                            esc_html__('This might mean your %1$s are invalid or have been revoked by WordPress.com. If everything looks fine on your end, you may want to ask %2$s to confirm your app is still allowed to use their API.', 'wp-crosspost'),
                            '<a href="' . admin_url('options-general.php?page=' . $this->prefix . '_settings') . '">' . esc_html__('OAuth credentials', 'wp-crosspost') . '</a>',
                            $this->linkToWordPressDotComSupport()
                        );
                        break;
                    default:
                        $msg .= ' ' . $this->maybeCaptureDebugOf($data);
                        $msg .= sprintf(
                            esc_html__('Unfortunately, I have no idea what WordPress.com is talking about. Consider asking %1$s for help. Tell them you are using %2$s, that you got the error shown above, and ask them to please support this tool. \'Cause, y\'know, it\'s not like you don\'t already have a WordPress blog, and don\'t they want you to use theirs, too?', 'wp-crosspost'),
                            $this->linkToWordPressDotComSupport(),
                            '<a href="https://wordpress.org/plugins/wp-crosspost/">' . esc_html__('WordPress Crosspost', 'wp-crosspost') . '</a>'
                        );
                        break;
                }
                if (!isset($options['debug'])) {
                    $msg .= '<br /><br />' . sprintf(
                        esc_html__('Additionally, you may want to turn on WordPress Crosspost\'s "%s" option to get more information about this error the next time it happens.', 'wp-crosspost'),
                        '<a href="' . admin_url('options-general.php?page=' . $this->prefix . '_settings#' . $this->prefix . '_debug') . '">'
                        . esc_html__('Enable detailed debugging information?', 'wp-crosspost') . '</a>'
                    );
                }
                $this->addAdminNotices($msg);
            } else {
                update_post_meta($post_id, $this->prefix . '_post_id', $data->ID);
                if ($prepared_post->params['status'] === 'publish') {
                    $url = 'http://' . $this->getWordPressSiteId($post_id) . '/?p=' . get_post_meta($post_id, $this->prefix . '_post_id', true);
                    $this->addAdminNotices(
                        esc_html__('Post crossposted.', 'wp-crosspost') . ' <a href="' . $url . '">' . esc_html__('View post on WordPress.com', 'wp-crosspost') . '</a>'
                    );
                    if ($msg = $this->maybeCaptureDebugOf($data)) { $this->addAdminNotices($msg); }
                }
            }
        }
    }

    private function captureDebugOf ($var) {
        ob_start();
        var_dump($var);
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }

    private function maybeCaptureDebugOf ($var) {
        $msg = '';
        $options = get_option($this->prefix . '_settings');
        if (isset($options['debug'])) {
            $msg .= esc_html__('Debug output:', 'wp-crosspost');
            $msg .= '<pre>' . $this->captureDebugOf($var) . '</pre>';
        }
        return $msg;
    }

    private function linkToWordPressDotComSupport () {
        return '<a href="https://support.wordpress.com/contact/">' . esc_html__('WordPress.com Support', 'wp-crosspost') . '</a>';
    }

    private function addAdminNotices ($msgs) {
        if (is_string($msgs)) { $msgs = array($msgs); }
        $notices = get_option('_' . $this->prefix . '_admin_notices');
        if (empty($notices)) {
            $notices = array();
        }
        $notices = array_merge($notices, $msgs);
        update_option('_' . $this->prefix . '_admin_notices', $notices);
    }

    private function showAdminNotices () {
        $notices = get_option('_' . $this->prefix . '_admin_notices');
        if ($notices) {
            foreach ($notices as $msg) {
                $this->showNotice($msg);
            }
            delete_option('_' . $this->prefix . '_admin_notices');
        }
    }

    private function getWordPressSiteId ($post_id) {
        $d = get_post_meta($post_id, $this->prefix . '_site_id', true);
        if (empty($d)) {
            $options = get_option($this->prefix . '_settings');
            $d = (isset($options['default_hostname'])) ? $options['default_hostname'] : '';
        }
        return $d;
    }

    private function getSettingForPost ($setting, $post_id) {
        $x = get_post_meta($post_id, $this->prefix . '_' . $setting);
        if (empty($x)) {
            $options = get_option($this->prefix . '_' . $setting);
            $x = (isset($options[$setting])) ? $options[$setting] : 0;
        }
        return $x;
    }

    public function removeFromService ($post_id) {
        $options = get_option($this->prefix . '_settings');
        $pid = get_post_meta($post_id, $this->prefix . '_post_id', true);
        $this->crosspostToWordPressDotCom($this->getWordPressSiteId($post_id), array(), $pid, true);
    }

    /**
     * @param array $input An array of of our unsanitized options.
     * @return array An array of sanitized options.
     */
    public function validateSettings ($input) {
        $safe_input = array();
        foreach ($input as $k => $v) {
            switch ($k) {
                case 'consumer_key':
                    if (empty($v)) {
                        $errmsg = __('Consumer key cannot be empty.', 'wp-crosspost');
                        add_settings_error($this->prefix . '_settings', 'empty-consumer-key', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'consumer_secret':
                    if (empty($v)) {
                        $errmsg = __('Consumer secret cannot be empty.', 'wp-crosspost');
                        add_settings_error($this->prefix . '_settings', 'empty-consumer-secret', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'default_hostname':
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'sync_content':
                    $safe_input[$k] = array();
                    foreach ($v as $x) {
                        $safe_input[$k][] = sanitize_text_field($x);
                    }
                break;
                case 'exclude_categories':
                    $safe_v = array();
                    foreach ($v as $x) {
                        $safe_v[] = sanitize_text_field($x);
                    }
                    $safe_input[$k] = $safe_v;
                break;
                case 'additional_markup':
                    $safe_input[$k] = trim($v);
                break;
                case 'use_excerpt':
                case 'exclude_tags':
                case 'crosspost_categories':
                case 'auto_publicize':
                case 'debug':
                    $safe_input[$k] = intval($v);
                break;
                case 'additional_tags':
                    if (is_string($v)) {
                        $tags = explode(',', $v);
                        $safe_tags = array();
                        foreach ($tags as $t) {
                            $safe_tags[] = sanitize_text_field($t);
                        }
                        $safe_input[$k] = $safe_tags;
                    }
                break;
            }
        }
        return $safe_input;
    }

    private function isConnectedToService () {
        $options = get_option($this->prefix . '_settings');
        return isset($this->wpcom) && get_option($this->prefix . '_access_token');
    }

    private function disconnectFromService () {
        @$this->wpcom->client->ResetAccessToken(); // Suppress session_start() warning.
        delete_option($this->prefix . '_access_token');
    }

    public function renderMetaBox ($post) {
        wp_nonce_field('editing_' . $this->prefix, $this->prefix . '_meta_box_nonce');
        if (!$this->isConnectedToService()) {
            $this->showError(__('WordPress Crosspost does not yet have a connection to WordPress.com. Are you sure you connected WordPress Crosspost to your WordPress.com account?', 'wp-crosspost'));
            return;
        }
        $options = get_option($this->prefix . '_settings');

        // Set default crossposting options for this post.
        $x = get_post_meta($post->ID, $this->prefix . '_crosspost', true);
        $d = $this->getWordPressSiteId($post->ID);
        $e = intVal($this->getSettingForPost('use_excerpt', $post->ID));

        $wpcom_id = get_post_meta($post->ID, 'wordpress_post_id', true);
        if ('publish' === $post->post_status && $wpcom_id) {
?>
<p>
    <a href="https://<?php print esc_attr($d);?>/?p=<?php print esc_attr($wpcom_id);?>" class="button button-small"><?php esc_html_e('View post on WordPress.com', 'wp-crosspost');?></a>
</p>
<?php
        }
?>
<fieldset>
    <legend style="display:block;"><?php esc_html_e('Send this post to WordPress.com?', 'wp-crosspost');?></legend>
    <p class="description" style="float: right; width: 75%;"><?php esc_html_e('If this post is in a category that WordPress Crosspost excludes, this will be ignored.', 'wp-crosspost');?></p>
    <ul>
        <li><label><input type="radio" name="<?php esc_attr_e($this->prefix);?>_crosspost" value="Y"<?php if ('N' !== $x) { print ' checked="checked"'; }?>> <?php esc_html_e('Yes', 'wp-crosspost');?></label></li>
        <li><label><input type="radio" name="<?php esc_attr_e($this->prefix);?>_crosspost" value="N"<?php if ('N' === $x) { print ' checked="checked"'; }?>> <?php esc_html_e('No', 'wp-crosspost');?></label></li>
    </ul>
</fieldset>
<fieldset>
    <legend><?php esc_html_e('Crossposting options', 'wp-crosspost');?></legend>
    <details open="open">
        <summary><?php esc_html_e('Destination & content', 'wp-crosspost');?></summary>
        <p><label>
            <?php esc_html_e('Send to my site named', 'wp-crosspost');?>
            <?php print $this->wordpressBlogsSelectField(array('name' => $this->prefix . '_destination'), $d);?>
        </label></p>
        <p><label>
            <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_use_excerpt" value="1"
                <?php if (1 === $e) { print 'checked="checked"'; } ?>
                title="<?php esc_html_e('Uncheck to send post content as crosspost content.', 'wp-crosspost');?>"
                />
            <?php esc_html_e('Send excerpt instead of main content?', 'wp-crosspost');?>
        </label></p>
    </details>
</fieldset>
    <?php if ($post->post_status !== 'publish') { ?>
<fieldset>
    <legend><?php esc_html_e('Social media broadcasts', 'wp-crosspost');?></legend>
    <details open="open"><!-- Leave open until browsers work out their keyboard accessibility issues with this. -->
        <summary><?php esc_html_e('Publicize to my accounts on', 'wp-crosspost');?></summary>
        <ul>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="1"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('all connected social networks', 'wp-crosspost');?>
            </label></li>
<!--
TODO: Why won't an array value work?
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="Facebook"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('Facebook', 'wp-crosspost');?>
            </label></li>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="LinkedIn"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('LinkedIn', 'wp-crosspost');?>
            </label></li>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="GooglePlus"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('Google+', 'wp-crosspost');?>
            </label></li>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="Tumblr"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('Tumblr', 'wp-crosspost');?>
            </label></li>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="Twitter"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('Twitter', 'wp-crosspost');?>
            </label></li>
            <li><label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_publicize[]" value="Path"
                    <?php if (!empty($options['auto_publicize'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-post.', 'wp-crosspost');?>"
                    />
                <?php esc_html_e('Path', 'wp-crosspost');?>
            </label></li>
-->
        </ul>
        <p>
            <label>
                <textarea id="<?php esc_attr_e($this->prefix);?>_publicize_message"
                    name="<?php esc_attr_e($this->prefix);?>_publicize_message"
                    title="<?php esc_attr_e('If your WordPress automatically publicizes new posts to your connected social media accounts, you can customize the message that will appear before the link to your post by entering it here.', 'wp-crosspost');?>"
                    style="width:100%;"
                    placeholder="<?php print sprintf(esc_attr__('New post: %s :)', 'tumblr-crosspostr'), esc_attr__($post->post_title));?>"></textarea>
                <span class="description"><?php esc_html_e('If you would like a custom message to appear before the link to your post, enter it here.', 'wp-crosspost');?></span>
            </label>
        </p>
    </details>
</fieldset>
<?php
        }
    }

    /**
     * Writes the HTML for the options page, and each setting, as needed.
     */
    // TODO: Add contextual help menu to this page.
    public function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-crosspost'));
        }
        $options = get_option($this->prefix . '_settings');
        if (isset($_GET['disconnect']) && wp_verify_nonce($_GET[$this->prefix . '_nonce'], 'disconnect_from_wordpress')) {
            $this->disconnectFromService();
?>
<div class="updated">
    <p>
        <?php esc_html_e('Disconnected from WordPress.com.', 'wp-crosspost');?>
        <span class="description"><?php esc_html_e('The connection to WordPress.com was disestablished. You can reconnect using the same credentials, or enter different credentials before reconnecting.', 'wp-crosspost');?></span>
    </p>
</div>
<?php
        }
?>
<h2><?php esc_html_e('WordPress Crosspost Settings', 'wp-crosspost');?></h2>
<form method="post" action="options.php">
<?php settings_fields($this->prefix . '_settings');?>
<fieldset><legend><?php esc_html_e('Connection to WordPress.com', 'wp-crosspost');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Required settings to connect to WordPress.', 'wp-crosspost');?>">
    <tbody>
        <tr<?php if (get_option($this->prefix . '_access_token')) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_consumer_key"><?php esc_html_e('WordPress Client ID', 'wp-crosspost');?></label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_consumer_key" name="<?php esc_attr_e($this->prefix);?>_settings[consumer_key]" value="<?php print esc_attr($options['consumer_key']);?>" placeholder="<?php esc_attr_e('Paste your Client ID key here', 'wp-crosspost');?>" />
                <p class="description">
                    <?php esc_html_e('Your WordPress Client ID is also called your "API key" or "consumer key."', 'wp-crosspost');?>
                    <?php print sprintf(
                        esc_html__('If you need a Client ID, you can %s.', 'wp-crosspost'),
                        '<a href="' . esc_attr($this->getWordPressAppRegistrationUrl()) . '" target="_blank" ' .
                        'title="' . __('Get a Client ID from WordPress.com by registering your WordPress blog as a new WordPress.com app.', 'wp-crosspost') . '">' .
                        __('create one here', 'wp-crosspost') . '</a>'
                    );?>
                </p>
            </td>
        </tr>
        <tr<?php if (get_option($this->prefix . '_access_token')) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_consumer_secret"><?php esc_html_e('OAuth consumer secret', 'wp-crosspost');?></label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_consumer_secret" name="<?php esc_attr_e($this->prefix);?>_settings[consumer_secret]" value="<?php print esc_attr($options['consumer_secret']);?>" placeholder="<?php esc_attr_e('Paste your client secret here', 'wp-crosspost');?>" />
                <p class="description">
                    <?php esc_html_e('Your client secret, also called your "consumer secret," is like your app password. Never share this with anyone.', 'wp-crosspost');?>
                </p>
            </td>
        </tr>
        <?php if (!get_option($this->prefix . '_access_token') && isset($options['consumer_key']) && isset($options['consumer_secret'])) { ?>
        <tr>
            <th class="wp-ui-notification" style="border-radius: 5px; padding: 10px;">
                <label for="<?php esc_attr_e($this->prefix);?>_oauth_authorize"><?php esc_html_e('Connect to WordPress.com:', 'wp-crosspost');?></label>
            </th>
            <td>
                <a href="<?php print wp_nonce_url(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_oauth_authorize'), 'wordpress-authorize');?>" class="button button-primary"><?php esc_html_e('Click here to connect to WordPress.com', 'wp-crosspost');?></a>
            </td>
        </tr>
        <?php } else if (get_option($this->prefix . '_access_token')) { ?>
        <tr>
            <th colspan="2">
                <div class="updated">
                    <p>
                        <?php esc_html_e('Connected to WordPress.com!', 'wp-crosspost');?>
                        <a href="<?php print wp_nonce_url(admin_url('options-general.php?page=' . $this->prefix . '_settings&disconnect'), 'disconnect_from_wordpress', $this->prefix . '_nonce');?>" class="button"><?php esc_html_e('Disconnect', 'wp-crosspost');?></a>
                        <span class="description"><?php esc_html_e('Disconnecting will stop cross-posts from appearing on or being imported from your WordPress.com site(s), and will reset the options below to their defaults. You can re-connect at any time.', 'wp-crosspost');?></span>
                    </p>
                </div>
            </th>
        </tr>
        <?php } ?>
    </tbody>
</table>
</fieldset>
        <?php if (get_option($this->prefix . '_access_token')) { ?>
<fieldset><legend><?php esc_html_e('Crossposting Options', 'wp-crosspost');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Options for customizing crossposting behavior.', 'wp-crosspost');?>">
    <tbody>
        <tr<?php if (!isset($options['default_hostname'])) : print ' class="wp-ui-highlight"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_default_hostname"><?php esc_html_e('Default WordPress blog for crossposts', 'wp-crosspost');?></label>
            </th>
            <td>
                <?php print $this->wordpressBlogsSelectField(array('id' => $this->prefix . '_default_hostname', 'name' => $this->prefix . '_settings[default_hostname]'), $this->getWordPressSiteId(0));?>
                <p class="description"><?php esc_html_e('Choose which WordPress blog you want to send your posts to by default. This can be overriden on a per-post basis, too.', 'wp-crosspost');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_sync_content"><?php esc_html_e('Sync posts from WordPress.com', 'wp-crosspost');?></label>
                <p class="description"><?php esc_html_e('(This feature is experimental. Please backup your website before you turn this on.)', 'wp-crosspost');?></p>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_sync_content">
                    <?php print $this->wordpressBlogsListCheckboxes(array('id' => $this->prefix . '_sync_content', 'name' => $this->prefix . '_settings[sync_content][]'), $options['sync_content']);?>
                </ul>
                <p class="description"><?php esc_html_e('Content you create on the WordPress blogs you select will automatically be copied to this blog.', 'wp-crosspost');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_categories"><?php esc_html_e('Do not crosspost entries in these categories:');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_exclude_categories">
                <?php foreach (get_categories(array('hide_empty' => 0)) as $cat) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                <?php if (isset($options['exclude_categories']) && in_array($cat->slug, $options['exclude_categories'])) : print 'checked="checked"'; endif;?>
                                value="<?php print esc_attr($cat->slug);?>"
                                name="<?php esc_attr_e($this->prefix);?>_settings[exclude_categories][]">
                            <?php print esc_html($cat->name);?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php esc_html_e('Will cause posts in the specificied categories never to be crossposted. This is useful if, for instance, you are creating posts automatically using another plugin and wish to avoid a feedback loop of crossposting back and forth from one service to another.', 'wp-crosspost');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_use_excerpt"><?php esc_html_e('Send excerpts instead of main content?', 'wp-crosspost');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['use_excerpt'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_use_excerpt" name="<?php esc_attr_e($this->prefix);?>_settings[use_excerpt]" />
                <label for="<?php esc_attr_e($this->prefix);?>_use_excerpt"><span class="description"><?php esc_html_e('When enabled, the excerpts (as opposed to the body) of your WordPress posts will be used as the main content of your crossposts. Useful if you prefer to crosspost summaries instead of the full text of your entires by default. This can be overriden on a per-post basis, too.', 'wp-crosspost');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_additional_markup"><?php esc_html_e('Add the following markup to each crossposted entry:', 'wp-crosspost');?></label>
            </th>
            <td>
                <textarea
                    id="<?php esc_attr_e($this->prefix);?>_additional_markup"
                    name="<?php esc_attr_e($this->prefix);?>_settings[additional_markup]"
                    placeholder="<?php esc_attr_e('Anything you type in this box will be added to every crosspost.', 'wp-crosspost');?>"><?php
        if (isset($options['additional_markup'])) {
            print esc_textarea($options['additional_markup']);
        } else {
            print '<p class="wp-crosspost-linkback"><a href="%permalink%" title="' . esc_html__('Go to the original post.', 'wp-crosspost') . '" rel="bookmark">%the_title%</a> ' . esc_html__('was originally published on', 'wp-crosspost') . ' <a href="%blog_url%">%blog_name%</a></p>';
        }
?></textarea>
                <p class="description"><?php _e('Text or HTML you want to add to each post. Useful for things like a link back to your original post. You can use <code>%permalink%</code>, <code>%the_title%</code>, <code>%blog_url%</code>, and <code>%blog_name%</code> as placeholders for the cross-posted post\'s link, its title, the link to the homepage for this site, and the name of this blog, respectively.', 'wp-crosspost');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_tags"><?php esc_html_e('Do not send post tags in crossposts', 'wp-crosspost');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['exclude_tags'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_exclude_tags" name="<?php esc_attr_e($this->prefix);?>_settings[exclude_tags]" />
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_tags"><span class="description"><?php esc_html_e('When enabled, tags on your WordPress posts are not applied to your crossposts. Useful if you maintain different taxonomies on your different sites.', 'wp-crosspost');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_crosspost_categories"><?php esc_html_e('Do not send post categories in crossposts', 'wp-crosspost');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['crosspost_categories'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_crosspost_categories" name="<?php esc_attr_e($this->prefix);?>_settings[crosspost_categories]" />
                <label for="<?php esc_attr_e($this->prefix);?>_crosspost_categories"><span class="description"><?php esc_html_e('When enabled, categories on your WordPress posts are not applied to your crossposts. Useful if you maintain different taxonomies on your different sites.', 'wp-crosspost');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_additional_tags">
                    <?php esc_html_e('Automatically add these tags to all crossposts:', 'wp-crosspost');?>
                </label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_additional_tags" value="<?php if (isset($options['additional_tags'])) : print esc_attr(implode(', ', $options['additional_tags'])); endif;?>" name="<?php esc_attr_e($this->prefix);?>_settings[additional_tags]" placeholder="<?php esc_attr_e('crosspost, magic', 'wp-crosspost');?>" />
                <p class="description"><?php print sprintf(esc_html__('Comma-separated list of additional tags that will be added to every crosspost. Useful if only some posts on your other blog are cross-posted and you want to know which of those posts were generated by this plugin. (These tags will always be applied regardless of the value of the "%s" option.)', 'wp-crosspost'), esc_html__('Do not send post tags in crossposts', 'wp-crosspost'));?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_auto_publicize">
                    <?php esc_html_e('Automatically publicize a link to your WordPress post on connected social networks?', 'wp-crosspost');?>
                </label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['auto_publicize'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_auto_publicize" name="<?php esc_attr_e($this->prefix);?>_settings[auto_publicize]" />
                <label for="<?php esc_attr_e($this->prefix);?>_auto_publicize"><span class="description"><?php print sprintf(esc_html__('When checked, new posts you create on WordPress will have their "%s" option enabled by default. You can always override this when editing an individual post.', 'wp-crosspost'), esc_html__('Publicize to my accounts on', 'wp-crosspost'));?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_debug">
                    <?php esc_html_e('Enable detailed debugging information?', 'wp-crosspost');?>
                </label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['debug'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_debug" name="<?php esc_attr_e($this->prefix);?>_settings[debug]" />
                <label for="<?php esc_attr_e($this->prefix);?>_debug"><span class="description"><?php
        print sprintf(
            esc_html__('Turn this on only if you are experiencing problems using this plugin, or if you were told to do so by someone helping you fix a problem (or if you really know what you are doing). When enabled, extremely detailed technical information is displayed as a WordPress admin notice when you take actions like sending a crosspost. If you have also enabled WordPress\'s built-in debugging (%1$s) and debug log (%2$s) feature, additional information will be sent to a log file (%3$s). This file may contain sensitive information, so turn this off and erase the debug log file when you have resolved the issue.', 'wp-crosspost'),
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG"><code>WP_DEBUG</code></a>',
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG_LOG"><code>WP_DEBUG_LOG</code></a>',
            '<code>' . content_url() . '/debug.log' . '</code>'
        );
                ?></span></label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
        <?php } ?>
<?php submit_button();?>
</form>
<?php
        $this->showDonationAppeal();
    } // end public function renderOptionsPage

    public function dispatchCrosspostArchivesPages () {
        if (!isset($_GET[$this->prefix . '_nonce']) || !wp_verify_nonce($_GET[$this->prefix . '_nonce'], 'crosspost_everything')) {
            $this->renderManagementPage();
        } else {
            if (!$this->isConnectedToService()) {
                wp_redirect(admin_url('options-general.php?page=' . $this->prefix . '_settings'));
                exit();
            }
            $posts = get_posts(array(
                'nopaging' => true,
                'order' => 'ASC',
            ));
            $crosspostified = array();
            foreach ($posts as $post) {
                if ($prepared_post = $this->prepareForWordPressDotCom($post->ID)) {
                    $data = $this->crosspostToWordPressDotCom($prepared_post->base_hostname, $prepared_post->params, $prepared_post->wpcom_pid);
                    update_post_meta($post->ID, 'wordpress_post_id', $data->ID);
                    $crosspostified[] = array('id' => $data->ID, 'base_hostname' => $prepared_post->base_hostname);
                }
            }
            $blogs = array();
            foreach ($crosspostified as $p) {
                $blogs[] = $p['base_hostname'];
            }
            $blogs_touched = count(array_unique($blogs));
            $posts_touched = count($crosspostified);
            print '<p>' . sprintf(
                _n(
                    'Success! %1$d post has been crossposted.',
                    'Success! %1$d posts have been crossposted to %2$d blogs.',
                    $posts_touched,
                    'wp-crosspost'
                ),
                $posts_touched,
                $blogs_touched
            ) . '</p>';
            print '<p>' . esc_html_e('Blogs touched:', 'wp-crosspost') . '</p>';
            print '<ul>';
            foreach (array_unique($blogs) as $blog) {
                print '<li><a href="' . esc_url("https://$blog/") . '">' . esc_html($blog) . '</a></li>';
            }
            print '</ul>';
            $this->showDonationAppeal();
        }
    }

    private function renderManagementPage () {
        $options = get_option($this->prefix . '_settings');
?>
<h2><?php esc_html_e('Crosspost Archives to WordPress.com', 'wp-crosspost');?></h2>
<p><?php esc_html_e('If you have post archives on this website, WordPress Crosspost can copy them to your WordPress.com blog.', 'wp-crosspost');?></p>
<p><a href="<?php print wp_nonce_url(admin_url('tools.php?page=' . $this->prefix . '_crosspost_archives'), 'crosspost_everything', $this->prefix . '_nonce');?>" class="button button-primary"><?php esc_html_e('Crosspost-ify Everything!', 'wp-crosspost');?></a></p>
<p class="description"><?php print sprintf(esc_html__('Copies all posts from your archives to your default WordPress.com blog (%s). This may take some time if you have a lot of content. If you do not want to crosspost a specific post, set the answer to the "Send this post to WordPress.com?" question to "No" when editing those posts before taking this action. If you have previously crossposted some posts, this will update that content on your WordPress.com blog(s).', 'wp-crosspost'), '<code>' . esc_html($options['default_hostname']) . '</code>');?></p>
<?php
        $this->showDonationAppeal();
    } // end renderManagementPage ()


    private function getBlogsToSync () {
        $options = get_option($this->prefix . '_settings');
        return (empty($options['sync_content'])) ? array() : $options['sync_content'];
    }

    public function setSyncSchedules () {
        if (!$this->isConnectedToService()) { return; }
        $options = get_option($this->prefix . '_settings');
        $blogs_to_sync = (empty($options['sync_content'])) ? array() : $options['sync_content'];
        // If we are being asked to sync, set up a daily schedule for that.
        if (!empty($blogs_to_sync)) {
            foreach ($blogs_to_sync as $x) {
                if (!wp_get_schedule($this->prefix . '_sync_content', array($x))) {
                    wp_schedule_event(time(), 'daily', $this->prefix . '_sync_content', array($x));
                }
            }
        }
        // For any blogs we know of but aren't being asked to sync,
        $known_blogs = array();
        $blog = $this->wpcom->getTokenSiteInfo();
        $known_blogs[] = parse_url($blog->URL, PHP_URL_HOST);
        $to_unschedule = array_diff($known_blogs, $blogs_to_sync);
        foreach ($to_unschedule as $x) {
            // check to see if there's a scheduled event to sync it, and,
            // if so, unschedule it.
            wp_unschedule_event(
                wp_next_scheduled($this->prefix . '_sync_content', array($x)),
                $this->prefix . '_sync_content',
                array($x)
            );
        }
    }

    public function deactivate () {
        $blogs_to_sync = $this->getBlogsToSync();
        if (!empty($blogs_to_sync)) {
            foreach ($blogs_to_sync as $blog) {
                wp_clear_scheduled_hook($this->prefix . '_sync_content', array($blog));
            }
        }
    }

    public function syncFromWordPressBlog ($base_hostname) {
        $options = get_option($this->prefix . '_settings');
        if (!isset($options['last_synced_ids'])) {
            $options['last_synced_ids'] = array();
        }
        $latest_synced_id = (isset($options['last_synced_ids'][$base_hostname]))
            ? $options['last_synced_ids'][$base_hostname]
            : 0;

        $ids_synced = array(0); // Init with 0
        $offset = 0;
        $limit = 100;
        $num_posts_to_get = 0;
        // If we never synced, trawl through entire archive.
        if (0 === $latest_synced_id) {
            $info = $this->wpcom->getTokenSiteInfo($base_hostname);
            $num_posts_to_get = $info->post_count; // get all of them
        } else {
            $num_posts_to_get = $limit * 2; // Just get the last 2 batches.
        }
        $i = 0;
        while ($i < $num_posts_to_get) {
            $params = array(
                'context' => 'edit', // Don't parse shortcodes, etc.
                'offset' => $offset,
                'number' => $limit,
                'type' => 'any',
                'status' => 'any',
                'order' => 'DESC',
                'order_by' => 'date'
            );
            $resp = $this->wpcom->getPosts($base_hostname, $params);
            // If there aren't as many posts as we're trying to get,
            if ($resp->found <= $num_posts_to_get) {
                // reset the loop condition so we only try getting
                // as many posts that actually exist.
                $num_posts_to_get = $resp->found;
            }
            $posts = $resp->posts;
            foreach (array_reverse($posts) as $post) { // "older" posts first
                $preexisting_posts = get_posts(array(
                    'meta_key' => 'wordpress_post_id',
                    'meta_value' => $post->ID
                ));
                if (empty($preexisting_posts)) {
                    if ($this->importPostFromWordPress($post)) {
                        $ids_synced[] = $post->ID;
                    }
                }
                $i++; // in foreach cuz we're counting posts
            }
            $offset = $offset + $limit; // Set up next fetch.
        }

        // Record the latest post ID to be sync'ed on the blog.
        $options['last_synced_ids'][$base_hostname] = ($latest_synced_id > max($ids_synced))
            ? $latest_synced_id
            : max($ids_synced);
        update_option($this->prefix . '_settings', $options);
    }

    /**
     * Searches the site taxonomy for categories matching the input object.
     *
     * @param object $categories Categories object returned by WordPress.com REST API
     * @return array An array of integers of local category IDs as closely matched as possible to the input.
     */
    private function correlateCategories ($categories) {
        require_once ABSPATH . 'wp-admin/includes/taxonomy.php'; // in case we're doing CRON
        $cat_ids = array();
        foreach ($categories as $k => $v) {
            if ($c = get_category_by_slug($v->slug)) {
                $cat_ids[] = $c->term_id;
            } else {
                $cat_ids[] = wp_insert_category(array(
                    'cat_name' => $v->name,
                    'category_nicename' => $v->slug,
                    'category_description' => $v->description
                    // TODO: Mirror category hierarchy with parents?
                ));
            }
        }
        return $cat_ids;
    }

    private function importPostFromWordPress ($post) {
        $wp_post = array();
        $wp_post['post_name'] = $post->slug;
        $wp_post['post_content'] = $post->content;
        $wp_post['post_excerpt'] = $post->excerpt;
        $wp_post['post_title'] = $post->title;
        $wp_post['post_status'] = $post->status;
        $wp_post['post_type'] = $post->type;
        $wp_post['post_parent'] = (false === $post->parent) ? 0 : $post->parent;
        $wp_post['post_password'] = $post->password;
        // TODO: Figure out how to handle multi-author blogs.
        //$wp_post['post_author'] = $post->author;
        $wp_post['post_date'] = date('Y-m-d H:i:s', strtotime($post->date));
        $wp_post['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($post->date));
        $wp_post['comment_status'] = ($post->comments_open) ? 'open' : 'closed';
        $wp_post['ping_status'] = ($post->pings_open) ? 'open' : 'closed';
        $wp_post['tags_input'] = $post->tags;
        $wp_post['post_category'] = $this->correlateCategories($post->categories);

        $wp_id = wp_insert_post($wp_post);
        if ($wp_id) {
            set_post_format($wp_id, $post->format);
            update_post_meta($wp_id, $this->prefix . '_destination', parse_url($post->URL, PHP_URL_HOST));
            update_post_meta($wp_id, 'wordpress_post_id', $post->ID);
            if (!empty($post->metadata)) {
                foreach ($post->metadata as $m) {
                    update_post_meta($wp_id, $this->prefix . '_' . parse_url($post->URL, PHP_URL_HOST) . '_' . $m->key, $m->value);
                }
            }
            if ($post->geo) {
                update_post_meta($wp_id, 'geo_latitude', $post->geo->latitude);
                update_post_meta($wp_id, 'geo_longitude', $post->geo->longitude);
                if (!empty($post->geo->address)) {
                    update_post_meta($wp_id, 'geo_address', $post->geo->address);
                }
            }

            // Import attachments
            if ($post->attachments) {
                $wp_subdir_from_post_timestamp = date('Y/m', strtotime($post->date));
                $wp_upload_dir = wp_upload_dir($wp_subdir_from_post_timestamp);
                if (!is_writable($wp_upload_dir['path'])) {
                    $msg = sprintf(
                        esc_html__('Your WordPress uploads directory (%s) is not writeable, so WordPress Crosspost could not import some media files directly into your Media Library. Media (such as images) will be referenced from their remote source rather than imported and referenced locally.', 'wp-crosspost'),
                        $wp_upload_dir['path']
                    );
                    error_log($msg);
                } else {
                    foreach ($post->attachments as $attachment) {
                        $data = wp_remote_get($attachment->URL);
                        if (200 != $data['response']['code']) {
                            $msg = sprintf(
                                esc_html__('Failed to get attachment (%1$s) from post (%2$s). Server responded: %3$s', 'wp-crosspost'),
                                $attachment->URL,
                                $post->URL,
                                print_r($data, true)
                            );
                            error_log($msg);
                        } else {
                            $f = wp_upload_bits(basename($attachment->URL), null, $data['body'], $wp_subdir_from_post_timestamp);
                            if ($f['error']) {
                                $msg = sprintf(
                                    esc_html__('Error saving file (%s): ', 'wp-crosspost'),
                                    basename($attachment->URL)
                                );
                                error_log($msg);
                            } else {
                                $wp_filetype = wp_check_filetype(basename($f['file']));
                                $wp_file_id = wp_insert_attachment(array(
                                    'post_title' => basename($f['file'], ".{$wp_filetype['ext']}"),
                                    'post_content' => '', // Always empty string.
                                    'post_status' => 'inherit',
                                    'post_mime_type' => $wp_filetype['type'],
                                    'guid' => $wp_upload_dir['url'] . '/' . basename($f['file'])
                                ), $f['file'], $wp_id);
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                $metadata = wp_generate_attachment_metadata($wp_file_id, $f['file']);
                                wp_update_attachment_metadata($wp_file_id, $metadata);
                                $new_content = str_replace($attachment->URL, $f['url'], get_post_field('post_content', $wp_id));
                                wp_update_post(array(
                                    'ID' => $wp_id,
                                    'post_content' => $new_content
                                ));
                            }
                        }
                    }
                }
            }
        }
        return $wp_id;
    }

    private function getWordPressAppRegistrationUrl () {
        $params = array(
            'title' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'redirect_uri' => admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_callback')
        );
        return $this->wpcom->getAppRegistrationUrl($params);
    }

    private function wordpressBlogsSelectField ($attributes = array(), $selected = false) {
        $html = '<select';
        if (!empty($attributes)) {
            foreach ($attributes as $k => $v) {
                $html .=  ' ' . $k . '="' . esc_attr($v) . '"';
            }
        }
        $html .= '>';
        $blog = $this->wpcom->getTokenSiteInfo();
        $html .= '<option value="' . esc_attr(parse_url($blog->URL, PHP_URL_HOST)) . '"';
        if ($selected && $selected === parse_url($blog->URL, PHP_URL_HOST)) {
            $html .= ' selected="selected"';
        }
        $html .= '>';
        $html .= esc_html($blog->name);
        $html .= '</option>';
        $html .= '</select>';
        return $html;
    }

    private function wordpressBlogsListCheckboxes ($attributes = array(), $selected = false) {
        $html = '';
        $blog = $this->wpcom->getTokenSiteInfo();
        $html .= '<li>';
        $html .= '<label>';
        $x = parse_url($blog->URL, PHP_URL_HOST);
        $html .= '<input type="checkbox"';
        if (!empty($attributes)) {
            foreach ($attributes as $k => $v) {
                $html .= ' ';
                switch ($k) {
                    case 'id':
                        $html .= $k . '="' . esc_attr($v) . '-' . esc_attr($x) . '"';
                        break;
                    default:
                        $html .= $k . '="' . esc_attr($v) . '"';
                        break;
                }
            }
        }
        if ($selected && in_array($x, $selected)) {
            $html .= ' checked="checked"';
        }
        $html .= ' value="' . esc_attr($x) . '"';
        $html .= '>';
        $html .= esc_html($blog->name) . '</label>';
        $html .= '</li>';
        return $html;
    }

}

$WP_Crosspost = new WP_Crosspost();
