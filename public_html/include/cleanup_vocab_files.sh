#!/bin/bash
#
# This cleanup script is scheduled to run nightly at 1 am
# and delete any vocabulary download files that are at least 4 days old.
# The assumption is that the requestor will have downloaded the file within 4 days.
# This script will prevent the server from running out of space as more large files are created over time.
#
# Lee Evans 04/02/2015
# ---------------------------------------------------------------------------------------------------------------------
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

find /home/admin/web/default.domain/public_html/vocab_files -type f -name vocab_download_\*.zip -mtime +3 -delete
