#!/bin/bash
set -Eeuo pipefail

MATECAT_ENV=$(env | grep '^MATECAT_' | sed -En "s/^MATECAT_//p")

cat > $APP_ROOT/inc/config.ini <<EOT
ENV=production
CHECK_FS=no

[production]
${MATECAT_ENV}

STORAGE_DIR=${MATECAT_STORAGE_DIR}
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
if [ -v TM_ANALYSIS_P1_MAX_EXECUTORS ]; then
  # Replace P1[max_executors] value
  sed -i "s/P1\[max_executors\] = [0-9]*/P1\[max_executors\] = $TM_ANALYSIS_P1_MAX_EXECUTORS/" $APP_ROOT/inc/task_manager_config.ini
fi

if [ -v TM_ANALYSIS_P2_MAX_EXECUTORS ]; then
  # Replace P1[max_executors] value
  sed -i "s/P2\[max_executors\] = [0-9]*/P2\[max_executors\] = $TM_ANALYSIS_P2_MAX_EXECUTORS/" $APP_ROOT/inc/task_manager_config.ini
fi

if [ -v TM_ANALYSIS_P3_MAX_EXECUTORS ]; then
  # Replace P1[max_executors] value
  sed -i "s/P3\[max_executors\] = [0-9]*/P3\[max_executors\] = $TM_ANALYSIS_P3_MAX_EXECUTORS/" $APP_ROOT/inc/task_manager_config.ini
fi

if [ -v GET_CONTRIBUTION_MAX_EXECUTORS ]; then
  # Replace P1[max_executors] value
  sed -i "s/CONTRIBUTION_GET\[max_executors\] = [0-9]*/CONTRIBUTION_GET\[max_executors\] = $GET_CONTRIBUTION_MAX_EXECUTORS/" $APP_ROOT/inc/task_manager_config.ini
fi


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

ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/API.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/TaskManager.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/activity_log.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/align_job_big.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/align_job_medium.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/align_job_small.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/aligner_segment_create.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/api_calls_time.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/dqf.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/fastAnalysis.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/fatal_errors.txt
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/get_contribution.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/glossary_read.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/glossary_write.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/jobs.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/log.txt
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/mail_queue.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/php_errors.txt
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/project_queue.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/qa_checks.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/query_cache.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/set_contribution.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/set_contribution_mt.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/tm_analysis_P1.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/tm_analysis_P2.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/tm_analysis_P3.log
ln -sf /proc/1/fd/1 $MATECAT_STORAGE_DIR/log_archive/ui_calls_time.log

chown -R www-data:www-data $MATECAT_STORAGE_DIR


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

    LimitRequestFieldSize 16000


    # It is also possible to configure the loglevel for particular
    # modules, e.g.
    #LogLevel info ssl:warn

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined

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


sed -i 's/^\(\[supervisord\]\)$/\1\nnodaemon=true/' /etc/supervisor/supervisord.conf
cat > /etc/supervisor/conf.d/supervisor.conf <<EOT
[program:apache]
command=apache2-foreground
process_name=%(program_name)s
numprocs=1
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:sse]
command=node $APP_ROOT/nodejs/server.js
process_name=%(program_name)s
numprocs=1
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:fast-analysis]
command=php $APP_ROOT/lib/Utils/Analysis/FastAnalysis.php $APP_ROOT/inc/task_manager_config.ini
process_name=%(program_name)s
numprocs=1
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:tm-analysis]
command=php $APP_ROOT/lib/Utils/Analysis/TmAnalysis.php $APP_ROOT/inc/task_manager_config.ini
process_name=%(program_name)s
numprocs=1
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOT

echo "Importing MySQL dump(s)"
until mysql -h $MATECAT_DB_SERVER -P $MYSQL_PORT -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT 1"; do
  echo "MySQL is unavailable - sleeping"
  sleep 1
done

if mysql -h $MATECAT_DB_SERVER -P $MYSQL_PORT -u root -p$MYSQL_ROOT_PASSWORD $MATECAT_DB_DATABASE; then
  echo "Database dump(s) already imported"
else
  mysql -h $MATECAT_DB_SERVER -P $MYSQL_PORT -u root -p$MYSQL_ROOT_PASSWORD < $APP_ROOT/INSTALL/matecat.sql
  echo "Dump(s) imported successfully"
fi


su -l www-data -s /bin/bash -c "php $APP_ROOT/support_scripts/run-sql-migrations.php"

docker-php-entrypoint $@