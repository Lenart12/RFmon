<?php

### RFmon configuration ###
// This file contains the configuration for RFmon.
// Copy this file to the same folder and rename it to conf.php
// Uncomment and change the values to configure RFmon

### Website configuration ###
$TITLE = "Unconfigured RFmon";
$LOCALE = "en_US"; # (Translated languages: en_US, sl_SI)
$TIMEZONE = "Europe/Ljubljana"; # https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
### End website configuration ###

### Audio recording configuration ###
$AUDIO_SRC_DIR = "/path/to/RFmon/rec"; // This path should be the same as the one in rfmon_sdr.conf
$TX_GROUPING_THRESHOLD = 45; // Group records that are within this threshold of eachother (in seconds)
$RECORD_MAX_AGE = 30 * (24 * 3600); // 30 days
### End audio recording configuration ###

### Password protection configuration ###
// Uncomment the following line to enable password protection
# $PASSWORD = "change_me";
### End password protection configuration ###

### Email notification configuration ###
// Uncomment the following lines to enable email notifications
// make sure that the web server can send emails and that the
// folder exists and is writable by the web server
# $NOTIFY_DIR = "/path/to/RFmon/notify";
# $NOTIFY_TIMEOUT = 2 * 3600; // Minimum time between last and new transmition for notifications to trigger (in seconds)
# $NOTIFY_WAIT_FOR_MORE = 3 * 60; // Time to wait for more transmitions before sending notification (in seconds)
# $NOTIFY_FROM = "$TITLE <rfmon@example.com>";
# $NOTIFY_LINK_HOST = "http://example.com/rfmon";
### End email notification configuration ###

### Transriptions configuration ###
// Currently only Slovene transcriptions are supported (set $LOCALE to 'sl_SI')
# $SHOW_TRANSCRIPTIONS = true;
### End transcriptions configuration ###
