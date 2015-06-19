#!/bin/sh

#BASE="/home/user/www/"
#FOLDER="$BASE/wp-content/themes/example/"
#LOG="$BASE/deeplio/logs/example.log"
#NOW=$(date)
#
## Start Sctipt
#echo "
#
#--------------------------------------------------------
#NEW DEPLOYMENT: $NOW" >> "$LOG"
#
## enable maintenance mode
#touch $BASE/.maintenance;
#
## Backup
#cd "$FOLDER" >> "$LOG"
#git checkout env/dev >> "$LOG"
#git add . >> "$LOG"
#git commit -m "Pre-Relese Commit $NOW" >> "$LOG"
#
#git checkout deploy/dev >> "$LOG"
#git pull >> "$LOG"
#
## disable maintenance mode
#rm -rf $BASE/.maintenance
#echo "End deeplio." >> "$LOG"

echo "Hello World" >> example.txt
