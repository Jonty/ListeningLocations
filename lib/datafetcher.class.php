<?php

    require_once('datastore.class.php');

    class DataFetcher {
        private $datastore;

        function __construct() {
            $this->store = new Datastore();
        }

        function fetchScrobbles($user, $startTimestamp, $endTimestamp, $latitude, $longitude) {

            $location = $this->store->getLocationByUserAndTime($user, $startTimestamp, $endTimestamp);

            if ($location) {
                if ($location['hasScrobbles'] == 1) {
                    return $this->store->getScrobbles(
                        $user, ($startTimestamp/1000), ($endTimestamp/1000)
                    );
                } else {
                    return array();
                }
            }

            $recentTracks = simplexml_load_string(
                implode('', file(
                    "http://wsdev.audioscrobbler.com/2.0/".
                    "?method=user.getrecenttracks&user={$user}".
                    "&api_key=b25b959554ed76058ac220b7b2e0a026".
                    "&from=".($startTimestamp/1000)."&to=".($endTimestamp/1000)
                ))
            );

            $recentTracks = (array) $recentTracks->recenttracks;

            $scrobbles = array();
            if (isset($recentTracks['track']) && $recentTracks['track']) {
                if (is_array($recentTracks['track'])) {
                    $scrobbles = $recentTracks['track'];
                } else {
                    $scrobbles = array($recentTracks['track']);
                }
            }

            $this->store->createLocation(
                $user, $startTimestamp, $endTimestamp, $latitude, $longitude, (int)($scrobbles == true)
            );

            $scrobbleData = array();
            foreach ($scrobbles as $scrobble) {
                $row = array(
                    'timestamp' => (int) strtotime($scrobble->date),
                    'artist'    => (string) $scrobble->artist,
                    'track'     => (string) $scrobble->name,
                );

                $scrobbleData[] = $row;

                $this->store->createScrobble(
                    $user, $row['timestamp'], $row['artist'], $row['track']
                );
            }

            return $scrobbleData;
        }

        function fetchAddress($latlng) {

            $address = $this->store->getAddressByLatLng($latlng);
            if ($address) return $address;

            $geocode = simplexml_load_string(implode('', file(
                "http://maps.googleapis.com/maps/api/geocode/xml".
                "?latlng={$latlng}&sensor=false"
            )));

            $address = $latlng;
            foreach ($geocode->result as $item) {
                if ($item->type == 'postal_code') {
                    $address = (string) $item->formatted_address;
                }
            }

            $this->store->createAddress($latlng, $address);

            return $address;
        }

    }
