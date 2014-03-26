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
