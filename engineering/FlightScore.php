<?php
session_start ();
$menu = 'flightscore';
require_once "../database/connecti_database.php";
include ("../engineering/BlockCustomer.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$flightScoreVisited = $_REQUEST ['flightScoreVisited'];

if($flightScoreVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airlineId']);
}else{
		$_SESSION['airlineId'] =  '';
		$_SESSION['platform'] =  '';
		$_SESSION['configType'] =  '';
		$_SESSION['tailsignList'] =  '';
		$_SESSION['software'] = '';
		$_SESSION['startDate'] = ''; 
		$_SESSION['endDate'] =  '';
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
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>

<link rel="stylesheet"
	href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>

</head>
<style>
.dropdown-menu{
	min-width: 103px;
}
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
<body id="bodyDiv" ng-controller="FlightScoringController">
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
				<li class="active">Flight Score</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title" style="padding-right: 12px;">
				<h2>Flight Score</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">
						
						<div class="panel panel-default">
							<!-- div class="panel-heading">
                                    <h4 class="panel-title">Search Flight Score</h4>
                                </div-->
							<div class="panel-body">
								<input type="hidden" id="airlineIds" ng-model="airlineIds"
									ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
								<div class="row">
									<div class="col-md-2 form-group">
										<label for="airline">Airline</label>
										<select id="airline" class="selectpicker show-tick"
											data-live-search="true" data-width="100%"
											style="max-width: 150px;"></select>
									</div>
									<div class="col-md-2 form-group">
										<label for="platform">Platform</label>
										<select id="platform" class="selectpicker show-tick"
											data-width="100%" data-max-width="120px;"></select>
									</div>
									<div class="col-md-2 form-group">
    									<label for="configType">Configuration</label>
										<select id="configType" class="selectpicker show-tick"
											data-width="100%">
										</select>
									</div>
									<div class="col-md-2 form-group">
										<label for="software">Software</label>
										<select id="software" class="selectpicker show-tick"
											data-width="100%" multiple title="All"
											data-live-search="true"
											data-selected-text-format="count > 3"></select>
									</div>
									<div class="col-md-2 form-group">
										<label for="tailsign">Tailsign</label>
										<select id="tailsign" class="selectpicker show-tick"
											data-width="100%" multiple title="All"
											data-live-search="true"
											data-selected-text-format="count > 3"></select>
									</div>
									<div class="col-md-1 form-group">
										<label for="startDateTimePicker">From</label>
										<input class="form-control dateChange" id="startDateTimePicker"
											type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>
									</div>
									<div class="col-md-1 form-group">
										<label for="endDateTimePicker">To</label>
										<div>
											<input class="form-control dateChange" id="endDateTimePicker"
												type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>
										</div>
									</div>
								</div>
								<div class="row" style="padding-left: 10px;">
    								<a data-toggle="collapse" href="#advancedFilter"
    									role="button" aria-expanded="false"
    									aria-controls="advancedFilter"><font style="font-weight: bold;">Advanced
    										Filter</font>&nbsp;<span
    									class="glyphicon glyphicon-chevron-right"></span></a>
								</div>
								<div class="collapse" id="advancedFilter">
									<div class="row">
										<div class="col-md-12 text-center">
											<label>Flight Score:&nbsp;&nbsp;</label> 0%
											&nbsp;&nbsp;&nbsp;&nbsp;<input id="fsSlider"
												data-slider-id="fsSlider" type="text" class="span2" value=""
												data-slider-min="0" data-slider-max="100"
												data-slider-step="1" data-slider-value="[0,0]" />&nbsp;&nbsp;&nbsp;&nbsp;100%
										</div>
									</div>
								</div>
								<br />
								<div class="row">
									<div class="col-md-12 text-left">
										<button id="filter" class="btn btn-primary"
											data-ng-click="getFlightScore()">Filter</button>
										&nbsp;&nbsp;&nbsp;
										<button id="reset" type="button" class="btn btn-reset"
											ng-click="resetFlightScore()">Reset</button>
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
								<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading
									data...
								</div>
        						<div id="noDataDiv" class="container-fluid text-center">
            						<label class="noData-label">No data available for the selected duration or filters</label>
            					</div>
								<div align="center" id="seatResetsChartLegend">
									<ul style="padding-left: 0px;">

										<a href="#" class="btn" data-ng-click="filterCategory('all');"
											style="border: 1px solid; width: 100px; border-radius: 4px;"><span
											style="background-color: #428bca"></span><i
											class="fa fa-square" style="font-size: 12px; color: #428bca;"></i>All</a>
										<a href="#" class="btn"
											data-ng-click="filterCategory('critical');"
											style="border: 1px solid; width: 130px; border-radius: 4px;"><span
											style="background-color: #fe5757"></span><i
											class="fa fa-square" style="font-size: 12px; color: #E04B4A;"></i>Critical
											issue</a>
										<a href="#" class="btn"
											data-ng-click="filterCategory('warning');"
											style="border: 1px solid; width: 130px; border-radius: 4px;"><span
											style="background-color: #FBC200"></span><i
											class="fa fa-square" style="font-size: 12px; color: #fe970a;"></i>Warning
											issue</a>
										<a href="#" class="btn"
											data-ng-click="filterCategory('noissue');"
											style="border: 1px solid; width: 100px; border-radius: 4px;"><span
											style="background-color: #2ECC40"></span><i
											class="fa fa-square" style="font-size: 12px; color: #95b75d;"></i>No
											issue</a>
									</ul>
								</div>
								<div class="table-editable" id="flightScoringDiv">
									<div class="alert alert-success" id="showRemarksAlert" style="display: none;">Remarks
										added</div>
									<table id="flightScoringTable" data-classes="table"
										data-pagination="true" data-page-list="[25, 50, 100, All]"
										data-page-size="25" data-striped="true" data-search="true"
										data-search-align="left" data-show-export="true">
										<thead>
											<tr>
												<th data-field="flightDate" data-sortable="true">Flight Date</th>
												<th data-field="tailSign" data-sortable="true">Tail Sign</th>
												<th data-field="platform" data-sortable="true">Platform</th>
												<th data-field="config" data-sortable="true">Config</th>
												<th data-field="software" data-sortable="true">Software</th>
												<th data-field="flightLegId" data-sortable="true"
													data-formatter="formatFlightLeg">Flight Leg #</th>
												<th data-field="cityPair" data-sortable="true">City Pair</th>
												<!-- <th data-field="flightDuration" data-sortable="true" data-formatter="formatFlightDuration">Flight Duration</th> -->
												<th data-field="flightDuration" data-sortable="true">Flight
													Duration</th>
												<th data-field="impactedLRUs"
													data-cell-style="impactedLRUStyle"
													data-formatter="formatImpactedLRUs">Reported LRUs</th>
												<th data-field="flightScore" data-sortable="true"
													data-cell-style="cellStyle"
													data-formatter="formatFlightScore">Flight Score</th>
												<th data-field="remarks" data-width="150px"
													data-formatter="formatRemarks">Remarks</th>
											</tr>
										</thead>
									</table>
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
	
	<div class="modal" data-sound="alert" id="modalTable">
		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Flight Score Info</h4>
				</div> 
				<div class="modal-body">
					<!--div style="background-color: #428bca; color: #FFF; border-color: #36b7e3; width: 100%; margin-bottom: 10px; line-height: 21px; padding: 15px; border: 1px solid transparent; border-radius: 4px;"-->
    				<div class="modal-alert-info">
    					<strong>Flights Status Formula</strong>
    					<br /> 
    					weighted mean = (sum(weightLRU *
						fault400Time)) / (sum(weightLRUi * numberOfFlyingLRUi)) <br />
						score value % = (1 - (weighted mean value / flight time)) * 100
    				</div>
    				<br/>
					<div>
    					<h5>
    						<b>Weightage Info</b>
    					</h5>
    				</div>
    				<table class="table table-hover">
    					<thead>
    						<tr>
    							<th width="50%">LRU Name</th>
    							<th width="50%">Weight</th>
    						</tr>
    					</thead>
    					<tbody>
    						<tr>
    							<td>DSU</td>
    							<td>{{DSU_weight}}</td>
    						</tr>
    						<tr>
    							<td>LAIC</td>
    							<td>{{LAIC_weight}}</td>
    						</tr>
    						<tr>
    							<td>ICMT</td>
    							<td>{{ICMT_weight}}</td>
    						</tr>
    						<tr>
    							<td>ADBG</td>
    							<td>{{ADBG_weight}}</td>
    						</tr>
    						<tr>
    							<td>QSEB</td>
    							<td>{{QSEB_weight}}</td>
    						</tr>
    						<tr>
    							<td>SDB</td>
    							<td>{{SDB_weight}}</td>
    						</tr>
    						<tr>
    							<td>SVDU</td>
    							<td>{{SVDU_weight}}</td>
    						</tr>
    						<tr>
    							<td>TPMU</td>
    							<td>{{TPMU_weight}}</td>
    						</tr>
    						<tr>
    							<td>TPCU</td>
    							<td>{{TPCU_weight}}</td>
    						</tr>
    					</tbody>
    				</table>
    				
    				<div>
    					<h5>
    						<b>Threshold Info</b>
    					</h5>
    				</div>
    				<table class="table table-hover">
    					<thead>
    						<tr>
    							<th width="50%">ColorCode</th>
    							<th width="50%">Color Range</th>
    						</tr>
    					</thead>
    					<tbody>
    						<tr>
    							<td>Green</td>
    							<td> > 98 </td>
    						</tr>
    						<tr>
    							<td>Orange</td>
    							<td>90 - 98</td>
    						</tr>
    						<tr>
    							<td>Red</td>
    							<td>< 90</td>
    						</tr>
    					</tbody>
    				</table>
    				
				</div>
			</div>
		</div>
	</div>
			
	<!-- START PRELOADS -->
	<!-- END PRELOADS -->
	<!-- START SCRIPTS -->
	<!-- START PLUGINS 
         <script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
         <script type="text/javascript" src="../js/plugins/jquery/jquery-ui.min.js"></script>
         <script type="text/javascript" src="../js/plugins/bootstrap/bootstrap.min.js"></script>  -->
	<!-- END PLUGINS -->
	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
	
	<!-- START THIS PAGE PLUGINS-->
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript"
		src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<!-- END THIS PAGE PLUGINS-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
</body>
<script>
var app = angular.module('myApp', []);
// var app = angular.module('myApp', ['ngAnimate', 'ui.bootstrap']);
//     var thalesApp = angular.module("thalesApp", ['ngRoute', 'ngSanitize', 'ngAnimate', 'ui.bootstrap']);
</script>
<script src="../controllers/FlightScoringController.js"></script>

<script>
var startDateTime= "<?php echo "$startDateTime";?>"; 
var endDateTime= "<?php echo "$endDateTime";?>";
var fsSliderSet = false;

if(<?php echo $flightScoreVisited;?>){
	var session_AirlineId="<?php echo $_SESSION['airlineId'];?>";
	var session_Platform="<?php echo $_SESSION['platform'];?>";
	var session_Config="<?php echo $_SESSION['configType'];?>";
	<?php 
		if($_SESSION ['software']!=null){
			$software = rtrim ( implode ( ",", $_SESSION ['software'] ), "," );
			$softwares = rtrim ( $software, "," );
		}
	?>	
	var session_Software='<?php echo $softwares;?>';	

	<?php 
		if($_SESSION ['tailsignList']!=null){
			$tailsignList = rtrim ( implode ( ",", $_SESSION ['tailsignList'] ), "," );
			$tailsignList = rtrim ( $tailsignList, "," );
		}
	?>
		
	var session_Tailsign='<?php echo $tailsignList;?>';
	console.log('tailsignlist: ' + session_Tailsign);
	var session_StartDate="<?php echo $_SESSION['startDate'];?>";
	var session_EndDate="<?php echo $_SESSION['endDate'];?>";
	var flightScoreVisited=true;
}else{
	var flightScoreVisited=false;
}

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
                       	
     $('#software').selectpicker({                              
                             size: 6
                       	});
                       	
     $('#tailsign').selectpicker({                              
                             size: 6
                       	});
                       	
     

	$fsSlider = $('#fsSlider').slider({});
	
	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	  });

	$('#platform').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	  });

	$('#configType').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadSoftwares();
	  });

	$('#software').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadTailsign();
	});

    // Toggle plus minus icon on show hide of collapse element
    $(".collapse").on('show.bs.collapse', function(){
    	$(this).parent().find(".glyphicon").removeClass("glyphicon-chevron-right").addClass("glyphicon-chevron-down");
    	$fsSlider.data('slider').setValue([0,100]);
    	fsSliderSet = true;
    }).on('hide.bs.collapse', function(){
    	$(this).parent().find(".glyphicon").removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-right");
    	$fsSlider.data('slider').setValue([0,0]);
    	fsSliderSet = false;
    });

});

function filterCategory(category) {
	console.log('Category: ' + category);
}

/**
 * Update the Fligh Score cell color based on the score
 */
function cellStyle(value, row, index) {
	value = value.replace(" %", "");
	
    if(value >= 98) {
    	return {
            css: {"background-color": "#2ECC40"}
        };
    } else if(value >= 90 && value < 98) {
    	return {
            css: {"background-color": "#FBC200"}
        };
    } else {
    	return {
            css: {"background-color": "#fe5757"}
        };
    }
}

/**
 * Format the Fligh Score value and append %
 */
function formatFlightScore(value, row, index, field) {
	return value + ' %';
}

function impactedLRUStyle(value, row, index) {
	return {
		css: {"word-break": "break-word"}
	};
}
	
/**
 * Format the Fligh Duration to append hours and mins.
 */
function formatFlightDuration(value, row, index, field) {
		var timeString = "";
		
		if(value) {
			time = value.split(":");

			if(time[0] > 0) {
				timeString = time[0] + " hours ";
			}

			if(time[1] > 0) {
				timeString += time[1] + " mins";
			}

			return timeString;
		}
		
		return '-';
		return "<a href='linkflight'></a>";
	}

function formatImpactedLRUs(value, row, index, field) {
	if(value) {
		value = value.replace(/,/g, ", ");
		return value;
	} else {
		return '-';
	}
}

function formatFlightLeg(value, row, index, field) {
	if(value) {
		value = value.replace(/,/g, ", ");
		var method = "javascript:analyzeFlightLegs(" + value + "," + row['aircraftId'] + ")";

		return "<a href='" + method + "'>" + value + "</a>";
	} else {
		return '-';
	}
}

function analyzeFlightLegs(flightLegIds, aircraftId) {
    // console.log('Flight Leg Ids: ' + flightLegIds);
    // console.log('aircraftId: ' + aircraftId);

	var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds+"&mainmenu=FlightScore";
	// console.log(url);
    var win = window.open(url, '_self');
    win.focus();
}


// var i=1;

function formatRemarks(value, row, index, field) {
	if(!value) {
		value = 'Double click to add Remarks';
		return '<div style="color: rgb(171, 171, 171);" id="div_' + row['flightLegId'] + '_' + row['aircraftId'] + '" ondblclick="showTextArea(\'' + row['flightLegId'] + '\',\'' + row['aircraftId'] + '\');">' + value + '</div>';
	} else {
		return '<div id="div_' + row['flightLegId'] + '_' + row['aircraftId'] + '" ondblclick="showTextArea(\'' + row['flightLegId'] + '\',\'' + row['aircraftId'] + '\');">' + value + '</div>';
	}
}

function showTextArea(id,acid) {
// 	alert('test: ' + id);
	value = $("#div_"+id+"_"+acid).text();
	if(value == 'Double click to add Remarks') {
		value = '';
	}
	
	$("#div_"+id+"_"+acid).html('<textarea class="form-control" style="height:100%" id="text_' + id + '_' + acid + '" onblur="updateRemark(\'' + id + '\',\'' + acid + '\')" >' + value + '</textarea>');
	$("#text_"+id+"_"+acid).focus();
}

function updateRemark(id,acid) {
// 	alert(id);
// 	alert('value: ' + $("#text_"+id).val());
	value = $("#text_"+id+"_"+acid).val();
	if(!value) {
		value = 'Double click to add Remarks';
	} else {
	    angular.element($("#ctrldiv")).scope().updateRemarks(id, acid, value);
	}
	
	$("#div_"+id+"_"+acid).text(value);
}

</script>

</html>