<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');

$action = $_REQUEST['action'];
$tailsign= $_REQUEST['tailsign'];
$airlineId= $_REQUEST['airlineId'];
$platform= $_REQUEST['platform'];
$configType= $_REQUEST['config'];
$startDateTime= $_REQUEST['startDateTime'];
$endDateTime= $_REQUEST['endDateTime'];

$dbName="";
$dbQuery="SELECT databaseName FROM aircrafts WHERE tailsign='$tailsign' AND airlineId=$airlineId";
	$result = mysqli_query($dbConnection, $dbQuery);
	if($result){
		while($row = mysqli_fetch_array($result)){
			$dbName=$row['databaseName'];
		}		
	}
if($action == "getFaultCodelist") {
	// Get all Fault Codes.	
	$query = "SELECT DISTINCT faultCode FROM $dbName.bit_fault where DATE(detectionTime) BETWEEN CAST('$startDateTime'AS DATE) AND CAST('$endDateTime'AS DATE)  ORDER BY faultCode";
	
	$result = mysqli_query($dbConnection, $query);
	$faultInfos = array();
	$faultInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			//array_push($faultInfos, $row); 
			//array_push($faultInfosForAutoSuggest, $row['faultCode'] . ':' . $row['faultDesc']);
			array_push($faultInfosForAutoSuggest, $row['faultCode']);
		}
	}
	# JSON-encode the response
    $json_response = json_encode($faultInfosForAutoSuggest);
    // # Return the response
    echo $json_response;
}else if($action == "getFailureCodelist") {
	// Get all FailureCodelist Codes.
	$query = "SELECT DISTINCT failureCode FROM $dbName.bit_failure where DATE(correlationDate) BETWEEN CAST('$startDateTime'AS DATE) AND CAST('$endDateTime'AS DATE) ORDER BY failureCode";
	
	$result = mysqli_query($dbConnection, $query);
	$faultInfos = array();
	$faultInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			//array_push($faultInfos, $row); 
			//array_push($faultInfosForAutoSuggest, $row['failureCode'] . ':' . $row['failureDesc']);
			
			array_push($faultInfosForAutoSuggest, $row['failureCode']);
		}
	}
	# JSON-encode the response
    $json_response = json_encode($faultInfosForAutoSuggest);
    // # Return the response
    echo $json_response;
}else if($action == "getImpactedServiceslist") {
	// Get all ImpactedServiceslist Codes.
	$query = "SELECT DISTINCT failureCode FROM $dbName.bit_servicefailure where DATE(correlationDate) BETWEEN CAST('$startDateTime'AS DATE) AND CAST('$endDateTime'AS DATE) ORDER BY failureCode";
	$result = mysqli_query($dbConnection, $query);
	$faultInfos = array();
	$faultInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			//array_push($faultInfos, $row); 
			//array_push($faultInfosForAutoSuggest, $row['failureCode'] . ':' . $row['failureDesc']);
			array_push($faultInfosForAutoSuggest, $row['failureCode']);
		}
	}
	# JSON-encode the response
    $json_response = json_encode($faultInfosForAutoSuggest);
    // # Return the response
    echo $json_response;
}else if($action == "getflightPhases") {
	$flightPhases = array("1:Pre-flightground",
							"2:Taxi-out", 
							"3:Take-off", 
							"4:Climb", 
							"5:Cruise", 
							"6:Descent", 
							"7:Landed", 
							"8:Taxi-in", 
							"9:Post-flight");
	# JSON-encode the response
    $json_response = json_encode($flightPhases);
    // # Return the response
    echo $json_response;
}else if($action == "getResets") {
	$resets = array("CommandedReboot",
							"UncommandedReboot");
	# JSON-encode the response
    $json_response = json_encode($resets);
    // # Return the response
    echo $json_response;
}else if($action == "getResetsCode") {
	$resets[] = (object) array('value' => 'CommandedReboot','label' => 'Commanded Reboot');
	$resets[] = (object) array('value' => 'UncommandedReboot','label' => 'Uncommanded Reboot');
	//$resets = array_push({},{});
	# JSON-encode the response
    $json_response = json_encode($resets);
    // # Return the response
    echo $json_response;
}else if($action == "getMonitorState") {
	$monitorState[] = (object) array('value' => '3','label' => 'Active');
	$monitorState[] = (object) array('value' => '1','label' => 'Inactive');
	//$resets = array_push({},{});
	# JSON-encode the response
    $json_response = json_encode($monitorState);
    // # Return the response
    echo $json_response;
}else if($action == "getAircraftId") {	
	$query = "SELECT id FROM aircrafts WHERE tailsign='$tailsign'";
	$result = mysqli_query($dbConnection, $query);
	$aircraftId='';

	if($result){
		while($row = mysqli_fetch_array($result)){			
			$aircraftId=$row['id'];
		}		
	}
	# JSON-encode the response
    $json_response = json_encode($aircraftId);
    // # Return the response
    echo $aircraftId;
}
else if($action == "getAirlineAcroynm") {	
	$query = "SELECT acronym FROM airlines WHERE id=$airlineId";
	$result = mysqli_query($dbConnection, $query);
	$airlineAcronym='';

	if($result){
		while($row = mysqli_fetch_array($result)){			
			$airlineAcronym=$row['acronym'];
		}		
	}
	# JSON-encode the response
    $json_response = json_encode($airlineAcronym);
    // # Return the response
    echo $json_response;
}
else if($action == "getTailsignlist") {	
	$query = "select distinct(tailsign) from aircrafts where airlineID=$airlineId and platform='$platform' and Ac_Configuration='$configType' order by tailsign";	
	$result = mysqli_query($dbConnection, $query);
	$tailsignList=array();
	$tailsignListSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			array_push($tailsignList, $row); 
			array_push($tailsignListSuggest, $row['tailsign']);
		}
	}
	# JSON-encode the response
    $json_response = json_encode($tailsignListSuggest);
    // # Return the response
    echo $json_response;
}
else if($action == "getAircraftIdnISP") {	
	$query = "SELECT id,ISP FROM aircrafts WHERE tailsign='$tailsign' LIMIT 1";
	
	$result = mysqli_query($dbConnection, $query);
	$tailsignISP = array();	
	if($result){
		while($row = mysqli_fetch_array($result)){			
			$tailsignISP[] = array('id' => $row['id'], 'ISP' => $row['ISP']);
		}		
	}
	# JSON-encode the response
    $json_response = json_encode($tailsignISP);
    // # Return the response
    echo $json_response;
}

?>
