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

    public function getPosts ($blog, $params = array()) {
        $url = "/sites/$blog/posts/";
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
        return $this->talkToService($url, array(), 'GET');
    }

    public function postToService ($blog, $params) {
        $api_method = "/sites/$blog/posts/new";
        return $this->talkToService($api_method, $params);
    }

    public function editOnService ($blog, $pid, $params = array()) {
        $api_method = "/sites/$blog/posts/$pid";
        return $this->talkToService($api_method, $params);
    }

    public function deleteFromService ($blog, $pid, $params = array()) {
        $api_method = "/sites/$blog/posts/$pid/delete";
        return $this->talkToService($api_method, $params);
    }

}
