<?php
session_start ();
$menu = 'ConnectivityUpload';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "30 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

MongoCursor::$timeout = -1;

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];


if(isset($aircraftId)) {
    checkAircraftPermission($dbConnection, $aircraftId);
}

if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.isp FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
      $airlineName = $row['name'];
      $dbName = $row['databaseName'];
	  $aircraftIsp = $row['isp'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $airlineName = $row['name'];
    $dbName = $row['databaseName'];
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

// STO working for only one flight leg... Need to see how to do it for multiple flight legs...
$query = "SELECT createDate, lastUpdate FROM $dbName.SYS_flight WHERE idFlightLeg IN ($flightLegs) LIMIT 1";
$result = mysqli_query($dbConnection, $query);

if($result) {
	$row = mysqli_fetch_array ( $result );
	$start = $row['createDate'];
	$end = $row['lastUpdate'];
	
	//echo "$start - $end"; exit;
	
	$connectivityActivity = $db->connectivityActivity;
	$where=array('$and' => array(	array("timestamp" => array('$gte' => $start, '$lte' => $end)),
									array("tailSign" => $aircraftTailsign)));
									
	$cursor = $connectivityActivity->find(
		$where,
		array("timestamp" => 1, "TGS_FLIGHT.flightPhase" => 1, "TGS_FLIGHT.altitude" => 1)
	)->sort(array("timestamp" => 1));
	
	//Smita
	$cursor->timeout(-1);
	
	$data = array();
	if($cursor->count() > 0) {		
		foreach($cursor as $document) {
			$flightPhase = $document['TGS_FLIGHT']['flightPhase'];
			
			switch($flightPhase) {
				case "preflight":
				case "postflightground";
					$flightPhase = "Ground";
					break;
				case "taxiout";
				case "taxiin";
					$flightPhase = "Taxi";
					break;
				case "climb":
				case "descentapproach":
					$flightPhase = "Climb/Descent";
					break;
				case "cruise":
					$flightPhase = "Cruise";
					break;
				default:
					$flightPhase = "";
			}		
			
			$newData = array(
				'time' => $document['timestamp'],
				'flightPhase' => $flightPhase,
				'altitude' => $document['TGS_FLIGHT']['altitude']
			);		
			
			//var_dump($newData); echo "<br><br>";
			$data[]	= $newData;				
		}
		//var_dump($data); exit;
	} else {
		$displayNoDataAlert = true;
	}
} else {
	echo "Error with query: $query"; exit;
}


//get the wifi and Omts availability data
function getWifiOmtsAvailabilityData($dbConnection,$collection,$flightLegs, $aircraftTailsign)
{		
	//echo "$flightLegs / $aircraftTailsign";
	$totalWifiOmtsAvailbilityPercentage = array();
	
	$where=array('$and' => array(	array("idFlightLeg" => array('$eq' => $flightLegs)),
									array("tailSign" => $aircraftTailsign)));
	
	/*
	$where = array("$and" => array(
		array('idFlightLeg' => $flightLegs),
		array("tailSign" => $aircraftTailsign)
	));
	*/
	
	$fields = array('wifiAvailability.totalOnPercentage',
					'wifiAvailability.manualPercentageOn',
					'omtsAvailability.totalOnPercentage',
					'omtsAvailability.manualPercentageOn',					
					'idFlightLeg');
	
	$cursor = $collection->find($where,$fields);
	//Smita
	$cursor->timeout(-1);
	
	//echo $cursor->count()."<br><br>";
	foreach ($cursor as $doc) 
	{
		//echo $doc['wifiAvailability']['manualPercentageOn'] . "/" . $doc['omtsAvailability']['manualPercentageOn'] ; exit;
		
		if(($doc['wifiAvailability']['totalOnPercentage'] !== NULL))
		{
			//echo $doc['wifiAvailability']['totalOnPercentage']; exit;
			//echo $doc['wifiAvailability']['totalOnPercentage'] . "<br>";
			$totalWifiOmtsAvailbilityPercentage['computedWifiPercentage'] = round($doc['wifiAvailability']['totalOnPercentage'],2);
			//echo $totalWifiOmtsAvailbilityPercentage['computedWifiPercentage'];
		}
		if(($doc['wifiAvailability']['manualPercentageOn'] != NULL))
		{
			$totalWifiOmtsAvailbilityPercentage['overriddenWifiPercentage'] = round($doc['wifiAvailability']['manualPercentageOn'],2);

		}		
		
		if(($doc['omtsAvailability']['totalOnPercentage'] !== NULL))
		{
			$totalWifiOmtsAvailbilityPercentage['computedOmtsPercentage'] = round($doc['omtsAvailability']['totalOnPercentage'],2);

		}
		if($doc['omtsAvailability']['manualPercentageOn'] != NULL )
		{
			$totalWifiOmtsAvailbilityPercentage['overriddenOmtsPercentage'] = round($doc['omtsAvailability']['manualPercentageOn'],2);

		}		

	}	

 return $totalWifiOmtsAvailbilityPercentage;		
}

//get the Root cause Wifi for the Flight leg
function getWifiRootCauseForFlightLeg($dbConnection,$collection,$flightLegs)
{
	$where=array("idFlightLeg" => array('$in' => $flightLegs));

	$cursor = $collection->find($where);

	//Smita
	$cursor->timeout(-1);
	
	foreach ($cursor as $doc) 
	{
		if(is_array($doc['wifiAvailabilityEvents']) && count($doc['wifiAvailabilityEvents']) > 0)
				{
					foreach($doc['wifiAvailabilityEvents']  as $temp)
					{	
						if($temp['computedFailure'] != ''){
							if($temp['manualFailureEntry'] != ''){
								$rootCauseStringWifi = $rootCauseStringWifi . $temp['manualFailureEntry'] . "\n";
							}else{
								$rootCauseStringWifi = $rootCauseStringWifi . $temp['computedFailure'] . "\n";
							}
							
						}
						if($temp['computedFailure'] == ''){
							if($temp['manualFailureEntry'] != ''){
								$rootCauseStringWifi = $rootCauseStringWifi . $temp['manualFailureEntry'] . "\n";
							}
						}
					}

				}
				
	}	
	
	return $rootCauseStringWifi;	
}

//get the Root Cause OMTS for the Flight leg
function getOmtsRootCauseForFlightLeg($dbConnection,$collection,$flightLegs)
{
						
	$where=array("idFlightLeg" => array('$in' => $flightLegs));

	$cursor = $collection->find($where);
//Smita
	$cursor->timeout(-1);
	foreach ($cursor as $doc) 
	{		

		if(is_array($doc['omtsAvailabilityEvents']) && count($doc['omtsAvailabilityEvents']) > 0)
				{
				
					foreach($doc['omtsAvailabilityEvents']  as $temp)
					{	
					
						if($temp['computedFailure'] != ''){		

							if($temp['manualFailureEntry'] != ''){
								$rootCauseStringOmts = $rootCauseStringOmts . $temp['manualFailureEntry'] . "\n";
							}else{
								$rootCauseStringOmts = $rootCauseStringOmts . $temp['computedFailure'] . "\n";
							}	

						}
						if($temp['computedFailure'] == ''){		
							if($temp['manualFailureEntry'] != ''){
								$rootCauseStringOmts = $rootCauseStringOmts . $temp['manualFailureEntry'] . "\n";
							}
									
						}	
									
					}
				}							
	}	
	
	return $rootCauseStringOmts;	
}

$flightLegsArray = getFlightInArray($flightLegs);

//remove duplicates in wifi RC
$rootCauseStringWifi = getWifiRootCauseForFlightLeg($dbConnection,$collection,$flightLegsArray);
$rootCauseStringWifiArray = explode("\n",$rootCauseStringWifi);
$rootCauseStringWifiUnique = array_unique($rootCauseStringWifiArray);
// Remove restricted area fault from list
$index = array_search('Restricted Area ',$rootCauseStringWifiUnique);
if($index !== FALSE){
    unset($rootCauseStringWifiUnique[$index]);
}

//remove duplicates in omts RC
$rootCauseStringOmts = getOmtsRootCauseForFlightLeg($dbConnection,$collection,$flightLegsArray);
$rootCauseStringOmtsArray = explode("\n",$rootCauseStringOmts);
$rootCauseStringOmtsUnique = array_unique($rootCauseStringOmtsArray);
// Remove restricted area fault from list
$index = array_search('Restricted Area ',$rootCauseStringOmtsUnique);
if($index !== FALSE){
    unset($rootCauseStringOmtsUnique[$index]);
}

function str_replace_json($search, $replace, $subject) 
{
    return json_decode(str_replace($search, $replace, json_encode($subject)), true);
}

$rootCauseStringOmtsUnique = str_replace_json('Restricted Area ', '', $rootCauseStringOmtsUnique);
//var_dump($rootCauseStringOmtsUnique);



$computedPercentage = array();
$computedPercentage = getWifiOmtsAvailabilityData($dbConnection,$collection,$flightLegs, $aircraftTailsign);
$flightLegsCount = count($flightLegsArray);

?>
<!DOCTYPE html>
<html lang="en" >
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link rel="stylesheet" href="../css/dataTables/datatables.min.css">	
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.2/css/fixedHeader.dataTables.min.css"/>
<script src="../js/dataTables/datatables.min.js"></script>
<script src="../js/moment/moment.min.js"></script>
</head>
<style>
		.ct-series-a .ct-slice-donut 	{
											/* give the donut slice a custom colour */
											stroke: #78AB46;
										}
		
		.ct-series-b .ct-slice-donut 	{
											/* give the donut slice a custom colour */
											stroke: #ff3333;
										}
		
		.ct-label {fill:rgba(255,255,255,1);color:rgba(0,0,0,.4);font-size:1.5rem;font-family:"Bookman Old Style";line-height:1}		
		.axis path,.axis line {
			fill: none;
			stroke:#b6b6b6;
			shape-rendering: crispEdges;
		}		
		.tick text {
			font-family: Arial, sans-serif;
			font-size:12px;
			fill:#888;
		}
		
		.grid .tick {
			stroke: lightgrey;
			opacity: 0.7;
		}
		.grid path {
			  stroke-width: 0;
		}
</style>
<body >
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
						class="fa fa-dedent"></span></a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Connectivity Upload</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Connectivity Upload</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
						            <h2 class="page-header">Connectivity</h2>
									<?php
										if($displayNoDataAlert) {
											echo "<div class=\"alert alert-warning\" role=\"alert\">
												  <span class=\"glyphicon glyphicon-exclamation-sign\" aria-hidden=\"true\"></span>
												  <span class=\"sr-only\">Error:</span>
												  No connectivity data has been uploaded for this flight.
												</div>
												<br>";
										}
									?>	
									<div class="row placeholders">
										<div class="col-md-6" id="WifiDataChart" style="height: 125px" > 
											<h4><strong>WIFI Availability</strong></h4>
											<br>
											<span id="contentDivWifi"> [Computed Value : 
													
															<?php 										
																if($computedPercentage['computedWifiPercentage'] !== NULL ){
																	echo $computedPercentage['computedWifiPercentage'] . "%";
																}else{
																	echo "N/A";
																}
															?> / Overridden Value : 
															<?php 
																if($computedPercentage['overriddenWifiPercentage'] !== NULL){
																	echo $computedPercentage['overriddenWifiPercentage'] . "%";
																}else{
																	echo "N/A";
																}
															?>]</span>
											<img src="../img/edit.png" title="Edit WIFI Availability %" onclick='return updateAvailabilityData(<?php echo "\"WIFI\""; ?>);'>
											<br><br>
											<div class="ct-chart"></div>
											<div id="NoDataWifiAvailable">
												<img src="../img/ajaxLoading.gif"> Loading Wifi Availability...
											</div>					
										</div>				
										<div class="col-md-6"  id="OmtsDataChart" style="height: 125px">
											<h4><strong>OMTS Availability</strong></h4>
											<br>
											<span  id="contentDivOmts">[Computed Value : 
															<?php 										
																if($computedPercentage['computedOmtsPercentage'] !== NULL){
																	echo $computedPercentage['computedOmtsPercentage'] . "%";
																}else{
																	echo "N/A";
																}
															?> / Overridden Value : 
															<?php 
																if($computedPercentage['overriddenOmtsPercentage'] !== NULL){
																	echo $computedPercentage['overriddenOmtsPercentage'] . "%";
																}else{
																	echo "N/A";
																} 
															?>]</span>
											<img src="../img/edit.png" title="Edit OMTS Availability %" onclick='return updateAvailabilityData(<?php echo "\"OMTS\""; ?>);'>
											<br><br>
											<div class="ct-chartOmtsOff"></div>
											<div id="NoDataOmtsAvailable">
												<img src="../img/ajaxLoading.gif"> Loading Omts Availability...
											</div>						
										</div>
									</div>
									<br>
									<br>
									<div class="row placeholders" id="rootCauseHolder">
										<div class="col-md-6">
											<div class="panel panel-default">
											  <div class="panel-body" style="background:#FCFCFC">						
												<h4 style="color:grey"><strong>Wifi Root Causes</strong></h4>
												<br>
												<div>
													<?php 
														$i = 0;
														if(count($rootCauseStringWifiUnique) > 1){
															foreach($rootCauseStringWifiUnique as $WifiRc){									
																if($WifiRc != ""){
																	if($i > 0) {
																		echo " / ";
																	}
																	echo $WifiRc;
																	$i++;
																}
															}
														}elseif(count($rootCauseStringWifiUnique) > 0){									
															foreach($rootCauseStringWifiUnique as $WifiRc){
																if($WifiRc == ""){
																	echo '<i>No Failure</i>';
																}
															}
														}else{
															echo '<i>No Failure</i>';
														}
													?>
												</div>                    
											  </div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="panel panel-default">
											  <div class="panel-body" style="background:#FCFCFC">
												<h4 style="color:grey"><strong>OMTS Root Causes</strong></h4>
												<br>						
												<div>
													<?php 
														$i = 0;
														if(count($rootCauseStringOmtsUnique) > 1){
															foreach($rootCauseStringOmtsUnique as $OmtsRc){
																if($OmtsRc != ""){
																	if($i > 0) {
																		echo " / ";
																	}
																	echo $OmtsRc;
																	$i++;
																}									
															}
														}elseif(count($rootCauseStringOmtsUnique) > 0){									
															foreach($rootCauseStringOmtsUnique as $OmtsRc){
																if($OmtsRc == ""){
																	echo '<i>No Failure</i>';
																}
															}
														}else{
															echo '<i>No Failure</i>';
														}
													?>
												</div>                    
											  </div>
											</div>
										</div>
									</div>
									<div>
										<div id="flightProfile"></div>
									</div>
									<br>
									<br>
						            <div id="connectivityTimeline"></div>
						            <div id="loadingConnectivityTimeline">
						                <img src="../img/ajaxLoading.gif"> Loading Timeline...
						            </div>
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

	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>	
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
</body>

<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<script>

$(document).ready(function(){
    $('.nav-sidebar li').removeClass('active');
    $("#sideBarConnectivity").addClass("active");
	
    data = {
        <?php 
            if($aircraftId != '')  {
                echo "aircraftId: $aircraftId";
            }
            else {
                echo "sqlDump: '$sqlDump'";
            }
            echo ",
                flightLegs: '$flightLegs'";
        ?>,
        connectivityTimeline: true
    };


    console.log('loading connectivity');
    $.ajax({
        type: "GET",
        dataType: "json",
        url: "../ajax/getAircraftTimeLineData.php",
        data: data,
        success: function(data) {
            console.log(data);
            createTimeline(data, 'connectivityTimeline', 'loadingConnectivityTimeline');
        },
        error: function (err) {
            console.log('Error', err);
        }
    });
	
		
	dataForAvailability = {
			<?php 
				if($aircraftId != '')  {
					echo "aircraftId: $aircraftId";
				}
				else {
					echo "sqlDump: '$sqlDump'";
				}
				echo ",
					flightLegs: '$flightLegs'";
			?>,
			connectivityTimeline: true
		};

	
	$.ajax({
        type: "GET",
        dataType: "json",
        url: "../ajax/getConnectivityAvailability.php",
        data: dataForAvailability,
        success: function(data) {
			console.log(data);
            updateChart(data,'NoDataWifiAvailable','NoDataOmtsAvailable');
        },
        error: function (err) {
            console.log('Error', err);
        }
    });
	
});

	
function updateAvailabilityData(dataType)
{	
	if (dataType == "WIFI")
	{
		var number = parseFloat(window.prompt("Enter The WIFI Availibility Percentage Value", "Please enter a number from 1 to 100"));		
	}
	else
	{
		var number = parseFloat(window.prompt("Enter The OMTS Availibility Percentage Value", "Please enter a number from 1 to 100"));
	}
	
	
    if (number <= 100 && number >= 0) 
	{
		var flightLegs = '<?php echo $flightLegs;?>';
		
		data = {
					number : number,
					flightLegs : flightLegs,
					dataType : dataType,
					connectivityTimeline: true
				};
			
				$.ajax({
				type: "GET",
				dataType: "json",
				url: "../ajax/writeDataOnToDatabase.php",
				data: data,
				success: function(data) {
					console.log(data);
					
					if(dataType == "WIFI") {
						$("#contentDivWifi").html("[Computed Value : "+data.wifiComputed + "% / Overridden Value : "+data.wifiOverridden + "%]");
						$("#NoDataWifiAvailable").load("#NoDataWifiAvailable");
					} else if(dataType == "OMTS") {
						$("#contentDivOmts").html("[Computed Value : "+data.omtsComputed + "% / Overridden Value : "+data.omtsOverridden + "%]");
						$("#NoDataOmtsAvailable").load("#NoDataOmtsAvailable");
					}
				},
				error: function (err) {
					console.log('Error', err);
				}
			});
		
	}
	else
	{
		alert("Please Provide a Valid Numbers between 0 to 100");
	}
}

function updateChart(data,noDataWifiAvailable,noDataOmtsAvailable){
		if (data.WifiOnAvailability != null && data.WifiOnAvailability != -1)
		{
			$('#'+noDataWifiAvailable).hide();
			new Chartist.Pie('.ct-chart', {
				  series: [data.WifiOnAvailability, data.WifiOffAvailability]
				}, {
				  donut: true,
				  donutWidth: 50,
				  startAngle: 270,
				  total: 200,
				  showLabel: true
				});	
		}
		else
		{			
			$("#"+noDataWifiAvailable).html("<em>No availability</em>");
		}
		
		if (data.OmtsOnAvailability != null && data.OmtsOnAvailability != -1)
		{
			$('#'+noDataOmtsAvailable).hide();
			new Chartist.Pie('.ct-chartOmtsOff', {
				  series: [data.OmtsOnAvailability, data.OmtsOffAvailability]
				}, {
				  donut: true,
				  donutWidth: 50,
				  startAngle: 270,
				  total: 200,
				  showLabel: true
				});	
		}
		else
		{
			$("#"+noDataOmtsAvailable).html("<em>No availability</em>");
		}
		
}


function createTimeline(data, timelineId, loadingId) {
    $('#'+loadingId).hide();

    var container = document.getElementById(timelineId);

    var groups = new vis.DataSet(
        data.groups
        );

    var items = new vis.DataSet(
        data.items
        );

    var options = {
        orientation: 'both',
        start: data.options.start,
        end: data.options.end,
        min: data.options.min,
        max: data.options.max,
        clickToUse: true,
        stack: false,
        multiselect: true
    };

    timeline = new vis.Timeline(container, items,  groups, options);
	
	//Root Cause Manual Entry
	timeline.on('contextmenu', function (props) {
	
		for(i in props){
			if(i == 'item'){
				//alert(i + "=" +props[i]); // debuging usage
				if(props[i] != ""){
					if(props[i].length > 0) {
						var event = props[i];
						var res = event.split("/")
						if(res[0] == "CON") {
							var connectivityObjectType = res[1]; 
							var aircraftId = <?php echo $aircraftId; ?>;
							var start = res[2];
							var end = res[3];
							var rootCauseManualEntry = window.prompt("Enter The manual Root Cause for the Failure");	
							data = {
								rootCause : rootCauseManualEntry,
								rootCauseStartTime : start,
								rootCauseEndTime	: end,
								rootCauseDataType : connectivityObjectType,
								rootCauseUpdated: true
							};
							
							$.ajax({
								type: "GET",
								dataType: "json",
								url: "../ajax/writeDataOnToDatabase.php",
								data: data,
								success: function(data) {
									console.log(data.rootCauseUpdated);
									location.reload();
								},
								error: function (err) {
									console.log('Error', err);
								}
							});
						}			
					}	
				}					
			}
		}
		props.event.preventDefault();
    });

	//view connectivity activity view page with mondo db logs for the event
    timeline.on('select', function (properties) {
		if(properties.items.length > 0) {
			var event = properties.items[0];
			var res = event.split("/")
			if(res[0] == "CON") {
				//var connectivityObjectType = res[1]; // not used
				var aircraftId = <?php echo $aircraftId; ?>;
				var start = res[2];
				var end = res[3];
		
				var url = "connectivityActivityView.php?aircraftId=" + aircraftId + "&start=" + start + "&end=" + end + "&flightLegs=<?php echo $flightLegs ?>";	
				var win = window.open(url, '_blank');
				win.focus();
			}			
		}		
		
		
        
    });

}
</script>
<script>
	// Inspiration:
	// http://bl.ocks.org/d3noob/e34791a32a54e015f57d
	// http://stackoverflow.com/questions/15471224/how-to-format-time-on-xaxis-use-d3-js
	// http://tributary.io/inlet/5186053
		
	var data = <?php echo json_encode($data); ?>;

    var profileWidth = document.getElementById("flightProfile").offsetWidth;
	
    var margin = {top: 30, right: 100, bottom: 30, left: 60};
    var width = profileWidth - margin.left - margin.right;
	//var width = window.innerWidth - margin.left - margin.right;	
    var height = 250 - margin.top - margin.bottom;
    var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;
    var x = d3.time.scale()
        .range([0, width]);

    var y = d3.scale.linear()
        .range([height, 0]);
		
	var z = d3.scale.ordinal()
		.domain(["Ground","Taxi","Climb/Descent","Cruise"])
        .rangePoints([height, 0]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom")
		.ticks(15)		
		.tickFormat(d3.time.format("%H:%M"));

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left");
		
	var zAxis = d3.svg.axis()
        .scale(z)		
        .orient("right")
		.tickValues(["Ground","Taxi","Climb/Descent","Cruise"]);

    var altitudeLine = d3.svg.line()
        .x(function(d) { return x(d.time); })
        .y(function(d) { return y(d.altitude); });

	var flightPhaseLine = d3.svg.line()
        .x(function(d) { return x(d.time); })
        .y(function(d) { return z(d.flightPhase); });
 
    var svg = d3.select("#flightProfile").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    data.forEach(function(d) {
        d.time = parseDate(d.time);
        d.altitude = +d.altitude;
    });

    x.domain(d3.extent(data, function(d) { return d.time; }));
    y.domain(d3.extent(data, function(d) { return d.altitude;}));
	//z.domain(d3.extent(data, function(d) { return d.flightPhase;}));

	svg.append("g")         
        .attr("class", "grid")
        .attr("transform", "translate(0," + height + ")")
        .call(make_x_axis()
            .tickSize(-height, 0, 0)
            .tickFormat("")
        )

    svg.append("g")         
        .attr("class", "grid")
        .call(make_y_axis()
            .tickSize(-width, 0, 0)
            .tickFormat("")
        )
	
    svg.append("g")
          .attr("class", "x axis")
          .attr("transform", "translate(0," + height + ")")
          .call(xAxis);

    svg.append("g")
          .attr("class", "y axis")
          .call(yAxis)
          .append("text")
          //.attr("transform", "rotate(-90)")
          .attr("y", -25)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Altitude")		  
		  .style("fill", "#1f77b4")
		  .style("font-family", "Arial")
		  .style("font-size", "12px")
		  .style("font-weight", "bold");	  
		  
	svg.append("g")
          .attr("class", "y axis")
		  .attr("transform", "translate(" + width + ",0)")
          .call(zAxis)
          .append("text")
          //.attr("transform", "rotate(-90)")		  
          .attr("x", 75)
		  .attr("y", -25)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Flight Phase")
		  .style("font-family", "Arial")
		  .style("font-size", "12px")
		  .style("font-weight", "bold");
	
	// Draw flight phase line
    svg.append("path")
		.datum(data)
		.attr("class", "line")
		.style("fill", "none")
		.style("stroke", "555")
		.attr("d", flightPhaseLine);
		
	// Draw altitude line
    svg.append("path")
		.datum(data)
		.attr("class", "line")
		.style("fill", "none")
		.style("stroke", "#1f77b4")
		.style("stroke-width", "3")
		.attr("d", altitudeLine);
		
	function make_x_axis() {        
		return d3.svg.axis()
			.scale(x)
			.orient("bottom")
			.ticks(20)
	}

	function make_y_axis() {        
		return d3.svg.axis()
			.scale(y)
			.orient("left")
			.ticks(10)
	}
</script>
</html>






