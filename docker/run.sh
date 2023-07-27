#!/bin/bash

php $APP_ROOT/support_scripts/run-sql-migrations.php

# Start background workers as www-data user
su -l www-data -s $APP_ROOT/daemons/restartAnalysis.sh

screen -d -m -S 'node' node $APP_ROOT/nodejs/server.js

apache2-foreground