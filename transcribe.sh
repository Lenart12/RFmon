#!/usr/bin/env bash

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"

### CONFIGURATION ###

CONF_PHP="$SCRIPT_DIR/html/conf.php"
function get_conf() { php -r "require '$CONF_PHP'; echo isset(\$$1) ? \$$1 : \"\";"; }

WATCH_DIR="$(get_conf "AUDIO_SRC_DIR")"
LOCALE="$(get_conf "LOCALE")"

### END OF CONFIGURATION ###

if [ ! -d "$WATCH_DIR" ]
then
    echo "Directory $WATCH_DIR does not exist."
    exit 1
fi


if [ ! -w "$WATCH_DIR" ]
then
    echo "Directory $WATCH_DIR is not writable."
    exit 1
fi


# Check if jq command is available
if ! command -v jq &> /dev/null
then
    echo "jq command not found."
    echo "Please install jq package."
    exit 1
fi

# Check if curl command is available
if ! command -v curl &> /dev/null
then
    echo "curl command not found."
    echo "Please install curl package."
    exit 1
fi

if [ "$LOCALE" != "sl_SI" ]
then
    echo "Only slovenian locale is supported."
    exit 1
fi


# Process existing .mp3 files in the watch directory
for file in "$WATCH_DIR"/*.mp3
do
    if [ -f "$file" ]
    then
        echo "Processing existing file: $file"
        TRANSCRIPTION_FILE="${file%.mp3}.txt"

        if [ -f "$TRANSCRIPTION_FILE" ]
        then
            echo "Transcription already exists for $file. Skipping."
            continue
        fi


        TRANSCRIBED_JSON="$(curl -F "model=e2e" -F "audio_file=@$file" "https://slovenscina.eu/api/translator/asr")"
        TRANSCRIPTION="$(echo "$TRANSCRIBED_JSON" | jq -r '.result')"

        # Save the transcription to a text file
        echo "$TRANSCRIPTION" > "$TRANSCRIPTION_FILE"
        echo "Transcription saved to: $TRANSCRIPTION_FILE"
    fi
done
