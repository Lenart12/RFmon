#!/usr/bin/env bash

SCRIPT_DIR=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
source "$SCRIPT_DIR/../util/config.sh"

if [ "$EUID" -ne 0 ]; then
    echo "Please run as root"
    exit 1
fi

# Install the service
SERVICE_PATH="/etc/systemd/system/rfmon.service"
MAIN_SCRIPT="$SCRIPT_DIR/rfmon.sh"

if [ -f $SERVICE_PATH ]; then
    echo "Service already exists. Do you want to overwrite it?"
    confirm "Overwrite the service?"
    if [ $? -eq 0 ]; then
        rm -f $SERVICE_PATH
    else
        echo "Aborting"
        exit 0
    fi
fi

echo "Installing service to $SERVICE_PATH"

cat > $SERVICE_PATH <<EOF
[Unit]
Description=RFmon Service
Wants=network.target
After=network.target

[Service]
ExecStart=/bin/bash "$MAIN_SCRIPT"
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

echo "Service installed [$SERVICE_PATH]:"
cat $SERVICE_PATH

confirm "Do you want to enable the service?"
if [ $? -eq 0 ]; then
    systemctl daemon-reload
    systemctl enable rfmon.service
    [ $? -eq 0 ] && echo "Service enabled" || echo "Failed to enable service"
fi

confirm "Do you want to start the service?"
if [ $? -eq 0 ]; then
    systemctl start rfmon.service
    [ $? -eq 0 ] && echo "Service started" || echo "Failed to start service"
fi

echo "Finished"
echo "* To check the service status run: systemctl status rfmon.service"
echo "* To stop the service run: systemctl stop rfmon.service"
echo "* To uninstall the service run: systemctl disable rfmon.service && rm $SERVICE_PATH"
