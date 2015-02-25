<?php
$email = $_POST["email"] ? $_POST["email"] : false;
$dfile = $_POST["dfile"] ? $_POST["dfile"] : false;

if (!$email || !$dfile) {
    die("Not valid email or file name!");
}

/* получатели */
$to= "<chupokabr@yandex.ru>"; #email $to = $email;

/* тема/subject */
$subject = "Vocabularies. Your download link";

/* сообщение */
$message = '
<html>
<head>
 <title>Vocabularies. Your download link</title>
</head>
<body>
<p>You can download vocabularies by this <a href="http://91.225.130.23/'.$dfile.'">link</a>!</p>
</body>
</html>
';

/* Для отправки HTML-почты вы можете установить шапку Content-type. */
$headers= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

/* дополнительные шапки */
$headers .= "From: Birthday Reminder <no-reply@$SERVER_NAME>\r\n";
$headers .= "Reply-To: no-reply@$SERVER_NAME\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();
#$headers .= "Cc: birthdayarchive@example.com\r\n";
#$headers .= "Bcc: birthdaycheck@example.com\r\n";

/* и теперь отправим из */
mail($to, $subject, $message, $headers);
