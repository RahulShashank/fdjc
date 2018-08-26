<?php
require_once "../database/connecti_database.php";
require_once "../common/computeFleetStatusData.php";

$airlineIds = $_REQUEST['airlineIds'];

require_once("../common/validateUser.php");
$approvedRoles = [$roles["admin"], $roles["engineer"]];
$auth->checkPermission($hash, $approvedRoles);

$query = "SELECT id, name, acronym, status, lastStatusComputed FROM airlines";
if(isset($airlineIds) && $airlineIds != -1) {
	$query .=  " WHERE id IN ($airlineIds)";
	// STO Note: this is not the best to appened the airlineIds here but this is causing problems if we bind it...
	// This would need to be imrpoved...
}

$query .= " order by name";

$stmt = $dbConnection->prepare($query) ;

// if(isset($airlineIds) && $airlineIds != -1) {
	// $stmt = $dbConnection->prepare($query) ;
	//$stmt = $dbConnection->prepare($query . " WHERE id IN(?)") ; // STO: Issue if doing this...
	//$stmt->bind_param("s", $airlineIds);
// } else {
	// $stmt = $dbConnection->prepare($query) ;
	// $query = "SELECT * from airlines";
	// $result = mysqli_query($dbConnection, $query);
	// while ($row = mysqli_fetch_array($result)) {
	// 	$status = getAirlineStatus($row['id']);
	// 	$row['status'] = $status;
	// 	$airlines[] = $row;
	// }
//}


$stmt->execute();
$stmt->bind_result($id, $name, $acronym, $status, $lastStatusComputed);

$airlines = array();
$dateTimeThreshold = date_modify(new DateTime(), '-7 day');
while ($stmt->fetch()) {

	$lastStatusComputedDateTime = new DateTime( $lastStatusComputed );
	if( $lastStatusComputedDateTime < $dateTimeThreshold ){
		$status=-1;
	}
	$airlines[] = array('id' => $id, 'name' => $name, 'acronym' => $acronym, 'status'=>$status, 'lastStatusComputed'=>$lastStatusComputed);
}

/*
// Need to compute the status now. I can't do it in previous loop because of prepared statement.
for ($i=0; $i < count($airlines); $i++) { 
	$data = $airlines[$i];
	$data['status'] = getAirlineStatus($data['id']);
	$airlines[$i] = $data;
}
*/

$stmt->close();


# JSON-encode the response
echo $json_response = json_encode($airlines);

?>
