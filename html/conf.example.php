<?php

### Website configuration ###
$TITLE = "Unconfigured RFmon";
$LOCALE = "en_US"; # (Translated languages: en_US, sl_SI)
$TIMEZONE = "Europe/Ljubljana"; # https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
### End website configuration ###

### Audio recording configuration ###
$AUDIO_SRC_DIR = "/path/to/RFmon/rec";
$TX_GROUPING_THRESHOLD = 45;
$RECORD_MAX_AGE = 30 * (24 * 3600); // 30 days
### End audio recording configuration ###

### Password protection configuration ###
// Uncomment the following line to enable password protection
# $PASSWORD = "change_me";
### End password protection configuration ###

### Email notification configuration ###
// Uncomment the following line to enable email notifications
// make sure that the web server can send emails and that the
// folder exists and is writable by the web server
# $NOTIFY_DIR = "/path/to/RFmon/notify";

# Notification configuration (only applicable if NOTIFY_DIR is set)
# $NOTIFY_TIMEOUT = 6 * 3600; // Minimum timeout between notifications in seconds
# $NOTIFT_SUBJECT = "Nova aktivnost na $TITLE";
# $NOTIFY_FROM = "$TITLE <rfmon@example.com>";
# $NOTIFY_LINK_HOST = "http://example.com/rfmon";
### End email notification configuration ###

### Transriptions configuration ###
# $SHOW_TRANSCRIPTIONS = true;
### End transcriptions configuration ###
