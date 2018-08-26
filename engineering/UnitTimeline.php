<?php
session_start();
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/computeFleetStatusData.php";

require_once("checkEngineeringPermission.php");

$itemStyle = "style: 'font-family: Helvetica; font-size: 10px; text-align: left'";
$itemStyleNew = "style: 'font-family: Helvetica; font-size: 10px; text-align: left;background-color: white'";
$sqlDump = $_REQUEST['db'];
$flightLegId = $_REQUEST['id'];
$flightLegs = $flightLegId;
$aircraftId = $_REQUEST['aircraftId'];
$mainMenu = $_REQUEST ['mainmenu'];
if($flightLegId == "") {
	header( 'Location: ./index.php' );
}

// Find out the aircraft database to be selected or sqlDump if provided.
if($aircraftId != '') {
	// Get information to display in header
	$query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.platform FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
	$result = mysqli_query($dbConnection, $query );

	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_array ( $result );
		$aircraftTailsign = $row ['tailsign'];
		$airlineId = $row['id'];
		$airlineName = $row['name'];
		$db = $row['databaseName'];
		$platform = $row['platform'];
	} else {
		echo "error: " . mysqli_error ( $error );
	}
} else if($sqlDump != '') {
	$db = $sqlDump;
} else {
	echo "Error - no aircraftId nor sqlDump";
	exit;
}


$unit  = strtok($_REQUEST['event'], '/');
$pageTitle = trimDBName($db)." > $flightLegId > $unit";
$pageIcon = "timeline.png";

$showFailures = 'on';
$showFaults = 'on';
$showEvents = 'on';
$showExtAppEvents = 'on';
$showImpServices = 'on';//smita

$selected = mysqli_select_db($dbConnection, $db)
or die("Could not select ".$db);

if(strpos($flightLegId, '-') > 0) {
	$leg1 = $type  = strtok($flightLegId, '-');
	$leg2 = $type  = strtok('-');
	$whereCondition = "WHERE idFlightLeg BETWEEN $leg1 AND $leg2";
} else {
	$whereCondition = "WHERE idFlightLeg in ($flightLegId)";
}

$dataItems = array();
$i=0;

$query = "SELECT * FROM SYS_flight
$whereCondition";

$result = mysqli_query($dbConnection, $query);

if($result) { // not every dump has the sys_flightphase table
	$keepLooping = true;
	$row = mysqli_fetch_array($result); // get first flight leg

	while($keepLooping) {
		$id = $row['idFlightLeg'];
		$flightNumber = $row['flightNumber'];
		$departureAirportCode = $row['departureAirportCode'];
		$arrivalAirportCode = $row['arrivalAirportCode'];
		$content = "$id - $flightNumber - $departureAirportCode - $arrivalAirportCode";
		$start = $row{'createDate'};

		if($aircraftId != '') {
			$endFlightLeg = $row{'lastUpdate'};
			//$keepLooping = false;
		} else {
			// for db, we need to get the createDate of the next flight leg in order to get the end date
			$nextRow = mysqli_fetch_array($result);
			if($nextRow) {
				$endFlightLeg = $nextRow{'createDate'};
			} else {
				// we have reached the last flight leg
				// there are two cases: the
				$query = "SELECT createDate FROM SYS_flight WHERE idFlightLeg = $id+1";
				$result = mysqli_query($dbConnection, $query);
				if($result != null && mysqli_num_rows($result) > 0) {
					$row = mysqli_fetch_array($result);
					$endFlightLeg = $row['createDate'];
				} else {
					// it is the very last flight leg of the database
					$endFlightLeg = $maxDateTime;
				}

				$keepLooping = false;
			}
		}

		$flightLegName = $row{'flightLeg'};
		$duration = dateDifference($start, $endFlightLeg, '%h Hours %i Minute %s Seconds');
		$title = "$id - $flightLegName - $flightNumber - $departureAirportCode - $arrivalAirportCode / $start --> $endFlightLeg / $duration";
			
		if(strpos($flightLegName, 'CL') === 0) {
			$subgroup = 1;
			$class = 'closed';
		} else {
			$subgroup = 0;
			$class = 'open';
		}
			
		if($aircraftId != '') {
			$status = getFlightStatus($db, $id, $platform);
		} else {
			$status = 0;
		}

		if($status == 0) {
			$class = 'statusOK';
		} else if($status == 1) {
			$class = 'statusWarning';
		} else if($status > 1) {
			$class = 'statusAlert';
		}

			
			
		$dataItems[$i++] = "{className: '$class', id: 'FLI/$id', group: '0', subgroup:'$subgroup', content: '$content', title: '$title',
				start: '$start', end: '$endFlightLeg', $itemStyle}";

		if($nextRow) {
			$row = $nextRow;
		} else {
			$row = mysqli_fetch_array($result);
		}

		if(!$row) {
			$keepLooping = false;
		}
	}
}

$query = "SELECT * FROM SYS_flightPhase $whereCondition ORDER BY startTime";
$result = mysqli_query($dbConnection, $query);

if($result) { // not every dump has the sys_flightphase table
	while ($row = mysqli_fetch_array($result)) {
		$id = $row{'idFlightPhase'};
		$content = "$id - ".getFlightPhaseDesc($id);
		$start = $row{'startTime'};
		$end = $row{'endTime'};
		$subgroupOrder = getFlightPhaseOrder($id);
		$dataItems[$i] = "{group: '1', subgroup:'$subgroupOrder', subgroupOrder:$subgroupOrder,
				content: '$content', title: '$content', start: '$start', end: '$end', $itemStyle}";
		$i++;
	}
}

//impacted services code ends 31/07/2017
$result = mysqli_query($dbConnection, "SELECT a.idFailure, a.failureCode, a.accusedHostName, a.correlationDate, a.lastUpdate, a.monitorState, b.failureDesc ,b.failureImpact,c.name,c.description
							FROM BIT_serviceFailure a LEFT JOIN $mainDB.sys_serviceFailureInfo b ON (a.failureCode = b.failureCode) LEFT JOIN $mainDB.sys_services c ON (a.idService = c.idService) 
							$whereCondition AND accusedHostName = '$unit'");

							if($result){
								while ($row = mysqli_fetch_array($result)) {
									$id = $row{'idFailure'};
									$failureCode = $row{'failureCode'};
									$failureDesc = formatStringForTimeLine($row['failureDesc']);
									$serviceName = $row['name'];
									$monitorState = getMonitorStateDesc($row['monitorState']);
									$hostname = $row{'accusedHostName'};
									$start = $row{'correlationDate'};
									$img = "<img src=\"../img/failure.png\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
									$content = "$img $serviceName - $failureCode - $failureDesc - $monitorState";

									if($row['monitorState'] == 1) { // inactive
										$end = $row{'lastUpdate'};
										$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
										$title = formatStringForTimeLine("$serviceName - $failureCode - $failureDesc / Start: $start / End: $end / Duration: $duration / $monitorState");
											
										$class = 'active';
											
									} else { // Still active
										$end = $endFlightLeg;
										$title = formatStringForTimeLine("$serviceName - $failureCode - $failureDesc / Start: $start / $monitorState");
											
										$class = 'criticalActive';
											
									}

									$dataItems[$i++] = "{className: '$class', id: 'FAIS/$id', content: '$content', title: '$title',
						start: '$start', end: '$end', $itemStyle, 
						group: '3', subgroup:'$failureCode'}";
								}
							}
							//impacted services code ends

							/*
							 $query = "SELECT a.idFailure, a.failureCode, a.accusedHostName, a.correlationDate, a.lastUpdate, a.legFailureCount, a.monitorState, b.failureDesc
							 FROM BIT_failure a , $mainDB.sys_failureinfo b
							 $whereCondition
							 AND a.failureCode = b.failureCode
							 AND accusedHostName = '$unit'";
							 */
							$query = "SELECT a.idFailure, a.failureCode, a.accusedHostName, a.correlationDate, a.lastUpdate, a.legFailureCount, a.monitorState, b.failureDesc
				FROM BIT_failure a LEFT JOIN $mainDB.sys_failureinfo b ON (a.failureCode = b.failureCode)
				$whereCondition
				AND accusedHostName = '$unit'";


				$criticalFailures = getCriticalFailures();
				$result = mysqli_query($dbConnection, $query);

				if($result){
					while ($row = mysqli_fetch_array($result)) {
						$id = $row{'idFailure'};
						$failureCode = $row{'failureCode'};
						$failureDesc = formatStringForTimeLine($row['failureDesc']);
						$legFailureCount = $row['legFailureCount'];
						$monitorState = getMonitorStateDesc($row['monitorState']);
						$hostname = $row{'accusedHostName'};
						$start = $row{'correlationDate'};
						$img = "<img src=\"../img/failure.png\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
						$content = "$img $legFailureCount x $failureCode - $failureDesc - $monitorState";

						if($row['monitorState'] == 1) { // inactive
							$end = $row{'lastUpdate'};
							$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
							$title = formatStringForTimeLine("$failureCode - $failureDesc / Start: $start / End: $end / Duration: $duration / $monitorState");
							if(in_array($failureCode, $criticalFailures)) {
								$class = 'critical';
							} else {
								$class = '';
							}
						} else { // Still active
							$end = $endFlightLeg;
							$title = formatStringForTimeLine("$failureCode - $failureDesc / Start: $start / $monitorState");
							if(in_array($failureCode, $criticalFailures)) {
								$class = 'criticalActive';
							} else {
								$class = 'active';
							}
						}
							
						$dataItems[$i++] = "{className: '$class', id: 'FAI/$id', content: '$content', title: '$title',
					start: '$start', end: '$end', $itemStyle, 
					group: '2', subgroup:'$failureCode'}";
					}
				}

				$criticalFaults = getCriticalFaults();
				/*
				 $result = mysqli_query($dbConnection, "SELECT a.idFault, a.faultCode, a.hostName, a.detectionTime, a.clearingTime, a.monitorState, a.param1, a.param2, a.param3, a.param4, b.faultDesc
				 FROM BIT_fault a, $mainDB.sys_faultinfo b
				 $whereCondition AND a.faultCode = b.faultCode AND hostName = '$unit'");
				 */

				$result = mysqli_query($dbConnection, "SELECT a.idFault, a.faultCode, a.hostName, a.detectionTime, a.clearingTime, a.monitorState, a.param1, a.param2, a.param3, a.param4, b.faultDesc
							FROM BIT_fault a LEFT JOIN $mainDB.sys_faultinfo b ON (a.faultCode = b.faultCode) 
							$whereCondition AND hostName = '$unit'");


							if($result){
								while ($row = mysqli_fetch_array($result)) {
									$id = $row{'idFault'};
									$faultCode = $row{'faultCode'};
									$failureDesc = $row{'faultDesc'};
									$hostname = $row{'hostName'};
									$start = $row{'detectionTime'};
									$monitorState = getMonitorStateDesc($row['monitorState']);
									$param1 = $row['param1'];
									$param2 = $row['param2'];
									$param3 = $row['param3'];
									$param4 = $row['param4'];
									$img = "<img src=\"../img/fault.png\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
									$content =  "$img $faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4";

									if($row['monitorState'] == 1) { // inactive
										$end = $row{'clearingTime'};
										$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
										$title = formatStringForTimeLine("$faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4 / Start: $start / End: $end / Duration: $duration / $monitorState");
										if(in_array($faultCode, $criticalFaults)) {
											$class = 'critical';
										} else {
											$class = '';
										}
									} else { // Still active
										$end = $endFlightLeg;
										$title = formatStringForTimeLine("$faultCode - $failureDesc / Start: $start / $monitorState");
										if(in_array($faultCode, $criticalFaults)) {
											$class = 'criticalActive';
										} else {
											$class = 'active';
										}

										$dataItems[$i] = "{className: '$class', id: '$hostname/$id', content: '$content', title: '$title',
					start: '$start', $itemStyle, 
					group: '4', subgroup:'$faultCode'}";
									}

									$dataItems[$i++] = "{className: '$class', id: '$hostname/$id', content: '$content', title: '$title',
					start: '$start', end: '$end', $itemStyle, 
					group: '4', subgroup:'$faultCode'}";
								}
							}

							$result = mysqli_query($dbConnection, "SELECT * FROM BIT_events $whereCondition AND eventData = '$unit'");
							if($result) { // not every dump has the BIT_events table
								while ($row = mysqli_fetch_array($result)) {
									$imgSrc =false;
									$id = $row{'idEvent'};
									$failureDesc = $row{'eventName'};
									$eventInfo2 = $row['eventInfo'];
									$hostname = $row{'eventData'};
									$start = $row{'lastUpdate'};
									$eventInfo = str_replace(' ', '', $row['eventInfo']);
									if(strpos($failureDesc, 'Commanded') === 0) {
										if($eventInfo == 'SWINSTALLRESET'){
											$imgSrc = '../img/swInstallReset.png';
										}
										elseif($eventInfo == 'POWERBUTTONRESET'){
											$imgSrc = '../img/powerBtnReset.png';
										}
										elseif($eventInfo == 'CREWRESET'){
											$imgSrc = '../img/crewReset.png';
										}
										else{
											$imgSrc = '../img/commandedReset.png';
										}
										//	$img = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
										$group = 5;
									} else {
										if($eventInfo == 'UNKNOWNRESETREASON'){
											$imgSrc = '../img/unknownReset.png';
										}
										elseif($eventInfo == 'SYSTEMREBOOT'){
											$imgSrc = '../img/systemReboot.png';
										}
										elseif($eventInfo == 'SKCOLDRESET'){
											$imgSrc = '../img/skColdReset.png';
										}
										elseif($eventInfo == 'POWERCOLDRESET'){
											$imgSrc = '../img/powerColdReset.png';
										}
										elseif($eventInfo == 'KERNELPANICRESET'){
											$imgSrc = '../img/kernelPanicReset.png';
										}
										elseif($eventInfo == 'GLIBCRESET'){
											$imgSrc = '../img/glibcReset.png';
										}
										elseif($eventInfo == 'FSCHECKRESET'){
											$imgSrc = '../img/fsCheckReset.png';
										}
										elseif($eventInfo == 'DUCATIRESET'){
											$imgSrc = '../img/ducatiReset.png';
										}
										elseif($eventInfo == 'ADBREBOOTRESET'){
											$imgSrc = '../img/adbRebootReset.png';
										}
										else{
											$imgSrc = '../img/uncommandedReset.png';

										}
										//	$img = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
										$group = 6;
									}
									if($imgSrc != false){
										$content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
										// $content = "<img src='$imgSrc' style=\"vertical-align:top; width: 12px; height: 12px;\">";
									}
									else{
										$imgSrc = '../img/defaultResetReason.png';
										$content = "<img src=\"$imgSrc\" style=\"vertical-align:top; width: 16px; height: 16px;\">";
										// $content = "<img src='$imgSrc' style=\"vertical-align:top; width: 12px; height: 12px;\">";
									}
									//$content = $img;
									$title = "$failureDesc - $eventInfo2 / Time: $start";
									$dataItems[$i] = "{id: 'EVT/$id', content: '$content', title: '$title',
					start: '$start', $itemStyleNew, 
					group: '$group', subgroup:'$failureDesc'}";
									$i++;
								}
							}

							$result = mysqli_query($dbConnection, "SELECT * FROM BIT_extAppEvent $whereCondition AND hostName = '$unit'");
							if($result) { // not every dump has the BIT_extAppEvent table
								while ($row = mysqli_fetch_array($result)) {
									//var_dump($row);
									$id = $row{'idExtAppEvent'};
									$faultCode = $row{'faultCode'};
									$hostname = $row{'hostName'};
									$start = $row{'detectionTime'};
									$img = "<img src=\"../img/extappevent.png\" style=\"vertical-align:top; width: 12px; height: 12px;\">";
									$content =  getExtAppEventDesc($faultCode);
									$title = "$faultCode - ".getExtAppEventDesc($faultCode)." / Time: $start";
									$dataItems[$i] = "{id: 'EXT/$id', content: '$img $content', title: '$title',
					start: '$start', $itemStyle, 
					group: '7', subgroup:'$faultCode'}";
									$i++;
								}
							}
	$flightAnalysisUrl="FlightAnalysis.php?aircraftId=$aircraftId&flightLegs=$flightLegs&mainmenu=$mainMenu";
	error_log('Url..'.$flightAnalysisUrl);
?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->

<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet"
	type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />

<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<link rel="stylesheet"
	href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link href="../css/vis.css" rel="stylesheet">
<script src="../js/vis.min.js"></script>
<script src="../js/angular-animate.js"></script>

</head>
<style type="text/css">
.dropdown-menu {
	min-width: 103px;
}

.contactBox {
	background-color: #FCFCFC;
	border: 1px solid #E8E8E8;
	padding: 10px;
}

.header {
	font-size: 13px;
	font-weight: 400;
	color: #434a54;
}

.modal-open {
	padding-right: 0px !important;
}

.modal-content {
	border-width: 0px !important;
	border-radius: 24px;
}
.uncommandedli b,.commandedli b {
	font-size: 12px;
}

.mailList {
	margin-left: -40px;
}

.commandedul li,.uncommandedul li {
	font-size: 12px;
}

.rebootImg {
	margin-top: -2px !important;
}

li.lli {
	font-size: 12px;
}

.lli img {
	width: 12px;
	height: 12px;
	margin-right: 10px;
	margin-top: -3px;
}

ul.commadedul,ul.uncommadedul {
	margin-left: -45px;
}

.mainBlock {
	height: 135px;
	background-color: #FCFCFC;
	border: 1px solid #E8E8E8;
	padding: 5px;
	margin-bottom: 15px;
	border-radius: 10px;
}

.mailList {
	list-style-type: none;
}

.listPadding {
	padding: 5px;
}
</style>
<body>
	<!-- START PAGE CONTAINER -->
	<div class="page-container">

		<!-- START PAGE SIDEBAR -->
	<?php include("SideNavBar.php"); ?>
		<!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div class="page-content">

			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span> </a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span> </a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li>
					<?php 
						if($mainMenu =='AircraftTimeline'){
							echo '<a href="AircraftTimeline.php?aircraftVisited=true">';
							echo $mainMenu;
							echo ' </a>';
						}else if($mainMenu =='LOPA'){
							echo '<a href="lopa.php?lopaVisited=true">';
							echo $mainMenu;
							echo ' </a>';
						}else if($mainMenu =='FlightScore'){
							echo '<a href="FlightScore.php?flightScoreVisited=true">';
							echo $mainMenu;
							echo ' </a>';
						}else if($mainMenu =='DownloadOffload'){
							echo '<a href="downloadOffload.php?downloadOffloadVisited=true">';
							echo $mainMenu;
							echo ' </a>';
						}else if($mainMenu =='MaintenanceActivities'){
							echo '<a href="MaintenanceActivities.php?maintenanceActivitiesVisited=true">';
							echo $mainMenu;
							echo ' </a>';
						}
					?>
				</li>
				<li><a href="<?php echo $flightAnalysisUrl;?>">FlightAnalysis</a></li>
				<li class="active">HostnameTimeline</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>
					<?php 
						echo '<a href="';
						echo $flightAnalysisUrl;
						echo '" style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Flight Analysis"></span></a>&nbsp;HostName Timeline';							
						echo ' </a>';
					?>
				</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="panel-group accordion">
									<div class="panel panel-info" style="box-shadow: 0px 1px 1px 0px rgba(0, 0, 0, 0.2);border-top-color: #1e293d;border-radius: 4px;margin-bottom: 4px;">
										<div class="panel-heading" style="padding: 0px; height: 30px;" id="rebootHeading">
											<h4 class="panel-title"
												style="padding: 0px !important; font-size: 13px;">
												<a href="#accOneColTwof" style="padding: 0px !important;"> <i
													class="glyphicon glyphicon-info-sign" aria-hidden="true"></i>&nbsp;&nbsp;
													Reboot Information </a>
											</h4>
										</div>
										<div class="panel-body" id="accOneColTwof"
											style="border: 1px solid #E8E8E8;">
											<div class="col-md-12">
												<ul class="mailList">
													<li class="commandedli"><b>Commanded Reboot</b>(<img
														src="../img/commandedReset.png" class="rebootImg"
														style="width: 16px; height: 16px">)
														<ul style="list-style-type: none" class="commadedul">
															<li class="col-md-3 lli listPadding"><img
																src="../img/swInstallReset.png"
																style="width: 16px; height: 16px">SW INSTALL RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/powerBtnReset.png"
																style="width: 16px; height: 16px">POWER BUTTON RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/crewReset.png" style="width: 16px; height: 16px">CREW
																RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/defaultResetReason.png"
																style="width: 16px; height: 16px">DEFAULT RESET</li>
														</ul>
													</li>
												</ul>
												<br /> <br />
												<ul class="mailList">
													<li class="uncommandedli"><b>Uncommanded Reboot</b>(<img
														src="../img/uncommandedReset.png" class="rebootImg"
														style="width: 16px; height: 16px">)
														<ul style="list-style-type: none" class="uncommadedul">
															<li class="col-md-3 lli listPadding"><img
																src="../img/systemReboot.png" style="width: 16px; height: 16px">SYSTEM
																REBOOT</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/skColdReset.png" style="width: 16px; height: 16px">SK
																COLD RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/powerColdReset.png"
																style="width: 16px; height: 16px">POWER COLD RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/glibcReset.png" style="width: 16px; height: 16px">GLIBC
																RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/ducatiReset.png" style="width: 16px; height: 16px">DUCATI
																RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/fsCheckReset.png" style="width: 16px; height: 16px">FS
																CHECK RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/kernelPanicReset.png"
																style="width: 16px; height: 16px">KERNEL PANIC RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/adbRebootReset.png"
																style="width: 16px; height: 16px">ADB REBOOT RESET</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/unknownReset.png" style="width: 16px; height: 16px">UNKNOWN
																RESET REASON</li>
															<li class="col-md-3 lli listPadding"><img
																src="../img/defaultResetReason.png"
																style="width: 16px; height: 16px">DEFAULT RESET</li>
														</ul>
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>
								<div id="visualization"></div>								
							</div>
						</div>
					</div>
				</div>
				<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
				  <div class="modal-dialog" role="document">
					<div class="modal-content" style="border-radius: 9px;margin-top: 83px;">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">Modal title</h4>
					  </div>
					  <div class="modal-body">
						<div style="text-align: center; padding: 10px;font-style: normal !important;" id="legend">
						</div>
					  </div>
					  
					</div>
				  </div>
				</div>
			</div>
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->


	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->

	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>

</body>
<script>
	angular.module('myApp', ['ngAnimate']);
	angular.module('myApp').controller('Ctrl', function ($scope) {
		$scope.oneAtATime = true;
	});
	  // DOM element where the Timeline will be attached
	  var container = document.getElementById('visualization');
	
	  var groups = new vis.DataSet([
	  	{ id: 0, content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
	  	{ id: 1, content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},
	  	{ id: 2, content: '<i class="fa fa-exclamation-circle fa-fw" aria-hidden="true"></i><br><strong>Failures</strong>', style: 'font-weight: bold; text-align: center'},
		{ id: 3, content: '<i class="fa fa-exclamation-circle fa-fw" aria-hidden="true"></i><br><strong>Impcated Services</strong>', style: 'font-weight: bold; text-align: center'},
	  	{ id: 4, content: '<i class="fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i><br><strong>Faults</strong>', style: 'font-weight: bold; text-align: center'},
	  	{ id: 5, content: '<i class="fa fa-power-off fa-fw" aria-hidden="true"></i><br><strong>Commanded Resets</strong>', style: 'font-weight: bold; text-align: center'},
	  	{ id: 6, content: '<i class="fa fa-power-off fa-fw" aria-hidden="true"></i><br><strong>Uncommanded Resets</strong>', style: 'font-weight: bold; text-align: center'},
	  	{ id: 7, content: '<i class="fa fa-codepen fa-fw" aria-hidden="true"></i><br><strong>Applications Events</strong>', style: 'font-weight: bold; text-align: center'}
	  	]);
	  
	  // Create a DataSet (allows two way data-binding)
	  var items = new vis.DataSet([
	  	<?php
	  	foreach ($dataItems as $dataItem) {
	  		echo $dataItem;
	  		echo ",";
	  	}
	  	?>
	  	]);
	
	  // Configuration for the Timeline
	  var options = {
	  	orientation: 'both',
	  	clickToUse: true,
	  	stack: false
	  };
	
	  // Create a Timeline
	  var timeline = new vis.Timeline(container, items, groups, options);
	  timeline.on('select', function (properties) {
	  	var event = properties.items[0];
	  	
	  	$.ajax({
	  		url: '../ajax/getFailureDetails.php',
	  		type: 'post',
	  		data: {'event': event, 'db': '<?php echo $db; ?>'},
	  		success: function(data, status) {
	  			if(data == '') {
	  				data = "<p style=\"font-style: normal;\">Click on a <u>Failure</u> to get details</p>";
	  			}
	  			//document.getElementById('legend').innerHTML = data;
				$('#myModal').find('.modal-title').text('Failure Description')
				$('#myModal').find('.modal-body #legend').html(data)
				$('#myModal').modal('show')
	  		},
	  		error: function(xhr, desc, err) {
	  			console.log(xhr);
	  			console.log("Details: " + desc + "\nError:" + err);
	  		}
		}); // end ajax call
	  });
</script>

</html>





