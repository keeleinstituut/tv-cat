#!/bin/bash

MATECAT_ENV=$(env | grep '^MATECAT_' | sed -En "s/^MATECAT_//p")

echo '---------------'
echo '------env------'
echo $MATECAT_ENV
echo ''

cat > $APP_ROOT/inc/config.ini <<EOT
ENV=production
CHECK_FS=no

[production]
${MATECAT_ENV}

EOT


cat > $APP_ROOT/nodejs/config.ini <<EOT
[server]
address = 0.0.0.0
port = 7788
path = /channel/updates

[queue]
name = /topic/matecat_sse_notifications
host = ${SSE_QUEUE_HOST}

port = ${SSE_QUEUE_PORT}
login = ${SSE_QUEUE_USER}
passcode = ${SSE_QUEUE_PASSWORD}

[log]
file = log/server.log
level = debug

[cors]
allowedOrigins[] = *
EOT

cp $APP_ROOT/inc/task_manager_config.ini.sample $APP_ROOT/inc/task_manager_config.ini
cp $APP_ROOT/inc/Error_Mail_List.ini.sample $APP_ROOT/inc/Error_Mail_List.ini
cp $APP_ROOT/inc/oauth_config.ini.sample $APP_ROOT/inc/oauth_config.ini
touch $APP_ROOT/inc/oauth-token-key.txt

cat > $APP_ROOT/inc/login_secret.dat <<EOT
${LOGIN_SECRET}
EOT

chown www-data:www-data $APP_ROOT/inc/config.ini
chown www-data:www-data $APP_ROOT/inc/task_manager_config.ini
chown www-data:www-data $APP_ROOT/inc/Error_Mail_List.ini
chown www-data:www-data $APP_ROOT/inc/oauth_config.ini
chown www-data:www-data $APP_ROOT/inc/oauth-token-key.txt
chown www-data:www-data $APP_ROOT/inc/login_secret.dat
chown www-data:www-data $APP_ROOT/nodejs/config.ini

chmod 400 $APP_ROOT/inc/config.ini
chmod 400 $APP_ROOT/inc/task_manager_config.ini
chmod 400 $APP_ROOT/inc/Error_Mail_List.ini
chmod 400 $APP_ROOT/inc/oauth_config.ini
chmod 400 $APP_ROOT/inc/oauth-token-key.txt
chmod 400 $APP_ROOT/inc/login_secret.dat
chmod 400 $APP_ROOT/nodejs/config.ini

echo 'Starting configure apache'

cat > /etc/apache2/sites-enabled/000-default.conf <<EOT
<VirtualHost *:80>
    # The ServerName directive sets the request scheme, hostname and port that
    # the server uses to identify itself. This is used when creating
    # redirection URLs. In the context of virtual hosts, the ServerName
    # specifies what hostname must appear in the request's Host: header to
    # match this virtual host. For the default virtual host (this file) this
    # value is not decisive as it is used as a last resort host regardless.
    # However, you must set it for any further virtual host explicitly.
    #ServerName www.example.com

    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html



    # It is also possible to configure the loglevel for particular
    # modules, e.g.
    #LogLevel info ssl:warn

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined

    # For most configuration files from conf-available/, which are
    # enabled or disabled at a global level, it is possible to
    # include a line for only one particular virtual host. For example the
    # following line enables the CGI configuration for this host only
    # after it has been globally disabled with "a2disconf".
    #Include conf-available/serve-cgi-bin.conf


    <Location /sse/ >
        ProxyPass http://localhost:7788/
        ProxyPassReverse http://localhost:7788/
    </Location>
</VirtualHost>
EOT

# shellcheck disable=SC2068
docker-php-entrypoint $@