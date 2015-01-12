<?php
	/*
	* Check if the Application running !
    *
    * @param     unknown_type $PID
    * @return     boolen
    */
   function is_running($PID){
       exec("ps $PID", $ProcessState);
       return(count($ProcessState) >= 2);
   }
   
   
   
include("/home/admin/web/default.domain/public_html/include/class.mysql.php");
include("/home/admin/web/default.domain/public_html/include/Users.php");

$db = new mysql_db();
if (!$db->IsConnected()) {
    echo "Нет соединения с базой данных :-(";
    exit();
}

$db->query('SET NAMES "utf8"');
$db->query('set character_set_connection=utf8');
$db->query('set names utf8');

$User = new User();
$list = $User->getUserList();

foreach($list as $item){
	$FName = str_replace(",", "_", $item["vocabs"]).".zip";
	$SERVER_NAME = "omop.ru";
   
   if(!is_running($item["PID"])){
		if(file_exists("/home/admin/web/default.domain/public_html/".$FName)){
			//Все готово. Отправляем почту
					
			/* получатели */
			#$to= "<chupokabr@yandex.ru>"; #email $to = $email;
			$to = $item["email"];

			/* тема/subject */
			$subject = "Vocabularies. Your download link";

			/* сообщение */
			$message = '
			<html>
			<head>
			 <title>Vocabularies. Your download link</title>
			</head>
			<body>
			<p>You can download vocabularies by this <a href="http://91.225.130.23/'.$FName.'">link</a>!</p>
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

			/* и теперь отправим из */
			mail($to, $subject, $message, $headers);
			
			$User->upStatus($item["PID"], 1);
		}
   }
}
?>