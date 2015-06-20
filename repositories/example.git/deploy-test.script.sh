#!/bin/sh

LOG="logs/deeplio.log"
NOW=$(date +"%Y-%m-%d-%H%M%S")

echo "SSH Deploy Script run: $NOW
" >> $LOG

## More usefull example:
#DEEPLIO="./"
#DEPLOY="./../deepl.io"
#LOG="./logs/deeplio.log"
#NOW=$(date +"%Y-%m-%d-%H%M%S")

## Start Sctipt
#echo "
#--------------------------------------------------------
#NEW DEPLOYMENT: $NOW" >> $LOG

#git checkout master >> $LOG
## clear untracked files
#git clean -df >> $LOG
## revert local changes
#git checkout -- . >> $LOG
#git pull >> $LOG

# copy some stuff around:
#cp $DEEPLIO/index.html $DEPLOY/
#cp $DEEPLIO/CHANGELOG.md $DEPLOY/

#echo "End Deploy." >> $LOG