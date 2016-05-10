<?include("include/app-config.php");?>
<?include("include/utility-functions.php");?>
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
    sendErrorEmail("downloads.php: Unable to connect to database, user=" . $database_user . ", database_password=" . $database_password . ", database=" . $database);
    header("Location:error.php?errorMessage=".urlencode("Error: Unable to connect to database"));
    die;
}
else {
      $stid0 = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = PRODV5');
      if ( ! $stid0 ) {
            $e = oci_error($conn);
            sendErrorEmail("downloads.php: oci_parse ALTER SESSION SET CURRENT_SCHEMA = PRODV5 failed, error message=" . $e['message']);
            header("Location:error.php?errorMessage=".urlencode("Error: unable to parse set database schema"));
            die;
        }
      $returnvalue = oci_execute($stid0);
      if (!$returnvalue) {
            $e = oci_error($stid0);
            sendErrorEmail("downloads.php: oci_execute ALTER SESSION SET CURRENT_SCHEMA = PRODV5 failed, error message=" . $e['message']);
            header("Location:error.php?errorMessage=".urlencode("Error: unable to execute set database schema"));
            die;
      }
        
      $stid = oci_parse($conn, "select c.vocabulary_id_v4 from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id where omop_req = 'Y' or (click_default = 'Y' and click_disabled = 'Y')");
      if ( ! $stid ) {
            $e = oci_error($conn);
            sendErrorEmail("downloads.php: oci_parse select c.vocabulary_id_v4 from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id where omop_req = 'Y' failed, error message=" . $e['message']);
            header("Location:error.php?errorMessage=".urlencode("Error: unable to parse call to access vocabulary and vocabulary_conversion tables"));
            die;
      }
      $returnvalue = oci_execute($stid);
      if (!$returnvalue) {
            $e = oci_error($stid);
            sendErrorEmail("downloads.php oci_execute select c.vocabulary_id_v4 from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id where omop_req = 'Y' failed, error message=" . $e['message']);
            header("Location:error.php?errorMessage=".urlencode("Error: unable to execute call to access vocabulary and vocabulary_conversion tables"));
            die;
      }
      
      $arVocab = array();
      $OMOPTypes = array();
      $index = 1000;
      while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
          $OMOPTypes[$index] = $row["VOCABULARY_ID_V4"];
          $index = $index + 1;
      }
}

// escape any embedded single quote by making it into two single quotes so that the Oracle insert statement later in the code will succeed
$email = $_POST["email"] ? str_replace("'","''", $_POST["email"]) : false;
$vocabids = $_POST["purpose"] ? str_replace("'","''", $_POST["purpose"]) : array();
$name = $_POST["user_name"] ? str_replace("'","''", $_POST["user_name"]) : false;
$Organization = $_POST["organization"] ? str_replace("'","''", $_POST["organization"]) : false;
$Address = $_POST["address"] ? str_replace("'","''", $_POST["address"]) : false;
$City = $_POST["city"] ? str_replace("'","''", $_POST["city"]) : false;
$Country = $_POST["country"] ? str_replace("'","''", $_POST["country"]) : false;
$Phone = $_POST["phone"] ? str_replace("'","''", $_POST["phone"]) : false;

$Title = $_POST["title"] ? str_replace("'","''", $_POST["title"]) : false;
$State = $_POST["state"] ? str_replace("'","''", $_POST["state"]) : false;
$Zip = $_POST["zip"] ? str_replace("'","''", $_POST["zip"]) : false;
$CDMVersion = $_POST["CDMVersion"] ? str_replace("'","''", $_POST["CDMVersion"]) : 4.5;

$LicensedIds = '';

if (!empty($vocabids)) {
    $query_license_required =
        "SELECT VOCABULARY_ID_V4, VOCABULARY_ID_V5"
        . " FROM VOCABULARY_CONVERSION "
        . " WHERE VOCABULARY_ID_V4 IN (" . implode(',', $vocabids). ") AND AVAILABLE = 'License required'";
    $stid_license_required = oci_parse($conn, $query_license_required);
    $result_license_required = oci_execute($stid_license_required);
    $licensed_vocabularies = array();
    while($row = oci_fetch_array($stid_license_required, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $idV5 = strtolower($row['VOCABULARY_ID_V5']);
        $licensed_vocabularies[$idV5] = $row['VOCABULARY_ID_V4'];
    }
    if (!empty($licensed_vocabularies)) {
        // check the user has appropriate licenses.
        $query_check_licenses =
            "SELECT VOCABULARY"
            . " FROM VOCAB_DOWNLOAD.USER_LICENSE "
            . " WHERE EMAIL_ADDRESS = '" . $email . "'";
        $stid_check_licenses = oci_parse($conn, $query_check_licenses);
        $result_check_licenses = oci_execute($stid_check_licenses);
        while($row = oci_fetch_array($stid_check_licenses, OCI_ASSOC+OCI_RETURN_NULLS)) {
            $license = trim($row['VOCABULARY']);
            $license = strtolower($license);
            unset($licensed_vocabularies[$license]);
        }
    }
    if (!empty($licensed_vocabularies)) {
        // user don't have some licenses
        $vocabids = array_diff($vocabids, $licensed_vocabularies);
        $LicensedIds = implode(',', array_values($licensed_vocabularies));
    }
}

if(!$email || !$name || !$Organization || !$Address || !$City  || !$Country || !$Phone) {
        die("Not valid information!");
}


// combine the OMOP required vocab ids with the user selected vocab ids
$allvocabids = $vocabids + $OMOPTypes;
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
if (!$PID) {
    $e = oci_error($stid);
    sendErrorEmail("downloads.php shell_exec failed, exec_string=" . $shell_exec_string );
    header("Location:error.php?errorMessage=".urlencode("Error: unable to generate export file"));
    die;
}

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
        FILE_CREATION_JOB_RUNNING_FLAG,
        LICENSED
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
        'Y',
        '" . $LicensedIds . "'
    )";

$stid_add_user_process = oci_parse($conn, $insert_user_process_sql);
if ( ! $stid_add_user_process ) {
            $e = oci_error($conn);
            sendErrorEmail("downloads.php: oci_parse INSERT INTO VOCAB_DOWNLOAD.VOCABULARY_USER table failed, sql=" . $insert_user_process_sql . ", error message=" . $e['message']);
            header("Location:error.php?errorMessage=".urlencode("Error: unable to log export file request"));
            die;
        }

$returnvalue = oci_execute($stid_add_user_process);
if (!$returnvalue) {
    $e = oci_error($stid_add_user_process);  // For oci_execute errors pass the statement handle
    sendErrorEmail("downloads.php: oci_execute INSERT INTO VOCAB_DOWNLOAD.VOCABULARY_USER table failed, sql=" . $e['sqltext'] . ", error message=" . $e['message']);
    header("Location:error.php?errorMessage=".urlencode("Error: unable to execute INSERT INTO VOCAB_DOWNLOAD.VOCABULARY_USER table"));
    die;    
}

// free all statement identifiers and close the database connection
oci_free_statement($stid);
oci_free_statement($stid0);
oci_free_statement($stid_add_user_process);
oci_close($conn);

header("Location: download-process.php");
exit;

