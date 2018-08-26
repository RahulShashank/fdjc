<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);
require_once "../database/connecti_database.php";
require_once "../common/computeFleetStatusData.php";
require_once "../common/datesConfiguration.php";
require_once "../common/fleetStatus.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$airlineId = $_REQUEST['airlineId'];
$fromPage = $_REQUEST['from'];
$inputPlatform = $_REQUEST['platform'];
$inputConfiguration = $_REQUEST['configuration'];

if(isset($fromPage) && $fromPage == "TSM") {
    // For Admin
    $query = "SELECT a.id, a.tailsign FROM $mainDB.aircrafts a WHERE a.airlineId = $airlineId order by tailsign";
    $stmt = $dbConnection->prepare($query) ;
    $stmt->execute();
    $stmt->bind_result($id, $tailsign);
    
    $aircrafts = array();
    while ($stmt->fetch()) {
        $aircrafts[] = array(
            'id' 		=> $id,
            'tailsign' 	=> $tailsign
        );
    }
    $stmt->close();
} else if(isset($airlineId)) {
	// For Engineering
	$query = "SELECT a.id , tailsign, noseNumber, type, msn, a.platform, software, databaseName, systemResetStatus, headEndStatus, firstClassStatus, businessClassStatus, economyClassStatus, connectivityStatus, status, lastStatusComputed, isp, Ac_Configuration, LF_RF, EIS,SW_PartNo,SW_installed, SW_Baseline, Map_Version, Content,c.LatestCustSW,maintenanceStatus  FROM ".
		"(SELECT id,airlineId, tailsign, noseNumber, type, msn, platform, software, databaseName, systemResetStatus, headEndStatus, firstClassStatus, businessClassStatus, economyClassStatus, connectivityStatus, status, lastStatusComputed, isp, Ac_Configuration, LF_RF, EIS,SW_PartNo,SW_installed, SW_Baseline, Map_Version, Content,maintenanceStatus ". 
		"from $mainDB.aircrafts WHERE airlineId=$airlineId ";
		
		if(is_array($inputPlatform)) {
		    $query .= " and platform in(";
		    foreach ($inputPlatform as $p) {
		        $query .= "'" . $p . "',";
		    }
		    $query = rtrim($query, ",") . ")";
		} else if(!empty($inputPlatform)) {
		    $query .= " and platform='$inputPlatform'";
		}
		
		if(is_array($inputConfiguration)) {
		    $query .= " and Ac_Configuration in(";
		    foreach ($inputConfiguration as $configType) {
		        $query .= "'" . $configType . "',";
		    }
		    $query = rtrim($query, ",") . ")";
		} else if(!empty($inputConfiguration)) {
		    $query .= " and Ac_Configuration='$inputConfiguration'";
		}
		
		$query .= ") a ".
		"LEFT JOIN (SELECT * FROM $mainDB.Configuration WHERE airlineId=$airlineId)c ".
		"ON a.airlineId=c.airlineId ".
		"AND a.platform=c.Platform ".
		"AND a.Ac_Configuration = c.ConfigName ORDER BY noseNumber ASC,tailsign ASC";
    error_log("Query: " . $query);
	$stmt = $dbConnection->prepare($query);
	//$stmt->bind_param("i", $airlineId);
	$stmt->bind_result($id, $tailsign, $noseNumber, $type, $msn, $platform, $software, $databaseName, $systemResetStatus, $headEndStatus, $firstClassStatus, $businessClassStatus, $economyClassStatus, $connectivityStatus, $status, $lastStatusComputed, $isp, $AC_config, $LF_RF, $EIS, $SW_PartNo, $SWinstalled, $SW_Baseline, $Map_Version, $Content, $LatestCustSW,  $maintenanceStatus);
	$stmt->execute();
	// $result = mysqli_query($dbConnection, $query);

	$aircrafts = array();
	
	$dateTimeThreshold = date_modify(new DateTime(), "- $fleetStatusPeriod day");

	while ($stmt->fetch()) {
	
		$lastStatusComputedDateTime = new DateTime( $lastStatusComputed );
		if( $lastStatusComputedDateTime < $dateTimeThreshold ){
			$systemResetStatus		=-1;
			$headEndStatus			=-1;
			$firstClassStatus		=-1;
			$businessClassStatus	=-1;
			$economyClassStatus		=-1;
			$connectivityStatus		=-1;
			$status					=-1;
		}
			
		$nwstatus = getstatus($status);
		if($SWinstalled == '0000-00-00'){
			$newSWinstalled = '';
		}
		else{
			$newSWinstalled = $SWinstalled;
		}
		
		if($EIS == '0000-00-00'){
			$newEIS = '';
		}
		else{
			$newEIS = $EIS;
		}
		
		
		if($noseNumber==''){
			$newtailsign = $tailsign;
		}
		else{
			$newtailsign = $tailsign." (".$noseNumber.")";
		}
				
		switch ($maintenanceStatus) {
			case 'Ground':
				$statusIcon = "road";
				break;
			case 'In Air':
				$statusIcon = "plane";
				break;
			case 'Watch':
				$statusIcon = "flag";
				break;
			case 'OK':
				$statusIcon = "ok";
				break;
			case 'Warning':
				$statusIcon = "warning-sign";
				break;
			case 'New Software':
				$statusIcon = "hdd";
				break;
			default:
				$statusIcon = "unchecked";
		}
		
		$maintenanceStatusLabel = "<span class=\"glyphicon glyphicon-$statusIcon\" aria-hidden=\"true\" title=\"$maintenanceStatus\" style=\"color:grey\"></span>";
		
		//$newtailsign = $tailsign;
		
		$aircrafts[] = array(
			'id' 					=> $id, 
			'tailsign' 				=> $tailsign,
			'nose'					=> $noseNumber,
// 		    'newtailsign' 			=> "<a href=\"../engineering/aircraftDashboard.php?aircraftId=$id\">$newtailsign</a>",
		    'newtailsign' 			=> $newtailsign,
		    'type' 					=> $type, 
			'msn' 					=> $msn, 
			'platform' 				=> $platform, 
			'software' 				=> $software,
			'databaseName' 			=> $databaseName,
			'systemResetStatus' 	=> $systemResetStatus,
			'headEndStatus' 		=> $headEndStatus,
			'firstClassStatus' 		=> $firstClassStatus,
			'businessClassStatus' 	=> $businessClassStatus,
			'economyClassStatus' 	=> $economyClassStatus,
			'connectivityStatus' 	=> $connectivityStatus,
			'status' 				=> $status,
			'lastStatusComputed' 	=> $lastStatusComputed,
			'isp' 					=> $isp,
			'Ac_Configuration'		=> $AC_config,
			'LF_RF'					=> $LF_RF,
			'newEIS'				=> $newEIS,
			'SW_PartNo'				=> $SW_PartNo,
			'newSWinstalled'		=> $newSWinstalled,
			'SW_Baseline'			=> $SW_Baseline,
			'Map_Version'			=> $Map_Version,
			'Content'				=> $Content,
		    'edit' 					=> "<a role=\"button\" style=\"cursor: pointer;\" data-toggle=\"modal\" data-target=\"#editAircraftModal\" data-tailsign=\"$tailsign\" data-status=\"$maintenanceStatus\" data-software=\"$software\" data-swpartno=\"$SW_PartNo\" data-swinstalled=\"$newSWinstalled\" data-swbaseline=\"$SW_Baseline\" data-mapversion=\"$Map_Version\" data-content=\"$Content\" data-msn=\"$msn\"><span class='fa fa-edit'></span></a>",
		    'statusIcon'			=> "<img src=\"../img/$nwstatus.png\" style=\"vertical-align:middle\" height=\"16\" width=\"16\">",
			'maintenanceStatus'		=> $maintenanceStatus,
			'maintenanceStatusLabel'=> $maintenanceStatusLabel,
			'LatestCustSW'			=> $LatestCustSW
		);
	}
 
	$stmt->close();
} else {
	// For Admin
	$query = "SELECT a.id, tailsign, type, msn, platform, software, name FROM $mainDB.aircrafts a, $mainDB.airlines b WHERE a.airlineId = b.id";
	$stmt = $dbConnection->prepare($query) ;
	$stmt->execute();
	$stmt->bind_result($id, $tailsign, $type, $msn, $platform, $software, $name);
	// $result = mysqli_query($dbConnection, $query);

	$aircrafts = array();
	while ($stmt->fetch()) {
		$aircrafts[] = array(
			'id' 		=> $id, 
			'tailsign' 	=> $tailsign, 
			'type' 		=> $type, 
			'msn' 		=> $msn, 
			'platform'	=> $platform, 
			'software' 	=> $software,
			'name' 		=> $name
		);
	}
	// while ($row = mysqli_fetch_array($result)) {
	// 	$aircrafts[] = $row;
	// }
}


# JSON-encode the response
echo $json_response = json_encode($aircrafts);

?>
