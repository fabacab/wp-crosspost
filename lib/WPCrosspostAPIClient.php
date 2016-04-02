<?php
/**
 * Super-skeletal class to interact with WordPress.com.
 */

// Loads OAuth consumer class via OAuthWP class.
require_once 'OAuthWP_WordPressDotCom.php';

class WP_Crosspost_API_Client extends WordPressDotCom_OAuthWP_Plugin {

    public function __construct ($client_id = '', $client_secret = '') {
        $this->client = new OAuthWP_WordPressDotCom;
        $this->client->client_id = $client_id;
        $this->client->client_secret = $client_secret;
        $this->client->Initialize();

        return $this;
    }

    public function getUserInfo () {
        return $this->talkToService('/me', array(), 'GET');
    }

    public function getTokenSiteInfo () {
        $data = $this->getUserInfo();
        return $this->talkToService('/sites/' . $data->token_site_id, array(), 'GET');
    }

    /**
     * Prepares a query string for a request.
     *
     * @param string $url
     * @param array $params
     *
     * @return string
     */
    private function prepareUrlParams ($url, $params = array()) {
        if (!empty($params)) {
            $url .= '?';
            foreach ($params as $k => $v) {
                $url .= "$k=$v&";
            }
            // Strip trailing '&' if it's there.
            if ('&' === substr($url, -1)) {
                $url = substr($url, 0, strlen($url) - 1);
            }
        }
        return $url;
    }

    /**
     * Gets information about blog posts on the remote site.
     *
     * @link https://developer.wordpress.com/docs/api/1.1/get/sites/%24site/posts/
     *
     * @param string $site
     * @param array $params
     *
     * @return stdClass
     */
    public function getPosts ($site, $params = array()) {
        $url = "/sites/$site/posts/";
        return $this->talkToService($this->prepareUrlParams($url, $params), array(), 'GET');
    }

    /**
     * Gets information about the remote site's Media Library.
     *
     * @link https://developer.wordpress.com/docs/api/1.1/get/sites/%24site/media/
     *
     * @param string $site
     * @param array $params
     *
     * @return stdClass
     */
    public function getMedia ($site, $params = array()) {
        $url = "/sites/$site/media/";
        return $this->talkToService($this->prepareUrlParams($url, $params), array(), 'GET');
    }

    /**
     * Gets information about a media item in the remote site's Media Library.
     *
     * @link https://developer.wordpress.com/docs/api/1.1/get/sites/%24site/media/%24media_ID/
     *
     * @param string $site
     * @param int $media_ID
     * @param array $params
     *
     * @return stdClass
     */
    public function getMediaByID ($site, $media_ID, $params = array()) {
        $url = "/sites/$site/media/$media_ID";
        return $this->talkToService($this->prepareUrlParams($url, $params), array(), 'GET');
    }

    public function postToService ($site, $params) {
        $api_method = "/sites/$site/posts/new";
        return $this->talkToService($api_method, $params);
    }

    public function editOnService ($site, $pid, $params = array()) {
        $api_method = "/sites/$site/posts/$pid";
        return $this->talkToService($api_method, $params);
    }

    public function deleteFromService ($site, $pid, $params = array()) {
        $api_method = "/sites/$site/posts/$pid/delete";
        return $this->talkToService($api_method, $params);
    }

    /**
     * Creates a new media object on the remote service.
     *
     * @link https://developer.wordpress.com/docs/api/1.1/post/sites/%24site/media/new/
     * @link http://www.phpclasses.org/package/7700-PHP-Authorize-and-access-APIs-using-OAuth.html
     *
     * @param string $site
     * @param array $params
     * @param array $opts Parameters to pass to underlying OAuth library.
     *
     * @return stdClass
     */
    public function uploadToService ($site, $params, $opts) {
        $api_method = "/sites/$site/media/new";
        return $this->talkToService($api_method, $params, 'POST', $opts);
    }

}
