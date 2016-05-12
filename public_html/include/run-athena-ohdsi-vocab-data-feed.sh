#!/bin/bash
#
############################################
# this is a weekly scheduled cron script to export a full copy of the ohdsi vocab data to the OHDSI cloud s3 bucket ohdsi-vocab
# Created by Lee Evans 
############################################
source "${BASH_SOURCE%/*}/app-config.sh"

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

/usr/bin/perl "$app_root_dir/include/internal_ohdsi_feed_dump.pl" $app_dbuser_prodv5 5 "$app_root_dir/vocab_files/athena-ohdsi-cloud-s3-datafeed.zip"

# pause for 10 seconds before uploading the file
sleep 10

# upload the file to the OHDSI S3 bucket - this is NOT a publically accessible S3 bucket
"$app_root_dir/include/s3upload.sh"

# pause for 15 minutes before cleaning up the exported file, to give the file time to transfer
sleep 900

# cleanup the exported file
rm "$app_root_dir/vocab_files/athena-ohdsi-cloud-s3-datafeed.zip"
