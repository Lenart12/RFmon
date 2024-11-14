#!/usr/bin/env bash

# Load configuration from conf.php
RFMON_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
RFMON_DIR=$(realpath "$RFMON_DIR/..")

CONF_PHP=$(realpath "$RFMON_DIR/html/conf.php")

if [ ! -f "$CONF_PHP" ]
then
    echo "Configuration file $CONF_PHP does not exist."
    exit 1
fi

# Load all set variables from conf.php
eval $(php -r "
require_once '$CONF_PHP';
\$IGNORED_VARS = ['IGNORED_VARS', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', 'argv', 'argc'];
foreach (\$GLOBALS as \$key => \$value) {
    if (!in_array(\$key, \$IGNORED_VARS)) {
        echo \$key . '=' . var_export(\$value, true) . PHP_EOL;
    }
}")

if [ -n "$NOTIFY_DIR" ]
then
    NOTIFY_ENABLED="YES"
else
    NOTIFY_ENABLED="NO"
fi

if [ "$SHOW_TRANSCRIPTIONS" == "true" ]
then
    TRANSCRIBE_AUDIO="YES"
else
    TRANSCRIBE_AUDIO="NO"
fi

NOTIFY_PHP="$RFMON_DIR/html/notify.php"

if [ ! -d "$AUDIO_SRC_DIR" ]
then
    echo "AUDIO_SRC_DIR [$AUDIO_SRC_DIR] does not exist."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -d "$NOTIFY_DIR" ]
then
    echo "NOTIFY_DIR [$NOTIFY_DIR] does not exist."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -w "$NOTIFY_DIR" ]
then
    echo "NOTIFY_DIR [$NOTIFY_DIR] is not writable."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -f "$NOTIFY_PHP" ]
then
    echo "NOTIFY_PHP [$NOTIFY_PHP] does not exist."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ ! -w "$AUDIO_SRC_DIR" ]
then
    echo "AUDIO_SRC_DIR [$AUDIO_SRC_DIR] is not writable."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ -z "$ASR_MODEL" ]
then
    echo "ASR_MODEL is not set."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ -z "$HF_TOKEN" ]
then
    echo "HF_TOKEN is not set."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ -z "$ASR_LANGUAGE" ]
then
    echo "ASR_LANGUAGE is not set."
    exit 1
fi

if [ ! -x "$RTLSDR_BIN_PATH" ]
then
    echo "RTLSDR_BIN_PATH [$RTLSDR_BIN_PATH] is not an executable file."
    exit 1
fi

if ! command -v inotifywait &> /dev/null
then
    echo "inotifywait command not found."
    echo "Please install inotify-tools package."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && ! command -v jq &> /dev/null
then
    echo "jq command not found."
    echo "Please install jq package."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && ! command -v curl &> /dev/null
then
    echo "curl command not found."
    echo "Please install curl package."
    exit 1
fi
