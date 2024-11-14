#!/usr/bin/env bash

SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/config.sh"

function transcribe_audio() {
    local audio_file="$1"
    local transcription
    local transcription_status

    local file_b64=$(base64 -w 0 "$audio_file")
    local payload="{\"inputs\":\"$file_b64\",\"parameters\":{\"generate_kwargs\":{\"language\": \"$ASR_LANGUAGE\"}}}"

    local transcribed_json=$(curl -s https://api-inference.huggingface.co/models/$ASR_MODEL -X POST -d "$payload" -H "Authorization: Bearer $HF_TOKEN")
    transcription="$(echo "$transcribed_json" | jq -re '.text')"
    transcription_status=$?

    if [ $transcription_status -eq 0 ]; then
        # Save the transcription to a text file
        local transcription_file="${audio_file%.mp3}.txt"
        echo "$transcription" > "$transcription_file"
        echo "Transcription saved to: $transcription_file"
    else
        echo "$transcribed_json"
        echo "Error processing transcription for $audio_file."
        return 1
    fi
}

function confirm_yn() {
    local choice
    read -p "$1 $2: " choice
    case "$choice" in
        y|Y ) return 0;;
        n|N ) return 1;;
        * ) return $3;;
    esac
}

function confirm() {
    return $(confirm_yn "$1" "Y/n" 0)
}

function confirm_n() {
    return $(confirm_yn "$1" "y/N" 1)
}


