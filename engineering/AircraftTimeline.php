<?php
session_start ();
$menu = 'timeline';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
error_log('airlineIds : '.$_SESSION['airlineIds']);
$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$aircraftVisited = $_REQUEST ['aircraftVisited'];

if($aircraftVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airline']);
}else{
	$_SESSION['airline'] = '';
	$_SESSION['platform'] = '';
	$_SESSION['configType'] = '';
	$_SESSION['software'] = '';
	$_SESSION['tailsign'] = '';
	$_SESSION['startDate'] = '';
	$_SESSION['endDate'] = '';
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

<!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->

<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet"	type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />

<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link href="../css/vis.css" rel="stylesheet">
<script src="../js/vis.min.js"></script>

</head>
<style type="text/css">
.dropdown-menu{
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
.modal-content{
	border-width:0px !important;
	border-radius: 24px;
}
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}
</style>
<body ng-controller="AircraftTimelineController">
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
				<li class="active">AircraftTimeline</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Aircraft Timeline</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">						
						<div class="panel panel-default">
							<div class="panel-body">
								<input type="hidden" id="airlineIds" ng-model="airlineIds"
									ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
								<div class="row">
									<div class="col-md-2 from-group">
										<label for="airline">Airline</label>
										<div>
											<select id="airline" class="selectpicker show-tick"
												data-live-search="true" data-width="100%"
												style="max-width: 150px;"></select>
										</div>
									</div>
									<div class="col-md-2 from-group">
										<label for="platform">Platform</label>
										<div>
											<select id="platform" class="selectpicker show-tick"
												data-width="100%" data-max-width="120px;"></select>
										</div>
									</div>
									<div class="col-md-2 from-group">
										<label for="configType">Configuration</label>
										<div>
											<select id="configType" class="selectpicker show-tick"
												data-width="100%">
											</select>
										</div>
									</div>
									
									<div class="col-md-2 from-group">
										<label for="tailsign">Tailsign</label>
										<div>
											<select id="tailsign" class="selectpicker show-tick"
												data-width="100%" data-live-search="true"
												></select>
										</div>
									</div>
									<div class="col-md-2 from-group">
										<label for="startDateTimePicker">From</label>
										<div>
											<input class="form-control dateChange" id="startDateTimePicker"
												type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>
										</div>
									</div>
									<div class="col-md-2 from-group">
										<label for="endDateTimePicker">To</label>
										<div>
											<input class="form-control dateChange" id="endDateTimePicker"
												type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>
										</div>
									</div>
								</div>
								<br/>
								<div class="row">
									<div class="col-md-12 text-left">
										<button id="filter" class="btn btn-primary"
											data-ng-click="getAircraftInfoData()">Filter</button>
										&nbsp;&nbsp;&nbsp;
										<button id="reset" type="button" class="btn btn-reset"
											ng-click="resetFilters()">Reset</button>
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
								<div class="col-md-3">
									<div class="contactBox">
										<div class="row" style="padding-top: 5px;">											
											<label class="col-sm-5"><b> <i class="fa fa-plane fa-fw"
													aria-hidden="true"></i> Tailsign : </b> </label>
											<div id="Atailsign" class="col-sm-7 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-5"><b> <i class="fa fa-plane fa-fw"
													aria-hidden="true"></i> MSN : </b> </label>
											<div id="msn" class="col-sm-7 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-5"><b> <i class="fa fa-calendar fa-fw"
													aria-hidden="true"></i> EIS : </b> </label>
											<div id="eis" class="col-sm-7 header"></div>
										</div>
									</div>
								</div>
								<div class="col-md-3">
									<div class="contactBox">
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i class="fa fa-plane fa-fw"
													aria-hidden="true"></i> AircraftType : </b> </label>
											<div id="ac_type" class="col-sm-5 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i class="fa fa-plane fa-fw"
													aria-hidden="true"></i> Configuration : </b> </label>
											<div id="config" class="col-sm-5 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i class="fa fa-cog fa-fw"
													aria-hidden="true"></i> Platform : </b> </label>
											<div id="Aplatform" class="col-sm-5 header"></div>
										</div>
									</div>
								</div>
								<div class="col-md-3">
									<div class="contactBox">
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i class="fa fa-codepen fa-fw"
													aria-hidden="true"></i> Baseline : </b> </label>
											<div id="sw_baseline" class="col-sm-5 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i
													class="fa fa-file-code-o fa-fw" aria-hidden="true"></i>
													Software : </b> </label>
											<div id="software" class="col-sm-5 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-7"><b> <i class="fa fa-calendar fa-fw"
													aria-hidden="true"></i> Installation : </b> </label>
											<div id="sw_installed" class="col-sm-5 header"></div>
										</div>
									</div>
								</div>
								<div class="col-md-3">
									<div class="contactBox">
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-6"><b> <i
													class="fa fa-file-archive-o fa-fw" aria-hidden="true"></i>
													SW P/N : </b> </label>
											<div id="sw_partno" class="col-sm-6 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-6"><b> <i class="fa fa-globe fa-fw"
													aria-hidden="true"></i> Map : </b> </label>
											<div id="mapVersion" class="col-sm-6 header"></div>
										</div>
										<div class="row" style="padding-top: 5px;">
											<label class="col-sm-6"><b> <i class="fa fa-cog fa-fw"
													aria-hidden="true"></i> Content : </b> </label>
											<div id="content" class="col-sm-6 header"></div>
										</div>
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
								<div id="loadingData" align="center">
										<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
										Loading Data...
								</div>
								<div id="dataTimeline">
									<div id="timeline" class="flightTimeline"></div>
									<br/>
									<div class="row">
										<div class="col-md-12">
											<div style="float:left" class="vertical-align: bottom">
												<button id="add" class="btn btn-default btn-sm" data-toggle="modal" data-target="#myModal">Legend</button>
												
											</div>
											<div class="text-right">				 
												<!--<span class="pull-left"><label>Legend:</label>&nbsp;&nbsp;<img src="../img/power.png" class="statusAlert" style="width: 12px; height: 12px;"></span>-->
												<button id="analyzeFlightLegs" class="btn btn-default btn-sm">Analyze selected Flight Leg(s)</button>
											</div>
										</div>
									</div>
								</div>
								<div id="errorInfo" class="container-fluid text-center">
									<h5>No timeline data available for the selected duration or selected
										filters</h5>
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
	
    <div class="modal" id="myModal">
		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 54px; border-radius: 6px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">BITE FILE</h4>
				</div> 
				<div class="modal-body">
					<ul style="list-style: none;">
                        <li><span style="background-color:#dcead0; width: 14px; height: 14px;"><img src="../img/power.png" style="width: 12px; height: 12px;"></span>&nbsp;&nbsp;
                            Reset in ground
                        </li>
						<br>
                        <li>
                          <span style=" color: #ffe44d;  background-color: #ffec80;  border-color: #ffd700; width: 14px; height: 14px;"><img src="../img/power.png" class="statusAlert" style="width: 12px; height: 12px;"></span>&nbsp;&nbsp;
						  Reset in Cruise,more than 15% and less then 30%
                        </li>
                        <br>
						<li>
                          <span style=" color: #ff7f01;  background-color: #fbc200;  border-color: #fb9800; width: 14px; height: 14px;"><img src="../img/power.png" class="statusAlert" style="width: 12px; height: 12px;"></span>&nbsp;&nbsp;
						  Reset in Cruise,more than 30% and less then 50%
                        </li>
                        <br>
                        <li>
                          <span style=" color: #8a3c3e;  background-color: #fe5757;  border-color: #fe2e2e; width: 14px; height: 14px;"><img src="../img/power.png" class="statusAlert" style="width: 12px; height: 12px;"></span>&nbsp;&nbsp;
						  Reset in Cruise,more than 50%
                        </li>
                    </ul>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->
	
	<!-- START PRELOADS -->
	<!-- END PRELOADS -->
	<!-- START SCRIPTS -->
	<!-- START PLUGINS 
	<!-- END PLUGINS -->
	<!-- START THIS PAGE PLUGINS-->

	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript"
		src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<!-- END THIS PAGE PLUGINS-->
<script>
	var app = angular.module('myApp', []);
</script>
<!-- <script src="../controllers/AircraftTimelineController.js"></script> -->
<script>
var airlineId_nav = "<?php echo "$airlineId_nav";?>";
var platform_nav = "<?php echo "$platform_nav";?>";
var configuration_nav = "<?php echo "$configuration_nav";?>";
var software_nav = "<?php echo "$software_nav";?>";
var tailsign_nav = "<?php echo "$tailsign_nav";?>";

var startDateTime= "<?php echo "$startDateTime";?>"; 
var endDateTime= "<?php echo "$endDateTime";?>";

$(document).ready(function(){
    $('.navbar-nav li').removeClass('active');
    $("#homeSideBarFlightScoring").addClass("active");


	$('#airline').selectpicker({                              
                             size: 6
                       	});
                       	
     $('#platform').selectpicker({                              
                             size: 6
                       	});
                       	
     $('#configType').selectpicker({                              
                             size: 6
                       	});
                       	
     $('#tailsign').selectpicker({                              
                             size: 6
                       	});                      	
     
	
	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	  });

	$('#platform').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	  });

	$('#configType').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadTailsign();
	  });
	  console.log('Visited : '+<?php echo $_REQUEST ['aircraftVisited'];?>);
	  
	if(<?php echo $_REQUEST ['aircraftVisited'];?>){
		angular.element($("#ctrldiv")).scope().loadAirlines();		
	 	$('#airline').val(<?php echo $_SESSION['airline'];?>);
	 	$('#platform').val('<?php echo $_SESSION['platform'];?>');
	 	$('#configType').val('<?php echo $_SESSION['configType'];?>');	 	
	 	$('#tailsign').val('<?php echo $_SESSION['tailsign'];?>');
	 	
	 }else{
		 angular.element($("#ctrldiv")).scope().loadAirlines();
	 }

});

$('#filter').click(function(){
	console.log('clicked');
	
});
</script>
<script>
	app.controller('AircraftTimelineController', function($scope, $http, $log, $window, $timeout, $parse) {
	
		$log.log("inside AircraftTimelineController");
		$('#loadingDiv').hide();
		$scope.airlineId = $("#airlineId").val();
		$('#errorInfo').hide();
		$('#dataTimeline').hide();
		$('#loadingData').hide();
		
		var airlineId_nav = $window.airlineId_nav;
		var platform_nav = $window.platform_nav;
		var configuration_nav = $window.configuration_nav;
		var software_nav = $window.software_nav;
		var tailsign_nav = $window.tailsign_nav;
		
		var startDate = $window.startDateTime;
		var endDate = $window.endDateTime;
		var firstTime=true;
		var timeline;
		var flightLegs = '';
		$scope.aircraftInfo={};
		$('#startDateTimePicker').datetimepicker({
			timepicker:false,
			format:'Y-m-d',
			value: startDate
		});
		
		$('#endDateTimePicker').datetimepicker({
			timepicker:false,
			format:'Y-m-d',
			value: endDate
		});
	
		$('#airline').selectpicker();
		$('#platform').selectpicker();
		$('#configType').selectpicker();
		$('#tailsign').selectpicker();
		
		$scope.loadAirlines = function() {
		    $http.get("../common/AirlineDAO.php", {
		        params: {
		        	action: "GET_AIRLINES_BY_IDS",
		            airlineIds: $("#airlineIds").val()
		        }
		    }).success(function (data,status) {
				//$("#airline").append('<option value="">All</option>');
	         	var airlineList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < airlineList.length; i++) {
					var al = airlineList[i];
					$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
				}
				
				if(airlineId_nav) {
					$("#airline").val(airlineId_nav);
				}
				
				$('#airline').selectpicker('refresh');
				$scope.selectedAirline = airlineList[0];
				if(<?php echo $_REQUEST ['aircraftVisited'];?>){
					$('#airline').val(<?php echo $_SESSION['airline'];?>);
					$('#airline').selectpicker('refresh');
					$scope.loadPlatforms();
				 }else{
					 var airlineIdfromAirlines='<?php echo $_REQUEST ['airlineId'];?>';
					 if(airlineIdfromAirlines!=''){		
							$('#airline').val(<?php echo $_REQUEST ['airlineId'];?>);
							$('#airline').selectpicker('refresh');		
					}
					$scope.loadPlatforms();					
				 }
		    });
	   };
	
		$scope.resetFilters = function() {
			if(<?php echo $_REQUEST ['aircraftVisited'];?>){
				var url = "AircraftTimeline.php?aircraftVisited=false";
				var win = window.open(url, '_self');
				win.focus();
			}else{
				clearAirlines();
				$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
				$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		
				$scope.loadAirlines();
				//$scope.getAircraftInfoData();
			}
		};
		
	    $scope.loadPlatforms = function() {
	    	clearPlatformSelect();
	    	clearConfigurationSelect();    	
	    	clearTailsignSelect();    	
	    	
			var airlineId = "";
			airlineId = getSelectedAirline();
			
	        var data = $.param({
	            airlineId: airlineId,
	            action: 'GET_PLATFORMS_FOR_AIRLINE'
	        });
	    
	        var config = {
	            headers : {
	                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
	            }
	        };
	
	        $http.post('../common/AirlineDAO.php', data, config)
	        .success(function (data, status, headers, config) {
				//$("#platform").append('<option value="">All</option>');
	         	var platformList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < platformList.length; i++) {
					var pf = platformList[i];
					$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
				}
				
				if(platform_nav) {
					$("#platform").val(platform_nav);
				}
				
				$('#platform').selectpicker('refresh');

	            //$scope.loadConfigTypes();
				if(<?php echo $_REQUEST ['aircraftVisited'];?>){
					$('#platform').val('<?php echo $_SESSION['platform'];?>');
					$('#platform').selectpicker('refresh');
				}
				
				$scope.loadConfigTypes();
	        })
	        .error(function (data, status, header, config) {
	        });
	    };
	
	    $scope.loadConfigTypes = function() {
	    	clearConfigurationSelect();
	    	
	    	clearTailsignSelect();    	
	    	
			var airlineId = "";
			var platform = "";
	
			airlineId = getSelectedAirline();
			platform = getSelectedPlatform();
	
	        var data = $.param({
	            airlineId: airlineId,
	            platform: platform,
	            action: 'GET_CONFIG_TYPE_FOR_AIRLINE_PLATFORM'
	        });
	    
	        var config = {
	            headers : {
	                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
	            }
	        };
	
	        $http.post('../common/AirlineDAO.php', data, config)
	        .success(function (data, status, headers, config) {
				//$("#configType").append('<option value="">All</option>');
	         	var configTypeList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < configTypeList.length; i++) {
					var config = configTypeList[i];
					$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
				}
				
				if(configuration_nav) {
					$("#configType").val(configuration_nav);
				}

				$('#configType').selectpicker('refresh');

	            //$scope.loadTailsign();
				if(<?php echo $_REQUEST ['aircraftVisited'];?>){
					$('#configType').val('<?php echo $_SESSION['configType'];?>');
					$('#configType').selectpicker('refresh');
				}
				
				$scope.loadTailsign();
	        });
	    };
	
	  
	    
		$scope.loadTailsign = function() {
	    	clearTailsignSelect();    	
	
			var airlineId = "";
			var platform = "";
			var configType = "";
			var software = "";
			
			airlineId = getSelectedAirline();
			platform = getSelectedPlatform();
			configType = getSelectedConfigType();		
			software = getSelectedSoftwares();
			
	        var data = $.param({
	            airlineId: airlineId,
	            platform: platform,
	            configType: configType,
	            software: software,
	            action: 'GET_TS_AND_ID_FOR_AIRLINE_PLTFRM_CNFG_SW'
	        });
	    
	        var config = {
	            headers : {
	                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
	            }
	        };
	
	        $http.post('../common/AirlineDAO.php', data, config)
	        .success(function (data, status, headers, config) {
				var tailsignList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < tailsignList.length; i++) {
					var ts = tailsignList[i];
					$("#tailsign").append('<option value=' + ts.id + '>' + ts.tailsign + '</option>');
				}
				$('#tailsign').selectpicker('refresh');
				
				if(tailsign_nav) {
					$("#tailsign").val(<?php echo $aircraftId ?>);
				}
				
				$('#tailsign').selectpicker('refresh');

				if(firstTime){
					//$scope.getAircraftInfoData();
					firstTime=false;					
					if(<?php echo $_REQUEST ['aircraftVisited'];?>){
						$('#tailsign').val(<?php echo $_SESSION['tailsign'];?>);
						$('#startDateTimePicker').val('<?php echo $_SESSION['startDate'];?>');
						$('#endDateTimePicker').val('<?php echo $_SESSION['endDate'];?>');
						$('#tailsign').selectpicker('refresh');
						$scope.getAircraftInfoData();						
					}else{
						$scope.getAircraftInfoData();
					}
				}else{
					if(<?php echo $_REQUEST ['aircraftVisited'];?>){
						$('#tailsign').val(<?php echo $_SESSION['tailsign'];?>);
						$('#startDateTimePicker').val('<?php echo $_SESSION['startDate'];?>');
						$('#endDateTimePicker').val('<?php echo $_SESSION['endDate'];?>');
						$('#tailsign').selectpicker('refresh');
						$scope.getAircraftInfoData();						
					}
				}
	        });
		};
	
		$scope.getAircraftInfoData = function() {
			var airlineId = "";
			var platform = "";
			var configType = "";
			var software = "";
			var tailsignList = "";
			$('#errorInfo').hide();
			$('#dataTimeline').hide();
			$('#timeline').hide();
			airlineId = getSelectedAirline();
			platform = getSelectedPlatform();
			configType = getSelectedConfigType();		
			software = getSelectedSoftwares();
			tailsignList = getSelectedTailsigns();
			
			startDate = $('#startDateTimePicker').val();
			endDate = $('#endDateTimePicker').val();
			
			var aircraftId = $("#tailsign").val();
	    	data={	aircraftId:aircraftId,
	    			airline:airlineId,
	    			platform:platform,
	    			configType:configType,
	    			software:software,
	    			startDate:startDate,
	    			endDate:endDate
	    			};
	    	var output="";
	    	$.ajax({
	            type: "GET",
	            dataType: "json",
	            url: "../ajax/GetAircraftInfo.php",
	            data: data,
	            success: function(data) {
	                //console.log(data);
	                $scope.aircraftInfo=data;
	                $scope.loadAircraftData($scope.aircraftInfo);
	                if(timeline != null) {
	                	if(timeline.body != null){
	                		timeline.destroy();
	                	}
	                    //$('#loadingTimeline').toggle();
	                }
	                getTimeLineData();	//Reload timeline     
	            },
	            error: function (err) {
	                //console.log('Error', err);
	            	$('#errorInfo').show();
		            $('#loadingData').hide();
	            }
	        });
	        // To remove the white space below the page content
			page_content_onresize();
		};
		
		$scope.loadAircraftData = function(aircraftData) {
			 $("#Atailsign").text((aircraftData.tailsign?aircraftData.tailsign:'-'));
			 $("#msn").text((aircraftData.msn?aircraftData.msn:'-'));
			 $("#eis").text((aircraftData.EIS?aircraftData.EIS:'-'));
			 $("#ac_type").text((aircraftData.Ac_Configuration?aircraftData.Ac_Configuration:'-'));
			 $("#config").text((aircraftData.Ac_Configuration?aircraftData.Ac_Configuration:'-'));
			 $("#Aplatform").text((aircraftData.platform?aircraftData.platform:'-'));
			 $("#sw_baseline").text((aircraftData.SW_Baseline?aircraftData.SW_Baseline:'-'));
			 $("#software").text((aircraftData.software?aircraftData.software:'-'));
			 $("#sw_installed").text((aircraftData.SW_installed?aircraftData.SW_installed:'-'));
			 $("#sw_partno").text((aircraftData.SW_PartNo?aircraftData.SW_PartNo:'-'));
			 $("#mapVersion").text((aircraftData.Map_Version?aircraftData.Map_Version:'-'));
			 $("#content").text((aircraftData.Content ? aircraftData.Content : '-'));
		}
		
		function getTimeLineData() {		
			var aircraftId = $("#tailsign").val();
			$('#loadingData').show();
			var dataParam={
						aircraftId:aircraftId,
						startDateTime: $("#startDateTimePicker").val(), 
						endDateTime: $("#endDateTimePicker").val()
			        };
		    $.ajax({
		        type: "POST",
		        dataType: "json",
		        url: "../ajax/getAircraftTimeLineData.php",
		        data: dataParam,
		        success: function(data) {	            
		            $('#dataTimeline').show();
		            $('#timeline').show();
		            createTimeline(data);
		        },
		        error: function (err) {
		            //console.log('Error', err);
		            $('#errorInfo').show();
		            $('#loadingData').hide();
		        }
		    });
		}
		
		function createTimeline(data) {
		    $('#loadingTimeline').hide();
		    $('#errorInfo').hide();
		    var container = document.getElementById('timeline');
	
		    var groups = new vis.DataSet(
		        data.groups
		        );
	
		    var items = new vis.DataSet(
		        data.items
		        );
	
		    var options = {
		        start: data.options.start,
		        end: data.options.end,
		        min: data.options.min,
		        max: data.options.max,
		        clickToUse: true,
		        stack: false,
		        multiselect: true
		    };
	
		    timeline = new vis.Timeline(container, items,  groups, options);
		    timeline.on('select', function (properties) {
		        var selectedItems = properties.items;
		        flightLegs = ''; // reset flight Legs
	
		        if(selectedItems != null) {
		            for (i = 0; i < selectedItems.length; i++) { 
		                var selectedItem = selectedItems[i];
		                var res = selectedItem.split("/");
		                if(res[0] == 'FLI') {
		                    if(flightLegs != '') {
		                        flightLegs += ',';
		                    }
		                    flightLegs += res[1];
		                }
		            }
		        }
		    });
		    timeline.on('contextmenu', function (props) {	      
		      props.event.preventDefault();
		    });
		    $('#loadingData').hide();
		}
	
	
		$("#analyzeFlightLegs").click(function(){		
		    if(flightLegs != '') {
		    	var aircraftId = $("#tailsign").val();	        
				var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegs+"&mainmenu=AircraftTimeline";			
		        var win = window.open(url, '_self');
		        win.focus();
		    }
		});
	
		
		function clearAirlines() {
	        $('#airline').empty();
	        $('#airline').selectpicker('refresh');
		}
		
		function clearPlatformSelect() {
	        $('#platform').empty();
	        $('#platform').selectpicker('refresh');
		}
		
		function clearConfigurationSelect() {
	        $('#configType').empty();
	        $('#configType').selectpicker('refresh');
		}	
		
		function clearTailsignSelect() {
	        $('#tailsign').empty();
	        $('#tailsign').selectpicker('refresh');
		}
		
		function getSelectedAirline() {
			return $('#airline').val();
		}
		
		function getSelectedPlatform() {
			return $('#platform').val();
		}
		
		function getSelectedConfigType() {
			return $('#configType').val();
		}
		
		function getSelectedSoftwares() {
			return $('#software').val();
		}
		
		function getSelectedTailsigns() {
			return $('#tailsign').val();
		}
		//$scope.loadAirlines();
	});
</script>
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
</body>
</html>