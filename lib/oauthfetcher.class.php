<?php

class OAuthFetcher {

    private $oauth;

    const CONSKEY    = 'jonty.co.uk';
    const CONSSEC    = 'DIRpj0xSbe/vOjgU/CBUAYq5';

    const REQ_URL    = 'https://www.google.com/accounts/OAuthGetRequestToken';
    const AUTH_URL   = 'https://www.google.com/latitude/apps/OAuthAuthorizeToken';
    const ACC_URL    = 'https://www.google.com/accounts/OAuthGetAccessToken';

    function __construct() {

        // In state=1 the next request should include an oauth_token.
        // If it doesn't go back to 0
        if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) {
            $_SESSION['state'] = 0;
        }

        try {

            $oauth = new OAuth(
                self::CONSKEY, self::CONSSEC, 
                OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI
            );
            $oauth->enableDebug();

            if ($_SESSION['state'] != 2) {

                if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {

                    $queryString = http_build_query(array(
                        'scope' => 'https://www.googleapis.com/auth/latitude',
                        'oauth_callback' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
                    ));

                    $requestToken = $oauth->getRequestToken(self::REQ_URL.'?'.$queryString);
                    $_SESSION['secret'] = $requestToken['oauth_token_secret'];
                    $_SESSION['state']  = 1;
                    
                    $queryString = http_build_query(array(
                        'oauth_token'   => $requestToken['oauth_token'],
                        'domain'        => $_SERVER['HTTP_HOST'],
                        'location'      => 'all',
                        'granularity'   => 'best',
                    ));

                    header('Location: '.self::AUTH_URL.'?'.$queryString);
                    exit;

                } else if($_SESSION['state'] == 1) {

                    $oauth->setToken($_GET['oauth_token'], $_SESSION['secret']);
                    $accessToken = $oauth->getAccessToken(self::ACC_URL);
                    
                    $_SESSION['state']  = 2;
                    $_SESSION['token']  = $accessToken['oauth_token'];
                    $_SESSION['secret'] = $accessToken['oauth_token_secret'];
                }

            }

            $oauth->setToken($_SESSION['token'], $_SESSION['secret']);
        
        } catch(OAuthException $e) {
            trigger_error("OAuth fail: " . print_r($e, true));
            print "Oh dear, something failed during the OAuth handshake with google!";
            exit;
        }

        $this->oauth = $oauth;
    }

    function fetch($url) {
        return $this->oauth->fetch($url);
    }

    function getLastResponse() {
        return $this->oauth->getLastResponse();
    }
}


