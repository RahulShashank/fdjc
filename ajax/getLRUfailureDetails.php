<?php
require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

//$query = "SELECT b.name, a.id, a.description, a.SB, a.lruNumber, a.modVal, a.rev, a.EO, a.Mandatory from lru_failures a, lru_table b where a.lruNumber=b.partNumber";
$query = "SELECT a.name, b.description, b.SB, b.EO, b.Mandatory, a.partNumber, c.modVal, c.rev, c.lruId, c.failureId from lru_failures b, lru_table a, partnumber_failures c where c.lruId=a.id and c.failureId=b.id";
$result = mysqli_query($dbConnection, $query);

$lruFailureDetails = array();
if($result){
	while ($row = mysqli_fetch_array($result)) {
		array_push($lruFailureDetails, array('name'=>$row['name'], 'description'=>$row['description'], 'sb'=>$row['SB'], 'lruNumber'=>$row['partNumber'], 'modVal'=>$row['modVal'], 'rev'=>$row['rev'], 'EO'=>$row['EO'], 'Mandatory'=>$row['Mandatory'], 'lruId'=>$row['lruId'], 'failureId'=>$row['failureId']));
			
	}
}

# JSON-encode the response
$json_response = json_encode($lruFailureDetails);

echo $json_response;
?>
