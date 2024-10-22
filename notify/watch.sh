#!/usr/bin/env bash

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"

### CONFIGURATION ###

WATCH_DIR="/hdd1/zaremon/rec"
NOTIFY_PHP="$SCRIPT_DIR/../html/notify.php"

### END OF CONFIGURATION ###


if [ ! -d "$WATCH_DIR" ]
then
    echo "Directory $WATCH_DIR does not exist."
    exit 1
fi

if [ ! -f "$NOTIFY_PHP" ]
then
    echo "File $NOTIFY_PHP does not exist."
    exit 1
fi

# Check if inotifywait command is available
if ! command -v inotifywait &> /dev/null
then
    echo "inotifywait command not found."
    echo "Please install inotify-tools package."
    exit 1
fi

echo "Watching directory: $WATCH_DIR"

# Monitor the directory for new .mp3 files
inotifywait -mq -e MOVED_TO "$WATCH_DIR" | while read INOTIFY_EVENT
do
    NEW_FILE="$WATCH_DIR/$(echo $INOTIFY_EVENT | cut -d " " -f3)"
    # Check if the new file is an mp3 file
    if [[ "$NEW_FILE" == *.mp3 ]]
    then
        # Call the notify.php script with the new file as an argument
        php "$NOTIFY_PHP" "$NEW_FILE"
    fi
done
