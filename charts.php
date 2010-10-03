<?php

    require_once('datastore.class.php');
    require_once('datafetcher.class.php');

    $store = new DataStore();
    $fetcher = new DataFetcher();

    $user = $_GET['user'];
    if (!$user) die("No user specified.");

    $scrobbles = $store->getAllScrobbles($user);

    $chart = array();
    foreach ($scrobbles as $scrobble) {
        $address = $fetcher->fetchAddress($scrobble['latitude'].','.$scrobble['longitude']);

        $address = preg_replace('/London.*?,/', 'London,', $address);
        
        if (!isset($chart[$address])) {
            $chart[$address] = array();
        }

        if (!isset($chart[$address][$scrobble['artist']])) {
            $chart[$address][$scrobble['artist']] = 0;
        }

        $chart[$address][$scrobble['artist']]++;
    }

    foreach ($chart as $place => $chart) {
        arsort($chart);
        print "<h4>{$place}</h4><ul>";
        foreach ($chart as $artist => $plays) {
            print "<li><b>{$plays}</b>. $artist</li>";
        }
        print "</ul><br>";
    }
