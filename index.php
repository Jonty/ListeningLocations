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

if (!isset($_GET['user'])) {
    ?>
    <h1>Listening Locations</h1>
    <b>Last.fm username:</b>
    <form><input type="text" name="user"><input type="submit" value="To google, and beyond!"></form>
    <small>You need to have a google latitude listening history for this to work. <a href="http://wiki.musichackday.org/index.php?title=ListeningLocations">Find out more.</small>
    <?php
    exit;
}
$user = $_GET['user'];

// In state=1 the next request should include an oauth_token.
// If it doesn't go back to 0
if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) {
    $_SESSION['state'] = 0;
}

require_once('datafetcher.class.php');
$fetcher = new DataFetcher();

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
    $lastTimestamp = time() * 1000;

    while ($fetchTimestamp != $lastTimestamp) {
        $fetchTimestamp = $lastTimestamp;

        $oauth->fetch("{$api_url}/location?max-results=1000&max-time={$fetchTimestamp}&granularity=best");
        $json = json_decode($oauth->getLastResponse());

        $lastTimestamp = null;
        $lastLat = null;
        $lastLong = null;

        foreach ($json->data->items as $location) {

            if ($lastTimestamp && $lastLat != $location->latitude && $lastLong != $location->longitude) {

                $scrobbles = $fetcher->fetchScrobbles($user, $location->timestampMs, $lastTimestamp, $location->latitude, $location->longitude);

                if ($scrobbles) {

                    $latlon = $location->latitude.','.$location->longitude;
                    $address = $fetcher->fetchAddress($latlon);

                    print date('r',$location->timestampMs/1000)." to ".date('r',$lastTimestamp/1000).": <b>{$address}\n</b><br>";

                    print "<ul>";
                    foreach ($scrobbles as $scrobble) {
                        print "<li>".$scrobble['artist']." - ".$scrobble['track']."\n</li>\n";
                    }

                    print "</ul><br>";
                    flush();ob_flush();
                }
            }

            // Collapse times in the same place
            if (!$lastTimestamp || ($lastLat != $location->latitude && $lastLong != $location->longitude)) {
                $lastLat = $location->latitude;
                $lastLong = $location->longitude;
                $lastTimestamp = $location->timestampMs;
            }
        }
    }


} catch(OAuthException $E) {
    print_r($E);
}
