<?php

### RFmon configuration ###
// This file contains the configuration for RFmon.
// Copy this file to the same folder and rename it to conf.php
// Uncomment and change the values to configure RFmon

### Website configuration ###
$TITLE = "Unconfigured RFmon";
$LOCALE = "en_US"; # (Translated languages: en_US, sl_SI)
$TIMEZONE = "Europe/Ljubljana"; # https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
$BASE_PATH = "/rfmon"; // Path to the RFmon app on the web server
### End website configuration ###

### Audio recording configuration ###
$SDR_GAIN = 40; // Gain of the SDR
$CENTER_FREQ = 446.0; // Center frequency of the SDR
$RF_FREQ = 446.05625; // Frequency to monitor
$RTLSDR_BIN_PATH = "/usr/local/bin/rtl_airband"; // Path to the rtl_airband binary
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
# $NOTIFY_FROM = "$TITLE <rfmon@example.com>"; // Email sender
# $NOTIFY_LINK_HOST = "http://example.com/rfmon"; // Hostname (and path) to rfmon
# $NOTIFY_AUTO_LOGIN = true; // Automatically log in user to the app when clicking the link (by adding a temporary login token to the link host URL ?h=...)
### End email notification configuration ###

### Transriptions configuration ###
# $SHOW_TRANSCRIPTIONS = true;
# https://github.com/openai/whisper#available-models-and-languages
# $ASR_MODEL = "openai/whisper-large-v3"; // Model for ASR
# $ASR_LANGUAGE = "auto"; // Language for ASR (auto, en, sl, de, fr...)
# https://huggingface.co/settings/tokens (create a token and paste it here)
# 'Make calls to the serverless Inference API' should be enabled
# $HF_TOKEN = "hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // Token for Hugging Face API
### End transcriptions configuration ###
