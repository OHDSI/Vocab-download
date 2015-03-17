<?include("include/app-config.php");?>
<?php

//Composer's autoload file loads all necessary files
require 'vendor/autoload.php';

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
  //echo "Unable to connect: " . var_dump( oci_error() );
  error_log("Unable to connect to Oracle database: " . var_dump( oci_error() ), 0);
  die();
}
$stid_user_process = oci_parse($conn, "select email_address, process_id, file_name from vocab_download.vocabulary_user where file_creation_job_running_flag = 'Y'");
oci_execute($stid_user_process);
$arUserProcess = [];
while ($row = oci_fetch_array($stid_user_process, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $arUserProcess[] = $row;
}

foreach($arUserProcess as $item){

    $FName = $item["FILE_NAME"];

    if(!is_running($item["PROCESS_ID"])){

        if(file_exists($zip_file_output_dir.$FName)){

            //Everything is ready - send the e-mail

            /* recipients */
            $to = $item["EMAIL_ADDRESS"];

            /* topic/subject */
            $subject = "OMOP Vocabularies. Your download link";

            /* message */
            if (substr($FName,0,17) == 'vocab_download_v4') {
                /* link to V4.5 control files folder */
                $ctl_files_folder = '<a href="https://github.com/OHDSI/CommonDataModel">V4.5 control files</a>';
            } else {
                /* link to V5 control files folder */
                $ctl_files_folder = '<a href="https://github.com/OHDSI/CommonDataModel">V5 control files</a>';
            };
            $message = '
            <html>
                <head>
                    <title>Vocabularies. Your download link</title>
                </head>
                <body>
                    <p>You can download the vocabularies file using this <a href="'.$vocabulary_server_URL.$FName.'">link</a>!</p>
                    <p>The database control files to load the vocabularies are here: ' . $ctl_files_folder  . '</p>
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

            //mail($to, $subject, $message, $headers);
            $mail = new PHPMailer;

            $mail->IsSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'smtp.mandrillapp.com';                 // Specify main and backup server
            $mail->Port = 587;                                    // Set the SMTP port
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $smtp_username;                     // SMTP username
            $mail->Password = $smtp_password;                     // SMTP password
            $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

            $mail->From = 'no-reply@ohdsi.org';
            $mail->FromName = 'OMOP Vocabulary Web Site';
            $mail->AddAddress($to);                               // Recipient email address

            $mail->IsHTML(true);                                  // Set email format to HTML

            $mail->Subject = $subject;
            $mail->Body    = $message;

            if(!$mail->Send()) {
                   echo 'Message could not be sent.';
                   echo 'Mailer Error: ' . $mail->ErrorInfo;
            }

            $update_user_process_sql =
                "UPDATE VOCAB_DOWNLOAD.VOCABULARY_USER SET FILE_CREATION_JOB_RUNNING_FLAG = 'N' WHERE PROCESS_ID = '".$item["PROCESS_ID"]."'";
            $stid_update_user_process = oci_parse($conn, $update_user_process_sql);
            oci_execute($stid_update_user_process);
        }
    }
}
// free all statement identifiers and close the database connection
oci_free_statement($stid_user_process);
oci_free_statement($stid_update_user_process);
oci_close($conn);

