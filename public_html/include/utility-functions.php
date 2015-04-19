<?php

function sendErrorEmail() {

    //Composer's autoload file loads all necessary files
    require '/home/admin/web/default.domain/public_html/vendor/autoload.php';

    global $SERVER_NAME;
    global $smtp_username;
    global $smtp_password;
    global $support_email_address;

    $arg_list = func_get_args();
    $errorMessageText = $arg_list[0];

    /* To send an HTML email mail, set Content-type header to text/html. */
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

    /* additional headers */
    $headers .= "From: OMOP Vocabulary Web Site <no-reply@$SERVER_NAME>\r\n";
    $headers .= "Reply-To: no-reply@$SERVER_NAME\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    /* and now send out the error email to the website support email address  */

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
    $mail->AddAddress($support_email_address);            // Recipient email address

    $mail->IsHTML(true);                                  // Set email format to HTML

    $mail->Subject = 'OMOP Vocabulary Web Site Error';
    $mail->Body    = $errorMessageText;

    if(!$mail->Send()) {
           error_log("sendErrorEmail: Message could not be sent.", 0);
           error_log("sendErrorEmail: PHPMailer error: " . $mail->ErrorInfo, 0);
    }
}
