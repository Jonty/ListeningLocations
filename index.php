<?php

$req_url = 'https://www.google.com/accounts/OAuthGetRequestToken?scope='.urlencode('https://www.googleapis.com/auth/latitude').'&oauth_callback=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

$authurl = 'https://www.google.com/latitude/apps/OAuthAuthorizeToken';
$acc_url = 'https://www.google.com/accounts/OAuthGetAccessToken';

$api_url = 'https://www.googleapis.com/latitude/v1';

$conskey = 'jonty.co.uk';
$conssec = 'DIRpj0xSbe/vOjgU/CBUAYq5';

session_start();

if (isset($_GET['reset'])) {
    unset($_SESSION['state']);
    unset($_SESSION['token']);
    unset($_SESSION['secret']);
    header('Location: http://jonty.co.uk/bits/listeninglocations');
    exit;
}

// In state=1 the next request should include an oauth_token.
// If it doesn't go back to 0
if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) {
    $_SESSION['state'] = 0;
}


try {

    $oauth = new OAuth($conskey, $conssec, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
    $oauth->enableDebug();

    if ($_SESSION['state'] != 2) {

        if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {

            $request_token_info = $oauth->getRequestToken($req_url);
            $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
            $_SESSION['state'] = 1;
            header('Location: '.$authurl.'?oauth_token='.$request_token_info['oauth_token'].'&domain='.urlencode($_SERVER['HTTP_HOST']).'&location=all&granularity=best');
            exit;

        } else if($_SESSION['state']==1) {

            $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
            $access_token_info = $oauth->getAccessToken($acc_url);
            $_SESSION['state'] = 2;
            $_SESSION['token'] = $access_token_info['oauth_token'];
            $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
        }

    }

    $oauth->setToken($_SESSION['token'],$_SESSION['secret']);

    $n = 0;
    $fetchTimestamp = null;
    $lastTimestamp = 1285853948000;//time()*1000;

    while ($fetchTimestamp != $lastTimestamp) {
        $fetchTimestamp = $lastTimestamp;

        $oauth->fetch("{$api_url}/location?max-results=1000&max-time={$fetchTimestamp}");
        $json = json_decode($oauth->getLastResponse());

        $lastTimestamp = null;
        $lastLat = null;
        $lastLong = null;
        $lastScrobbleTime = time();

        foreach ($json->data->items as $location) {
            if ($location->timestampMs > ($lastScrobbleTime*1000)) {
                continue;
            }

            $lastTimestamp = $location->timestampMs;

            $lastLat = $location->latitude;
            $lastLong = $location->longitude;

            print $n++.". ".date('r',$location->timestampMs/1000).": {$location->latitude}, {$location->longitude}\n<br>";

            $scrobbles = simplexml_load_string(implode('',file("http://wsdev.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=jonocole&api_key=b25b959554ed76058ac220b7b2e0a026&from=".(($location->timestampMs/1000)-7600)."&to=".(($location->timestampMs/1000)+7600))));

            foreach ($scrobbles->recenttracks as $scrobble) {
                print "\t".$scrobble->track->artist." - ".$scrobble->track->name."\n<br>";
                $lastScrobbleTime = $scrobble->date->uts;
            }
                
        }
    }


} catch(OAuthException $E) {
    print_r($E);
}
