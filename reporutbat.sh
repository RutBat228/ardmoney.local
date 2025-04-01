#!/data/data/com.termux/files/usr/bin/bash

REPO_URL="https://github.com/RutBat228/rutapps/archive/refs/heads/main.zip"
LOCAL_DIR="$HOME/rutapps"
TMP_DIR="$HOME/rutapps-main"

wget $REPO_URL -O latest.zip
unzip -o latest.zip -d $HOME
rsync -av --exclude=".git" $TMP_DIR/ $LOCAL_DIR/
rm -rf $TMP_DIR latest.zip

echo "Репозиторий обновлён!"
