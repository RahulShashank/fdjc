<?php
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once "../common/computeFleetStatusData.php";

$itemStyle = "font-family: Helvetica; font-size: 10px; text-align: left";
$systemResetSvduRatio = getSystemResetSvduRatio();
$flightPhases = getFlightPhases();
 //var_dump();

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$flightLegs = $_REQUEST['flightLegs'];
$dataType = $_REQUEST['dataType'];
$displayFaults = $_REQUEST['faultsTimeline'];
$displayResets = $_REQUEST['resetsTimeline'];
$displayApplications = $_REQUEST['applicationsTimeline'];
$displayServices = $_REQUEST['servicesTimeline'];
$displayConnectivity = $_REQUEST['connectivityTimeline'];
$updatedPercentage = $_REQUEST['wifiPercentage'];
$IsRootCauseUpdated = $_REQUEST['rootCauseUpdated'];
$rootCauseStartTime = $_REQUEST['rootCauseStartTime'];
$rootCauseEndTime = $_REQUEST['rootCauseEndTime'];
$rootCauseDataType = $_REQUEST['rootCauseDataType'];
$rootCause = $_REQUEST['rootCause'];

if($aircraftId != ''){
	$query = "SELECT databaseName, platform, tailsign FROM aircrafts a WHERE a.id = $aircraftId";
	$result = mysqli_query($dbConnection, $query);
	if($result && mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
		$platform = $row['platform'];
		$tailSign = $row['tailsign'];
		$selected = mysqli_select_db($dbConnection, $dbName)
			or die("Could not select ".$dbName);
	} else {
		 echo "<br>error: ".mysql_error($dbhandle);
	}
}

if($displayConnectivity)
{		
	if($dataType == 'WIFI')			
	{
		$updatedWifiPercentage = createWifiAvailabilityDataUpdated($dbConnection,$collection,$flightLegs,$tailSign);
		$computedWifiPercentage = getWifiAvailabilityData($dbConnection,$collection,$flightLegs,$tailSign);
	}
	elseif($dataType == 'OMTS')
	{
		$updatedOmtsPercentage = createOmtsAvailabilityDataUpdated($dbConnection,$collection,$flightLegs,$tailSign);
		$computedOmtsPercentage = getOmtsAvailabilityData($dbConnection,$collection,$flightLegs,$tailSign);
	}
	else
	{
		echo "Input Not VALID";
	}
}

if($IsRootCauseUpdated)
{
 $rootCauseUpdated = writeRootCauseInDB($collection,$rootCause,$rootCauseStartTime,$rootCauseEndTime,$rootCauseDataType,$tailSign);
}

$currentWifiAndOmtsData = array(
								'wifiOverridden' => $updatedWifiPercentage,
								'wifiComputed' => $computedWifiPercentage,
								'omtsOverridden' => $updatedOmtsPercentage,
								'omtsComputed' => $computedOmtsPercentage,
								'rootCauseUpdated' => $rootCauseUpdated);

echo json_encode($currentWifiAndOmtsData, JSON_NUMERIC_CHECK );

//write the root cause entered into mongodb
function writeRootCauseInDB($collection,$rootCause,$startTime,$endTime,$dataType,$tailSign)
{

	 if($dataType == 'WIFION'){
		//$where = array(	"wifiAvailabilityEvents.wifiOnStartTime" => $startTime);
		$where=array('$and' => array(	array("wifiAvailabilityEvents.startTime" => $startTime),
										array("wifiAvailabilityEvents.endTime" => $endTime),
										array("wifiAvailabilityEvents.description" => 'WIFI-ON'),
										array("tailSign" => $tailSign)));		
										
		$newdata = array('$set' => array('wifiAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata); 
		
	 }else if($dataType == 'WIFIOFF'){
		//$where = array(	"wifiAvailabilityEvents.wifiOffStartTime" => $startTime);
		$where=array('$and' => array(	array("wifiAvailabilityEvents.startTime" => $startTime),
										array("wifiAvailabilityEvents.endTime" => $endTime),
										array("wifiAvailabilityEvents.description" => 'WIFI-OFF'),
										array("tailSign" => $tailSign)));	
										
		$newdata = array('$set' => array('wifiAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata);  
	 
	 }else if($dataType == 'OMTSON'){
		//$where = array(	"omtsAvailabilityEvents.omtsOnStartTime" => $startTime);
		$where=array('$and' => array(	array("omtsAvailabilityEvents.startTime" => $startTime),
										array("omtsAvailabilityEvents.endTime" => $endTime),
										array("omtsAvailabilityEvents.description" => 'OMTS-ON'),
										array("tailSign" => $tailSign)));	
										
		$newdata = array('$set' => array('omtsAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata);  
	 
	 }else if($dataType == 'OMTSOFF'){
	 
		//$where = array(	"omtsAvailabilityEvents.omtsOffStartTime" => $startTime);
		$where=array('$and' => array(	array("omtsAvailabilityEvents.startTime" => $startTime),
										array("omtsAvailabilityEvents.endTime" => $endTime),
										array("omtsAvailabilityEvents.description" => 'OMTS-ON'),
										array("tailSign" => $tailSign)));	
												
		$newdata = array('$set' => array('omtsAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata); 
		
	 }else if($dataType == 'WIFIRA'){
		//$where = array(	"wifiAvailabilityEvents.wifiRestrictedStartTime" => $startTime);
		$where=array('$and' => array(	array("wifiAvailabilityEvents.startTime" => $startTime),
										array("wifiAvailabilityEvents.endTime" => $endTime),
										array("wifiAvailabilityEvents.description" => 'WIFI RESTRICTED AREA'),
										array("tailSign" => $tailSign)));	
																								
		$newdata = array('$set' => array('wifiAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata);  
	 
	 }else if($dataType == 'OMTSRA'){
		//$where = array(	"omtsAvailabilityEvents.omtsRestrictedStartTime" => $startTime);
		$where=array('$and' => array(	array("omtsAvailabilityEvents.startTime" => $startTime),
										array("omtsAvailabilityEvents.endTime" => $endTime),
										array("omtsAvailabilityEvents.description" => 'OMTS-RESTICTED'),
										array("tailSign" => $tailSign)));	
														
		$newdata = array('$set' => array('omtsAvailabilityEvents.$.manualFailureEntry' => $rootCause));
		$collection->update($where,$newdata);  
	 
	 }else{
		echo "Data Type Not a OMTS Or WIFI Parameters<br>";
	 }

	return $rootCause; 
}


function createWifiAvailabilityDataUpdated($dbConnection, $collection, $flightLegs,$tailSign)
{
	$where = array("idFlightLeg" => $flightLegs);
	
	$newdata = array('$set' => array(	'wifiAvailability.manualPercentageOn' => $_REQUEST['number'],
										'wifiAvailability.manualPercentageOff' => (100 - $_REQUEST['number'])));
	
	$collection->update($where,$newdata);
		
 return $_REQUEST['number'];
}

function getWifiAvailabilityData($dbConnection,$collection,$flightLegs,$tailSign)
{
	$totalWifiAvailbilityPercentage = 0;
	$where = array('idFlightLeg' => array('$eq' => $flightLegs));
	
	$fields = array('wifiAvailability.totalOnPercentage',
					'idFlightLeg');
	
	$cursor = $collection->find($where,$fields);
	
	foreach ($cursor as $doc) 
	{
	
		if(($doc['wifiAvailability']['totalOnPercentage'] != null) || ($doc['wifiAvailability']['totalOnPercentage'] != null))
		{
			$totalWifiAvailbilityPercentage = $doc['wifiAvailability']['totalOnPercentage'];

		}
		else
		{
			//do nothing
		}
		
	return round($totalWifiAvailbilityPercentage,2);
	}	
		
 return $_REQUEST['number'];
}
	
function getOmtsAvailabilityData($dbConnection,$collection,$flightLegs,$tailSign)
{
	$totalOmtsAvailbilityPercentage = 0;
	$where = array('idFlightLeg' => array('$eq' => $flightLegs));
	
	$fields = array('omtsAvailability.totalOnPercentage',
					'idFlightLeg');
	
	$cursor = $collection->find($where,$fields);
	
	foreach ($cursor as $doc) 
	{
	
		if(($doc['omtsAvailability']['totalOnPercentage'] != null))
		{
			$totalOmtsAvailbilityPercentage = $doc['omtsAvailability']['totalOnPercentage'];

		}
		else
		{
			//do nothing
		}
		
	return round($totalOmtsAvailbilityPercentage,2);
	}	
		
 return $_REQUEST['number'];
}	

function createOmtsAvailabilityDataUpdated($dbConnection, $collection, $flightLegs,$tailSign)
{
	$where = array("idFlightLeg" => $flightLegs);
	
	$newdata = array('$set' => array(	'omtsAvailability.manualPercentageOn' => $_REQUEST['number'],
										'omtsAvailability.manualPercentageOff' => (100 - $_REQUEST['number'])));
	
	$collection->update($where,$newdata);
		
 return $_REQUEST['number'];
}	

?>
