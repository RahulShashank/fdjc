<?php
require_once "../database/connecti_database.php";
//require_once('../engineering/checkEngineeringPermission.php');

$query = "SELECT a.id as id, a.partNumber as partNumber, a.name as name, a.lruType as lruType from lru_table a ORDER BY a.name ASC";
//$result = mysqli_query($dbConnection, $query);
//$lruPartnumberDetails=[];

/*if($result){
	while ($row = mysqli_fetch_array($result)) {
		//array_push($lruPartnumberDetails, array('id'=>$row['id'], 'partNumber'=>$row['partNumber'], 'name'=>$row['name'], 'lruType'=>$row['lruType']));
		array_push($lruPartnumberDetails, $row['id']);
	}
}*/
	$stmt = $dbConnection->prepare($query) ;
	$stmt->execute();
	$stmt->bind_result($id, $partNumber, $name, $lruType);
	// $result = mysqli_query($dbConnection, $query);

	$aircrafts = array();
	while ($stmt->fetch()) {
		$lruPartnumberDetails[] = array(
			'id' 		=> $id,
			'partNumber' 		=> $partNumber,
			'name' 		=> $name,
			'lruType' 		=> $lruType
		);
	}

# JSON-encode the response
echo $json_response = json_encode($lruPartnumberDetails);

//echo $json_response;
?>
