<?include("include/app-config.php");?>
<?php
/*
 * Send email to user with download file url if the zip file job has finished since the last cron invocation
 * and update the vocabulary_user table row status so we don't send the email again in future cron invocations
 *
 * @param     unknown_type $PID
 * @return     boolean
*/

// Check if the zip file job is running
function is_running($PID){
   $ProcessState = 0;
   exec("ps $PID", $ProcessState);
   return(count($ProcessState) >= 2);
}
   
$conn=oci_connect($database_user, $database_password, $database);
if ( ! $conn ) {
  echo "Unable to connect: " . var_dump( oci_error() );
  die();
}
//else { 
//    $stid1 = oci_parse($conn, 'SET NAMES "utf8"');
//    oci_execute($stid1);
//    $stid2 = oci_parse($conn, 'set character_set_connection=utf8');
//    oci_execute($stid2);
//    $stid3 = oci_parse($conn, 'set names utf8');
//    oci_execute($stid3);
//}
$stid_user_process = oci_parse($conn, 'select email_address, process_id, vocabulary_list from vocab_download.vocabulary_user');
oci_execute($stid_user_process);
$arUserProcess = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $arUserProcess[] = $row;
}

foreach($arUserProcess as $item){
   
    $FName = str_replace(",", "_", $item["vocabulary_list"]).".zip";
   
    if(!is_running($item["process_id"])){
        
        if(file_exists($zip_file_output_dir.$FName)){

            //Everything is ready - send the e-mail

            /* recipients */
            $to = $item["email_address"];

            /* topic/subject */
            $subject = "OMOP Vocabularies. Your download link";

            /* message */
            $message = '
            <html>
                <head>
                    <title>Vocabularies. Your download link</title>
                </head>
                <body>
                    <p>You can download the vocabularies file using this <a href="'.$vocabulary_server_URL.$FName.'">link</a>!</p>
                </body>
            </html>
            ';

            /* To send an HTML email mail, set Content-type header to text/html. */
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

            /* additional headers */
            $headers .= "From: OMOP Vocabulary Web Site <no-reply@$SERVER_NAME>\r\n";
            $headers .= "Reply-To: no-reply@$SERVER_NAME\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            /* and now send out the email */
            mail($to, $subject, $message, $headers);

            $update_user_process_sql = 
                "UPDATE VOCAB_DOWNLOAD.VOCABULARY_USER SET FILE_CREATION_JOB_RUNNING_FLAG = 'N' WHERE PROCESS_ID = '".$item["process_id"]."'";
            $stid_update_user_process = oci_parse($conn, $update_user_process_sql);
            oci_execute($stid_update_user_process);
        }
    }
}
// free all statement identifiers and close the database connection
//oci_free_statement($stid1);
//oci_free_statement($stid2);
//oci_free_statement($stid3);
oci_free_statement($stid_user_process);
oci_free_statement($stid_update_user_process);
oci_close($conn);
