<?php

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');
/*$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );
$serialNumber = trim($_REQUEST['res']);*/
$data = json_decode(stripslashes($_POST['data']));
$data = array_unique($data);
 $resultArr = [];
foreach($data as $d){
    $d = trim($d);
    $query = "SELECT * from repair_serialNumber A INNER JOIN repair_unitInfo B ON A.id=B.idSerialNumber INNER JOIN repair_failureInfo C ON B.id=C.idUnit WHERE A.serialNumber = '$d'";
	
    $result = mysqli_query($dbConnection, $query);
		if($result){
			while($row = mysqli_fetch_assoc($result)) {
				array_push($resultArr, $row);
			}
		}
   //  $i++;
}
	echo $json_response = json_encode($resultArr);
?>