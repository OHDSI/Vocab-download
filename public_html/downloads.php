<?php
$conn=oci_connect("hstan", "sld73mdj", "91.225.130.5/OMOP");
  if ( ! $conn ) {
    echo "Unable to connect: " . var_dump( oci_error() );
    die();
  }
  else {   
	$stid = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = V5DEV');
	oci_execute($stid);
	
	$stid = oci_parse($conn, 'select c.click_default, c.vocabulary_id_v4, c.vocabulary_id_v5, v.vocabulary_name, c.omop_req, c.available from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id');
	oci_execute($stid);
	$arVocab = array();
	$OMOPTypes = array();
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		if($row["OMOP_REQ"] == "Y") {
			$OMOPTypes[] = $row;			
		}
		$arVocab[] = $row;
	}	
  }


$email = $_POST["email"] ? $_POST["email"] : false;
$purpose = $_POST["purpose"] ? $_POST["purpose"] : array();
$name = $_POST["user_name"] ? $_POST["user_name"] : false;
$Organization = $_POST["organization"] ? $_POST["organization"] : false;
$Address = $_POST["address"] ? $_POST["address"] : false;
$City = $_POST["city"] ? $_POST["city"] : false;
$Country = $_POST["country"] ? $_POST["country"] : false;
$Phone = $_POST["phone"] ? $_POST["phone"] : false;

$Title = $_POST["title"] ? $_POST["title"] : false;
$State = $_POST["state"] ? $_POST["state"] : false;
$Zip = $_POST["zip"] ? $_POST["zip"] : false;
$CDMVervion = $_POST["CDMVervion"] ? $_POST["CDMVervion"] : 4;

if(!$email || !$name || !$Organization || !$Address || !$City  || !$Country || !$Phone)
	die("Not valid information!");
	
if(in_array("OMOPTypes", $purpose)){
	unset($purpose[array_search("OMOPTypes", $purpose)]);
	foreach($OMOPTypes as $OMOPTypeVocab){
		$purpose[] = $OMOPTypeVocab["VOCABULARY_ID_V4"];
	}
}
	
include("include/class.mysql.php");
include("include/Users.php");

$db = new mysql_db();
if (!$db->IsConnected()) {
    echo "Нет соединения с базой данных :-(";
    exit();
}

$db->query('SET NAMES "utf8"');
$db->query('set character_set_connection=utf8');
$db->query('set names utf8');

$User = new User();
	
$Cred = "hstan/sld73mdj@91.225.130.5/OMOP";
	
$FName = implode("_", $purpose).".zip";
$VocIds = implode(",", $purpose);
if($CDMVervion == 4){	
	//If Dumpler Version 4. Exec dumpV4.pl script
	$PID = shell_exec('nohup  /home/admin/web/default.domain/private/dumpV4.pl '.$Cred.' '.$FName. ' '.$VocIds.' > /dev/null & echo $!');
} else {
	//Preparing exec params. Convert ID to Code
	$vocabCodes = array();
	foreach($arVocab as $VocabItem){
		foreach($purpose as $vocabID){
			if($VocabItem["VOCABULARY_ID_V4"] == $vocabID){
				$vocabCodes[] = $VocabItem["VOCABULARY_ID_V5"]; 
			}
		}
	}
	//If Dumpler Version 5. Exec dumpV5.pl script
	$vocabCodesStr = implode(",", $vocabCodes);
	$PID = shell_exec('nohup  /home/admin/web/default.domain/private/DumperV5/dumpV5.pl '.$Cred.' '.$FName. ' '.$vocabCodesStr.' > /dev/null & echo $!');
}
//Add To Log
$User->addUser($email, $name, $Organization, $Address, $City, $Country, $Phone, $VocIds, $PID, $Title, $State, $Zip);
header("Location: /download-process.php");
exit;

?>