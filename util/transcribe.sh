#!/usr/bin/env bash

SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/common.sh"

if [ "$TRANSCRIBE_AUDIO" != "YES" ]; then
    echo "Transcriptions are disabled. Set \$SHOW_TRANSCRIPTIONS = true in conf.php to enable."
    exit 0
fi

OVERWRITE_ALL=NO
confirm_n "Do you want to overwrite all existing transcriptions?"
if [ $? -eq 0 ]; then
    OVERWRITE_ALL=YES
fi

# Process existing .mp3 files in the watch directory
for file in "$AUDIO_SRC_DIR"/*.mp3
do
    if [ -f "$file" ]
    then
        TRANSCRIPTION_FILE="${file%.mp3}.txt"

        if [ "$OVERWRITE_ALL" != "YES" ] && [ -f "$TRANSCRIPTION_FILE" ]
        then
            echo "Transcription for $file exists. Skipping."
            continue
        fi

        echo "Processing file: $file"
        transcribe_audio_cold "$file"

        sleep 5
    fi
done
