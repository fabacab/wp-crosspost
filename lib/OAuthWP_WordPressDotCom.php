<?php
require_once 'OAuthWP.php';

class OAuthWP_WordPressDotCom extends OAuthWP {

    public function __construct () {
        // Set WordPress's variables.
        $this->authorize_url = 'https://public-api.wordpress.com/oauth2/authorize';
        $this->request_token_url = 'https://public-api.wordpress.com/oauth2/token';
        $this->access_token_url = 'https://public-api.wordpress.com/oauth2/token';
        $this->dialog_url = $this->authorize_url . '?client_id={CLIENT_ID}'
            . '&redirect_uri={REDIRECT_URI}&response_type=code&state={STATE}';
        $this->access_token_type = 'Bearer';
    }

    // Override so clients can ignore the API base url.
    public function CallAPI ($url, $method, $params, $opts, &$resp) {
        return parent::CallAPI('https://public-api.wordpress.com/rest/v1' . $url, $method, $params, $opts, $resp);
    }
}

abstract class WordPressDotCom_OAuthWP_Plugin extends Plugin_OAuthWP {

    public function getAppRegistrationUrl ($params = array()) {
        $params = array(
            'title' => $params['title'],
            'description' => $params['description'],
            'url' => $params['url'],
            'redirect_uri' => $params['redirect_uri']
        );
        return $this->appRegistrationUrl('https://developer.wordpress.com/apps/new/', $params);
    }

}
