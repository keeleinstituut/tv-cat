#!/bin/bash


# Start background workers as www-data user
su -l www-data -s $APP_ROOT/daemons/restartAnalysis.sh

screen -d -m -S 'node' node $APP_ROOT/nodejs/server.js

apache2-foreground