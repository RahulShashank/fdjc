#!/bin/bash

# FTP directory from which the filles will be picked for importing
#FTP_DIR="/var/www/html/biteanalytics/cronjobs/ftp"
FTP_DIR="/home/exchangebitefiles/files"

_DIR="${0%/*}"
_LOG_FILE="$_DIR"/auto_import.log
STATUS_FILE="$_DIR"/cronstatus.dat

for FILE in "$FTP_DIR"/* "$FTP_DIR"/BITE/*; do
	extension="${FILE##*.}"
	filename="${FILE##*/}"

	if [ "$extension" = 'tgz' -o "$extension" = 'zip' ] ; then
		# check the file if it's in use
		if !(sudo lsof +D "$FTP_DIR" | grep -c -i "$FILE") ; then
			echo "$(date) - Sending file - $FILE - for processing" >> $_LOG_FILE
			#curl -d "filename=$FILE" http://localhost/biteanalytics/cronjobs/AutoImportBITEFiles.php
			curl -d "filename=$FILE" http://54.215.222.38/biteanalytics/cronjobs/AutoImportBITEFiles.php
			break
		fi
	fi
done
