#!/bin/sh

#
# This file is part of vsr extend analysis
# @author Vitor Reis <vitor@d5w.com.br>
#

# Cron job to monitor server status monit per second and save with analyze
# Install, add cronjob: * * * * * /bin/bash <directory>/cron.sh

DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
for ((i = 1; i <= 60; i++)); do
  top -b -H -n1 -p0 | php "$DIR/save.php"
  echo "[$(date +"%Y-%m-%d %H:%M:%S")] $i/60"
  sleep 1
done

