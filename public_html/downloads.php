<?include("include/app-config.php");?>
<?php
$conn=oci_connect($database_user, $database_password, $database);
if ( ! $conn ) {
  echo "Unable to connect: " . var_dump( oci_error() );
  die();
}
else {   
      $stid0 = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = PRODV5');
      oci_execute($stid0);

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
//$purpose = $_POST["purpose"] ? $_POST["purpose"] : array();
$name = $_POST["user_name"] ? $_POST["user_name"] : false;
$Organization = $_POST["organization"] ? $_POST["organization"] : false;
$Address = $_POST["address"] ? $_POST["address"] : false;
$City = $_POST["city"] ? $_POST["city"] : false;
$Country = $_POST["country"] ? $_POST["country"] : false;
$Phone = $_POST["phone"] ? $_POST["phone"] : false;

$Title = $_POST["title"] ? $_POST["title"] : false;
$State = $_POST["state"] ? $_POST["state"] : false;
$Zip = $_POST["zip"] ? $_POST["zip"] : false;
$CDMVersion = $_POST["CDMVersion"] ? $_POST["CDMVersion"] : 4.5;

if(!$email || !$name || !$Organization || !$Address || !$City  || !$Country || !$Phone) {
	die("Not valid information!");
}
	
//if(in_array("OMOPTypes", $purpose)){
//	unset($purpose[array_search("OMOPTypes", $purpose)]);
	foreach($OMOPTypes as $OMOPTypeVocab){
		$arVocabIds[] = $OMOPTypeVocab["VOCABULARY_ID_V4"];
	}
//}

if($CDMVersion == 4.5){
    $Cred = $database_prodv4_credentials;
} else {
    $Cred = $database_prodv5_credentials;
}
	
$FName = implode("_", $arVocabIds).".zip";
$VocIds = implode(",", $arVocabIds);
// unix version:
//$shell_exec_string = 'nohup '.$perl_dump_script_dir.'dump_1_line.pl '.$Cred.' '.$zip_file_output_dir.$CDMVersion.' '.$FName. ' '.$VocIds.' > /dev/null & echo $!';
// windows version:
$shell_exec_string = 'perl '.$perl_dump_script_dir.'dump_1_line.pl '.$Cred.' '.$CDMVersion.' '.$zip_file_output_dir.$FName. ' '.$VocIds;
$PID = shell_exec($shell_exec_string);

//$stid1 = oci_parse($conn, 'SET NAMES "utf8"');
//oci_execute($stid1);
//$stid2 = oci_parse($conn, 'set character_set_connection=utf8');
//oci_execute($stid2);
//$stid3 = oci_parse($conn, 'set names utf8');
//oci_execute($stid3);

$insert_user_process_sql = 
    "INSERT INTO VOCAB_DOWNLOAD.VOCABULARY_USER (
        EMAIL_ADDRESS,
        NAME,
        ORGANIZATION,
        ADDRESS,
        CITY_NAME,
        COUNTRY_NAME,
        PHONE_NUMBER,
        VOCABULARY_LIST,
        PROCESS_ID,
        TITLE,
        STATE,
        ZIP_CODE,
        FILE_CREATION_JOB_RUNNING_FLAG
    ) VALUES (
        '" . $email . "',
        '" . $name . "',
        '" . $Organization . "',
        '" . $Address . "',
        '" . $City . "',
        '" . $Country . "',
        '" . $Phone . "',
        '" . $VocIds . "',
        '" . $PID . "',
        '" . $Title . "',
        '" . $State . "',
        '" . $Zip . "',
        'Y'
    )";
$stid_add_user_process = oci_parse($conn, $insert_user_process_sql);
oci_execute($stid_add_user_process);

// Add this user and their zip file process id to the vocabulary_user (user/process) table
// !!! commented out below line for testing !!!
//$User->addUser($email, $name, $Organization, $Address, $City, $Country, $Phone, $VocIds, $PID, $Title, $State, $Zip);
        
// free all statement identifiers and close the database connection
oci_free_statement($stid);
oci_free_statement($stid0);
//oci_free_statement($stid1);
//oci_free_statement($stid2);
//oci_free_statement($stid3);
oci_free_statement($stid_add_user_process);
oci_close($conn);

header("Location: /download-process.php");
exit;
