<!DOCTYPE html>
<?php
// session_start();
session_start ();

$menu = 'lopa';
require_once ("checkEngineeringPermission.php");
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
// require_once "../common/checkPermission.php";
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';

$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

$query = "SELECT a.id, a.acronym FROM airlines a WHERE id IN ($airlineIds) ORDER BY a.acronym";

$result = mysqli_query ( $dbConnection, $query );
$airlines = array ();
if ($result) {
	while ( $row = mysqli_fetch_array ( $result ) ) {
		$airlines [] = $row ['acronym'];
	}
}

$query = "SELECT a.id FROM airlines a WHERE id IN ($airlineIdList) ORDER BY a.acronym limit 1";

$result = mysqli_query ( $dbConnection, $query );
if ($result) {
	while ( $row = mysqli_fetch_array ( $result ) ) {
		$airlineId = $row ['id'];
	}
}

// Existing Flight Phases

$flightPhases = array (
		"1:Pre-flight ground",
		"2:Taxi out",
		"3:Take off",
		"4:Climb",
		"5:Cruise",
		"6:Descent",
		"7:Landed",
		"8:Taxi in",
		"9:Post-flight" 
);

// Get all Fault Codes.
$query = "SELECT a.idFaultInfo, a.faultCode, a.faultDesc FROM sys_faultinfo a";
$result = mysqli_query ( $dbConnection, $query );
$faultInfos = array ();
$faultInfosForAutoSuggest = array ();
if ($result) {
	while ( $row = mysqli_fetch_array ( $result ) ) {
		array_push ( $faultInfos, $row );
		array_push ( $faultInfosForAutoSuggest, $row ['faultCode'] . ':' . $row ['faultDesc'] );
	}
}

// Get all Failure Codes.
$query = "SELECT a.idFailureInfo, a.failureCode, a.failureDesc FROM sys_failureinfo a";
$result = mysqli_query ( $dbConnection, $query );
$failureInfos = array ();
$failureInfosForAutoSuggest = array ();
if ($result) {
	while ( $row = mysqli_fetch_array ( $result ) ) {
		array_push ( $failureInfos, $row );
		array_push ( $failureInfosForAutoSuggest, $row ['failureCode'] . ':' . $row ['failureDesc'] );
	}
}

// Get all Failure Codes.
$query = "SELECT a.idFailureInfo, a.failureCode, a.failureDesc FROM sys_servicefailureinfo a";
$result = mysqli_query ( $dbConnection, $query );
$impactedServicesInfos = array ();
$impactedServicesInfosForAutoSuggest = array ();
if ($result) {
	while ( $row = mysqli_fetch_array ( $result ) ) {
		array_push ( $impactedServicesInfos, $row );
		array_push ( $impactedServicesInfosForAutoSuggest, $row ['failureCode'] . ':' . $row ['failureDesc'] );
	}
}

$lopaVisited = $_REQUEST ['lopaVisited'];

if($lopaVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airline']);
}else{
	$_SESSION['airline'] = '';
	$_SESSION['platform'] = '';
	$_SESSION['configType'] = '';
	$_SESSION['software'] = '';
	$_SESSION['tailsign'] = '';
	$_SESSION['startDate'] = '';
	$_SESSION['endDate'] = '';
	$_SESSION['faultCode'] = '';
	$_SESSION['failureCode'] = '';
	$_SESSION['ImpactedServicesCode'] = '';
	$_SESSION['flightPhases'] = '';
	$_SESSION['resetCode'] = '';
}

$aircraftId = $_REQUEST['aircraftId'];
error_log("Aircraft Id from another page: " . $aircraftId);

$airlineId_nav = 0;
$platform_nav = "";
$configuration_nav = "";
$software_nav = "";
$tailsign_nav = "";

if($aircraftId > 0) {
    $query = "select airlineId, platform, Ac_Configuration as configuration, software, tailsign from aircrafts where id=$aircraftId";
    
    $result = mysqli_query($dbConnection, $query);
    
    error_log("All Details Query: ".$query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        if ($row = mysqli_fetch_assoc($result)) {
            $airlineId_nav = $row['airlineId'];
            $platform_nav = $row['platform'];
            $configuration_nav = $row['configuration'];
            $software_nav = $row['software'];
            $tailsign_nav = $row['tailsign'];
        }
    }
}

?>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
<title>BITE Analytics</title>
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
<script src="../js/Chart.HeatMap.S.js"></script>

<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>

</head>
<style>
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}
 body.modal-open {
    overflow: hidden !important;
    position:fixed !important;
    width: 100% !important;
}
</style>
<body id="bodyDiv"  ng-controller="lopaDataController">
	<!-- START PAGE CONTAINER -->
	<div class="page-container">
		<!-- START PAGE SIDEBAR -->
        <?php include("SideNavBar.php"); ?>
        <!-- END PAGE SIDEBAR -->
		<!-- PAGE CONTENT -->
		<div id="here" class="page-content" style="height: 100% !important;">
			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span></a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SEARCH 
                  <li class="xn-search">
                  	<form role="form">
                  		<input type="text" name="search" placeholder="Search..." />
                  	</form>
                  </li>-->
				<!-- END SEARCH -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">LOPA</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title" style="padding-right: 12px;">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					LOPA
				</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#mb-info">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>				
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="row" id="ctrldiv">
									<div class="col-md-12">
									</div>
								</div>
								<div class="row">
									<div class="col-md-2 form-group">										
										<label for="selectedAirline"> Airline </label>
										<select id="selectedAirline" class="selectpicker show-tick"
											data-live-search="true" data-width="100%"
											style="max-width: 150px;"></select>
									</div>
									<div class="col-md-2 form-group">										
										<label for="selectedPlatform"> Platform </label>
										<select id="selectedPlatform" class="selectpicker show-tick"
											data-live-search="true" data-width="100%"
											style="max-width: 150px;"></select>
									</div>
									<div class="col-md-2 form-group">										
										<label for="selectedConfigType"> Config Type </label>
										<select id="selectedConfigType" class="selectpicker show-tick"
											data-live-search="true" data-width="100%"
											style="max-width: 150px;"></select>
									</div>
									<div class="col-md-2 form-group">										
										<label for="selectedTailsign"> Tailsign </label>
										<select id="selectedTailsign" class="selectpicker show-tick"
											data-live-search="true" data-width="100%"
											style="max-width: 150px;"></select>
									</div>
									<div class="col-md-2 form-group">										
										<label for="startDateTimePicker"> From </label>
										<input id="startDateTimePicker" type="text"
											name="startDateTime" size="15" class="form-control dateChange" readonly='true'>
									</div>
									<div class="col-md-2 form-group">										
										<label for="endDateTimePicker"> To </label>
										<input id="endDateTimePicker" type="text" name="startDateTime"
											size="15" class="form-control dateChange" readonly='true'>
									</div>
								</div>
								<div class="row" style="padding-left: 10px;">
								<a data-toggle="collapse" href="#advancedFilter"
									role="button" aria-expanded="false"
									aria-controls="advancedFilter"><font
									style="font-weight: bold;">Advanced Filter</font>&nbsp;<span
									class="glyphicon glyphicon-chevron-right"></span></a></div><br/>
								<div class="collapse" id="advancedFilter">
								<div class="row">
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="faultCode"> Fault Code </label>
											<select id="faultCode" class="selectpicker show-tick"
												data-live-search="true" multiple data-width="100%"
												style="max-width: 150px;" disabled=true></select>
										</div>
									</div>
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="failureCode"> Failure Code </label>
											<select id="failureCode" class="selectpicker show-tick"
												data-live-search="true" multiple data-width="100%"
												style="max-width: 150px;" disabled=true></select>
										</div>
									</div>
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="impactedServicesCode"> Impacted Services </label>
											<select id="impactedServicesCode"
												class="selectpicker show-tick" data-live-search="true"
												multiple data-width="100%" style="max-width: 150px;"
												disabled=true></select>
										</div>
									</div>
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="monitorState"> Monitor State</label>
											<select id="monitorState"
												class="selectpicker show-tick" data-live-search="true"
												multiple  title="All" data-width="100%" style="max-width: 150px;"></select>
										</div>
									</div>
																					
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="flightPhases"> Flight Phases</label>
											<select id="flightPhases" class="selectpicker show-tick"
												data-live-search="true" multiple data-width="100%"
												style="max-width: 150px;"></select>
										</div>
									</div>
									<div class="col-md-2 form-group">										
										<div class="row">
											<label for="resetsValue">Resets</label>
											<select id="resetsValue" title="All"  class="selectpicker show-tick"
												data-live-search="true" multiple data-width="100%"
												style="max-width: 150px;"></select>
										</div>
									</div></div>

								</div>
								<div class="row">
									<div class="col-md-12 text-left">
										<button id="filter" class="btn btn-primary">Filter</button>
										&nbsp;&nbsp;
										<button id="resetbtn" class="btn btn-reset">Reset</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div id="errorInfo" class="container-fluid text-center">
									<label class="noData-label">No data available for the selected duration or selected
										filters</label>
								</div>
								<div id="btnInfo" class="container-fluid">
									<div class="text-center">
										<div class="container-fluid">
											<div class="btn-group">
												<button id="resetslbl" type="button" class="btn btn-primary"
													style="border-radius: 0px !important;">Resets</button>
												<!-- <button id="activeFailureslbl" type="button"
													class="btn btn-primary">Active Failures</button> -->
												<button id="allFailureslbl" type="button"
													class="btn btn-primary">All Failures</button>
												<button id="allFaultslbl" type="button"
													class="btn btn-primary">All Faults</button>
												<button id="impactedServiceslbl" type="button"
													class="btn btn-primary">Impacted Services</button>
												<button id="eventslbl" type="button" class="btn btn-primary">Events</button>
											</div>
										</div>
									</div>
									<br />
									<div align="center" id="seatResetsChartLegend">
										<div class="btn-group" data-toggle="buttons"
											style="padding-left: 80px;">
											<label class="btn active"> <input type="radio" name='view'
												id="lopaRadioBtn" checked onchange="filterCategory('lopa');"><i
												class="fa fa-circle-o fa-2x"></i><i
												class="fa fa-dot-circle-o fa-2x"></i> <span
												style="font-family: 'Open Sans', sans-serif; font-size: 12px;">
													Threshold View</span>
											</label> <label class="btn"> <input type="radio" name='view'
												id="heatmapRadioBtn" onchange="filterCategory('heatmap');"><i
												class="fa fa-circle-o fa-2x"></i><i
												class="fa fa-dot-circle-o fa-2x"></i><span
												style="font-family: 'Open Sans', sans-serif; font-size: 12px;">
													Heatmap View</span>
											</label>
										</div>
										<div class="btn-group pull-right" style="padding-top: 5px;">
											<button class="btn btn-primary active dropdown-toggle"
												data-toggle="dropdown" style="float: right;">
												<i class="fa fa-bars"></i> Export Data
											</button>
											<ul class="dropdown-menu" role="menu"
												style="right: 0; min-width: 118px;">
												<li><a href="#" onclick="exportLopaSeatViewData('csv')"> <img
														src="../img/icons/csv.png" width="24px"> CSV
												</a></li>
												<li><a href="#" onclick="exportLopaSeatViewData('xls')"> <img
														src="../img/icons/xls.png" width="24px"> XLS
												</a></li>
											</ul>
										</div>
									</div>
									<div id="panel">
										<div id="resetsLopaPanel">
											<div align="center" style="overflow: auto;">
												<table id="resetsLopa" align="center">
												</table>
												<div id="loadingResetsLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="resetsHeatmapPanel" align="center"
											style="overflow: auto;">
											<canvas id="resetsHeatmap" class="scrollChart" height="240"></canvas>
										</div>
										<div id="resetsPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="resetsPerHourData" align="center">
												</table>
											</div>
										</div>
										<div id="activeFailuresLopaPanel" style="display: none">
											<!--<span class="text-center">Active Failures</span>
                                       <br>-->
											<div align="center" style="overflow: auto;">
												<table id="activeFailuresLopa" align="center"></table>
												<div id="loadingActiveFailuresLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="activeFailuresHeatmapPanel">
											<div id="container" align="center" style="overflow: auto;">
												<canvas id="activeFailuresHeatmap" class="scrollChart"></canvas>
											</div>
										</div>
										<div id="activeFailuresPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="activeFailuresPerHourData" align="center">
												</table>
											</div>
										</div>
										<div id="allFailuresLopaPanel" style="display: none">
											<!--<span class="text-center">All Failures</span>
                                       <br>-->
											<div align="center" style="overflow: auto;">
												<table id="failuresLopa" align="center"></table>
												<div id="loadingFailuresLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="failuresHeatmapPanel">
											<div id="container" align="center" style="overflow: auto;">
												<canvas id="failuresHeatmap" class="scrollChart"></canvas>
											</div>
										</div>
										<div id="failuresPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="failuresPerHourData" align="center">
												</table>
											</div>
										</div>
										<div id="allFaultsLopaPanel" style="display: none">
											<!--<span class="text-center">All Faults</span>
                                       <br>-->
											<div align="center" style="overflow: auto;">
												<table id="faultsLopa" align="center"></table>
												<div id="loadingFaultsLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="faultsHeatmapPanel">
											<div id="container" align="center" style="overflow: auto;">
												<canvas id="faultsHeatmap" class="scrollChart"></canvas>
											</div>
										</div>
										<div id="faultsPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="faultsPerHourData" align="center">
												</table>
											</div>
										</div>
										<div id="impactedServicesPanel" style="display: none">
											<!--<span class="text-center">Impacted Services</span>
                                       <br>-->
											<div align="center" style="overflow: auto;">
												<table id="impactedServicesLopa" align="center"></table>
												<div id="loadingImpactedServicesLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="impactedServicesHeatmapPanel" align="center">
											<div id="container" style="overflow: auto;">
												<canvas id="impactedServicesHeatmap" class="scrollChart"></canvas>
											</div>
										</div>
										<div id="impactedServicesPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="impactedServicesPerHourData" align="center">
												</table>
											</div>
										</div>
										<div id="eventsPanel" style="display: none">
											<!--<span class="text-center">Events</span>
                                       <br>-->
											<div align="center" style="overflow: auto;">
												<table id="eventsLopa" align="center"></table>
												<div id="loadingEventsLopa">
													<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
													Loading LOPA...
												</div>
											</div>
										</div>
										<div id="eventsHeatmapPanel" align="center">
											<div id="container" style="overflow: auto;">
												<canvas id="eventsHeatmap" class="scrollChart"></canvas>
											</div>
										</div>
										<div id="eventsPerHourDataPanel">
											<div align="center" style="overflow: auto;">
												<table id="eventsPerHourData" align="center">
												</table>
											</div>
										</div>
									</div>
									<!--<div style="height: 400px;  overflow-x: scroll;">
                                 <canvas id="scrollChart" height="200" width="220"></canvas>
                                 </div>
                                 <div id="container" style=" overflow-x: scroll;">
                                 <canvas id="scrollChart" ></canvas>
                                 </div>-->
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row" id="seatModal">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<!-- Tabular View  -->
								<div class="tab-content" role="tab" data-toggle="tab">
									<div id="loadingTabData" align="center">
										<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
										Loading Data...
									</div>
									<div id="tabularData">
										<h4 id="seatHeader" class="modal-title">Seat Details</h4>
										<div class="btn-group"
											style="margin-bottom: 5px; width: 100%;">
											<button class="btn btn-primary active dropdown-toggle"
												data-toggle="dropdown" style="float: right;">
												<i class="fa fa-bars"></i> Export Data
											</button>
											<ul class="dropdown-menu" role="menu"
												style="right: 0; left: 89.5%; min-width: 103px;">
												<li><a href="#" onclick="exportOffloads('csv')"> <img
														src="../img/icons/csv.png" width="24px"> CSV
												</a></li>
												<li><a href="#" onclick="exportOffloads('xls')"> <img
														src="../img/icons/xls.png" width="24px"> XLS
												</a></li>
											</ul>
										</div>
										<br /> <br />
										<!-- Nav tabs -->
										<ul class="nav nav-tabs" role="tablist" id="myTabs">
											<li role="presentation" id="summaryPanel" class="active"><a
												href="#summaryTab" aria-controls="summaryTab" role="tab"
												data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Summary</a></li>
											<li role="presentation" id="resetsPanel"><a href="#resetsTab"
												aria-controls="resetsTab" role="tab" data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Resets</a></li>
											<!-- <li role="presentation" id="activeFailuresPanel"><a
												href="#activeFailuresTab" aria-controls="activeFailuresTab"
												role="tab" data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Active
													Failures</a></li> -->
											<li role="presentation" id="failuresPanel"><a
												href="#failuresTab" aria-controls="failuresTab" role="tab"
												data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">All
													Failures</a></li>
											<li role="presentation" id="faultsPanel"><a href="#faultsTab"
												aria-controls="faultsTab" role="tab" data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">All
													Faults</a></li>
											<li role="presentation" id="impactPanel"><a
												href="#impactServicesTab" aria-controls="impactServicesTab"
												role="tab" data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Impacted
													Services</a></li>
											<li role="presentation" id="appeventsPanel"><a
												href="#eventsTab" aria-controls="eventsTab" role="tab"
												data-toggle="tab"
												style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Events</a></li>
											<!--
                                       <li role="presentation"><a href="#maintenanceTab" aria-controls="maintenanceTab" role="tab" data-toggle="tab">Maintenance</a></li>
                                       -->
										</ul>
										<!-- Tab panes -->
										<div class="tab-content">
											<div role="tabpanel" class="tab-pane active" id="summaryTab">
												<br>
												<div class="row">
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="row">
																<div class="col-xs-12">
																	<form class="form-horizontal">
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Serial Number: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduSerialNumber"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">LRU Type: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduLruType"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">HW Part Number: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduHwPartNumber"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Revision: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduRevision"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Mod: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduMod"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Mac Address: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduMacAddress"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Last Update: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="svduLastUpdate"></span>
																				</div>
																			</div>
																		</div>
																	</form>
																</div>
															</div>
														</div>
													</div>
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="row">
																<div class="col-xs-12">
																	<form class="form-horizontal">
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Serial Number: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuSerialNumber"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">LRU Type: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuLruType"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">HW Part Number: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuHwPartNumber"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Revision: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuRevision"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Mod: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuMod"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Mac Address: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuMacAddress"></span>
																				</div>
																			</div>
																		</div>
																		<div class="form-group margin-bottom">
																			<label for="currentStatus"
																				class="col-sm-6 control-label">Last Update: </label>
																			<div class="col-sm-6">
																				<div class="form-control-static">
																					<span id="pcuLastUpdate"></span>
																				</div>
																			</div>
																		</div>
																	</form>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="resetsTab">
												<br>
												<div class="row">
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduResetsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="lastUpdate" data-sortable="true">Time</th>
																			<th data-field="eventName" data-sortable="true">Type</th>
																			<th data-field="eventInfo" data-sortable="true">Reset
																				Reason</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuResetsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="lastUpdate" data-sortable="true">Time</th>
																			<th data-field="eventName" data-sortable="true">Type</th>
																			<th data-field="eventInfo" data-sortable="true">Reset
																				Reason</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="activeFailuresTab">
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduActiveFailuresTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="failureCode" data-sortable="true">Code</th>
																			<th data-field="failureDesc" data-sortable="true">Description</th>
																			<th data-field="legFailureCount" data-sortable="true">Leg
																				Count</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuActiveFailuresTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="failureCode" data-sortable="true">Code</th>
																			<th data-field="failureDesc" data-sortable="true">Description</th>
																			<th data-field="legFailureCount" data-sortable="true">Leg
																				Count</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="failuresTab">
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduFailuresTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="failureCode" data-sortable="true">Code</th>
																			<th data-field="failureDesc" data-sortable="true">Description</th>
																			<th data-field="legFailureCount" data-sortable="true">Leg
																				Count</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuFailuresTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="failureCode" data-sortable="true">Code</th>
																			<th data-field="failureDesc" data-sortable="true">Description</th>
																			<th data-field="legFailureCount" data-sortable="true">Leg
																				Count</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="faultsTab">
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduFaultsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="faultCode" data-sortable="true">Code</th>
																			<th data-field="faultDesc" data-sortable="true">Description</th>
																			<th data-field="reportingHostname"
																				data-sortable="true">Reporting Host</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="detectionTime" data-sortable="true">Detection
																				Time</th>
																			<th data-field="clearingTime" data-sortable="true">Clearing
																				Time</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuFaultsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="faultCode" data-sortable="true">Code</th>
																			<th data-field="faultDesc" data-sortable="true">Description</th>
																			<th data-field="reportingHostname"
																				data-sortable="true">Reporting Host</th>
																			<th data-field="monitorState" data-sortable="true">State</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="detectionTime" data-sortable="true">Detection
																				Time</th>
																			<th data-field="clearingTime" data-sortable="true">Clearing
																				Time</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="impactServicesTab">
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduImpactedServicesTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="failureCode" data-sortable="true">Failure
																				Code</th>
																			<th data-field="failureDesc" data-sortable="true">Failure
																				Description</th>
																			<th data-field="failureImpact" data-sortable="true">Failure
																				Impact</th>
																			<th data-field="name" data-sortable="true">Service
																				Name</th>
																			<th data-field="description" data-sortable="true">Service
																				Description</th>
																			<th data-field="monitorState" data-sortable="true">Monitor
																				Impact</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuImpactedServicesTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="correlationDate" data-sortable="true">Correlation
																				Date</th>
																			<th data-field="idFlightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="failureCode" data-sortable="true">Failure
																				Code</th>
																			<th data-field="failureDesc" data-sortable="true">Failure
																				Description</th>
																			<th data-field="failureImpact" data-sortable="true">Failure
																				Impact</th>
																			<th data-field="name" data-sortable="true">Service
																				Name</th>
																			<th data-field="description" data-sortable="true">Service
																				Description</th>
																			<th data-field="monitorState" data-sortable="true">Monitor
																				Impact</th>
																			<th data-field="lastUpdate" data-sortable="true">Last
																				Update</th>
																			<th data-field="duration" data-sortable="true">Duration</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="eventsTab">
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="svduEventsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="detectionTime" data-sortable="true">Detection
																				Time</th>
																			<th data-field="flightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="reportingHostName"
																				data-sortable="true">Reporting Hostname</th>
																			<th data-field="faultCode" data-sortable="true">Fault
																				Code</th>
																			<th data-field="faultDesc" data-sortable="true">Fault
																				Description</th>
																			<th data-field="param1" data-sortable="true">Param 1</th>
																			<th data-field="param2" data-sortable="true">Param 2</th>
																			<th data-field="param3" data-sortable="true">Param 3</th>
																			<th data-field="param4" data-sortable="true">Param 4</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
												<br>
												<div class="row">
													<div class="col-md-12">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">
															<div class="table-responsive">
																<table id="pcuEventsTable"
																	data-classes="table table-no-bordered table-hover"
																	data-striped="true">
																	<thead>
																		<tr>
																			<th data-field="idFlightLeg" data-sortable="true"
																				data-formatter="formatFlightLeg">Flight Leg</th>
																			<th data-field="detectionTime" data-sortable="true">Detection
																				Time</th>
																			<th data-field="flightPhase" data-sortable="true">Flight
																				Phase</th>
																			<th data-field="reportingHostName"
																				data-sortable="true">Reporting Hostname</th>
																			<th data-field="faultCode" data-sortable="true">Fault
																				Code</th>
																			<th data-field="faultDesc" data-sortable="true">Fault
																				Description</th>
																			<th data-field="param1" data-sortable="true">Param 1</th>
																			<th data-field="param2" data-sortable="true">Param 2</th>
																			<th data-field="param3" data-sortable="true">Param 3</th>
																			<th data-field="param4" data-sortable="true">Param 4</th>
																		</tr>
																	</thead>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div role="tabpanel" class="tab-pane" id="maintenanceTab">
												<br>
												<div class="row">
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i>
															SVDU
														</h4>
														<div class="chart-panel">-</div>
													</div>
													<div class="col-md-6">
														<h4>
															&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i>
															Handset
														</h4>
														<div class="chart-panel">-</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->
	
	<div class="modal" data-sound="alert" id="mb-info">
		<div class="modal-dialog modal-md" style="background-color: #f5f5f5;margin-top: 100px;border-radius: 6px;width: 700px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">LOPA Info</h4>
				</div> 
				<div class="modal-body">
					<div>
						<h5>
							<b>Threshold Info</b>
						</h5>
					</div>
					<table class="table table-bordered table-hover">
						<thead>
							<tr class="active">
								<th>ColorCode</th>
								<th>Reset</th>
								<th>Failures</th>
								<th>Faults</th>
								<th>ImpactedServices</th>
								<th>Events</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<th>Black</th>
								<th>Count >= 10</th>
								<th>Count >= 20</th>
								<th>Count >= 10</th>
								<th>Count >= 20</th>
								<th>Count >= 20</th>
							</tr>
							<tr>
								<th>Red</th>
								<th>Count >= 5</th>
								<th>Count >= 10</th>
								<th>Count >= 5</th>
								<th>Count >= 10</th>
								<th>Count >= 10</th>
							</tr>
							<tr>
								<th>Yellow</th>
								<th>Count >= 3</th>
								<th>Count >= 5</th>
								<th>Count >= 3</th>
								<th>Count >= 5</th>
								<th>Count >= 5</th>
							</tr>
							<tr>
								<th>Blue</th>
								<th>Count < 3</th>
								<th>Count < 5</th>
								<th>Count < 3</th>
								<th>Count < 5</th>
								<th>Count < 5</th>
							</tr>
						</tbody>
					</table>
					<br />
					<div>
						<h5>
							<b>Heatmap Info</b>
						</h5>
					</div>
					<div align="center">
						<img src="../img/gradientInfo.png">
					</div>
					<br />    				
				</div>
			</div>
		</div>
	</div>
	<!-- END MESSAGE BOX-->
	<!-- START SCRIPTS -->
	<!-- START PLUGINS 
         <script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
         <script type="text/javascript" src="../js/plugins/jquery/jquery-ui.min.js"></script>
         <script type="text/javascript" src="../js/plugins/bootstrap/bootstrap.min.js"></script>  -->
	<!-- END PLUGINS -->
	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
	
	<!-- START THIS PAGE PLUGINS-->
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-europe-mill-en.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-us-aea-en.js'></script>
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript"
		src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<!-- END THIS PAGE PLUGINS-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
	<script type="text/javascript">
    var airlineId_nav = "<?php echo "$airlineId_nav";?>";
    var platform_nav = "<?php echo "$platform_nav";?>";
    var configuration_nav = "<?php echo "$configuration_nav";?>";
    var software_nav = "<?php echo "$software_nav";?>";
    var tailsign_nav = "<?php echo "$tailsign_nav";?>";
    
         $('#btnInfo').hide();
            $('#seatModal').hide();
            $('.nav-sidebar li').removeClass('active');
            $("#homeSideBarLOPA").addClass("active");
            $('#errorInfo').hide();
                    var app = angular.module("myApp", []);
         
                    app.controller('lopaDataController', function($scope, $http, $window) {
                        init();
                        var firstTime = true;
                    	var airlineId_nav = $window.airlineId_nav;
                    	var platform_nav = $window.platform_nav;
                    	var configuration_nav = $window.configuration_nav;
                    	var software_nav = $window.software_nav;
                    	var tailsign_nav = $window.tailsign_nav;
         
                        function init() {
                            getAirlines();
                        }
         
                        function getAirlines() {
                        	var data = $.param({
                                action: 'GET_AIRLINES',
                                airIds: "<?php echo $airlineIds ?>"
                            });
         
                            var config = {
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                                }
                            };
         
                            $http.post('../engineering/lopaData.php', data, config)
                                .success(function(data, status, headers, config) {
                    			//$("#airline").append('<option value="">All</option>');
                    			$('#selectedAirline').empty();
                             	var airlineList = JSON.parse(JSON.stringify(data));
                    			for (var i = 0; i < airlineList.length; i++) {
                    				var al = airlineList[i];
                    				$("#selectedAirline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
                    			}
                    			
                    			if(airlineId_nav) {
                    				$("#selectedAirline").val(airlineId_nav);
                    			}
                    			
                    			$('#selectedAirline').selectpicker('refresh');
                    			
                    			if(<?php echo $_REQUEST ['lopaVisited'];?>){
                					$('#selectedAirline').val(<?php echo $_SESSION['airline'];?>);
                					$('#selectedAirline').selectpicker('refresh');
                					$scope.loadPlatforms();
                				 }else{
                					 $scope.selectedAirline = airlineList[0];                                

                					 if(airlineId_nav) {
                    					 $("#selectedAirline").val(airlineId_nav);
                    					 $scope.selectedAirline = airlineId_nav;
                					 }

                					 $('#selectedAirline').selectpicker('refresh');
                                     $scope.loadPlatforms($scope.selectedAirline);
                				 }	
                    	       
                    	    });
         
                        }

                        $scope.loadPlatforms = function() {
                        	var selairlineId = $("#selectedAirline").val();
                            var airlineId = selairlineId;
         
                            var data = $.param({
                                airlineId: airlineId,
                                action: 'GET_PLATFORMS'
                            });
         
                            var config = {
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                                }
                            };
         
                            $http.post('lopaData.php', data, config)
                                .success(function(data, status, headers, config) {
                                	$('#selectedPlatform').empty();
                                    $("#selectedAirline").css("border-color", "");
                                    $("#selectedPlatform").css("border-color", "");
                                    $("#selectedConfigType").css("border-color", "");
                                    $("#error").empty();
                                    var platformList = JSON.parse(JSON.stringify(data));
                        			for (var i = 0; i < platformList.length; i++) {
                        				var al = platformList[i];
                        				$("#selectedPlatform").append('<option value=' + al.platform + '>' + al.platform + '</option>');
                        			}
                        			$('#selectedPlatform').selectpicker('refresh');
                        			
                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){
                    					$('#selectedPlatform').val('<?php echo $_SESSION['platform'];?>');
                    					$('#selectedPlatform').selectpicker('refresh');
                    					$scope.loadConfigTypes();
                    				 }else{
                    					 $scope.selectedPlatform = platformList[0];

                    					 if(platform_nav) {
                    						 $scope.selectedPlatform = platform_nav;
                        					 $("#selectedPlatform").val(platform_nav);
                        					 $('#selectedPlatform').selectpicker('refresh');
                    					 }
                    			                    					                                 
                                         $scope.loadConfigTypes($scope.selectedAirline,$scope.selectedPlatform);
                    				 }
         
                                })
                                .error(function(data, status, header, config) {});
                        };
         
                        $scope.loadConfigTypes = function() {
                        	var selairlineId = $("#selectedAirline").val();
                        	var selPlatform = $("#selectedPlatform").val();
                            var selectedPlatform = selPlatform;
                            var airlineId = selairlineId;
                            $('#selectedConfigType').empty();
                            var data = $.param({
                                airlineId: airlineId,
                                platform: selectedPlatform,
                                action: 'GET_CONFIG_TYPE'
                            });
         
                            var config = {
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                                }
                            };
         
                            $http.post('lopaData.php', data, config)
                                .success(function(data, status, headers, config) {
                                    $("#selectedPlatform").css("border-color", "");
                                    $("#error").empty();
                              
                                    var configList = JSON.parse(JSON.stringify(data));
                        			for (var i = 0; i < configList.length; i++) {
                        				var al = configList[i];
                        				var selOption = al.configType;
                        				$("#selectedConfigType").append("<option value='" + selOption + "'>" + selOption + "</option>");
                        			}
                        			$('#selectedConfigType').selectpicker('refresh');
                        			
                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){
                    					$('#selectedConfigType').val('<?php echo $_SESSION['configType'];?>');
                    					$('#selectedConfigType').selectpicker('refresh');
                    					$scope.loadTailsigns();
                    				 }else{
                    					 $scope.selectedConfigType = configList[0];

                						if(configuration_nav) {
                							$scope.selectedConfigType = configuration_nav;
                							$("#selectedConfigType").val(configuration_nav);
                							$('#selectedConfigType').selectpicker('refresh');
                						}
                    			                    					                                 
                                         $scope.loadTailsigns($scope.selectedAirline, $scope.selectedPlatform, $scope.selectedConfigType);
                    				 }
                                })
                                .error(function(data, status, header, config) {});
                        };
         
                        $scope.loadTailsigns = function() {
                        	var selPlatform = $("#selectedPlatform").val();
                            var selairlineId = $("#selectedAirline").val();
                            var selconfigType = $("#selectedConfigType").val();
                            
                            var selectedPlatform = selPlatform;
                            var airlineId = selairlineId;
                            var configType = selconfigType;
                            //$('#btnInfo').hide();
                            $('#selectedTailsign').empty();
                            $.ajax({
                                type: "GET",
                                url: "../ajax/getBiteData.php",
                                data: {
                                    'action': 'getTailsignlist',
                                    'airlineId': airlineId,
                                    'config': configType,
                                    'platform': selectedPlatform
                                },
                                success: function(data) {
                                    tailsignList = JSON.parse(data);
                        			for (var i = 0; i < tailsignList.length; i++) {
                        				var al = tailsignList[i];
                        				$("#selectedTailsign").append('<option value=' + al + '>' + al + '</option>');
                        			}

                        			if(tailsign_nav) {
                        				$("#selectedTailsign").val(tailsign_nav);
                        			}
                                                			
                        			$('#selectedTailsign').selectpicker('refresh');
                        			//$scope.selectedTailsign = tailsignList[0];                                
                                     
                                    if (firstTime) {
                                        //fromDate = new Date($("#startDateTimePicker").val());
                                        //toDate = new Date($("#endDateTimePicker").val());
                                        
                                        if(<?php echo $_REQUEST ['lopaVisited'];?>){
                        					$('#selectedTailsign').val('<?php echo $_SESSION['tailsign'];?>');
                        					$('#selectedTailsign').selectpicker('refresh');
                        					tailsign = $("#selectedTailsign").val();
                        					$('#startDateTimePicker').val('<?php echo $_SESSION['startDate'];?>');
                    						$('#endDateTimePicker').val('<?php echo $_SESSION['endDate'];?>');
                        					getFaultCodelist(airlineId, selectedPlatform, configType, tailsign);
                        					loadFrame(tailsign);
                        				 }else{
                        					 fromDate = $("#startDateTimePicker").val();
                                             toDate = $("#endDateTimePicker").val();
                                             $("#resetslbl").addClass("active");
                                             tailsign = tailsignList[0];
                                             if(tailsign_nav) {
                                            	 tailsign = tailsign_nav;
                                             }
                                             loadFrame(tailsign);
                                             firstTime = false;
                                             var tailsignA = tailsignList[0];
                                             $('#failureCode').empty();
                                             $('#faultCode').empty();
                                             $('#impactedServicesCode').empty();
                                             getFaultCodelist(airlineId, selectedPlatform, configType, tailsign);
                        				 }
                                    } else {
                                    	if(<?php echo $_REQUEST ['lopaVisited'];?>){
                                    		$('#selectedTailsign').val('<?php echo $_SESSION['aircraftId'];?>');
                                    		$('#selectedTailsign').selectpicker('refresh');
                                    		$('#startDateTimePicker').val('<?php echo $_SESSION['startDate'];?>');
                    						$('#endDateTimePicker').val('<?php echo $_SESSION['endDate'];?>');
                                    		var tailsignA= $("#selectedTailsign").val();
                                    		getFaultCodelist(airlineId, selectedPlatform, configType, tailsignA);
                                        }else{
	                                        var tailsignA = tailsignList[0];
                                            if(tailsign_nav) {
                                            	tailsignA = tailsign_nav;
                                            }
	                                        
	                                        $('#failureCode').empty();
	                                        $('#faultCode').empty();
	                                        $('#impactedServicesCode').empty();
	                                        getFaultCodelist(airlineId, selectedPlatform, configType, tailsignA);
                                        }
                                    }
         
                                },
                                error: function(err) {
                                    console.log('Error', err);
                                }
                            });
                        };
         
                        $scope.validate = function() {
                            $("#selectedTailsign").css("border-color", "");
                            $("#error").empty();
                        };
                        $("#resetbtn").click(function(event) {
                        	if(<?php echo $_REQUEST ['lopaVisited'];?>){
                    			var url = "lopa.php?lopaVisited=false";
                    			var win = window.open(url, '_self');
                    			win.focus();
                    		}else{
	                            $.blockUI({
	                                message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
	                            });
	                            toDate = new Date();
	                            fromDate = new Date();
	                            fromDate.setDate(fromDate.getDate() - 6);
	         
	                            $('#startDateTimePicker').datetimepicker({
	                                format: "Y-m-d",
	                                value: fromDate,
	                                timepicker: false,
	                                weeks: true
	                            });
	         
	                            $('#endDateTimePicker').datetimepicker({
	                                format: "Y-m-d",
	                                value: toDate,
	                                step: 15,
	                                timepicker: false,
	                                weeks: true
	                            });
	                            document.getElementById("advancedFilter").style.display = "none";
	         
	                            $('#resetsValue').empty();
	                            getResets();
	                            $('#flightPhases').empty();
	                            getflightPhases();
	         
	                            selectedPlatform = $("#selectedPlatform").val();
	                            airlineId = $("#selectedAirline").val();
	                            configType = $("#selectedConfigType").val();
	                            tailsign = $("#selectedTailsign").val();
	                            $('#faultCode').empty();
	                            $('#failureCode').empty();
	                            $('#impactedServicesCode').empty();
	                            getFaultCodelist(airlineId, selectedPlatform, configType, tailsign);
	                            		
	                            getAirlines();
	                            $.unblockUI();
                    		}
                        });
         
                    });
         
                    function getFailureCodelist(airlineIdA, selectedPlatformA, configTypeA, tailsignA) {
                        //var fromDateA = new Date($("#startDateTimePicker").val());
                        //var toDateA = new Date($("#endDateTimePicker").val());
                         var fromDateA = $("#startDateTimePicker").val();
                        var toDateA = $("#endDateTimePicker").val();
                        
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getFailureCodelist',
                                'airlineId': airlineIdA,
                                'config': configTypeA,
                                'platform': selectedPlatformA,
                                'tailsign': tailsignA,
                                'startDateTime': fromDateA,
                                'endDateTime': toDateA,
                            },
                            success: function(data) {
                                if (data) {
                                    var failureCodelist = JSON.parse(data);
                                    for (var i = 0; i < failureCodelist.length; i++) {
                                        var ts = failureCodelist[i];
                                        $("#failureCode").append("<option value='" + ts + "'>" + ts + "</option>");
                                    }
                                    
                                    $('#failureCode').selectpicker('refresh');
                                }
                                if (failureCodelist.length > 0) {                                    
                                	var element =document.getElementById('failureCode'); 
                                	element.disabled=false;                              	
                                } else {                                    
                                	var element =document.getElementById('failureCode'); 
                                	element.disabled=true; 
                                }
                                $('#failureCode').selectpicker('refresh');

                                if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                	var string = "<?php echo $_SESSION['failureCode'];?>";
                                	var array = string.split(",");
                                	$('#failureCode').val(array);		
                                	$('#failureCode').selectpicker('refresh');
                                }
                                
                                getImpactedServiceslist(airlineIdA, selectedPlatformA, configTypeA, tailsignA);
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function getFaultCodelist(airlineIdA, selectedPlatformA, configTypeA, tailsignA) {                        
                        //var fromDateA = new Date($("#startDateTimePicker").val());
                        //var toDateA = new Date($("#endDateTimePicker").val());
                        var fromDateA = $("#startDateTimePicker").val();
                        var toDateA = $("#endDateTimePicker").val();
                        
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getFaultCodelist',
                                'airlineId': airlineIdA,
                                'config': configTypeA,
                                'platform': selectedPlatformA,
                                'tailsign': tailsignA,
                                'startDateTime': fromDateA,
                                'endDateTime': toDateA,
                            },
                            success: function(data) {
                                if (data) {
                                    var faultCodelist = JSON.parse(data);
                                    for (var i = 0; i < faultCodelist.length; i++) {
                                        var ts = faultCodelist[i];
                                        $("#faultCode").append("<option value='" + ts + "'>" + ts + "</option>");
                                    }
                                   
                                    $('#faultCode').selectpicker('refresh');
                                }
                                if (faultCodelist.length > 0) {                                    
                                	var element =document.getElementById('faultCode'); 
                                	element.disabled=false;                              	
                                } else {                                    
                                	var element =document.getElementById('faultCode'); 
                                	element.disabled=true; 
                                }
                                $('#faultCode').selectpicker('refresh');

                                if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                	var string = "<?php echo $_SESSION['faultCode'];?>";
                                	var array = string.split(",");
                                	$('#faultCode').val(array);		
                                	$('#faultCode').selectpicker('refresh');
                                }
                                
                                getFailureCodelist(airlineIdA, selectedPlatformA, configTypeA, tailsignA);
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function getImpactedServiceslist(airlineIdA, selectedPlatformA, configTypeA, tailsignA) {
                        //var fromDateA = new Date($("#startDateTimePicker").val());
                        //var toDateA = new Date($("#endDateTimePicker").val());
                        var fromDateA = $("#startDateTimePicker").val();
                        var toDateA = $("#endDateTimePicker").val();
                        
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getImpactedServiceslist',
                                'airlineId': airlineIdA,
                                'config': configTypeA,
                                'platform': selectedPlatformA,
                                'tailsign': tailsignA,
                                'startDateTime': fromDateA,
                                'endDateTime': toDateA,
                            },
                            success: function(data) {
                                if (data) {
                                    var impactedServiceslist = JSON.parse(data);
                                    for (var i = 0; i < impactedServiceslist.length; i++) {
                                        var ts = impactedServiceslist[i];
                                        $("#impactedServicesCode").append("<option value='" + ts + "'>" + ts + "</option>");
                                    }

                                    $('#impactedServicesCode').selectpicker('refresh');
                                    if (impactedServiceslist.length > 0) {                                        
                                    	var element =document.getElementById('impactedServicesCode'); 
                                    	element.disabled=false;                                   	
                                    } else {                                        
                                    	var element =document.getElementById('impactedServicesCode'); 
                                    	element.disabled=true; 
                                    }
                                    $('#impactedServicesCode').selectpicker('refresh');

                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                    	var string = "<?php echo $_SESSION['ImpactedServicesCode'];?>";
                                    	var array = string.split(",");
                                    	$('#impactedServicesCode').val(array);		
                                    	$('#impactedServicesCode').selectpicker('refresh');
                                    }
                                }
                           
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    //var tableHeight=document.getElementById("resetsLopa");
                    var toDate;
                    var fromDate;
                    var tailsign;
                    var faultCodes;
                    var failureCodes;
                    var ImpactedServicesCodes;
                    var flightPhases;
                    var resetsEvent;
         
                    function loadFrame(tailsign) {
                        $('#btnInfo').hide();
                        $(".dz-hidden-input").prop("disabled", true);
                        //fromDate = new Date($("#startDateTimePicker").val());
                        //toDate = new Date($("#endDateTimePicker").val());

                        fromDate = $("#startDateTimePicker").val();
                        toDate = $("#endDateTimePicker").val();
                        
                        tailsign = tailsign;
                        document.getElementById('lopaRadioBtn').checked = true;
                        //reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate,tailsign);	
                        /*	reloadLopa('activeFailures', 'loadingActiveFailuresLopa', 'activeFailuresLopa', fromDate, toDate,tailsign);
                        	reloadLopa('failures', 'loadingFailuresLopa', 'failuresLopa', fromDate, toDate,tailsign);
                        	reloadLopa('faults', 'loadingFaultsLopa', 'faultsLopa', fromDate, toDate,tailsign);
                        	reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate,tailsign);	
                        	reloadLopa('impactedServices', 'loadingImpactedServicesLopa', 'impactedServicesLopa', fromDate, toDate,tailsign);
                        	reloadLopa('applications', 'loadingEventsLopa', 'eventsLopa', fromDate, toDate,tailsign);
                        	loadHeatmap(fromDate, toDate,tailsign);*/
                        $('#eventsPerHourDataPanel').hide();
                        $('#impactedServicesPerHourDataPanel').hide();
                        $('#faultsPerHourDataPanel').hide();
                        $('#activeFailuresPerHourDataPanel').hide();
                        $('#failuresPerHourDataPanel').hide();
                        $('#resetsPerHourDataPanel').hide();
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    }

                   
         
                    $(document).ready(function() {
                    
                        var filterClick = true;
                        $('#btnInfo').hide();
                        $('#seatModal').hide();
                        $('.nav-sidebar li').removeClass('active');
                        $("#homeSideBarLOPA").addClass("active");
                        $('#errorInfo').hide();
                        toDate = new Date();
                        fromDate = new Date();
                        fromDate.setDate(fromDate.getDate() - 6);
         
                        $('#startDateTimePicker').datetimepicker({
                            format: "Y-m-d",
                            value: fromDate,
                            timepicker: false,
                            weeks: true
                        });
                        
                        $('#selectedAirline').selectpicker({                              
                             size: 6
                       	});
         
                        $('#selectedTailsign').selectpicker({                              
                             size: 6
                       	});
                        $('#faultCode').selectpicker({                              
                            size: 6
                      	});
                        $('#failureCode').selectpicker({                              
                            size: 6
                      	});
                        $('#impactedServicesCode').selectpicker({                              
                            size: 6
                      	});
                       
                        $('#selectedPlatform').selectpicker({                              
                            size: 6
                      	});
                        $('#selectedConfigType').selectpicker({                              
                            size: 6
                      	});
         
                        $('#endDateTimePicker').datetimepicker({
                            format: "Y-m-d",
                            value: toDate,
                            step: 15,
                            timepicker: false,
                            weeks: true
                        });
         
                        $("#startDateTimePicker").on("blur", function(e) {
                            loadingAdvancedFilters();
                        });
         
                        $("#endDateTimePicker").on("blur", function(e) {
                            loadingAdvancedFilters();
                        });
         
                        function loadingAdvancedFilters() {
                            var selectedPlatformA = $("#selectedPlatform").val();
                            var airlineIdA = $("#selectedAirline").val();
                            var configTypeA = $("#selectedConfigType").val();
                            var tailsignA = $("#selectedTailsign").val();
                            $('#failureCode').empty();
                            $('#faultCode').empty();
                            $('#impactedServicesCode').empty();
                            getFaultCodelist(airlineIdA, selectedPlatformA, configTypeA, tailsignA);
                            
                        }

                        $('#selectedAirline').on('change', function(){
                    	    angular.element($("#ctrldiv")).scope().loadPlatforms();
                    	  });

                    	$('#selectedPlatform').on('change', function(){
                    	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
                    	  });

                    	$('#selectedConfigType').on('change', function(){
                    	    angular.element($("#ctrldiv")).scope().loadTailsigns();
                    	  });
         
                  
                        $('#flightPhases').val(['4:Climb', '5:Cruise']);

                      
                    });
                    $.blockUI.defaults.css = {
                        padding: 0,
                        margin: 0,
                        width: '30%',
                        top: '45%',
                        left: '44%',
                        textAlign: 'center',
                        cursor: 'wait'
                    };
                    $("#alertify-ok").click(function(event) {	
                    	window.location.href = '../common/logout_user.php';	
                    });
         
                    $("#filter").click(function(event) {
                        
                        document.getElementById('lopaRadioBtn').checked = true;
                        $.blockUI({
                            message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                        });
                        //fromDate = new Date($("#startDateTimePicker").val());
                        //toDate = new Date($("#endDateTimePicker").val());

                        fromDate = $("#startDateTimePicker").val();
                        toDate = $("#endDateTimePicker").val();

                        
                        tailsign = $("#selectedTailsign").val();
                        
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                        
                        //loadHeatmap(fromDate, toDate,tailsign);
                        //loadPerHourData(fromDate, toDate,tailsign);
                        event.preventDefault();
                    });
         
                    function reloadLopa(dataType, loadingIcon, table, startDate, endDate, tailsign) {
                        $('#' + table).html('');
                        $('#' + loadingIcon).hide();
                        $.blockUI({
                            message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                        });
                        $('#seatModal').hide();
                        loadLopa(dataType, loadingIcon, table, startDate, endDate, tailsign);
                    }
                    var tableHeightnWidth = '';
                    var tableHeight = '';
                    var tableWidth = '';
         
                    function loadLopa(dataType, loadingIcon, table, startDate, endDate, tailsign) {
                        $("#filter").prop('disabled', true);
                        $("#resetbtn").prop('disabled', true);
                        $("#" + table).bootstrapTable('destroy');
                        //tailsign=$("#selectedTailsign").val();
                        $("#" + table).removeClass('lopa-panel');
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        
                        var airline=$('#selectedAirline').val();
                        var platf=$('#selectedPlatform').val();
                        var config=$('#selectedConfigType').val();
                        var aircraft=$('#selectedTailsign').val();
                        
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];

                        if($('#resetsValue').val()==null){
                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                        }else{
                        	resetsEvent = $("#resetsValue").val();
                        }

                        if($('#monitorState').val()==null){
                        	$('#monitorState').val([1,3]);
                        }
         
                        if (faultCodes != null) {
                            
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                            
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }                       
                        
                        $('#errorInfo').hide();
                        //$('#' + loadingIcon).show();
                        data = {
                            tailsign: tailsign,
                            startDateTime: startDate,
                            endDateTime: endDate,
                            dataType: dataType,
                            faultCode: faultCodesarray.toString(),
                            failureCode: failureCodessarray.toString(),
                            ImpactedServicesCode: ImpactedServicesCodesarray.toString(),
                            flightPhases: flightPhasessarray.toString(),
                            resetCode: resetsarray.toString(),
                            aircraftId:$('#selectedTailsign').val(),
        	    			airline:$('#selectedAirline').val(),
        	    			platform:$('#selectedPlatform').val(),
        	    			configType:$('#selectedConfigType').val(),
        	    			monitorState:$('#monitorState').val()
                        };
         
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getLopaDatafetch.php",
                            data: data,
         
                            success: function(data) {
                                filteredData = data;
         
                                if (data.indexOf("Could not select") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    $.unblockUI();
                                } else if (data.indexOf("No Data Available") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    $.unblockUI();
                                } else {
                                    $('#btnInfo').show();
                                    $('#errorInfo').hide();
                                    $('#' + loadingIcon).hide();
                                    $("#" + table).bootstrapTable('destroy');
                                    $('#table').bootstrapTable();
                                    $("#" + table).append(data);
                                    				
                                    $("#" + table).addClass('lopa-panel');
                                    $('#seatModal').hide();
                                    $('#panel').show();
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    //setTimeout(function(){ $.unblockUI() },800);	
                                    $.unblockUI();
                                    tableHeightnWidth = document.getElementById(table);
                                    if (tableHeightnWidth.offsetHeight != 0 && tableHeightnWidth.offsetWidth != 0) {
                                        tableHeight = tableHeightnWidth.offsetHeight;
                                        tableWidth = tableHeightnWidth.offsetWidth;
                                    }
                                }
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function loadPerHourData(fromDate, toDate, tailsign) {
                        reloadPerHourData('reset', 'loadingResetsLopa', 'resetsPerHourData', fromDate, toDate, tailsign);
                        reloadPerHourData('activeFailures', 'loadingResetsLopa', 'activeFailuresPerHourData', fromDate, toDate, tailsign);
                        reloadPerHourData('failures', 'loadingResetsLopa', 'failuresPerHourData', fromDate, toDate, tailsign);
                        reloadPerHourData('faults', 'loadingResetsLopa', 'faultsPerHourData', fromDate, toDate, tailsign);
                        reloadPerHourData('impactedServices', 'loadingResetsLopa', 'impactedServicesPerHourData', fromDate, toDate, tailsign);
                        reloadPerHourData('applications', 'loadingResetsLopa', 'eventsPerHourData', fromDate, toDate, tailsign);
                    }
         
                    function reloadPerHourData(dataType, loadingIcon, table, startDate, endDate, tailsign) {
                        $("#filter").prop('disabled', true);
                        $("#resetbtn").prop('disabled', true);
                        $('#' + table).bootstrapTable('destroy');
                        //tailsign=$("#selectedTailsign").val();
                        $("#" + table).removeClass('lopa-panel');
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        resetsEvent = $("#resetsValue").val();
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];
         
                        if (faultCodes != null) {
                            /*for(code in faultCodes){
                            	var str=faultCodes[code].split(':');
                            	faultCodesarray.push(str[0]);
                            }*/
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                            
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }
         
                        $('#errorInfo').hide();
                        //$('#' + loadingIcon).show();
                        data = {
                            tailsign: tailsign,
                            startDateTime: startDate,
                            endDateTime: endDate,
                            dataType: dataType,
                            faultCode: faultCodesarray.toString(),
                            failureCode: failureCodessarray.toString(),
                            ImpactedServicesCode: ImpactedServicesCodesarray.toString(),
                            flightPhases: flightPhasessarray.toString(),
                            resetCode: resetsarray.toString()
                        };
         
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getLopaDataforPerHourData.php",
                            data: data,
                            success: function(data) {
                                $("#" + table).bootstrapTable('destroy');
                                if (data.indexOf("Could not select") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    $.unblockUI();
                                } else if (data.indexOf("No Data Available") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    $.unblockUI();
                                } else {
                                    $('#' + table).empty();
                                    $('#' + table).bootstrapTable('destroy');
                                    $('#' + table).append(data);
                                    $("#" + table).addClass('lopa-panel');
                                    $('#seatModal').hide();
                                    $('#panel').show();
                                    data = '';
                                    $("#filter").prop('disabled', false);
                                    $("#resetbtn").prop('disabled', false);
                                    $.unblockUI();
                                }
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function loadHeatmap(fromDate, toDate, tailsign) {
                        reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate, tailsign);
                        reloadHeatmapLopa('activeFailures', 'loadingResetsLopa', 'activeFailuresHeatmap', fromDate, toDate, tailsign);
                        reloadHeatmapLopa('failures', 'loadingResetsLopa', 'failuresHeatmap', fromDate, toDate, tailsign);
                        reloadHeatmapLopa('faults', 'loadingResetsLopa', 'faultsHeatmap', fromDate, toDate, tailsign);
                        reloadHeatmapLopa('impactedServices', 'loadingResetsLopa', 'impactedServicesHeatmap', fromDate, toDate, tailsign);
                        reloadHeatmapLopa('applications', 'loadingResetsLopa', 'eventsHeatmap', fromDate, toDate, tailsign);
                    }
         
                    function reloadHeatmapLopa(dataType, loadingIcon, table, startDate, endDate, tailsign) {
                        //$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading Heatmap..." });
                        //$("#" + table).remove();		
                        $("#" + table).hide();
         
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        //resetsEvent = $("#resetsValue").val();
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];    

                        if($('#resetsValue').val()==null){
                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                        }else{
                        	resetsEvent = $("#resetsValue").val();
                        }

                        if($('#monitorState').val()==null){
                        	$('#monitorState').val([1,3]);
                        }                    
         
                        if (faultCodes != null) {
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                           
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }
         
                        $('#errorInfo').hide();
                        //$('#' + loadingIcon).show();
                        data = {
                            tailsign: tailsign,
                            startDateTime: startDate,
                            endDateTime: endDate,
                            dataType: dataType,
                            faultCode: faultCodesarray.toString(),
                            failureCode: failureCodessarray.toString(),
                            ImpactedServicesCode: ImpactedServicesCodesarray.toString(),
                            flightPhases: flightPhasessarray.toString(),
                            resetCode: resetsarray.toString(),
                            aircraftId:$('#selectedTailsign').val(),
        	    			airline:$('#selectedAirline').val(),
        	    			platform:$('#selectedPlatform').val(),
        	    			configType:$('#selectedConfigType').val(),
        	    			monitorState:$('#monitorState').val()
                        };
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getLopaDataforHeatmap.php",
                            data: data,
         
                            success: function(data) {
                                if (data.indexOf("Could not select") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                } else if (data.indexOf("No Data Available") >= 0) {
                                    $('#btnInfo').hide();
                                    $('#errorInfo').show();
                                    $('#' + loadingIcon).hide();
                                    $('#seatModal').hide();
                                    $('#panel').hide();
                                } else {
                                    filteredData = JSON.parse(data);
                                    var label = filteredData[0];
                                    var data = filteredData[1];
                                    var xnotLabelr = filteredData[2];
                                    var xnotLabel = filteredData[3];
                                    var index = xnotLabel[0] - 1;
         
                                    var values = [];
         
                                    function remove(array, element) {
                                        const index = element;
                                        array.splice(index, 1);
                                    }
         
                                    for (var k = 0; k < xnotLabelr.length; k++) {
                                        for (var i = 0; i < data.length; i++) {
                                            remove(data[i].data, xnotLabelr[k] - 1);
                                        }
                                    }
         
                                    //label.splice(index, 0, " ");
                                    for (var l = 0; l < data.length; l++) {
                                        //data[l].data.splice(index, 0, -1);							
                                    }
         
                                    for (var i = 0; i < data.length; i++) {
                                        var d1 = data[i].data;
                                        for (var k = 0; k < d1.length; k++) {
                                            if (d1[k] != 0 && d1[k] != -1) {
                                                if (values.indexOf(d1[k]) === -1) {
                                                    values.push(d1[k]);
                                                }
                                            }
                                        }
                                    }
         
                                    //$("#filter").prop('disabled', false);
         
                                    //var colorTestColors = ['greenyellow','orange','red'];
                                    var colorTestColors = ['skyblue', 'red', 'black'];
         
                                    if (values.length > 4) {
                                        var options = {
                                            responsive: false,
                                            colors: colorTestColors,
                                            colorInterpolation: 'gradient',
                                            showLabels: true,
                                            rounded: true,
                                            roundedRadius: 0.1,
                                            paddingScale: 0.1,
                                            tooltipTemplate: "<%if (value==-1){} else {%> Seat <%= xLabel %><%= yLabel %> - <%= value %> <%}%>",
                                        };
                                    } else {
                                        var options = {
                                            responsive: false,
                                            colors: colorTestColors,
                                            colorInterpolation: 'palette',
                                            showLabels: true,
                                            rounded: true,
                                            roundedRadius: 0.1,
                                            paddingScale: 0.1,
                                            tooltipTemplate: "<%if (value==-1){} else {%> Seat <%= xLabel %><%= yLabel %> - <%= value %> <%}%>",
                                        };
                                    }
         
                                    var ctx1 = document.getElementById(table).getContext('2d');
                                    if (tableWidth < 100) {
                                       
                                        $(".scrollChart").width(tableWidth);
                                        $(".scrollChart").css('min-width', tableWidth);
                                        $(".scrollChart").css('min-height', tableHeight);
                                        tableHeight = tableHeight;
                                    } else {
                                        $(".scrollChart").width(tableWidth);
                                        $(".scrollChart").css('min-width', tableWidth);
                                        $(".scrollChart").css('min-height', tableHeight);
                                        tableHeight = tableHeight;
                                    }
                                    var heatmapData = {
                                        labels: label,
                                        datasets: data
                                    };
                                    var scrollChart = new Chart(ctx1, tableHeight, tableWidth).HeatMap(heatmapData, options);
                                    ctx1.canvas.onclick = function(evt) {
                                        var activeBox = scrollChart.getBoxAtEvent(evt);
                                        //console.log(activeBox.value);
                                        if (activeBox == undefined) {
                                            //activeBox.value=-1;
                                        } else {
                                            if (activeBox.value != -1) {
                                                var seatConcat = activeBox.label + activeBox.datasetLabel;
                                                console.log('Seat: ' + seatConcat);
                                                seatSelected(seatConcat, activeBox.value);
                                            }
                                        }
                                    };
         
                                    ctx1.stroke();
                                    setTimeout(function() {
                                        $.unblockUI()
                                    }, 800);
                                    $("#" + table).show();
                                }
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
         
                    }
         
                    $('#resetslbl').click(function(event) {
                        panelDisplay('resetsLopaPanel', 'activeFailuresLopaPanel', 'allFailuresLopaPanel', 'allFaultsLopaPanel', 'impactedServicesPanel', 'eventsPanel', 'resetslbl', 'allFailureslbl', 'activeFailureslbl', 'allFaultslbl', 'impactedServiceslbl', 'eventslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    });
         
                    function panelDisplay(panel1, panel2, panel3, panel4, panel5, panel6, label1, label2, label3, label4, label5, label6) {
                        $('#' + panel1).show();
                        $('#' + panel2).hide();
                        $('#' + panel3).hide();
                        $('#' + panel4).hide();
                        $('#' + panel5).hide();
                        $('#' + panel6).hide();
                        $('#seatModal').hide();

                       
                        $('#' + label1).addClass('active');
                        $('#' + label2).removeClass('active');
                        $('#' + label3).removeClass('active');
                        $('#' + label4).removeClass('active');
                        $('#' + label5).removeClass('active');
                        $('#' + label6).removeClass('active');
         
                    }
         
                    $('#activeFailureslbl').click(function(event) {
                        panelDisplay('activeFailuresLopaPanel', 'resetsLopaPanel', 'allFailuresLopaPanel', 'allFaultsLopaPanel', 'impactedServicesPanel', 'eventsPanel', 'activeFailureslbl', 'allFailureslbl', 'resetslbl', 'allFaultslbl', 'impactedServiceslbl', 'eventslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
         
                    });
         
                    $('#allFaultslbl').click(function(event) {
                        panelDisplay('allFaultsLopaPanel', 'resetsLopaPanel', 'allFailuresLopaPanel', 'activeFailuresLopaPanel', 'impactedServicesPanel', 'eventsPanel', 'allFaultslbl', 'allFailureslbl', 'resetslbl', 'activeFailureslbl', 'impactedServiceslbl', 'eventslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    });
         
                    $('#allFailureslbl').click(function(event) {
                        panelDisplay('allFailuresLopaPanel', 'resetsLopaPanel', 'allFaultsLopaPanel', 'activeFailuresLopaPanel', 'impactedServicesPanel', 'eventsPanel', 'allFailureslbl', 'allFaultslbl', 'resetslbl', 'activeFailureslbl', 'impactedServiceslbl', 'eventslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    });
         
                    $('#impactedServiceslbl').click(function(event) {
                        panelDisplay('impactedServicesPanel', 'resetsLopaPanel', 'allFaultsLopaPanel', 'activeFailuresLopaPanel', 'allFailuresLopaPanel', 'eventsPanel', 'impactedServiceslbl', 'allFaultslbl', 'resetslbl', 'activeFailureslbl', 'allFailureslbl', 'eventslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    });
         
                    $('#eventslbl').click(function(event) {
                        panelDisplay('eventsPanel', 'resetsLopaPanel', 'allFaultsLopaPanel', 'activeFailuresLopaPanel', 'impactedServicesPanel', 'allFailuresLopaPanel', 'eventslbl', 'allFailureslbl', 'resetslbl', 'activeFailureslbl', 'impactedServiceslbl', 'allFaultslbl');
                        if (document.getElementById('lopaRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('lopa');
                        } else if (document.getElementById('heatmapRadioBtn').checked) {
                            //$('#flightPhases').multiselect('enable');
                            filterCategory('heatmap');
                        } else if (document.getElementById('perHourRadioBtn').checked) {
                            $('#flightPhases').multiselect('disable');
                            filterCategory('perhour');
                        }
                    });
         
                    function filterCategory(category) {
         
                        if ($('#resetslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate, tailsign);
         
                                displayPanel('resetsLopaPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
         
                            } else if (category == 'lopa') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });			
                                reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate, tailsign);
                                displayPanel('resetsHeatmapPanel', 'resetsLopaPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
         
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                
                                $('#flightPhases').multiselect('disable');
                                displayPanel('resetsHeatmapPanel', 'resetsPerHourDataPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsLopaPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //reloadPerHourData('reset', 'loadingResetsLopa', 'resetsPerHourData', fromDate, toDate,tailsign);
                            }
                        } else if ($('#activeFailureslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('activeFailures', 'loadingResetsLopa', 'activeFailuresHeatmap', fromDate, toDate, tailsign);
                                displayPanel('activeFailuresLopaPanel', 'activeFailuresHeatmapPanel', 'resetsHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
                            } else if (category == 'lopa') {								
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadLopa('activeFailures', 'loadingActiveFailuresLopa', 'activeFailuresLopa', fromDate, toDate, tailsign);
                                displayPanel('activeFailuresHeatmapPanel', 'activeFailuresLopaPanel', 'resetsHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                $('#flightPhases').multiselect('disable');
                                //reloadPerHourData('activeFailures', 'loadingResetsLopa', 'activeFailuresPerHourData', fromDate, toDate,tailsign);
                                displayPanel('activeFailuresHeatmapPanel', 'activeFailuresPerHourDataPanel', 'resetsHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresLopaPanel', 'failuresPerHourDataPanel');
                            }
                        } else if ($('#allFailureslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('failures', 'loadingResetsLopa', 'failuresHeatmap', fromDate, toDate, tailsign);
                                displayPanel('allFailuresLopaPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
                            } else if (category == 'lopa') {
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadLopa('failures', 'loadingFailuresLopa', 'failuresLopa', fromDate, toDate, tailsign);
                                displayPanel('failuresHeatmapPanel', 'allFailuresLopaPanel', 'faultsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                $('#flightPhases').multiselect('disable');
                                //reloadPerHourData('failures', 'loadingResetsLopa', 'failuresPerHourData', fromDate, toDate,tailsign);
                                displayPanel('failuresHeatmapPanel', 'failuresPerHourDataPanel', 'faultsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'allFailuresLopaPanel');
                            }
                        } else if ($('#allFaultslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('faults', 'loadingResetsLopa', 'faultsHeatmap', fromDate, toDate, tailsign);
                                displayPanel('allFaultsLopaPanel', 'faultsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
                                
                            } else if (category == 'lopa') {	
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadLopa('faults', 'loadingFaultsLopa', 'faultsLopa', fromDate, toDate, tailsign);
                                displayPanel('faultsHeatmapPanel', 'allFaultsLopaPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                $('#flightPhases').multiselect('disable');
                                //reloadPerHourData('faults', 'loadingResetsLopa', 'faultsPerHourData', fromDate, toDate,tailsign);
                                displayPanel('faultsHeatmapPanel', 'faultsPerHourDataPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'allFaultsLopaPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                            }
                        } else if ($('#impactedServiceslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('impactedServices', 'loadingResetsLopa', 'impactedServicesHeatmap', fromDate, toDate, tailsign);
                                displayPanel('impactedServicesPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
                            
                            } else if (category == 'lopa') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                
                                reloadLopa('impactedServices', 'loadingImpactedServicesLopa', 'impactedServicesLopa', fromDate, toDate, tailsign);
                                displayPanel('impactedServicesHeatmapPanel', 'impactedServicesPanel', 'eventsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                $('#flightPhases').multiselect('disable');
                                //reloadPerHourData('impactedServices', 'loadingResetsLopa', 'impactedServicesPerHourData', fromDate, toDate,tailsign);
                                displayPanel('impactedServicesHeatmapPanel', 'impactedServicesPerHourDataPanel', 'eventsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
         
                            }
                        } else if ($('#eventslbl').hasClass('active')) {
                            if (category == 'heatmap') {
                                //$('#flightPhases').multiselect('enable');
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadHeatmapLopa('applications', 'loadingResetsLopa', 'eventsHeatmap', fromDate, toDate, tailsign);
                                displayPanel('eventsPanel', 'eventsHeatmapPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                $('#seatModal').hide();
                             } else if (category == 'lopa') {	
                                $.blockUI({
                                    message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                                });
                                reloadLopa('applications', 'loadingEventsLopa', 'eventsLopa', fromDate, toDate, tailsign);
                                displayPanel('eventsHeatmapPanel', 'eventsPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsPerHourDataPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
                                //setTimeout(function(){ $.unblockUI() },800);
                            } else if (category == 'perhour') {
                                $('#seatModal').hide();
                                $('#flightPhases').multiselect('disable');
                                //reloadPerHourData('applications', 'loadingResetsLopa', 'eventsPerHourData', fromDate, toDate,tailsign);
                                displayPanel('eventsHeatmapPanel', 'eventsPerHourDataPanel', 'resetsHeatmapPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsPanel', 'impactedServicesPerHourDataPanel', 'faultsPerHourDataPanel', 'resetsPerHourDataPanel', 'activeFailuresPerHourDataPanel', 'failuresPerHourDataPanel');
         
                            }
                        } else {
                        	$.blockUI({
                                message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..."
                            });			
                            reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate, tailsign);
                            displayPanel('resetsHeatmapPanel', 'resetsLopaPanel', 'activeFailuresHeatmapPanel', 'failuresHeatmapPanel', 'faultsHeatmapPanel', 'impactedServicesHeatmapPanel', 'eventsHeatmapPanel');
                            
                        }
                    }
         
                    function displayPanel(panel1, panel2, panel3, panel4, panel5, panel6, panel7, panel8, panel9, panel10, panel11, panel12, panel13) {
                        $('#' + panel1).hide();
                        $('#' + panel2).show();
                        $('#' + panel3).hide();
                        $('#' + panel4).hide();
                        $('#' + panel5).hide();
                        $('#' + panel6).hide();
                        $('#' + panel7).hide();
                        $('#' + panel8).hide();
                        $('#' + panel9).hide();
                        $('#' + panel10).hide();
                        $('#' + panel11).hide();
                        $('#' + panel12).hide();
                        $('#' + panel13).hide();
                    }
         
                    function displayClassforLopaPanel(panel1, panel2, panel3, panel4, panel5, panel6, panel7, tab1, tab2, tab3, tab4, tab5, tab6, tab7) {
         
                        $('#' + panel1).removeClass('active');
                        $('#' + panel2).removeClass('active');
                        $('#' + panel3).removeClass('active');
                        $('#' + panel4).removeClass('active');
                        $('#' + panel5).removeClass('active');
                        $('#' + panel6).removeClass('active');
         
                        $('#' + tab1).removeClass('active');
                        $('#' + tab2).removeClass('active');
                        $('#' + tab3).removeClass('active');
                        $('#' + tab4).removeClass('active');
                        $('#' + tab5).removeClass('active');
                        $('#' + tab6).removeClass('active');
         
                        $('#' + panel7).addClass('active');
                        $('#' + tab7).addClass('active');
         
                    }
         
                    var jsonData;
                    var seatValue;
                    var countV;
         
                    function seatSelected(seat, count) {
                    	console.log('Seat: ' + seat);
                        //$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading Data..." });	
                        getAircraftId();
                        $('#seatModal').show();
                        $('#tabularData').hide();
                        $('#loadingTabData').show();
                        //$('#loadingTabData').hide();
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        //resetsEvent = $("#resetsValue").val();
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];

                        if($('#resetsValue').val()==null){
                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                        }else{
                        	resetsEvent = $("#resetsValue").val();
                        }

                        if($('#monitorState').val()==null){
                        	$('#monitorState').val([1,3]);
                        }
         
                        if (faultCodes != null) {
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }
         
                        if ($('#allFaultsLopaPanel').css('display') == 'block' || $('#faultsPerHourDataPanel').css('display') == 'block' || $('#faultsHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'resetsPanel', 'failuresPanel', 'activeFailuresPanel', 'impactPanel', 'appeventsPanel', 'faultsPanel', 'summaryTab', 'resetsTab', 'activeFailuresTab', 'failuresTab', 'impactServicesTab', 'eventsTab', 'faultsTab');
         
                        }
                        if ($('#resetsLopaPanel').css('display') == 'block' || $('#resetsPerHourDataPanel').css('display') == 'block' || $('#resetsHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'faultsPanel', 'failuresPanel', 'activeFailuresPanel', 'impactPanel', 'appeventsPanel', 'resetsPanel', 'summaryTab', 'faultsTab', 'activeFailuresTab', 'failuresTab', 'impactServicesTab', 'eventsTab', 'resetsTab');
         
                        }
                        if ($('#activeFailuresLopaPanel').css('display') == 'block' || $('#activeFailuresPerHourDataPanel').css('display') == 'block' || $('#activeFailuresHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'faultsPanel', 'failuresPanel', 'resetsPanel', 'impactPanel', 'appeventsPanel', 'activeFailuresPanel', 'summaryTab', 'faultsTab', 'resetsTab', 'failuresTab', 'impactServicesTab', 'eventsTab', 'activeFailuresTab');
         
                        }
                        if ($('#allFailuresLopaPanel').css('display') == 'block' || $('#failuresPerHourDataPanel').css('display') == 'block' || $('#failuresHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'faultsPanel', 'activeFailuresPanel', 'resetsPanel', 'impactPanel', 'appeventsPanel', 'failuresPanel', 'summaryTab', 'faultsTab', 'resetsTab', 'activeFailuresTab', 'impactServicesTab', 'eventsTab', 'failuresTab');
         
                        }
                        if ($('#impactedServicesPanel').css('display') == 'block' || $('#impactedServicesPerHourDataPanel').css('display') == 'block' || $('#impactedServicesHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'faultsPanel', 'failuresPanel', 'resetsPanel', 'activeFailuresPanel', 'appeventsPanel', 'impactPanel', 'summaryTab', 'faultsTab', 'resetsTab', 'failuresTab', 'activeFailuresTab', 'eventsTab', 'impactServicesTab');
                        }
                        if ($('#eventsPanel').css('display') == 'block' || $('#eventsPerHourDataPanel').css('display') == 'block' || $('#eventsHeatmapPanel').css('display') == 'block') {
                            displayClassforLopaPanel('summaryPanel', 'faultsPanel', 'failuresPanel', 'resetsPanel', 'impactPanel', 'activeFailuresPanel', 'appeventsPanel', 'summaryTab', 'faultsTab', 'resetsTab', 'failuresTab', 'impactServicesTab', 'activeFailuresTab', 'eventsTab');
         
                        }
         
                        $('#seatModal').show();
                        seatValue = seat;
                        countV = count;
                        document.getElementById("seatHeader").innerHTML = "Seat " + seat;
         
                        $('#svduSerialNumber').html('-');
                        $('#svduLruType').html('-');
                        $('#svduHwPartNumber').html('-');
                        $('#svduRevision').html('-');
                        $('#svduMod').html('-');
                        $('#svduMacAddress').html('-');
                        $('#svduLastUpdate').html('-');
         
                        $('#pcuSerialNumber').html('-');
                        $('#pcuLruType').html('-');
                        $('#pcuHwPartNumber').html('-');
                        $('#pcuRevision').html('-');
                        $('#pcuMod').html('-');
                        $('#pcuMacAddress').html('-');
                        $('#pcuLastUpdate').html('-');
         
                        $('#svduResetsTable').bootstrapTable('destroy');
                        $('#pcuResetsTable').bootstrapTable('destroy');
                        $('#svduActiveFailuresTable').bootstrapTable('destroy');
                        $('#pcuActiveFailuresTable').bootstrapTable('destroy');
                        $('#svduFailuresTable').bootstrapTable('destroy');
                        $('#pcuFailuresTable').bootstrapTable('destroy');
                        $('#svduFaultsTable').bootstrapTable('destroy');
                        $('#pcuFaultsTable').bootstrapTable('destroy');
                        $('#svduImpactedServicesTable').bootstrapTable('destroy');
                        $('#pcuImpactedServicesTable').bootstrapTable('destroy');
                        $('#svduEventsTable').bootstrapTable('destroy');
                        $('#pcuEventsTable').bootstrapTable('destroy');
         
                        $("#loading").show();
         
                        fromDate = $("#startDateTimePicker").val();
                        toDate = $("#endDateTimePicker").val();
                        
                        tailsign = $("#selectedTailsign").val();
         
                        data = {
                            aircraftId: tailsign,
                            startDateTime: fromDate,
                            endDateTime: toDate,
                            seat: seat,
                            faultCode: faultCodesarray.toString(),
                            failureCode: failureCodessarray.toString(),
                            ImpactedServicesCode: ImpactedServicesCodesarray.toString(),
                            flightPhases: flightPhasessarray.toString(),
                            resetCode: resetsarray.toString(),
                            countValue: count,
                            monitorState:$('#monitorState').val()
                        };
         
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getSeatDetailsforLopa.php",
                            data: data,
                            success: function(data) {
         
                                // Need to convert from json string to json object to we can pass it to the table
                                //jsonData = $.parseJSON(data);
                                jsonData = JSON.parse(data);
                                if (jsonData.svduDetails.length > 0) {
                                    $('#svduSerialNumber').html(jsonData.svduDetails[0].serialNumber);
                                    $('#svduLruType').html(jsonData.svduDetails[0].lruType);
                                    $('#svduHwPartNumber').html(jsonData.svduDetails[0].hwPartNumber);
                                    $('#svduRevision').html(jsonData.svduDetails[0].revision);
                                    $('#svduMod').html(jsonData.svduDetails[0].mod);
                                    $('#svduMacAddress').html(jsonData.svduDetails[0].macAddress);
                                    $('#svduLastUpdate').html(jsonData.svduDetails[0].lastUpdate);
                                }
                                if (jsonData.pcuDetails.length > 0) {
                                    $('#pcuSerialNumber').html(jsonData.pcuDetails[0].serialNumber);
                                    $('#pcuLruType').html(jsonData.pcuDetails[0].lruType);
                                    $('#pcuHwPartNumber').html(jsonData.pcuDetails[0].hwPartNumber);
                                    $('#pcuRevision').html(jsonData.pcuDetails[0].revision);
                                    $('#pcuMod').html(jsonData.pcuDetails[0].mod);
                                    $('#pcuMacAddress').html(jsonData.pcuDetails[0].macAddress);
                                    $('#pcuLastUpdate').html(jsonData.pcuDetails[0].lastUpdate);
                                }
         
                                $('#svduResetsTable').bootstrapTable({
                                    data: jsonData.svduResets
                                });
         
                                $('#pcuResetsTable').bootstrapTable({
                                    data: jsonData.pcuResets
                                });
         
                                $('#svduActiveFailuresTable').bootstrapTable({
                                    data: jsonData.svduActiveFailures
                                });
         
                                $('#pcuActiveFailuresTable').bootstrapTable({
                                    data: jsonData.pcuActiveFailures
                                });
         
                                $('#svduFailuresTable').bootstrapTable({
                                    data: jsonData.svduFailures
                                });
         
                                $('#pcuFailuresTable').bootstrapTable({
                                    data: jsonData.pcuFailures
                                });
         
                                $('#svduFaultsTable').bootstrapTable({
                                    data: jsonData.svduFaults
                                });
         
                                $('#pcuFaultsTable').bootstrapTable({
                                    data: jsonData.pcuFaults
                                });
         
                                $('#svduImpactedServicesTable').bootstrapTable({
                                    data: jsonData.svduImpactedServices
                                });
         
                                $('#pcuImpactedServicesTable').bootstrapTable({
                                    data: jsonData.pcuImpactedServices
                                });
         
                                $('#svduEventsTable').bootstrapTable({
                                    data: jsonData.svduEvents
                                });
         
                                $('#pcuEventsTable').bootstrapTable({
                                    data: jsonData.pcuEvents
                                });
                                $('#tabularData').show();
                                //$('#loadingTabData').hide();
                                //setTimeout(function(){ $.unblockUI() },800);
                                if (count > 30) {
                                    setTimeout(function() {
                                        $('#loadingTabData').hide()
                                    }, 600);
                                } else {
                                    $('#loadingTabData').hide();
                                }
                                //$("#loading").hide();
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function exportOffloads(data) {
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        //resetsEvent = $("#resetsValue").val();
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];

                        if($('#resetsValue').val()==null){
                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                        }else{
                        	resetsEvent = $("#resetsValue").val();
                        }

                        if($('#monitorState').val()==null){
                        	$('#monitorState').val([1,3]);
                        }
         
                        if (faultCodes != null) {                            
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }
         
                        fromDate = $("#startDateTimePicker").val();
                        toDate = $("#endDateTimePicker").val();
                        
                        tailsign = $("#selectedTailsign").val();
                        var startDT = fromDate;
                        var endDT = toDate;
                        var formatType = data;
                        data = {
                            startDateTime: fromDate,
                            endDateTime: toDate,
                            seatValue: seatValue,
                            faultCode: faultCodesarray.toString(),
                            failureCode: failureCodessarray.toString(),
                            ImpactedServicesCode: ImpactedServicesCodesarray.toString(),
                            flightPhases: flightPhasessarray.toString(),
                            resetCode: resetsarray.toString(),
                            countValue: countV,
                            monitorState:$('#monitorState').val()
                        };
         				var monitorStateValue=$('#monitorState').val();
                        var url = "../engineering/exportLopaViewData.php?tailsign=" + tailsign + "&startDateTime=" + startDT + "&endDateTime=" + endDT + "&seat=" + seatValue + "&formatType=" + formatType + "&faultCode=" + faultCodesarray.toString() + "&failureCode=" + failureCodessarray.toString() + "&ImpactedServicesCode=" + ImpactedServicesCodesarray.toString() + "&flightPhases=" + flightPhasessarray.toString() + "&resetCode=" + resetsarray.toString() + "&countValue=" + countV+"&monitorState="+monitorStateValue;
                        window.location = url;
         
                    }
         
                    function exportLopaSeatViewData(data) {
                        faultCodes = $("#faultCode").val();
                        failureCodes = $("#failureCode").val();
                        ImpactedServicesCodes = $("#impactedServicesCode").val();
                        commandedResets = $("#commandedResets").val();
                        unCommandedResets = $("#unCommandedResets").val();
                        flightPhases = $("#flightPhases").val();
                        //resetsEvent = $("#resetsValue").val();
                        var filteredData;
                        var faultCodesarray = [];
                        var failureCodessarray = [];
                        var ImpactedServicesCodesarray = [];
                        var flightPhasessarray = [];
                        var resetsarray = [];

                        if($('#resetsValue').val()==null){
                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                        }else{
                        	resetsEvent = $("#resetsValue").val();
                        }

                        if($('#monitorState').val()==null){
                        	$('#monitorState').val([1,3]);
                        }
         
                        if (faultCodes != null) {
                            faultCodesarray = faultCodes;
                        }
                        if (failureCodes != null) {
                            failureCodessarray = failureCodes;
                        }
                        if (ImpactedServicesCodes != null) {
                            ImpactedServicesCodesarray = ImpactedServicesCodes;
                        }
                        if (flightPhases != null) {
                            for (code in flightPhases) {
                                var str = flightPhases[code].split(':');
                                flightPhasessarray.push(str[0]);
                            }
                        }
         
                        if (resetsEvent != null) {
                            for (r in resetsEvent) {
                                var str = resetsEvent[r].split(':');
                                var str1 = "'" + str[0] + "'";
                                resetsarray.push(str1);
                            }
                        }
                        
                        fromDate = $("#startDateTimePicker").val();
                        toDate = $("#endDateTimePicker").val();
                        
                        tailsign = $("#selectedTailsign").val();
                        var startDT = fromDate;
                        var endDT = toDate;
                        var formatType = data;
                        data = {
                            startDateTime: fromDate,
                            endDateTime: toDate,
                            seatValue: seatValue
                        };
         
                        var type = "all";
                        var url = "../engineering/exportLopaSeatViewData.php?tailsign=" + tailsign + "&startDateTime=" + startDT + "&endDateTime=" + endDT + "&seat=" + seatValue + "&formatType=" + formatType + "&faultCode=" + faultCodesarray.toString() + "&failureCode=" + failureCodessarray.toString() + "&ImpactedServicesCode=" + ImpactedServicesCodesarray.toString() + "&flightPhases=" + flightPhasessarray.toString() + "&resetCode=" + resetsarray.toString() + "&countValue=" + countV + "&dataType=" + type+"&monitorState="+$('#monitorState').val();
                        window.location = url;
                    }
         
                    getflightPhases();
         
                    function getflightPhases() {
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getflightPhases'
                            },
                            success: function(data) {
                                if (data) {
                                    var flightPhaseslist = JSON.parse(data);
                                    for (var i = 0; i < flightPhaseslist.length; i++) {
                                        var ts = flightPhaseslist[i];
                                        $("#flightPhases").append('<option value=' + ts + '>' + ts + '</option>');
                                    }
                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                    	var string = "<?php echo $_SESSION['flightPhases'];?>";
                                    	var array = string.split(",");
                                    	if(array!=''){
                                    		var flightA=[];
                                        	for(r in array){
												var flightDesc=getFilghtPhasesDesc(array[r]);
												flightA.push(flightDesc);
                                            }
                                    		$('#flightPhases').val(flightA);		
                                    		$('#flightPhases').selectpicker('refresh');
                                    	}else{
                                    		$('#flightPhases').val(['4:Climb', '5:Cruise']);
                                            $('#flightPhases').selectpicker('refresh');
                                        }
                                    }else{
                                        $('#flightPhases').val(['4:Climb', '5:Cruise']);
                                        $('#flightPhases').selectpicker('refresh');
                                    }
                                  //  $('#flightPhases').multiselect('rebuild');
                                }
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
                    function getFilghtPhasesDesc(val){
                        var str='';
						switch(val){
							case "1":
						        str='1:Pre-flightground';
						        break;
						    case "2":
						    	str='2:Taxi-out';
						        break;
						    case "3":
						    	str='3:Take-off';
						        break;
						    case "4":
						    	str='4:Climb';
						        break;
						    case "5":
						    	str='5:Cruise';
						        break;
						    case "6":
						    	str='6:Descent';
						        break;
						    case "7":
						    	str='7:Landed';
						        break;
						    case "8":
						    	str='8:Taxi-in';
						        break;
						    case "9":
						    	str='9:Post-flight';
						        break;
						    default:
						    	str='';
						        break;
						}
						return str;
                    }
         
                    getResets();
         
                    function getResets() {
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getResetsCode'
                            },
                            success: function(data) {
                                if (data) {
                                    var resetlist = JSON.parse(data);
                                    for (var i = 0; i < resetlist.length; i++) {
                                        var ts = resetlist[i];
                                        $("#resetsValue").append('<option value=' + ts.value + '>' + ts.label + '</option>');
                                    }
                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                    	var string = "<?php echo $_SESSION['resetCode'];?>";
                                    	var array = string.split(",");
                                    	if(array!=''){
                                        	var resetA=[];
                                        	for(r in array){												
												var str = array[r];
												str = str.substr(1).slice(0, -1);
												resetA.push(str);
                                            }                                        	
                                    		$('#resetsValue').val(resetA);		
                                        	$('#resetsValue').selectpicker('refresh');
                                        }else{
                                        	$('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                                            $('#resetsValue').selectpicker('refresh');
                                        }                                    	
                                    }else{
                                        $('#resetsValue').val(['CommandedReboot', 'UncommandedReboot']);
                                        $('#resetsValue').selectpicker('refresh');
                                    }
                                   // $('#resetsValue').multiselect('rebuild');
                                }
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    var aircraftId = '';
         
                    function getAircraftId() {
                        tailsign = $("#selectedTailsign").val();
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getAircraftId',
                                'tailsign': tailsign
                            },
                            success: function(data) {
                                aircraftId = data;
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function getTailsignlist(selectedAirline, selectedPlatform, selectedConfigType) {
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getTailsignlist',
                                'airlineId': selectedAirline.id,
                                'config': selectedConfigType.configType,
                                'platform': selectedPlatform
                            },
                            success: function(data) {
                                tailsignList = JSON.parse(data);
                                for (var i = 0; i < tailsignList.length; i++) {
                                    var ts = tailsignList[i];
                                    $("#selectedTailsign").append('<option value=' + ts + '>' + ts + '</option>');
                                }
                                $('#selectedTailsign').multiselect('rebuild');
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
         
                    function formatFlightLeg(value, row, index, field) {
                        if (value) {
         
                            //value = value.replace(/,/g, ", ");						
                            var method = "javascript:analyzeFlightLegs(" + value + "," + aircraftId + ")";
                            return "<a onclick='" + method + "'>" + value + "</a>";
         
                        } else {
                            return '-';
                        }
                    }
         
                    function analyzeFlightLegs(flightLegIds, aircraftId) {
                        var url = "FlightAnalysis.php?aircraftId=" + aircraftId + "&flightLegs=" + flightLegIds+"&mainmenu=LOPA";
                        var win = window.open(url, '_self');
                        win.focus();
                    }
         
                    function toggleIcon(e) {
                        $(e.target)
                            .prev('.panel-heading')
                            .find(".more-less")
                            .toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
                    }
                    $('.panel-group').on('hidden.bs.collapse', function toggleEvent(e) {
                        document.getElementById("collapseOne").style.display = "none";
                    });
                    $('.panel-group').on('shown.bs.collapse', function toggleEvent(e) {
                        document.getElementById("collapseOne").style.display = "block";
                    });
                    $(".collapse").on('show.bs.collapse', function() {
                        document.getElementById("advancedFilter").style.display = "block";
                        $(this).parent().find(".glyphicon-chevron-right").removeClass("glyphicon-chevron-right").addClass("glyphicon-chevron-down");
                    }).on('hide.bs.collapse', function() {
                        document.getElementById("advancedFilter").style.display = "none";
                        $(this).parent().find(".glyphicon-chevron-down").removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-right");
                    });
         
                    function getInfoModal() {
                        console.log('image clicked');
                    }	
                    getMonitorState();
                    
                    function getMonitorState() {
                        $.ajax({
                            type: "GET",
                            url: "../ajax/getBiteData.php",
                            data: {
                                'action': 'getMonitorState'
                            },
                            success: function(data) {
                                if (data) {
                                    var resetlist = JSON.parse(data);
                                    for (var i = 0; i < resetlist.length; i++) {
                                        var ts = resetlist[i];
                                        $("#monitorState").append('<option value=' + ts.value + '>' + ts.label + '</option>');
                                    }
                                    if(<?php echo $_REQUEST ['lopaVisited'];?>){                                	
                                    	var string = "<?php echo $_SESSION['monitorState'];?>";
                                    	var array = string.split(",");
                                    	if(array!=''){
                                        	var monitorState=[];
                                        	for(r in array){												
												var str = array[r];
												str = str.substr(1).slice(0, -1);
												monitorState.push(str);
                                            }                                        	
                                    		$('#monitorState').val(monitorState);		
                                        	$('#monitorState').selectpicker('refresh');
                                        }else{
                                        	$('#monitorState').val([1,3]);
                                            $('#monitorState').selectpicker('refresh');
                                        }                                    	
                                    }else{
                                        $('#monitorState').val([1,3]);
                                        $('#monitorState').selectpicker('refresh');
                                    }
                                   // $('#resetsValue').multiselect('rebuild');
                                }
         
                            },
                            error: function(err) {
                                console.log('Error', err);
                            }
                        });
                    }
                       	
                
      </script>
	<!-- END SCRIPTS -->
	
	<script src="../controllers/userInformationCtrl.js"></script>
</body>
</html>
