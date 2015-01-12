<?php
class User {
	function addUser($email, $name, $Organization, $Address, $City, $Country, $Phone, $vocabs, $PID, $Title = '', $State = '', $Zip = ''){
		global $db;
		$sql = "INSERT INTO `Users` (
        `id`,
        `email`,
		`name`,
		`Organization`,
		`Address`,
		`City`,
		`Country`,
		`Phone`,
		`vocabs`,
		`PID`,
		`Title`,
		`State`,
		`Zip`,
		`status`,
		`date`
        ) VALUES (
        NULL,
        '" . $email . "',
		'" . $name . "',
		'" . $Organization . "',
		'" . $Address . "',
		'" . $City . "',
		'" . $Country . "',
		'" . $Phone . "',
		'" . $vocabs . "',
		'" . $PID . "',
		'" . $Title . "',
		'" . $State . "',
		'" . $Zip . "',
		'0',
		NOW()
        )";

        $r = $db->query($sql);

        return $r;
	}
	
	function upStatus($PID, $status){
		global $db;

        $sql = "UPDATE  `Users`
        SET
        `status` =  '" . $status . "'
       WHERE `PID` = '" . $PID . "'";

        $r = $db->query($sql);

        return $r;
	}
	
	function getUserList($status = 0){
		global $db;


        $sql = "SELECT * FROM `Users` WHERE `status` = 0 LIMIT 100";
        $r = $db->query($sql);
        $arResult = array();
        while ($item = $db->database_fetch_assoc($r)) {
            $arResult[] = $item;
        }

        return $arResult;
	
	}
	
}
?>