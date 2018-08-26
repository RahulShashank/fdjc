<?php
session_start ();
$menu = 'ConnectivityStatus';
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
include ("BlockCustomer.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

$firstTime = $_REQUEST['firstTime'];
error_log('First time : '.$firstTime);
$aircraftId = $_REQUEST['aircraftId'];

//SB:code added for date filter
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

$airlineId = $_REQUEST['airlineId'];
$tailsign = $_REQUEST['tailsign'];
$firstTime = $_REQUEST['firstTime'];

if($firstTime){
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
	} else {
	    echo "Error - no aircraftId";
	    exit;
	}
	
	if($aircraftIsp!="KaNoVAR"){
		require_once "../database/connecti_mongoDB.php";
		// Find latest date
		$cursor = $collection->find(array("tailSign" => $aircraftTailsign), array("startTime"))->sort(array("startTime" => -1))->limit(1);
		//var_dump($cursor->getNext());exit;
		
		$fields = array('_id',
						'startTime',
						'idFlightLeg',
						'cityPair',
						'flightNumber',
						'test',
						'flightFailure',
						'altitudeEvent.startTime',
						'altitudeEvent.endTime',
						'wifiAvailabilityEvents.manualFailureEntry',
						'wifiAvailabilityEvents.computedFailure',
						'omtsAvailabilityEvents.computedFailure',
						'omtsAvailabilityEvents.manualFailureEntry',
						'wifiAvailability.totalOnPercentage',
						'wifiAvailability.manualPercentageOn',
						'omtsAvailability.totalOnPercentage',
						'wifiAvailability.manualPercentageOn',
						'wifiAvailabilityEvents.description');
						
		if($startDate == '' && $endDate == ''){		
			$cursor = $collection->find(array("tailSign" => $aircraftTailsign),$fields)->sort(array("startTime" => 1));
			//get current date
			$endDate=date('Y-m-d H:i:s');
			$startDate1 = date_create("$endDateTime");												
			date_sub($startDate1,date_interval_create_from_date_string("6 days"));
			$startDate = date_format($startDate1,"Y-m-d H:i:s");
			
			$where = array('$and' => array(	array("startTime" => array('$gte' => $startDateTime,'$lte' => $endDateTime )),
										array("tailSign" => $aircraftTailsign)));
			$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
		
		}else{
			$where = array('$and' => array(	array("startTime" => array('$gte' => $startDate,'$lte' => $endDate )),
										array("tailSign" => $aircraftTailsign)));
			$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
		}
	}else{
		require_once "../database/connecti_mongoDB_Ka.php";
		// Find latest date
		$cursor = $collection->find(array("tailSign" => $aircraftTailsign), array("startTime"))->sort(array("startTime" => -1))->limit(1);
		//var_dump($cursor->getNext());exit;
		
		$fields = array('_id',
						'startTime',
						'idFlightLeg',
						'cityPair',
						'flightNumber',
						'test',
						'flightFailure',
						'altitudeEvent.startTime',
						'altitudeEvent.endTime',
						'wifiAvailabilityEvents.manualFailureEntry',
						'wifiAvailabilityEvents.computedFailure',
						'omtsAvailabilityEvents.computedFailure',
						'omtsAvailabilityEvents.manualFailureEntry',
						'wifiAvailability.totalOnPercentage',
						'wifiAvailability.manualPercentageOn',
						'omtsAvailability.totalOnPercentage',
						'wifiAvailability.manualPercentageOn',
						'bandwidthTX',
						'bandwidthRX',
						'wifiAvailabilityEvents.description');
						
		if($startDate == '' && $endDate == ''){		
			//$cursor = $collection->find(array("tailSign" => $aircraftTailsign),$fields)->sort(array("startTime" => 1));
			//get current date
			$endDate=date('Y-m-d H:i:s');
			$startDate1 = date_create("$endDateTime");												
			date_sub($startDate1,date_interval_create_from_date_string("6 days"));
			$startDate = date_format($startDate1,"Y-m-d H:i:s");
			
			$where = array('$and' => array(	array("startTime" => array('$gte' => $startDateTime,'$lte' => $endDateTime )),
										array("tailSign" => $aircraftTailsign)));
			$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
		
		}else{
			$where = array('$and' => array(	array("startTime" => array('$gte' => $startDate,'$lte' => $endDate )),
										array("tailSign" => $aircraftTailsign)));
			//var_dump($where);							
			$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
			//var_dump($cursor);
		}
	}
}

// iterate cursor to display title of documents
//SB:Code added to get aircraftId as per the timestamp.
function getFlightLegId($timeStamp,$dbConnection,$dbName)
{
	$query = "SELECT idFlightLeg FROM $dbName.SYS_flight WHERE createDate <= '$timeStamp' AND lastUpdate >= '$timeStamp' ";
	error_log('getFlightLegId : '.$query);
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $idFlightLeg = $row['idFlightLeg'];
    } 
	return $idFlightLeg;	
}
// iterate cursor to display title of documents

?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
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
</head>
<style>
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}
</style>
<body id="ctrldiv" ng-controller="ConnectivityStatusController">
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
				<li class="active">Connectivity Status</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Connectivity Status</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">							
							<div class="panel-body">
								<div class="row">
									<div class="col-md-2">
                						<label for="airlinetable">Airline</label>
            							<select id="airlinetable" class="selectpicker show-tick" data-width="100%" data-selected-text-format="count > 3" data-live-search="true"></select>
                					</div>
                					<div class="col-md-2">
                						<label for="tailsigntable">Tailsign</label>
            							<select id="tailsigntable" class="selectpicker show-tick" data-width="100%" 
            							data-selected-text-format="count > 3" data-live-search="true"></select>
                						<input type="hidden" id="aircraftIdTable" name="aircraftIdTable" />
                					</div> 
									<div class="col-md-2">										
										<label for="startDateTimePicker">From</label>
										<input class="form-control dateChange" id="startDateTimePicker"	type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>																				
									</div>
									<div class="col-md-2">										
										<label for="endDateTimePicker">To</label>
										<input class="form-control dateChange" id="endDateTimePicker" type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>											
									</div>	
									<!--div style="padding-left: 0px;margin-top: 21px;margin-left: 15px;">
										<button id="filterbtn" class="btn btn-primary"	style="margin-left: 5px;" >Filter</button> 
										<button id="reset" type="button" class="btn btn-primary" style="margin-left: 5px;" >Reset</button>
									</div-->
									<div class="col-md-4">
                						<label for="buttons">&nbsp;&nbsp;</label>
                						<div>
                							<button id="filterbtn" class="btn btn-primary">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset"">Reset</button>
                						</div>
                					</div>							
								</div>
								<br/>								
							</div>
						</div>
					</div>
				</div>

			<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">							
							<div class="panel-body text-center">
								<?php
									$isDataAvailable=false;
									if (isset ( $cursor )) {
										foreach ($cursor as $document) {
											if($document['startTime']!=''){
												$isDataAvailable=true;
												break;
											}
										}
									}
									if($aircraftIsp!="KaNoVAR"){										
										if($isDataAvailable){
											include("connectivityStatusTable.php");
										}else{
											echo "<label class=\"noData-label\"> No data available for the selected duration or selected filters </label>";
										}
									}else{
										if($isDataAvailable){
											include("kaConnectivityStatusTable.php");
										}else{
											echo "<label class=\"noData-label\"> No data available for the selected duration or selected filters </label>";
										}
									}
								?>
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
</body>
<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
<script type="text/javascript" src="../js/plugins.js"></script>
<script type="text/javascript" src="../js/actions.js"></script>
<script type="text/javascript" src="../controllers/ConnectivityStatusController.js"></script>
<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<link href="../css/dropzone.css" type="text/css" rel="stylesheet" />
<script src="../js/dropzone.js"></script>
<script type="text/javascript">	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDatetime = formatDate(priorDate);
	var endDatetime = formatDate(today);
	var session_airline='<?php echo "$airlineId";?>';
	var session_tailsign='<?php echo "$tailsign";?>';
	var session_firstTime='<?php echo "$firstTime";?>';
	
	$(document).ready(function(){
			var firstTime=true;
			var endDate= "<?php echo "$endDate";?>";
			var startDate= "<?php echo "$startDate";?>"; 
			
			if(endDate=="" && startDate==""){
				$('#startDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: startDatetime
				});
				
				$('#endDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: endDatetime
				});
			}else{			
				$('#startDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: startDate
				});
				
				$('#endDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: endDate
				});
			}
			
	}); 
	$("#filterbtn").click(function(event){	
		getConnectivityStatus();		
	});

	function getConnectivityStatus(){		
		data = {        
				aircraftId: $('#aircraftIdTable').val(),
				startDateTime: $("#startDateTimePicker").val(), 
				endDateTime: $("#endDateTimePicker").val(),
				firstTime:false
			};
			firstTime=false;
			var aircraftId= $('#aircraftIdTable').val();
			var tailsignfortable= $('#tailsigntable').val();
			var airlineIdfortable= $('#airlinetable').val();
			var startDate = $("#startDateTimePicker").val();
			var endDate = $("#endDateTimePicker").val();
		    //call same URL with start and end date as parameters in addition to aircraftId			
			window.location.href = "ConnectivityStatus.php?aircraftId="+aircraftId+"&startDate="+startDate+"&endDate="+endDate+"&firstTime="+firstTime+"&tailsign="+tailsignfortable+"&airlineId="+airlineIdfortable;

		    // To remove the white space below the page content
			page_content_onresize();			
	}

	$("#reset").click(function(event){	
		window.location.href = "ConnectivityStatus.php";
	});

	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}

	</script>
</html>





