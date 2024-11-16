#!/usr/bin/env bash

SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/config.sh"

function transcription_name() {
    local audio_file="$1"
    echo "${audio_file%.mp3}.txt"
}

function transcribe_audio() {
    local audio_file="$1"
    local transcription
    local transcription_status
    local transcribed_json

    local file_b64=$(base64 -w 0 "$audio_file")
    local payload="{\"inputs\":\"$file_b64\",\"parameters\":{\"generate_kwargs\":{\"language\": \"$ASR_LANGUAGE\"}}}"

    transcribed_json=$(
        curl -s https://api-inference.huggingface.co/models/$ASR_MODEL \
        -X POST \
        -d "$payload" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $HF_TOKEN" \
        --max-time $HF_TIMEOUT_WARM
    )
    local request_status=$?
    
    if [ $request_status -eq 28 ]; then
        echo "Warm timeout reached while processing transcription."
        return 1
    fi

    if [ $request_status -ne 0 ]; then
        echo "[$transcribed_json]"
        echo "Error processing transcription for $audio_file. [Status: $request_status]"
        return 1
    fi

    transcription="$(echo "$transcribed_json" | jq -ren 'input.text')"
    transcription_status=$?

    if [ $transcription_status -eq 0 ]; then
        # Save the transcription to a text file
        local transcription_file=$(transcription_name "$audio_file")
        echo "$transcription" > "$transcription_file"
        echo "Transcription saved to: $transcription_file"
    else
        echo "$transcribed_json"
        echo "Error processing transcription for $audio_file."
        return 1
    fi
}

function transcribe_audio_cold() {
    local audio_file="$1"
    local transcription
    local transcription_status
    local transcribed_json
    local transcription_file=$(transcription_name "$audio_file")

    echo "Transcribing $audio_file"

    local file_b64=$(base64 -w 0 "$audio_file")
    local payload="{\"inputs\":\"$file_b64\",\"parameters\":{\"generate_kwargs\":{\"language\": \"$ASR_LANGUAGE\"}}}"

    local timeout=$HF_TIMEOUT_COLD
    local t0="$(date +%s)"

    # While transciption file does not exist
    while [ ! -f "$transcription_file" ]; do
        transcribed_json=$(
            curl -s https://api-inference.huggingface.co/models/$ASR_MODEL \
            -X POST \
            -d "$payload" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $HF_TOKEN" \
            -H "X-wait-for-model: 1" \
            -H "X-use-cache: 0" \
            -m $timeout \
        )
        local request_status=$?
        
        # Update timeout
        local t1=$(date +%s)
        timeout=$((HF_TIMEOUT_COLD - (t1 - t0)))

        if [ $request_status -eq 28 ] || [ $timeout -le 0 ]; then
            echo "Cold timeout reached while waiting for transcription."
            return 1
        fi

        if [ $request_status -ne 0 ]; then
            echo "[$transcribed_json]"
            echo "Error processing transcription for $audio_file. [Status: $request_status]"
            continue
        fi

        transcription="$(echo "$transcribed_json" | jq -ren 'input.text')"
        transcription_status=$?

        if [ $transcription_status -eq 0 ]; then
            # Save the transcription to a text file
            echo "$transcription" > "$transcription_file"
        else
            echo "[$transcribed_json]"
        fi
    done

    local tf="$(date +%s)"
    local tt="$((tf - t0))"
    echo "Transcription saved to: $transcription_file in $tt seconds."
    return 0
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


