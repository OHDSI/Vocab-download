#!/bin/bash
##########################################################################
# upload the zip file to the OHDSI cloud inbound vocab data feed s3 bucket
##########################################################################
source "${BASH_SOURCE%/*}/app-config.sh"
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
cd "$app_root_dir/vocab_files"
file=athena-ohdsi-cloud-s3-datafeed.zip
bucket=ohdsi-vocab
resource="/${bucket}/${file}"
contentType="application/zip"
dateValue=`date -R`
stringToSign="PUT\n\n${contentType}\n${dateValue}\n${resource}"
s3Key=AKIAIRNLDAOUQ2CFPIVQ
s3Secret=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
signature=`echo -en ${stringToSign} | openssl sha1 -hmac ${s3Secret} -binary | base64`
curl --max-time 900 --connect-timeout 900 --keepalive-time 30 -v -L -X PUT -T "$app_root_dir/vocab_files/${file}" \
  -H "Host: ${bucket}.s3.amazonaws.com" \
  -H "Date: ${dateValue}" \
  -H "Content-Type: ${contentType}" \
  -H "Authorization: AWS ${s3Key}:${signature}" \
  https://${bucket}.s3.amazonaws.com/${file}

