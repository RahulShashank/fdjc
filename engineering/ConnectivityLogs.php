<?php
session_start ();
$menu = 'ConnectivityLogs';
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
//require_once "../database/connecti_mongoDB.php";
require_once("../map/airports.php");

require_once("checkEngineeringPermission.php");
include ("BlockCustomer.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "30 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$aircraftId = $_REQUEST['aircraftId'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

$airlineId = $_REQUEST['airlineId'];
$tailsign = $_REQUEST['tailsign'];
$firstTime = $_REQUEST['firstTime'];
$start = $_REQUEST['startDate'].' 00:00:00';
$end = $_REQUEST['endDate'].' 23:59:59';

if($firstTime!=""){
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
	
	if($aircraftIsp!="KaNoVAR"){
		require_once "../database/connecti_mongoDB.php";
		//SB:Get data for logsView
		//$collection = $db->connectivityActivity;
		$cursorHeader = $collectionActivity->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)))->limit(2);
		$cursorBody = $collectionActivity->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)));
		
		if($cursorBody->count() == 0) {
			$displayNoDataAlert = true;
		}
	}else{
		require_once "../database/connecti_mongoDB_Ka.php";
		if( ($start == '') && ($end == '') ) {
			// Get flight leg start time and end time - work for only one flight leg...
			$query = "SELECT createDate, lastUpdate FROM $dbName.SYS_flight WHERE idFlightLeg IN ($flightLegs) LIMIT 1";	
			$result = mysqli_query($dbConnection, $query);
			
			if($result) {
				$row = mysqli_fetch_array($result);
				$start = $row['createDate'];
				$end = $row['lastUpdate'];		
			} else {
				echo "error with query $query"; exit;
			}
		}
		
		$collection = $db->Ka_connectivityActivity;
		$cursorHeader = $collection->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)))->limit(1);
		$cursorBody = $collection->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)));
		
		if($cursorBody->count() == 0) {
			$displayNoDataAlert = true;
		}
	}

}
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
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
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
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}
.bootstrap-table .table > thead > tr > th {
    vertical-align: bottom;
    border-bottom: 0px solid #ddd;
}
	/* style for fixed header and scrollable body table */
    .fixed-table-body {
        overflow-x: auto;
        overflow-y: auto;
        height: 100% !important;
    }
    
    .fixed-table-header {
        margin-right: 15px;
    }
</style>
<body id="ctrldiv" ng-controller="ConnectivityLogsController">
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
				<li class="active">Connectivity Logs</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Connectivity Logs</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">							
							<div class="panel-body">
								<div class="row">
									<div class="col-md-2">
										<input type="hidden" id="airlineIds" ng-model="airlineIds"
                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
                					<input type="hidden" id="aircraftId"  />
                						<label for="airline">Airline</label>
                						<div>
                							<select id="airline" class="selectpicker show-tick" data-live-search="true" data-dropup-auto="false"  data-width="100%" ></select>
                						</div>
                					</div>
                					<div class="col-md-2" style="padding-left: 0px;">
                						<label for="tailsign">Tailsign</label>
                						<div>
                							<select id="tailsign" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" data-dropup-auto="false"  data-live-search="true" ></select>
                						</div>
                						<input type="hidden" id="aircraftIdTable" name="aircraftIdTable" />
                					</div> 
									<div class="col-md-2" style="padding-left: 0px;">										
										<label for="startDateTimePicker">From</label>
										<input class="form-control dateChange" id="startDateTimePicker"	type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>																				
									</div>
									<div class="col-md-2" style="padding-left: 0px;">										
										<label for="endDateTimePicker">To</label>
										<input class="form-control dateChange" id="endDateTimePicker" type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>											
									</div>
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
							<div class="panel-body  text-center">
								<div class="row">
									<?php
										$isDataAvailable=false;
										if (isset ( $cursorBody )) {
											foreach($cursorBody as $key => $value) {
												if(is_array($value) && $value != '-'){
													$isDataAvailable=true;
													break;
												}
											}
										}
										if($aircraftIsp!="KaNoVAR"){										
											if($isDataAvailable){
												include("connectivityLogTable.php");
											}else{
												echo "<label class=\"noData-label\"> No data available for the selected duration or selected filters </label>";
											}
										}else{
											if($isDataAvailable){
												include("kaConnectivityLogTable.php");
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
<script type="text/javascript" src="../controllers/ConnectivityLogsController.js"></script>
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
		getConnectivityLogs();
	});
	
	$('#connectivityDataTable').bootstrapTable({			
			exportOptions: {
				fileName: 'ConnectivityLogs'
			}});

	function getConnectivityLogs(){
		$('#connectivityDataTable').bootstrapTable("destroy");
		data = {        
			aircraftId: $('#aircraftIdTable').val(),
			startDateTime: $("#startDateTimePicker").val(), 
			endDateTime: $("#endDateTimePicker").val(),
			firstTime:false
		};
		firstTime=false;
		var aircraftId= $('#aircraftId').val();
		var tailsign= $('#tailsign').val();
		var airlineId= $('#airline').val();
		var startDate = $("#startDateTimePicker").val();
		var endDate = $("#endDateTimePicker").val();
	    //call same URL with start and end date as parameters in addition to aircraftId			
		window.location.href = "ConnectivityLogs.php?aircraftId="+aircraftId+"&firstTime="+firstTime+"&startDate="+startDate+"&endDate="+endDate+"&tailsign="+tailsign+"&airlineId="+airlineId;	
		
	}
	
	$("#reset").click(function(event){	
		window.location.href = "ConnectivityLogs.php";
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





