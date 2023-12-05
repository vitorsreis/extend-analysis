#!/bin/sh

# Cron job for server with top command to monitor server status per second and save to database

# Install: add in crontab -e
# * * * * * /bin/bash <directory>/bin/cronServerWithTop.sh

DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
for ((i = 1; i <= 60; i++)); do
  top -b -H -n1 -p0 | php "$DIR/serverWithTop.php"
  echo "[$(date +"%Y-%m-%d %H:%M:%S")] $i/60"
  sleep 1
done

