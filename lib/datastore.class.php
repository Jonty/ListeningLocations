<?php

    class DataStore {
        private $dbFile = 'datastore.db';
        private $db;

        function db($reinitialise = false) {
            if (!$this->db || $reinitialise) {
                $this->db = new PDO("sqlite:{$this->dbFile}");
            }

            if (!$reinitialise && !$this->db->prepare("SELECT 1 FROM locations;")) {
                $this->createDb();
            }

            return $this->db;
        }

        function createDb() {
            // Reinit the db to nuke the error that caused the create to occur
            $db = $this->db(true);

            $db->query('
                CREATE TABLE addresses (
                    latlng string,
                    address string,
                    PRIMARY KEY (latlng)
                );
            ');

            $db->query('
                CREATE TABLE scrobbles (
                    user string,
                    timestamp string,
                    artist string,
                    track string,
                    PRIMARY KEY (user, timestamp)
                );
            ');

            $db->query('
                CREATE TABLE locations (
                    user string,
                    startTimestamp string,
                    endTimestamp string,
                    latitude string,
                    longitude string,
                    hasScrobbles boolean,
                    PRIMARY KEY (user, startTimestamp, endTimestamp)
                );
            ');

            $db->query('
                CREATE TABLE tags (
                    artist string,
                    tag string,
                    PRIMARY KEY (artist)
                );
            ');
        }

        function getLocationByUserAndTime($user, $startTimestamp, $endTimestamp) {
            $db = $this->db();

            $oLocationFetch = $db->prepare(
                'SELECT * FROM locations WHERE user = ? AND startTimestamp = ? AND endTimestamp = ?;'
            );

            $oLocationFetch->execute(
                array($user, $startTimestamp, $endTimestamp)
            );

            $aLocationData = null;
            if ($aRows = $oLocationFetch->fetchAll()) {
                $aLocationData = $aRows[0];
            }

            return $aLocationData;
        }

        function getAddressByLatLng($latlng) {
            $db = $this->db();

            $oAddressFetch = $db->prepare(
                'SELECT * FROM addresses WHERE latlng = ?;'
            );

            $oAddressFetch->execute(
                array($latlng)
            );

            $aAddressData = null;
            if ($aRows = $oAddressFetch->fetchAll()) {
                $aAddressData = $aRows[0]['address'];
            }

            return $aAddressData;
        }

        function getScrobbles($user, $startTimestamp, $endTimestamp) {
            $db = $this->db();

            $oScrobbleFetch = $db->prepare(
                'SELECT * FROM scrobbles WHERE user = ? AND timestamp >= ? AND timestamp <= ?;'
            );

            $oScrobbleFetch->execute(
                array($user, $startTimestamp, $endTimestamp)
            );

            $aScrobbleData = null;
            if ($aRows = $oScrobbleFetch->fetchAll()) {
                $aScrobbleData = $aRows;
            }

            return $aScrobbleData;
        }

        function getAllScrobbles($user) {
            $db = $this->db();

            $oScrobbleFetch = $db->prepare('
                SELECT timestamp, artist, track, latitude, longitude
                FROM locations, scrobbles
                WHERE hasScrobbles = 1 AND timestamp * 1000 >= startTimestamp 
                    AND timestamp * 1000 <= endTimestamp AND locations.user = ? 
                    AND scrobbles.user = ?
                ORDER BY timestamp DESC;
            ');

            $oScrobbleFetch->execute(
                array($user, $user)
            );

            $aScrobbleData = null;
            if ($aRows = $oScrobbleFetch->fetchAll()) {
                $aScrobbleData = $aRows;
            }

            return $aScrobbleData;
        }

        function createLocation($user, $startTimestamp, $endTimestamp, $latitude, $longitude, $hasScrobbles) {
            $db = $this->db();

            $oAddressInsert = $db->prepare('
                INSERT INTO locations
                    (user, startTimestamp, endTimestamp, latitude, longitude, hasScrobbles)
                VALUES
                    (?,?,?,?,?,?);'
            );

            $aAddressData = array(
                $user, $startTimestamp, $endTimestamp, $latitude, $longitude, $hasScrobbles
            );

            return $oAddressInsert->execute($aAddressData);
        }

        function createAddress($latlng, $address) {
            $db = $this->db();

            $oAddressInsert = $db->prepare('
                INSERT INTO addresses
                    (latlng, address)
                VALUES
                    (?,?);'
            );

            $aAddressData = array(
                $latlng, $address
            );

            return $oAddressInsert->execute($aAddressData);
        }

        function createScrobble($user, $timestamp, $artist, $track) {
            $db = $this->db();

            $oAddressInsert = $db->prepare('
                INSERT INTO scrobbles
                    (user, timestamp, artist, track)
                VALUES
                    (?,?,?,?);'
            );

            $aAddressData = array(
                $user, $timestamp, $artist, $track
            );

            return $oAddressInsert->execute($aAddressData);
        }


    }
