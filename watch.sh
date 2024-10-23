#!/usr/bin/env bash

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"

### CONFIGURATION ###

CONF_PHP="$SCRIPT_DIR/html/conf.php"
function get_conf() { php -r "require '$CONF_PHP'; echo isset(\$$1) ? \$$1 : \"\";"; }

WATCH_DIR="$(get_conf "AUDIO_SRC_DIR")"
SHOW_TRANSCRIPTIONS="$(get_conf "SHOW_TRANSCRIPTIONS")"
NOTIFY_DIR="$(get_conf "NOTIFY_DIR")"
LOCALE="$(get_conf "LOCALE")"

### END OF CONFIGURATION ###

if [ -n "$NOTIFY_DIR" ]
then
    NOTIFY_ENABLED="YES"
else
    NOTIFY_ENABLED="NO"
fi

if [ "$SHOW_TRANSCRIPTIONS" == "1" ]
then
    TRANSCRIBE_AUDIO="YES"
else
    TRANSCRIBE_AUDIO="NO"
fi

if [ $NOTIFY_ENABLED == "NO" ] && [ $TRANSCRIBE_AUDIO == "NO" ]
then
    echo "Neither notifications nor transcriptions are enabled. Exiting."
    exit 1
fi

NOTIFY_PHP="$SCRIPT_DIR/html/notify.php"

if [ ! -d "$WATCH_DIR" ]
then
    echo "Directory $WATCH_DIR does not exist."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -d "$NOTIFY_DIR" ]
then
    echo "Directory $NOTIFY_DIR does not exist."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -w "$NOTIFY_DIR" ]
then
    echo "Directory $NOTIFY_DIR is not writable."
    exit 1
fi

if [ $NOTIFY_ENABLED == "YES" ] && [ ! -f "$NOTIFY_PHP" ]
then
    echo "File $NOTIFY_PHP does not exist."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ ! -w "$WATCH_DIR" ]
then
    echo "Directory $WATCH_DIR is not writable."
    exit 1
fi

if [ "$TRANSCRIBE_AUDIO" == "YES" ] && [ "$LOCALE" != "sl_SI" ]
then
    echo "Only slovenian locale is supported for transcription."
    exit 1
fi


# Check if inotifywait command is available
if ! command -v inotifywait &> /dev/null
then
    echo "inotifywait command not found."
    echo "Please install inotify-tools package."
    exit 1
fi

# Check if jq command is available
if [ "$TRANSCRIBE_AUDIO" == "YES" ] && ! command -v jq &> /dev/null
then
    echo "jq command not found."
    echo "Please install jq package."
    exit 1
fi

# Check if jq command is available
if [ "$TRANSCRIBE_AUDIO" == "YES" ] && ! command -v curl &> /dev/null
then
    echo "curl command not found."
    echo "Please install curl package."
    exit 1
fi

echo "Watching directory: $WATCH_DIR"
echo "Notifications enabled: $NOTIFY_ENABLED"
echo "Notify script: $NOTIFY_PHP"
echo "Notify directory: $NOTIFY_DIR"
echo "Transcriptions enabled: $TRANSCRIBE_AUDIO"

# Monitor the directory for new .mp3 files
inotifywait -mq -e MOVED_TO "$WATCH_DIR" | while read INOTIFY_EVENT
do
    NEW_FILE="$WATCH_DIR/$(echo $INOTIFY_EVENT | cut -d " " -f3)"
    # Check if the new file is an mp3 file
    if [[ "$NEW_FILE" == *.mp3 ]]
    then
        echo "New voice recording: $NEW_FILE"

        # Call the notify.php script with the new file as an argument
        if [ "$NOTIFY_ENABLED" == "YES" ]
        then
            php "$NOTIFY_PHP" "$NEW_FILE"
        fi

        # Transcribe the audio file
        if [ "$TRANSCRIBE_AUDIO" == "YES" ]
        then
            TRANSCRIBED_JSON="$(curl -F "model=e2e" -F "audio_file=@$NEW_FILE" "https://slovenscina.eu/api/translator/asr")"

            TRANSCRIPTION="$(echo "$TRANSCRIBED_JSON" | jq -r '.result')"

            # Save the transcription to a text file
            TRANSCRIPTION_FILE="${NEW_FILE%.mp3}.txt"
            echo "$TRANSCRIPTION" > "$TRANSCRIPTION_FILE"

            echo "Transcription saved to: $TRANSCRIPTION_FILE"
        fi
    fi
done
