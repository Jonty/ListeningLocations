<?php

require_once('lib/datafetcher.class.php');
require_once('lib/oauthfetcher.class.php');

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
    <form>
        <input type="text" name="user">
        <input type="submit" value="To google, and beyond!">
    </form>
    <small>
        You need to have a google latitude listening history for this to work.
        <a href="http://wiki.musichackday.org/index.php?title=ListeningLocations">Find out more.
    </small>
    
    <?php
    exit;
}

$fetcher = new DataFetcher();
$oauth = new OAuthFetcher(); // This will do the OAuth bounce handshake dance.

$fetchTimestamp = null;
$lastTimestamp = time() * 1000;

while ($fetchTimestamp != $lastTimestamp) {
    $fetchTimestamp = $lastTimestamp;

    try {

        $queryString = http_build_query(array(
            'max-results'   => 1000,
            'max-time'      => $fetchTimestamp,
            'granularity'   => 'best',
        ));

        $apiUrl = 'https://www.googleapis.com/latitude/v1';
        $oauth->fetch("{$apiUrl}/location?{$queryString}");

    } catch(OAuthException $e) {
        trigger_error("OAuth fail: " . print_r($e, true));

        ?>
        Something failed during the OAuth data fetch from google! 
        Are you sure you have <a href='https://www.google.com/latitude/history'>Google Latitude location history</a> enabled?
        <?php
        exit;
    }

    $lastTimestamp = $lastLat = $lastLong = $foundScrobbles = null;
    $json = json_decode($oauth->getLastResponse());

    if (!$json->data->items) {
        print "You don't seem to have a latitude location history yet! Come back when you're older.";
        exit;
    }

    foreach ($json->data->items as $location) {

        if ($lastTimestamp && $lastLat != $location->latitude && $lastLong != $location->longitude) {

            $scrobbles = $fetcher->fetchScrobbles(
                $_GET['user'],
                $location->timestampMs,
                $lastTimestamp, 
                $location->latitude,
                $location->longitude
            );

            if ($scrobbles) {
                $address = $fetcher->fetchAddress("{$location->latitude},{$location->longitude}");

                print date('r',$location->timestampMs/1000)." to ".date('r',$lastTimestamp/1000).": <b>{$address}\n</b><br>";

                print "<ul>";
                foreach ($scrobbles as $scrobble) {
                    print "<li>{$scrobble['artist']} - {$scrobble['track']}</li>\n";
                    $foundScrobbles = 1;
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

if (!$foundScrobbles) {
    print "No scrobbles were found during the time periods registered on Latitude. Listen to more music already.";
}
