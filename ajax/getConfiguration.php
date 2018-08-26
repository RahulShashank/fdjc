<?php
require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

require_once "../common/computeFleetStatusData.php";
require_once "../common/datesConfiguration.php";

$airlineId = $_REQUEST['airlineId'];

$inputPlatform = $_REQUEST['platform'];
$inputConfiguration = $_REQUEST['configuration'];

if(isset($airlineId)) {
	// For Engineering
	$query = "SELECT id,ConfigName,Platform,PreviousSW,PreviousCustSW,LatestSW,LatestCustSW,FutureSW,FutureSwDate FROM Configuration WHERE airlineID=$airlineId ";
	
	if(is_array($inputPlatform)) {
	    $query .= " and Platform in(";
	    foreach ($inputPlatform as $p) {
	        $query .= "'" . $p . "',";
	    }
	    $query = rtrim($query, ",") . ")";
	} else if(!empty($inputPlatform)) {
	    $query .= " and Platform='$inputPlatform'";
	}
	
	if(is_array($inputConfiguration)) {
	    $query .= " and ConfigName in(";
	    foreach ($inputConfiguration as $configType) {
	        $query .= "'" . $configType . "',";
	    }
	    $query = rtrim($query, ",") . ")";
	} else if(!empty($inputConfiguration)) {
	    $query .= " and ConfigName='$inputConfiguration'";
	}
		$query .= "	ORDER BY ConfigName ASC";
		
	$stmt = $dbConnection->prepare($query) ;
	//$stmt->bind_param("i", $airlineId);
	$stmt->execute();
	$stmt->bind_result($id, $ConfigName, $Platform, $PreviousSW, $PreviousCustSW, $LatestSW, $LatestCustSW, $FutureSW, $FutureSwDate);

	$aircrafts = array();
		
	while ($stmt->fetch()) {
	
	if($FutureSwDate == '0000-00-00'){
		$newFutureSwDate = '';
	}
	else{
		$newFutureSwDate = $FutureSwDate;
	}
			
		$aircrafts[] = array(
			'id' 					=> $id, 
			'ConfigName' 			=> $ConfigName, 
			'Platform' 				=> $Platform, 
			'PreviousSW' 			=> $PreviousSW, 
			'PreviousSWVersion' 	=> $PreviousCustSW,
			'LatestSW' 				=> $LatestSW, 
			'LatestSWVersion' 		=> $LatestCustSW,
			'FutureSW' 				=> $FutureSW,
			'newFutureSwDate' 		=> $newFutureSwDate,		
			'edit' 					=> "<a role=\"button\" data-toggle=\"modal\" data-target=\"#myModal\" data-configname=\"$ConfigName\" data-platform=\"$Platform\" data-previoussw=\"$PreviousSW\" data-previousswversion=\"$PreviousCustSW\" data-futuresw=\"$FutureSW\" data-latestsw=\"$LatestSW\" data-latestswversion=\"$LatestCustSW\" data-futureswdate=\"$newFutureSwDate\"><span class='glyphicon glyphicon-edit'></span></a>",
			'wiring'				=>"<a role=\"button\"><input type=\"file\" width=\"10px\" height=\"10px\" style=\"vertical-align:middle\"><img src=\"../img/upload.png\" width=\"20px\" height=\"20px\" style=\"vertical-align:middle\"></a>"
		);
	}
 
	$stmt->close();
}


# JSON-encode the response
echo $json_response = json_encode($aircrafts);

?>
