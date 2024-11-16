#!/usr/bin/env bash

SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/../util/common.sh"

echo "Watching directory: $AUDIO_SRC_DIR"
echo "Notifications enabled: $NOTIFY_ENABLED"
echo "Transcriptions enabled: $TRANSCRIBE_AUDIO"

# Monitor the directory for new .mp3 files
function watch_for_new_files() {
    echo "Starting to watch for new audio files in $AUDIO_SRC_DIR"
    local inotify_event
    inotifywait -mq -e MOVED_TO "$AUDIO_SRC_DIR" | while read inotify_event
    do
        local new_file="$AUDIO_SRC_DIR/$(echo $inotify_event | cut -d " " -f3)"
        # Check if the new file is an mp3 file
        if [[ "$new_file" == *.mp3 ]]
        then
            echo "New voice recording: $new_file"

            # Transcribe the audio file
            if [ "$TRANSCRIBE_AUDIO" == "YES" ]
            then
                transcribe_audio "$new_file"
            fi

            # Call the notify.php script with the new file as an argument
            if [ "$NOTIFY_ENABLED" == "YES" ]
            then
                php "$NOTIFY_PHP" "$new_file" &
            fi
        fi
    done

}

function run_airband_rtlsdr_service() {
    echo "Starting airband-rtlsdr service"
    # Create a temporary file for configuration
    local config_file=$(mktemp -t rfmon_sdr.XXXXXXXXXX.conf) 
    echo "Config file: $config_file"

    # Write the configuration to the temporary file
    cat > $config_file <<EOF
devices:
({
    type = "rtlsdr";
    index = 0;
    gain = $SDR_GAIN;
    centerfreq = $CENTER_FREQ;
    channels: (
        {
        freq =  $RF_FREQ;
        modulation = "nfm";
        outputs: (
            {
            type = "file";
            directory = "$AUDIO_SRC_DIR";
            filename_template = "zm";
            continuous = false;
            split_on_transmission = true;
            include_freq = false;
            }
        );
        }
    );
});
EOF

    # Start the airband-rtlsdr service with the configuration file
    $RTLSDR_BIN_PATH -Fe -c $config_file
    local service_status=$?

    rm -f $config_file

    # If the service fails, return an error
    if [ $service_status -ne 0 ]; then
        echo "Error starting airband-rtlsdr service"
        return $service_status
    fi
}


function start_services() {
    # Start both services, when one stops the other should stop as well

    # Start the airband-rtlsdr service
    run_airband_rtlsdr_service &
    local airband_pid=$!

    # Start the watch service
    watch_for_new_files &
    local watch_pid=$!

    echo "Started services with PIDs: SDR:$airband_pid Watch:$watch_pid"

    # Wait for the services to finish
    wait -n $airband_pid $watch_pid

    for pid in $airband_pid $watch_pid; do
        if ps -p $pid > /dev/null; then
            echo "Stopping service with PID $pid"
            pkill -P $pid
            kill $pid
        fi
    done
}

start_services
