<?php
$email = $_POST["email"] ? $_POST["email"] : false;
$dfile = $_POST["dfile"] ? $_POST["dfile"] : false;

if (!$email || !$dfile) {
    die("Not valid email or file name!");
}

/* addressees */
$to= "<info@ohdsi.org>"; #email $to = $email;

/* theme/subject */
$subject = "Vocabularies. Your download link";

/* message */
$message = '
<html>
<head>
 <title>Vocabularies. Your download link</title>
</head>
<body>
<h1>Link for downloading the Standardized Vocabularies</h1>
<p>Please download and load the Standardized Vocabularies as following:</p>
<ol>
<li>Click on this <a href="http://91.225.130.23/'.$dfile.'">link</a> to download the zip file. 
Typical file sizes, depending on the number of vocabularies selected, are between 30 and 250 MB.</li>
<li>Unpack.</li>
<li>If needed, create the tables.</li>
<li>Load the unpacked files into the tables.</li>
</ol>
<p>For current CDM version 5 you can find DDL and loading scripts <a href="https://github.com/OHDSI/CommonDataModel">here</a>. 
For CDM version 4 you can find DDL and loading scripts inside a <a href="https://github.com/OHDSI/CommonDataModel">subfolder</a> to the current version 5. 
DDL files are provided in the folders Oracle/, PostgreSQL/ and SQL Server/, while the loading scripts are in the subfolder VocabImport/.</p>
<br>
<p>If you hit problems please use the <a href="http://forums.ohdsi.org/c/implementers">OHDSI Forum pages</a>, and somebody will help you. You will need to register.</p>
<p>Christian Reich and the Vocabulary Team</p>
</body>
</html>
';

/* To define the html content type in the header */
$headers= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

/* Additional fields */
$headers .= "From: Webmaster <info@ohdsi.org>\r\n";
$headers .= "Reply-To: info@ohdsi.org\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

/* And send out */
mail($to, $subject, $message, $headers);
