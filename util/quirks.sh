SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/config.sh"

function quirks_after_transcription() {
    local audio_file="$1"
    local transcription_file="$2"

    if [ "$QUIRKS_TRANSCRIBE_LANG_SL_HALLUCINATIONS" == "true" ]; then
        local transcription="$(cat "$transcription_file")"

        # If transcription is " Hvala.", then it is probably a hallucination, so set it to empty string.
        if [ "$transcription" == " Hvala." ]; then
            echo -n > "$transcription_file"
        fi
    fi
}
