#!/bin/bash

# Create the symbiota config dir if it doesn't exist
if [ ! -d /usr/local/etc/symbiota ]; then
    mkdir /usr/local/etc/symbiota
fi

# Template the config files
configure-symbiota

# TODO: Just copying these as-is right now; Add to configure-symbiota
cp /usr/local/etc/symbiota/php/index_template.php ${PATH_SYMBIOTA}/index.php
cp /usr/local/etc/symbiota/css/main_template.css ${PATH_SYMBIOTA}/css/main.css
cp /usr/local/etc/symbiota/css/speciesprofile_template.css ${PATH_SYMBIOTA}/css/speciesprofile.css

# inotify will re-run configure-symbiota whenever a symbiota config file changes
while inotifywait -qq -r -e modify /usr/local/etc/symbiota/; do
    configure-symbiota
    cp /usr/local/etc/symbiota/php/index_template.php ${PATH_SYMBIOTA}/index.php
    cp /usr/local/etc/symbiota/css/main_template.css ${PATH_SYMBIOTA}/css/main.css
    cp /usr/local/etc/symbiota/css/speciesprofile_template.css ${PATH_SYMBIOTA}/css/speciesprofile.css
done &

exec "$@"
