<?php

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');
/*$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );
$serialNumber = trim($_REQUEST['res']);*/
$data = json_decode(stripslashes($_POST['data']));
 $resultArr = [];
foreach($data as $d){
   //  $i = 0;
    $query = "SELECT * from repair_serialNumber A INNER JOIN repair_failureInfo B ON A.id=B.idSerialNumber INNER JOIN repair_unitInfo C ON A.id=C.idSerialNumber WHERE serialNumber = '$d'";
	
    $result = mysqli_query($dbConnection, $query);
		if($result){
			while($row = mysqli_fetch_array($result)) {
				array_push($resultArr, $row);
			}
		}
   //  $i++;
}
	echo $json_response = json_encode($resultArr);
?>