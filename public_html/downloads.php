<?include("include/app-config.php");?>
<?php

function getGUID(){

    mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = chr(123)// "{"
        .substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12)
        .chr(125);// "}"
    return $uuid;

}

$conn=oci_connect($database_user, $database_password, $database);
if ( ! $conn ) {
  echo "Unable to connect: " . var_dump( oci_error() );
  die();
}
else {
      $stid0 = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = PRODV5');
      oci_execute($stid0);

      $stid = oci_parse($conn, "select c.vocabulary_id_v4 from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id where omop_req = 'Y'");
      oci_execute($stid);
      $arVocab = array();
      $OMOPTypes = array();
      $index = 0;
      while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
          $OMOPTypes[$index] = $row["VOCABULARY_ID_V4"];
          $index = $index + 1;
      }
}

$email = $_POST["email"] ? $_POST["email"] : false;
$vocabids = $_POST["purpose"] ? $_POST["purpose"] : array();
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


// merge in the OMOP required vocab ids
$allvocabids = array_merge($vocabids, $OMOPTypes);
$allvocabids = array_unique($allvocabids);

if($CDMVersion == 4.5){
    $Cred = $database_prodv4_credentials;
} else {
    $Cred = $database_prodv5_credentials;
}
$VocIds = implode(",", $allvocabids);
//$FName = implode("_", $vocabids).".zip";
if($CDMVersion == 4.5){
    $FName = "vocab_download_v4_5_" . getGUID() . ".zip";
} else {
    $FName = "vocab_download_v5_" . getGUID() . ".zip";
}

$shell_exec_string = 'nohup '.$perl_dump_script_dir.'dump.pl '.$Cred.' '.$CDMVersion.' '.$zip_file_output_dir.$FName.' '.$VocIds.' > /dev/null & echo $!';
$PID = shell_exec($shell_exec_string);


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
	FILE_NAME,
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
        " . $PID . ",
        '" . $Title . "',
        '" . $State . "',
        '" . $Zip . "',
        '" . $FName. "',
        'Y'
    )";
$stid_add_user_process = oci_parse($conn, $insert_user_process_sql);

$returnvalue = oci_execute($stid_add_user_process);
if (!$returnvalue) {
    $e = oci_error($stid_add_user_process);  // For oci_execute errors pass the statement handle
    print htmlentities($e['message']);
    print "\n<pre>\n";
    print htmlentities($e['sqltext']);
    printf("\n%".($e['offset']+1)."s", "^");
    print  "\n</pre>\n";
}

// free all statement identifiers and close the database connection
oci_free_statement($stid);
oci_free_statement($stid0);
oci_free_statement($stid_add_user_process);
oci_close($conn);

header("Location: download-process.php");
exit;

