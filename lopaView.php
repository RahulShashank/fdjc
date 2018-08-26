<!DOCTYPE html>
<?php
	/*session_start();
	require_once("../common/validateUser.php");
	$approvedRoles = [$roles["admin"]];
	$auth->checkPermission($hash, $approvedRoles);	*/
	require_once "database/connecti_database.php";
	require_once "common/functions.php";
	require_once "common/checkPermission.php";
	require_once 'libs/PHPExcel/PHPExcel.php';
	require_once 'libs/PHPExcel/PHPExcel/IOFactory.php';

	require_once("checkEngineeringPermission.php");

	$airlineIds = '1,10';
	
	

?>
<html lang="en" data-ng-app="myApp">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../img/favicon.ico">

    <title>BITE Analytics</title>

    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/vis.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="../css/dropzone.css" type="text/css" rel="stylesheet" />
    <link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
	<link href="../css/bootstrap-table.css" rel="stylesheet" />
    <!-- Custom styles for this template -->
    <link href="../css/dashboardHome.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../css/font-awesome.min.css">

    <script src="../js/jquery-1.11.2.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/angular.js"></script>
    <script src="../js/angular-route.js"></script>
	<script src="../js/angular-cookies.js"></script>
    <script src="../js/vis.min.js"></script>
    <script src="../js/dropzone.js"></script>
    <script src="../js/jquery.datetimepicker.js"></script>
	<script src="../js/checklist-model.js"></script>
	<script src="../js/bootstrap-table.js"></script>
	<script src="../js/jquery.blockUI.js"></script>
	<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
	<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
	
	<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
	<script src="../js/bootstrap-multiselect.js"></script>
	<script src="../js/Chart.HeatMap.S.js"></script>
	
	<style>
		.gradedBtn:hover{
			background-color: #808080 !important;
			background-position: 0 -20px !important;
		}
		.multiselect-container dropdown-menu{
			min-width:300px !important;
			width: 300px !important;
		}
		.scrollChart {
			min-width: 1500px;			
			min-height: 220px;
			width:1500px;
			background-color: #FCFCFC;
			border: 1px solid #E8E8E8;
		}
		.scrollChart1 {
			min-width: 100px !important;
			max-width: 1500px !important;
			height: 275px !important;
			width: 100%;
			background-color: #FCFCFC;
			border: 1px solid #E8E8E8;
		}
		
		.filterAlignment{			
			padding-left: 5px;
			padding-right: 5px;
			padding-top: 2px;
		}
		
		
		.panel-group .panel {
			border-radius: 0;
			box-shadow: none;
			
		}

		.panel-default > .panel-heading {
			padding: 0;
			border-radius: 0;
			color: #212121;			
		}	

		.panel-title {
			font-size: 14px;
		}

		.panel-title > a {
			display: block;
			padding: 15px;
			text-decoration: none;
		}

		.more-less {
			float: right;
			
		}

		
		label.btn span {
		  font-size: 1.1em ;
		}

		label input[type="radio"] ~ i.fa.fa-circle-o{
			color: #337ab7;    display: inline;
		}
		label input[type="radio"] ~ i.fa.fa-dot-circle-o{
			display: none;
		}
		label input[type="radio"]:checked ~ i.fa.fa-circle-o{
			display: none;
		}
		label input[type="radio"]:checked ~ i.fa.fa-dot-circle-o{
			color: #337ab7;    display: inline;
		}
		label:hover input[type="radio"] ~ i.fa {
		color: #333;
		}

		label input[type="checkbox"] ~ i.fa.fa-square-o{
			color: #337ab7;    display: inline;
		}
		label input[type="checkbox"] ~ i.fa.fa-check-square-o{
			display: none;
		}
		label input[type="checkbox"]:checked ~ i.fa.fa-square-o{
			display: none;
		}
		label input[type="checkbox"]:checked ~ i.fa.fa-check-square-o{
			color: #337ab7;    display: inline;
		}
		label:hover input[type="checkbox"] ~ i.fa {
		color: #333;
		}

		div[data-toggle="buttons"] label.active{
			color: #333;
			font-weight: bold;
		}
		

		div[data-toggle="buttons"] label {
		display: inline-block;
		padding: 6px 12px;
		margin-bottom: 0;
		font-size: 14px;
		font-weight: normal;
		line-height: 2em;
		text-align: left;
		white-space: nowrap;
		vertical-align: top;
		cursor: pointer;
		background-color: none;
		border: 0px solid 
		#337ab7;
		border-radius: 3px;
		color: #337ab7;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		-o-user-select: none;
		user-select: none;
		}

		div[data-toggle="buttons"] label:hover {
		color: #7AA3CC;
		}

		div[data-toggle="buttons"] label:active, div[data-toggle="buttons"] label.active {
		-webkit-box-shadow: none;
		box-shadow: none;
		}
		
		.fa-2x {
			font-size: 1.5em;
		}
		.lopa-panel{
			padding: 1px;
			padding-top: 10px;
			padding-right: 10px;
		}
	
	</style>
</head>
<body ng-controller="lopaDataController">
	<?php
        include("topNavBar.html");
    ?>
	<div class="container-fluid">
        <div class="row">
            <?php
                include("homeSideBar.html");
            ?>
            <div id="mainDiv" class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <h3 class="page-header">LOPA</h3>
                <div class="panel panel-default filterPanel">
					<div class="panel-body">
							<div class="row">
								<div class="col-md-12">
									<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px;color:#337ab7;" data-toggle="modal" data-target="#myModal"></i></span>
             
									<div id="myModal" class="modal fade" role="dialog">
									  <div class="modal-dialog modal-lg">
									
										<!-- Modal content-->
										<div class="modal-content">
											<!-- <div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="modal-title">LRU Weight</h4>
											</div> -->
											<div class="modal-body">
											<div><h5><b>Threshold Info</b></h5></div>
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
												<br/>
												<div><h5><b>Heatmap Info</b></h5></div>
													<div align="center"> <img src="../img/gradientInfo.png"  > </div>
												<br/>
												<!-- <br/>
												<div><h5><b>Per Hour Data Info</b></h5></div>
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
												</table>-->
											</div>
											<!-- <div class="modal-footer">
												<div class="row">
												<div class="text-center"> 
													<button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
												</div>
												</div>
											</div> -->
										</div>
									  </div>
									</div>  

								</div>
							</div>
						 <div class="row">
							<div class="col-md-2">
								<div class="row filterAlignment" style="padding-left:20px;" ><b> Airline	</b></div>
								<div class="row filterAlignment" style="padding-left:20px;">
									<select id="selectedAirline" name="airlineNameList" ng-model="selectedAirline"  class="form-control" ng-change="loadPlatforms(selectedAirline)" ng-options="option.acronym for option in airlines track by option.id" ng-required="true" >
									</select>
								 </div>
							</div>
							<div class="col-md-2">
								 <div class="row filterAlignment"><b>  Platform	</b></div>
								<div class="row filterAlignment">
									<select id="selectedPlatform" name="platformList" class="form-control" ng-model="selectedPlatform" ng-change="loadConfigTypes(selectedAirline,selectedPlatform)" ng-options="option.platform for option in platforms track by option.platform" ng-required="true">
									  <option value=''>--Select--</option>
									</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="row filterAlignment"><b>  Config Type	</b> </div>
								<div class="row filterAlignment">
									<select id="selectedConfigType" name="configTypeList" class="form-control" ng-model="selectedConfigType" ng-options="option.configType for option in configTypeList track by option.configType" ng-change="loadTailsigns(selectedAirline,selectedPlatform,selectedConfigType)" ng-required="true">
									  <option value=''>--Select--</option>
									</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="row filterAlignment"><b>  Tailsign	</b> </div>
								<div class="row filterAlignment">
									<select id="selectedTailsign"></select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="row filterAlignment" style="padding-bottom:3px;"><b>  From	</b> </div>
								<div class="row" style="padding-right:20px;">
									<input id="startDateTimePicker" type="text" name="startDateTime" size="15" class="form-control">
								</div>
							</div>
							<div class="col-md-2">
								<div class="row filterAlignment" style="padding-bottom:3px;"><b>  To	</b> </div>
								<div class="row" style="padding-right:20px;">
									<input id="endDateTimePicker" type="text" name="startDateTime" size="15" class="form-control">
								</div>
							</div>
						<!--	-->
							<div id="error" style="display:inline-block"></div>
						  </div>
							<br/>
						   <a data-toggle="collapse" href="#advancedFilter" role="button" aria-expanded="false" aria-controls="advancedFilter"><font style="font-weight: bold;">Advanced Filter</font>&nbsp;<span class="glyphicon glyphicon-chevron-right"></span></a>
                            <div class="collapse panel-body" id="advancedFilter">
                                <div class="row">
												<div class="col-md-2" style="padding-top:5px;">
													<div class="row filterAlignment" ><b>  Fault Code	</b> </div>
													<div class="row filterAlignment">
														<select id="faultCode" multiple="multiple"></select>
													</div>
												</div>
												<div class="col-md-2" style="padding-top:5px;">
													<div class="row filterAlignment" ><b>  Failure Code	</b> </div>
													<div class="row filterAlignment">
														<select id="failureCode" multiple="multiple"></select>
													</div>
												</div>
												<div class="col-md-3" style="padding-top:5px;">
													<div class="row filterAlignment" > <b> ImpactedServices Code	</b> </div>
													<div class="row filterAlignment">
														<select id="impactedServicesCode" multiple="multiple"></select>
													</div>
												</div>
												<div class="col-md-2" id="flightPhasesDiv" style="padding-top:5px;">
													<div class="row filterAlignment" > <b> Flight Phases	</b> </div>
													<div class="row filterAlignment">
														<select id="flightPhases" multiple="multiple" ></select>
													</div>
												</div>
												<div class="col-md-3" style="padding-top:5px;">
													<div class="row filterAlignment" ><b>  Resets	</b> </div>
													<div class="row filterAlignment">
														<select id="resetsValue" multiple="multiple"></select>
													</div>
												</div>
										  </div>                            
                            </div>

						
						<div class="row">
							<div class="col-md-12 text-center">
								<button id="filter" class="btn btn-primary" >Filter</button> &nbsp;&nbsp;
								<button id="resetbtn" class="btn btn-primary" >Reset</button>
							</div>
						</div>
					</div>
				</div>
					<div id="errorInfo" class="container-fluid text-center" >
						<h5>No data available for the selected duration or selected filters</h5>
					</div>
				<div id="btnInfo" class="container-fluid">
					<div class="text-center">
							<div class="container-fluid">
							 <div class="btn-group">
								<button id="resetslbl" type="button" class="btn btn-primary">Resets</button>
								<button id="activeFailureslbl" type="button" class="btn btn-primary">Active Failures</button>
								<button id="allFailureslbl" type="button" class="btn btn-primary">All Failures</button>
								<button id="allFaultslbl" type="button" class="btn btn-primary">All Faults</button>
								<button id="impactedServiceslbl" type="button" class="btn btn-primary">Impacted Services</button>
								<button id="eventslbl" type="button" class="btn btn-primary">Events</button>
							  </div>
							  </div>
						</div>
						<div class="btn-group" style="margin-bottom: 5px; width:100%;" >						
							<button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" style="float: right;"><i class="fa fa-bars"></i> Export Lopa Data</button>
							<ul class="dropdown-menu" role="menu" style="right: 0; left:87%; min-width: 130px;">
								<li><a href="#" onclick="exportLopaSeatViewData('csv')"> <img src="../img/csv.png" width="24px"> CSV</a></li>
								<li><a href="#" onclick="exportLopaSeatViewData('xls')"> <img src="../img/xls.png" width="24px"> XLS</a></li>
							</ul>
						</div>
						<div align="center" id="seatResetsChartLegend" class="chart-legend">
							<!--<ul style="padding-left: 0px;">
								<li>
									<a class="btn" type="radio" onclick="filterCategory('lopa');" style="border: 1px solid; width: 140px;"><span style="background-color:#428bca"></span>Threshold View</a>
									<a  class="btn" onclick="filterCategory('heatmap');" style="border: 1px solid; width: 140px;"><span style="background-color:#fe5757"></span>Heatmap View</a>
									<a  class="btn" onclick="filterCategory('perhour');" style="border: 1px solid; width: 140px;"><span style="background-color:#FBC200"></span>Per Hour View</a>
								</li>
							</ul>-->
							<div class="btn-group" data-toggle="buttons">
								<label class="btn active">
								  <input type="radio" name='view' id="lopaRadioBtn" checked onchange="filterCategory('lopa');"><i class="fa fa-circle-o fa-2x"></i><i class="fa fa-dot-circle-o fa-2x"></i> <span>  Threshold View</span>
								</label>
								<label class="btn">
								  <input type="radio" name='view' id="heatmapRadioBtn" onchange="filterCategory('heatmap');"><i class="fa fa-circle-o fa-2x"></i><i class="fa fa-dot-circle-o fa-2x"></i><span> Heatmap View</span>
								</label>
								<!--<label class="btn">
								  <input type="radio" name='view' id="perHourRadioBtn" onchange="filterCategory('perhour');"><i class="fa fa-circle-o fa-2x"></i><i class="fa fa-dot-circle-o fa-2x"></i><span> Per Hour Data View</span>
								</label>-->
							  </div>
															
					</div>
					
					<br>
					<div id="panel">
						<div id="resetsLopaPanel">
							<div align="center"  style="overflow: auto;">						
								<table id="resetsLopa" align="center">
								</table>
								<div id="loadingResetsLopa">
									<img src="../img/loadingicon1.gif" style="height:50px;"> <br/> Loading LOPA...
								</div>
							</div>
							
						</div>
							
							<div id="resetsHeatmapPanel" align="center"  style="overflow: auto;">
								<canvas id="resetsHeatmap" class="scrollChart" height="240" ></canvas>
							</div>
						
						<div id="resetsPerHourDataPanel">
							<div align="center"  style="overflow: auto;">						
								<table id="resetsPerHourData" align="center">
								</table>
							</div>
							
						</div>
							
				<div id="activeFailuresLopaPanel" style="display:none">
					<!--<span class="text-center">Active Failures</span>
					<br>-->
					<div align="center"  style="overflow: auto;">
						<table id="activeFailuresLopa" align="center"></table>
						<div id="loadingActiveFailuresLopa">
							<img src="../img/loadingicon1.gif" style="height:50px;"> <br/>  Loading LOPA...
						</div>
					</div>									
				</div>
				<div id="activeFailuresHeatmapPanel">
					<div id="container" align="center"  style="overflow: auto;">
						<canvas id="activeFailuresHeatmap" class="scrollChart" ></canvas>
					</div>	
				</div>
						<div id="activeFailuresPerHourDataPanel">
							<div align="center"  style="overflow: auto;">						
								<table id="activeFailuresPerHourData" align="center">
								</table>
							</div>
							
						</div>
				<div id="allFailuresLopaPanel" style="display:none">
					<!--<span class="text-center">All Failures</span>
					<br>-->
					<div align="center"  style="overflow: auto;">	
						<table id="failuresLopa" align="center"></table>
						<div id="loadingFailuresLopa">
							<img src="../img/loadingicon1.gif" style="height:50px;">  <br/> Loading LOPA...
						</div>
					</div>
					
				</div>
				<div id="failuresHeatmapPanel">
					<div id="container" align="center"  style="overflow: auto;">
						<canvas id="failuresHeatmap" class="scrollChart" ></canvas>
					</div>	
				</div>
				<div id="failuresPerHourDataPanel">
					<div align="center"  style="overflow: auto;">						
						<table id="failuresPerHourData" align="center">
						</table>
					</div>
							
				</div>
				<div id="allFaultsLopaPanel" style="display:none">
					<!--<span class="text-center">All Faults</span>
					<br>-->
					<div align="center" style="overflow: auto;">	
						<table id="faultsLopa" align="center"></table>
						<div id="loadingFaultsLopa">
							<img src="../img/loadingicon1.gif" style="height:50px;"> <br/>  Loading LOPA...
						</div>
					</div>
				</div>
				<div id="faultsHeatmapPanel">
					<div id="container" align="center"  style="overflow: auto;">
						<canvas id="faultsHeatmap" class="scrollChart" ></canvas>
					</div>
				</div>
				<div id="faultsPerHourDataPanel">
					<div align="center"  style="overflow: auto;">						
						<table id="faultsPerHourData" align="center">
						</table>
					</div>
							
				</div>
				<div id="impactedServicesPanel" style="display:none">
					<!--<span class="text-center">Impacted Services</span>
					<br>-->
					<div align="center"  style="overflow: auto;">	
						<table id="impactedServicesLopa" align="center"></table>
						<div id="loadingImpactedServicesLopa">
							<img src="../img/loadingicon1.gif" style="height:50px;"> <br/>  Loading LOPA...
						</div>
					</div>
					
				</div>
				<div id="impactedServicesHeatmapPanel" align="center">
					<div id="container"  style="overflow: auto;">
						<canvas id="impactedServicesHeatmap" class="scrollChart" ></canvas>
					</div>
				</div>				
				<div id="impactedServicesPerHourDataPanel">
					<div align="center"  style="overflow: auto;">						
						<table id="impactedServicesPerHourData" align="center">
						</table>
					</div>							
				</div>
				<div id="eventsPanel" style="display:none">
					<!--<span class="text-center">Events</span>
					<br>-->
					<div align="center"  style="overflow: auto;">	
						<table id="eventsLopa" align="center"></table>
						<div id="loadingEventsLopa">
							<img src="../img/loadingicon1.gif" style="height:50px;"> <br/>  Loading LOPA...
						</div>
					</div>
					
				</div>
				<div id="eventsHeatmapPanel" align="center">
					<div id="container"  style="overflow: auto;">
						<canvas id="eventsHeatmap" class="scrollChart" ></canvas>
					</div>
				</div>
				<div id="eventsPerHourDataPanel">
					<div align="center"  style="overflow: auto;">						
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

			<br><br>
			
			<!-- Tabular View  -->
            <div class="tab-content" id="seatModal" role="tab" data-toggle="tab">
					
					
					<div id="loadingTabData" align="center">
							<img src="../img/loadingicon1.gif" style="height:50px;"> <br/>  Loading Data...
						</div>
					<div id="tabularData">
						<h4 id="seatHeader" class="modal-title">Seat Details</h4>	
						<div class="btn-group" style="margin-bottom: 5px; width:100%;" >						
							<button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" style="float: right;"><i class="fa fa-bars"></i> Export Table Data</button>
							<ul class="dropdown-menu" role="menu" style="right: 0; left:87%; min-width: 130px;">
								<li><a href="#" onclick="exportOffloads('csv')"> <img src="../img/csv.png" width="24px"> CSV</a></li>
								<li><a href="#" onclick="exportOffloads('xls')"> <img src="../img/xls.png" width="24px"> XLS</a></li>
							</ul>
						</div>
						<!-- Nav tabs -->
						<ul class="nav nav-tabs" role="tablist" id="myTabs">
							<li role="presentation" id="summaryPanel" class="active"><a href="#summaryTab" aria-controls="summaryTab" role="tab" data-toggle="tab">Summary</a></li>
							<li role="presentation" id="resetsPanel"><a href="#resetsTab" aria-controls="resetsTab" role="tab" data-toggle="tab">Resets</a></li>
							<li role="presentation" id="activeFailuresPanel"><a href="#activeFailuresTab" aria-controls="activeFailuresTab" role="tab" data-toggle="tab">Active Failures</a></li>
							<li role="presentation" id="failuresPanel"><a href="#failuresTab" aria-controls="failuresTab" role="tab" data-toggle="tab">All Failures</a></li>							
							<li role="presentation" id="faultsPanel"><a href="#faultsTab" aria-controls="faultsTab" role="tab" data-toggle="tab">All Faults</a></li>
							<li role="presentation" id="impactPanel"><a href="#impactServicesTab" aria-controls="impactServicesTab" role="tab" data-toggle="tab">Impacted Services</a></li>
							<li role="presentation" id="appeventsPanel"><a href="#eventsTab" aria-controls="eventsTab" role="tab" data-toggle="tab">Events</a></li>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="row">
												<div class="col-xs-12">
													<form class="form-horizontal">
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label text-left">Serial Number: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduSerialNumber"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">LRU Type: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduLruType"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">HW Part Number: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduHwPartNumber"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Revision: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduRevision"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Mod: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduMod"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Mac Address: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="svduMacAddress"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Last Update: </label>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="row">
												<div class="col-xs-12">
													<form class="form-horizontal">
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label text-left">Serial Number: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuSerialNumber"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">LRU Type: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuLruType"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">HW Part Number: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuHwPartNumber"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Revision: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuRevision"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Mod: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuMod"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Mac Address: </label>
															<div class="col-sm-6">
																<div class="form-control-static">
																	<span id="pcuMacAddress"></span>
																</div>
															</div>
														</div>
														<div class="form-group margin-bottom">
															<label for="currentStatus" class="col-sm-6 control-label">Last Update: </label>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduResetsTable" data-classes="table table-no-bordered table-hover"  data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="lastUpdate" data-sortable="true">Time</th>
															<th data-field="eventName" data-sortable="true">Type</th>
															<th data-field="eventInfo" data-sortable="true">Reset Reason</th>
														</tr> 
													</thead>
												</table>
											</div>
										</div>
									</div>
									<div class="col-md-6">
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuResetsTable" data-classes="table table-no-bordered table-hover"  data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="lastUpdate" data-sortable="true">Time</th>
															<th data-field="eventName" data-sortable="true">Type</th>
															<th data-field="eventInfo" data-sortable="true">Reset Reason</th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduActiveFailuresTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="failureCode" data-sortable="true">Code</th>
															<th data-field="failureDesc" data-sortable="true">Description</th>
															<th data-field="legFailureCount" data-sortable="true">Leg Count</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuActiveFailuresTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="failureCode" data-sortable="true">Code</th>
															<th data-field="failureDesc" data-sortable="true">Description</th>
															<th data-field="legFailureCount" data-sortable="true">Leg Count</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduFailuresTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="failureCode" data-sortable="true">Code</th>
															<th data-field="failureDesc" data-sortable="true">Description</th>
															<th data-field="legFailureCount" data-sortable="true">Leg Count</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuFailuresTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="failureCode" data-sortable="true">Code</th>
															<th data-field="failureDesc" data-sortable="true">Description</th>
															<th data-field="legFailureCount" data-sortable="true">Leg Count</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduFaultsTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="faultCode" data-sortable="true">Code</th>
															<th data-field="faultDesc" data-sortable="true">Description</th>
															<th data-field="reportingHostname" data-sortable="true">Reporting Host</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="detectionTime" data-sortable="true">Detection Time</th>
															<th data-field="clearingTime" data-sortable="true">Clearing Time</th>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuFaultsTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="faultCode" data-sortable="true">Code</th>
															<th data-field="faultDesc" data-sortable="true">Description</th>
															<th data-field="reportingHostname" data-sortable="true">Reporting Host</th>
															<th data-field="monitorState" data-sortable="true">State</th>
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="detectionTime" data-sortable="true">Detection Time</th>
															<th data-field="clearingTime" data-sortable="true">Clearing Time</th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduImpactedServicesTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr>   
															<th data-field="idFlightLeg" data-sortable="true"  data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>	
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="failureCode" data-sortable="true">Failure Code</th>
															<th data-field="failureDesc" data-sortable="true">Failure Description</th>
															<th data-field="failureImpact" data-sortable="true">Failure Impact</th>
															<th data-field="name" data-sortable="true">Service Name</th>
															<th data-field="description" data-sortable="true">Service Description</th>
															<th data-field="monitorState" data-sortable="true">Monitor Impact</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuImpactedServicesTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true"  data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="correlationDate" data-sortable="true">Correlation Date</th>	
															<th data-field="idFlightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="failureCode" data-sortable="true">Failure Code</th>
															<th data-field="failureDesc" data-sortable="true">Failure Description</th>
															<th data-field="failureImpact" data-sortable="true">Failure Impact</th>
															<th data-field="name" data-sortable="true">Service Name</th>
															<th data-field="description" data-sortable="true">Service Description</th>
															<th data-field="monitorState" data-sortable="true">Monitor Impact</th>
															<th data-field="lastUpdate" data-sortable="true">Last Update</th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="svduEventsTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="detectionTime" data-sortable="true">Detection Time</th>
															<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="reportingHostName" data-sortable="true">Reporting Hostname</th>
															<th data-field="faultCode" data-sortable="true">Fault Code</th>
															<th data-field="faultDesc" data-sortable="true">Fault Description</th>
															<th data-field="param1" data-sortable="true">Param 1 </th>
															<th data-field="param2" data-sortable="true">Param 2 </th>
															<th data-field="param3" data-sortable="true">Param 3 </th>
															<th data-field="param4" data-sortable="true">Param 4 </th>
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
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											<div class="table-responsive">
												<table id="pcuEventsTable" data-classes="table table-no-bordered table-hover" data-striped="true">
													<thead>
														<tr> 
															<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">Flight Leg</th>
															<th data-field="detectionTime" data-sortable="true">Detection Time</th>
															<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
															<th data-field="reportingHostName" data-sortable="true">Reporting Hostname</th>
															<th data-field="faultCode" data-sortable="true">Fault Code</th>
															<th data-field="faultDesc" data-sortable="true">Fault Description</th>
															<th data-field="param1" data-sortable="true">Param 1 </th>
															<th data-field="param2" data-sortable="true">Param 2 </th>
															<th data-field="param3" data-sortable="true">Param 3 </th>
															<th data-field="param4" data-sortable="true">Param 4 </th>
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
										<h4>&nbsp;<i class="fa fa-desktop fa-fw" aria-hidden="true"></i> SVDU</h4>
										<div class="chart-panel">
											-
										</div>
									</div>
									<div class="col-md-6">
										<h4>&nbsp;<i class="fa fa-mobile fa-lg" aria-hidden="true"></i> Handset</h4>
										<div class="chart-panel">
											-
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
<script type="text/javascript">	
    var app = angular.module("myApp",[]);

    app.controller('lopaDataController', function($scope, $http) {
        init();
		var firstTime = true;
		
        function init() {
            getAirlines();	
			//$timeout("getAirliens", 2000);
			//$timeout("loadPlatforms", 2000);
        }

        function getAirlines() {
            var data = $.param({
                action: 'GET_AIRLINES',
				airIds:"<?php echo $airlineIds ?>"
            });
        
            var config = {
                headers : {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                }
            };

            $http.post('lopaData.php', data, config)
            .success(function (data, status, headers, config) {
                 
                $scope.airlines = data;	
				$scope.selectedAirline=$scope.airlines[0];
				//if(firstTime) {console.log('first time loading');
					$scope.loadPlatforms($scope.selectedAirline);
				//}
            })
            .error(function (data, status, header, config) {
            });
            
        }

        $scope.loadPlatforms = function(selectedAirline) {
            var airlineId = selectedAirline.id;

            var data = $.param({
                airlineId: airlineId,
                action: 'GET_PLATFORMS'
            });
        
            var config = {
                headers : {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                }
            };

            $http.post('lopaData.php', data, config)
            .success(function (data, status, headers, config) {
                $("#selectedAirline").css("border-color","");
                $("#selectedPlatform").css("border-color","");
                $("#selectedConfigType").css("border-color","");
                $("#error").empty();
                $scope.platforms = data;
				$scope.selectedPlatform=$scope.platforms[0];
				//if(firstTime) {console.log('first time loading');
					$scope.loadConfigTypes($scope.selectedAirline,$scope.selectedPlatform);
				//}

            })
            .error(function (data, status, header, config) {
            });
        };
            
        $scope.loadConfigTypes = function(selectedAirline, selectedPlatform) {
            var selectedPlatform = selectedPlatform.platform;
            var airlineId = selectedAirline.id;

            var data = $.param({
                airlineId: airlineId,
                platform: selectedPlatform,
                action: 'GET_CONFIG_TYPE'
            });
        
            var config = {
                headers : {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
                }
            };

            $http.post('lopaData.php', data, config)
            .success(function (data, status, headers, config) {
                $("#selectedPlatform").css("border-color","");
                $("#error").empty();
                $scope.configTypeList = data;
				$scope.selectedConfigType=$scope.configTypeList[0];
				//if(firstTime) {console.log('first time loading');
					$scope.loadTailsigns($scope.selectedAirline,$scope.selectedPlatform,$scope.selectedConfigType);
				//}
            })
            .error(function (data, status, header, config) {
            });
        };
		
        $scope.loadTailsigns = function(selectedAirline, selectedPlatform, selectedConfigType) {
            var selectedPlatform = selectedPlatform.platform;
            var airlineId = selectedAirline.id;
            var configType = selectedConfigType.configType;
            //$('#btnInfo').hide();
			$('#selectedTailsign').empty();
			$.ajax({
					type: "GET",
					url: "../ajax/getBiteData.php",
					data: { 
							'action': 'getTailsignlist',
							'airlineId' :selectedAirline.id,
							'config' :selectedConfigType.configType,
							'platform' :selectedPlatform
						},
					success: function(data) {					
							tailsignList=JSON.parse(data);
							
						for (var i = 0; i < tailsignList.length; i++) {
							var ts = tailsignList[i];
							$("#selectedTailsign").append('<option value=' + ts + '>' + ts + '</option>');
						}						
					$('#selectedTailsign').multiselect('rebuild');							
						//if(firstTime){							
														
							//loadHeatmap(fromDate,toDate,tailsign);
							//loadPerHourData(fromDate,toDate,tailsign);
							//loadFrame(tailsign);
							if(firstTime){
								fromDate = new Date ( $("#startDateTimePicker").val() );
								toDate = new Date ( $("#endDateTimePicker").val() );							
								$("#resetslbl").addClass("active");	
								tailsign=tailsignList[0];								
								loadFrame(tailsign);
								firstTime = false;								
							}else{
								
							}
							
							$('#failureCode').empty();
							$('#faultCode').empty();
							$('#impactedServicesCode').empty();
							getFaultCodelist(airlineId,selectedPlatform,configType,tailsign);
													
							
					},
					error: function (err) {
						console.log('Error', err);
					}
				});
        };			
		
				
		
        $scope.validate = function() {
        	$("#selectedTailsign").css("border-color","");
        	$("#error").empty();
        };
		$("#resetbtn").click(function(event) {
			$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });	
			toDate = new Date();
			fromDate = new Date();
			fromDate.setDate(fromDate.getDate() - 6);
			
			$('#startDateTimePicker').datetimepicker({
				format: "Y-m-d",
				value: fromDate,
				timepicker:false,
				weeks:true
			});

			$('#endDateTimePicker').datetimepicker({
				format: "Y-m-d",
				value: toDate,
				step:15,
				timepicker:false,
				weeks:true
			});					
			document.getElementById("advancedFilter").style.display = "none";
			
			$('#resetsValue').empty();
			getResets();				
			$('#flightPhases').empty();
			getflightPhases();	
			
			
			selectedPlatform = $("#selectedPlatform").val();
			airlineId = $("#selectedAirline").val();
			configType =$("#selectedConfigType").val(); 
			tailsign=$("#selectedTailsign").val();
			$('#faultCode').empty();
			$('#failureCode').empty();
			$('#impactedServicesCode').empty();
            getFaultCodelist(airlineId,selectedPlatform,configType,tailsign);
			//$('#failureCode').empty();
			//getFailureCodelist(airlineId,selectedPlatform,configType,tailsign);
			//$('#impactedServicesCode').empty();
			//getImpactedServiceslist(airlineId,selectedPlatform,configType,tailsign);			
			getAirlines();
		});
	  
    });
	
	
	function getFailureCodelist(airlineIdA,selectedPlatformA,configTypeA,tailsignA){	
			var fromDateA = new Date ( $("#startDateTimePicker").val() );
			var toDateA = new Date ( $("#endDateTimePicker").val() );
			$('#failureCode').multiselect('disable');
			$.ajax({
				type: "GET",
				url: "../ajax/getBiteData.php",
				data: { 
						'action': 'getFailureCodelist',
						'airlineId' :airlineIdA,
						'config' :configTypeA,
						'platform' :selectedPlatformA,
						'tailsign' :tailsignA,
						'startDateTime': fromDateA.getFullYear() + '-' + (fromDateA.getMonth() + 1) + '-' + fromDateA.getDate(),
						'endDateTime': toDateA.getFullYear() + '-' + (toDateA.getMonth() + 1) + '-' + toDateA.getDate(),
					},
				success: function(data) {
					if(data) {
					var failureCodelist = JSON.parse(data);
						for (var i = 0; i < failureCodelist.length; i++) {
							var ts = failureCodelist[i];
							$("#failureCode").append('<option value=' + ts + '>' + ts + '</option>');
						}
						$('#failureCode').multiselect('rebuild');
					}
					if(failureCodelist.length>0){
						$('#failureCode').multiselect('enable');
					}else{
						$('#failureCode').multiselect('disable');
					}
					getImpactedServiceslist(airlineIdA,selectedPlatformA,configTypeA,tailsignA);
					
				},
				error: function (err) {
					console.log('Error', err);
				}
			});
		}
		
		
		function getFaultCodelist(airlineIdA,selectedPlatformA,configTypeA,tailsignA){
			//$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading Advanced Filter Data..." });
			var fromDateA = new Date ( $("#startDateTimePicker").val() );
			var toDateA = new Date ( $("#endDateTimePicker").val() );
			$('#faultCode').multiselect('disable');
			$('#failureCode').multiselect('disable');
			$('#impactedServicesCode').multiselect('disable');
			$.ajax({
				type: "GET",
				url: "../ajax/getBiteData.php",
				data: { 
						'action': 'getFaultCodelist',
						'airlineId' :airlineIdA,
						'config' :configTypeA,
						'platform' :selectedPlatformA,
						'tailsign' :tailsignA,
						'startDateTime': fromDateA.getFullYear() + '-' + (fromDateA.getMonth() + 1) + '-' + fromDateA.getDate(),
						'endDateTime': toDateA.getFullYear() + '-' + (toDateA.getMonth() + 1) + '-' + toDateA.getDate(),
					},
				success: function(data) {
					if(data) {
					var faultCodelist = JSON.parse(data);
						for (var i = 0; i < faultCodelist.length; i++) {
							var ts = faultCodelist[i];
							$("#faultCode").append('<option value=' + ts + '>' + ts + '</option>');
						}
						$('#faultCode').multiselect('rebuild');
					}
					if(faultCodelist.length>0){
						$('#faultCode').multiselect('enable');
					}else{
						$('#faultCode').multiselect('disable');
					}
					getFailureCodelist(airlineIdA,selectedPlatformA,configTypeA,tailsignA);	
					
				},
				error: function (err) {
					console.log('Error', err);
				}
			});
		}
		
		function getImpactedServiceslist(airlineIdA,selectedPlatformA,configTypeA,tailsignA){	
			var fromDateA = new Date ( $("#startDateTimePicker").val() );
			var toDateA = new Date ( $("#endDateTimePicker").val() );
			$('#impactedServicesCode').multiselect('disable');
			$.ajax({
				type: "GET",
				url: "../ajax/getBiteData.php",
				data: { 
						'action': 'getImpactedServiceslist',
						'airlineId' :airlineIdA,
						'config' :configTypeA,
						'platform' :selectedPlatformA,
						'tailsign' :tailsignA,
						'startDateTime': fromDateA.getFullYear() + '-' + (fromDateA.getMonth() + 1) + '-' + fromDateA.getDate(),
						'endDateTime': toDateA.getFullYear() + '-' + (toDateA.getMonth() + 1) + '-' + toDateA.getDate(),
					},
				success: function(data) {
					if(data) {
					var impactedServiceslist = JSON.parse(data);
						for (var i = 0; i < impactedServiceslist.length; i++) {
							var ts = impactedServiceslist[i];
							$("#impactedServicesCode").append('<option value=' + ts + '>' + ts + '</option>');
						}
						$('#impactedServicesCode').multiselect('rebuild');
					}
					if(impactedServiceslist.length>0){
						$('#impactedServicesCode').multiselect('enable');
					}else{
						$('#impactedServicesCode').multiselect('disable');
					}
					//setTimeout(function(){ $.unblockUI() },800);
					
				},
				error: function (err) {
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

	function loadFrame(tailsign){	
		$('#btnInfo').hide();
		$(".dz-hidden-input").prop("disabled",true);
		fromDate = new Date ( $("#startDateTimePicker").val() );
		toDate = new Date ( $("#endDateTimePicker").val() );				
		tailsign=tailsign;	
		document.getElementById('lopaRadioBtn').checked=true;
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
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}
	}

    $(document).ready(function(){
		var filterClick=true;
		$('#btnInfo').hide();	
        $('.nav-sidebar li').removeClass('active');
        $("#homeSideBarLOPA").addClass("active");
		$('#errorInfo').hide();
		toDate = new Date();
		fromDate = new Date();
		fromDate.setDate(fromDate.getDate() - 6);
		
		$('#startDateTimePicker').datetimepicker({
			format: "Y-m-d",
			value: fromDate,
			timepicker:false,
			weeks:true
		});

		$('#endDateTimePicker').datetimepicker({
			format: "Y-m-d",
			value: toDate,
			step:15,
			timepicker:false,
			weeks:true
		});
		
		$("#startDateTimePicker").on("blur", function(e) {
			loadingAdvancedFilters();
		});
		
		$("#endDateTimePicker").on("blur", function(e) {
			loadingAdvancedFilters();
		});
		
		/*$("#startDateTimePicker").datetimepicker({
		  onChangeDateTime:loadingAdvancedFilters
		});
		$("#endDateTimePicker").datetimepicker({
		  onChangeDateTime:loadingAdvancedFilters
		});*/
		function loadingAdvancedFilters(){
		    var selectedPlatformA = $("#selectedPlatform").val();
			var airlineIdA = $("#selectedAirline").val();
			var configTypeA =$("#selectedConfigType").val(); 
			var tailsignA=$("#selectedTailsign").val();
			$('#failureCode').empty();
			$('#faultCode').empty();
			$('#impactedServicesCode').empty();
            //getFailureCodelist(airlineId,selectedPlatform,configType,tailsign);
			getFaultCodelist(airlineIdA,selectedPlatformA,configTypeA,tailsignA);
			//getImpactedServiceslist(airlineId,selectedPlatform,configType,tailsign);
		}
		
		
		$('#selectedTailsign').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,			
			maxHeight: 300,
			maxWidth:'100%',
			onChange: function(element, checked) {
				selectedPlatform = $("#selectedPlatform").val();
				airlineId = $("#selectedAirline").val();
				configType =$("#selectedConfigType").val(); 
				tailsign=$("#selectedTailsign").val();
				$('#failureCode').empty();
				$('#faultCode').empty();
				$('#impactedServicesCode').empty();
				//getFailureCodelist(airlineId,selectedPlatform,configType,tailsign);
				getFaultCodelist(airlineId,selectedPlatform,configType,tailsign);
				//getImpactedServiceslist(airlineId,selectedPlatform,configType,tailsign);
            }
		});
		
		$('#faultCode').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,			
			maxHeight: 300,
			maxWidth:300
		});
		$('#failureCode').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
			maxHeight: 300,
			maxWidth:300			
		});
		$('#impactedServicesCode').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
			maxHeight: 300,
			maxWidth:300
		});
		$('#flightPhases').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
			maxHeight: 300,
			maxWidth:300
		});
		$('#flightPhases').val(['4:Climb', '5:Cruise']);
		$('#resetsValue').multiselect({
			includeSelectAllOption: true,
			selectAllText: 'All',
			nonSelectedText: 'None',
			allSelectedText: 'All',
			buttonWidth: '100%',
			selectedClass: '',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
			maxHeight: 300,
			maxWidth:300
		});
		
		
		
		//loadFrame(tailsign);
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
	
	$("#filter").click(function(event) {		
			//loadFrame(tailsign);
			document.getElementById('lopaRadioBtn').checked=true;
			$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
			fromDate = new Date ( $("#startDateTimePicker").val() );
			toDate = new Date ( $("#endDateTimePicker").val() );
			tailsign=$("#selectedTailsign").val();
			/*reloadLopa('activeFailures', 'loadingActiveFailuresLopa', 'activeFailuresLopa', fromDate, toDate,tailsign);
			reloadLopa('failures', 'loadingFailuresLopa', 'failuresLopa', fromDate, toDate,tailsign);
			reloadLopa('faults', 'loadingFaultsLopa', 'faultsLopa', fromDate, toDate,tailsign);
			reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate,tailsign);		
			reloadLopa('impactedServices', 'loadingImpactedServicesLopa', 'impactedServicesLopa', fromDate, toDate,tailsign);
			reloadLopa('applications', 'loadingEventsLopa', 'eventsLopa', fromDate, toDate,tailsign);
			loadHeatmap(fromDate, toDate,tailsign);*/
			if(document.getElementById('lopaRadioBtn').checked) {
			  $('#flightPhases').multiselect('enable');
			  filterCategory('lopa');
			}else if(document.getElementById('heatmapRadioBtn').checked) {
			  $('#flightPhases').multiselect('enable');
			  filterCategory('heatmap');
			}else if(document.getElementById('perHourRadioBtn').checked) {
			  $('#flightPhases').multiselect('disable');
			  filterCategory('perhour');
			}
		
		//loadHeatmap(fromDate, toDate,tailsign);
		//loadPerHourData(fromDate, toDate,tailsign);
		event.preventDefault();
	});
	
	function reloadLopa(dataType, loadingIcon, table, startDate, endDate,tailsign) {
		$('#' + table).html('');
		$('#' + loadingIcon).hide();
		$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });	
		$('#seatModal').hide();
		loadLopa(dataType, loadingIcon, table, startDate, endDate,tailsign);
	}
	var tableHeightnWidth='';
	var tableHeight='';
	var tableWidth='';
		
	function loadLopa(dataType, loadingIcon, table, startDate, endDate,tailsign) {
		$("#filter").prop('disabled', true);
		$("#resetbtn").prop('disabled', true);
		$("#" + table).bootstrapTable('destroy');
		//tailsign=$("#selectedTailsign").val();
		$("#" + table).removeClass('lopa-panel');
		faultCodes=$("#faultCode").val();
		failureCodes=$("#failureCode").val();
		ImpactedServicesCodes=$("#impactedServicesCode").val();
		commandedResets=$("#commandedResets").val();
		unCommandedResets=$("#unCommandedResets").val();
		flightPhases=$("#flightPhases").val();
		resetsEvent=$("#resetsValue").val();
		var filteredData;
		var faultCodesarray=[];
		var failureCodessarray=[];
		var ImpactedServicesCodesarray=[];
		var flightPhasessarray=[];
		var resetsarray=[];
		
		if(faultCodes!=null){			
			/*for(code in faultCodes){
				var str=faultCodes[code].split(':');
				faultCodesarray.push(str[0]);
			}*/
			faultCodesarray=faultCodes;
		}
		if(failureCodes!=null){
			/*for(code in failureCodes){
				var str=failureCodes[code].split(':');
				failureCodessarray.push(str[0]);
			}	*/	
			failureCodessarray=failureCodes;			
		}
		if(ImpactedServicesCodes!=null){
			/*for(code in ImpactedServicesCodes){
				var str=ImpactedServicesCodes[code].split(':');
				ImpactedServicesCodesarray.push(str[0]);
			}*/
			ImpactedServicesCodesarray=ImpactedServicesCodes;
		}
		if(flightPhases!=null){
			for(code in flightPhases){
				var str=flightPhases[code].split(':');
				flightPhasessarray.push(str[0]);
			}
		}
		
		if(resetsEvent!=null){
			for(r in resetsEvent){
				var str=resetsEvent[r].split(':');
				var str1="'"+str[0]+"'";
				resetsarray.push(str1);
			}
		}
		
		$('#errorInfo').hide();
		//$('#' + loadingIcon).show();
		data = {
			tailsign:tailsign,
			startDateTime: startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate(),
			endDateTime: endDate.getFullYear() + '-' + (endDate.getMonth() + 1) + '-' + endDate.getDate(),
			dataType: dataType,
			faultCode:faultCodesarray.toString(),
			failureCode:failureCodessarray.toString(),
			ImpactedServicesCode:ImpactedServicesCodesarray.toString(),
			flightPhases:flightPhasessarray.toString(),
			resetCode:resetsarray.toString()
		};
		
		$.ajax({
			type: "GET",
			url: "../ajax/getLopaDatafetch.php",
			data: data,
			
			success: function(data) {
			filteredData=data;
			
				if (data.indexOf("Could not select") >= 0) {
					$('#btnInfo').hide();
					$('#errorInfo').show();						
					$('#' + loadingIcon).hide();	
					$('#seatModal').hide();					
					$('#panel').hide();
					$("#filter").prop('disabled', false);
					$("#resetbtn").prop('disabled', false);				
					$.unblockUI();
				}else if(data.indexOf("No Data Available") >= 0){
					$('#btnInfo').hide();
					$('#errorInfo').show();						
					$('#' + loadingIcon).hide();	
					$('#seatModal').hide();					
					$('#panel').hide();
					$("#filter").prop('disabled', false);
					$("#resetbtn").prop('disabled', false);				
					$.unblockUI();
				}else{					
					$('#btnInfo').show();
					$('#errorInfo').hide();	
					$('#' + loadingIcon).hide();
					$("#" + table).bootstrapTable('destroy');
					$('#table').bootstrapTable();
					$("#" + table).append(data);
					//tableHeightnWidth=document.getElementById(table);
					//if(tableHeightnWidth.offsetHeight!=0 && tableHeightnWidth.offsetWidth!=0){						
					//	tableHeight=tableHeightnWidth.offsetHeight+10;
					//	tableWidth=tableHeightnWidth.offsetWidth;
					//}					
					$("#" + table).addClass('lopa-panel');
					$('#seatModal').hide();
					$('#panel').show();	
					$("#filter").prop('disabled', false);
					$("#resetbtn").prop('disabled', false);				
					//setTimeout(function(){ $.unblockUI() },800);	
					$.unblockUI();
					tableHeightnWidth=document.getElementById(table);
					if(tableHeightnWidth.offsetHeight!=0 && tableHeightnWidth.offsetWidth!=0){						
						tableHeight=tableHeightnWidth.offsetHeight+10;
						tableWidth=tableHeightnWidth.offsetWidth;
					}					
				}
				
			},
			error: function (err) {
				console.log('Error', err);
			}
		});
	}
	
			function loadPerHourData(fromDate, toDate,tailsign){
				reloadPerHourData('reset', 'loadingResetsLopa', 'resetsPerHourData', fromDate, toDate,tailsign);
				reloadPerHourData('activeFailures', 'loadingResetsLopa', 'activeFailuresPerHourData', fromDate, toDate,tailsign);
				reloadPerHourData('failures', 'loadingResetsLopa', 'failuresPerHourData', fromDate, toDate,tailsign);
				reloadPerHourData('faults', 'loadingResetsLopa', 'faultsPerHourData', fromDate, toDate,tailsign);
				reloadPerHourData('impactedServices', 'loadingResetsLopa', 'impactedServicesPerHourData', fromDate, toDate,tailsign);
				reloadPerHourData('applications', 'loadingResetsLopa', 'eventsPerHourData', fromDate, toDate,tailsign);
			}
			
			function reloadPerHourData(dataType, loadingIcon, table, startDate, endDate,tailsign) {
				$("#filter").prop('disabled', true);
				$("#resetbtn").prop('disabled', true);
				$('#' + table).bootstrapTable('destroy');
				//tailsign=$("#selectedTailsign").val();
				$("#" + table).removeClass('lopa-panel');
				faultCodes=$("#faultCode").val();
				failureCodes=$("#failureCode").val();
				ImpactedServicesCodes=$("#impactedServicesCode").val();
				commandedResets=$("#commandedResets").val();
				unCommandedResets=$("#unCommandedResets").val();
				flightPhases=$("#flightPhases").val();
				resetsEvent=$("#resetsValue").val();
				var filteredData;
				var faultCodesarray=[];
				var failureCodessarray=[];
				var ImpactedServicesCodesarray=[];
				var flightPhasessarray=[];
				var resetsarray=[];
				
				if(faultCodes!=null){			
					/*for(code in faultCodes){
						var str=faultCodes[code].split(':');
						faultCodesarray.push(str[0]);
					}*/
					faultCodesarray=faultCodes;
				}
				if(failureCodes!=null){
					/*for(code in failureCodes){
						var str=failureCodes[code].split(':');
						failureCodessarray.push(str[0]);
					}	*/	
					failureCodessarray=failureCodes;			
				}
				if(ImpactedServicesCodes!=null){
					/*for(code in ImpactedServicesCodes){
						var str=ImpactedServicesCodes[code].split(':');
						ImpactedServicesCodesarray.push(str[0]);
					}*/
					ImpactedServicesCodesarray=ImpactedServicesCodes;
				}
				if(flightPhases!=null){
					for(code in flightPhases){
						var str=flightPhases[code].split(':');
						flightPhasessarray.push(str[0]);
					}
				}
				
				if(resetsEvent!=null){
					for(r in resetsEvent){
						var str=resetsEvent[r].split(':');
						var str1="'"+str[0]+"'";
						resetsarray.push(str1);
					}
				}
				
				$('#errorInfo').hide();
				//$('#' + loadingIcon).show();
				data = {
					tailsign:tailsign,
					startDateTime: startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate(),
					endDateTime: endDate.getFullYear() + '-' + (endDate.getMonth() + 1) + '-' + endDate.getDate(),
					dataType: dataType,
					faultCode:faultCodesarray.toString(),
					failureCode:failureCodessarray.toString(),
					ImpactedServicesCode:ImpactedServicesCodesarray.toString(),
					flightPhases:flightPhasessarray.toString(),
					resetCode:resetsarray.toString()
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
						}else if(data.indexOf("No Data Available") >= 0){
							$('#btnInfo').hide();
							$('#errorInfo').show();								
							$('#' + loadingIcon).hide();	
							$('#seatModal').hide();					
							$('#panel').hide();
							$("#filter").prop('disabled', false);
							$("#resetbtn").prop('disabled', false);
							$.unblockUI();
						}else{		
							$('#' + table).empty();
							$('#' + table).bootstrapTable('destroy');							
							$('#' + table).append(data);							    
							$("#" + table).addClass('lopa-panel');
							$('#seatModal').hide();
							$('#panel').show();				
							data='';
							$("#filter").prop('disabled', false);
							$("#resetbtn").prop('disabled', false);
							$.unblockUI();
						}
						
					},
					error: function (err) {
						console.log('Error', err);
					}
				});
			}
	
	
			function loadHeatmap(fromDate, toDate,tailsign){
				reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate,tailsign);
				reloadHeatmapLopa('activeFailures', 'loadingResetsLopa', 'activeFailuresHeatmap', fromDate, toDate,tailsign);
				reloadHeatmapLopa('failures', 'loadingResetsLopa', 'failuresHeatmap', fromDate, toDate,tailsign);
				reloadHeatmapLopa('faults', 'loadingResetsLopa', 'faultsHeatmap', fromDate, toDate,tailsign);
				reloadHeatmapLopa('impactedServices', 'loadingResetsLopa', 'impactedServicesHeatmap', fromDate, toDate,tailsign);
				reloadHeatmapLopa('applications', 'loadingResetsLopa', 'eventsHeatmap', fromDate, toDate,tailsign);
			}
	
	function reloadHeatmapLopa(dataType, loadingIcon, table, startDate, endDate,tailsign){	
		//$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading Heatmap..." });
		//$("#" + table).remove();		
		$("#" + table).hide();
		
		faultCodes=$("#faultCode").val();
		failureCodes=$("#failureCode").val();
		ImpactedServicesCodes=$("#impactedServicesCode").val();
		commandedResets=$("#commandedResets").val();
		unCommandedResets=$("#unCommandedResets").val();
		flightPhases=$("#flightPhases").val();
		resetsEvent=$("#resetsValue").val();
		var filteredData;
		var faultCodesarray=[];
		var failureCodessarray=[];
		var ImpactedServicesCodesarray=[];
		var flightPhasessarray=[];
		var resetsarray=[];		
		//$('#resetsLopa').hide();
		
		if(faultCodes!=null){			
			/*for(code in faultCodes){
				var str=faultCodes[code].split(':');
				faultCodesarray.push(str[0]);
			}*/
			faultCodesarray=faultCodes;
		}
		if(failureCodes!=null){
			/*for(code in failureCodes){
				var str=failureCodes[code].split(':');
				failureCodessarray.push(str[0]);
			}	*/	
			failureCodessarray=failureCodes;			
		}
		if(ImpactedServicesCodes!=null){
			/*for(code in ImpactedServicesCodes){
				var str=ImpactedServicesCodes[code].split(':');
				ImpactedServicesCodesarray.push(str[0]);
			}*/
			ImpactedServicesCodesarray=ImpactedServicesCodes;
		}
		if(flightPhases!=null){
			for(code in flightPhases){
				var str=flightPhases[code].split(':');
				flightPhasessarray.push(str[0]);
			}
		}
		
		if(resetsEvent!=null){
			for(r in resetsEvent){
				var str=resetsEvent[r].split(':');
				var str1="'"+str[0]+"'";
				resetsarray.push(str1);
			}
		}
		
		$('#errorInfo').hide();
		//$('#' + loadingIcon).show();
		data = {
			tailsign:tailsign,
			startDateTime: startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate(),
			endDateTime: endDate.getFullYear() + '-' + (endDate.getMonth() + 1) + '-' + endDate.getDate(),
			dataType: dataType,
			faultCode:faultCodesarray.toString(),
			failureCode:failureCodessarray.toString(),
			ImpactedServicesCode:ImpactedServicesCodesarray.toString(),
			flightPhases:flightPhasessarray.toString(),
			resetCode:resetsarray.toString()
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
				}else if(data.indexOf("No Data Available") >= 0){
					$('#btnInfo').hide();
					$('#errorInfo').show();	
					$('#' + loadingIcon).hide();	
					$('#seatModal').hide();					
					$('#panel').hide();
				}else{
					filteredData=JSON.parse(data);
						var label=filteredData[0];
						var data=filteredData[1];
						var xnotLabelr=filteredData[2];
						var xnotLabel=filteredData[3];
						var index=xnotLabel[0]-1;
						
						var values=[];
						
						function remove(array, element) {
							const index = element;
							array.splice(index, 1);
						}
						
						for(var k=0;k<xnotLabelr.length;k++){
							for(var i = 0; i < data.length; i++) {
								remove(data[i].data,xnotLabelr[k]-1);								
							}
						}
						
						
						//label.splice(index, 0, " ");
						for(var l=0;l<data.length;l++){							
								//data[l].data.splice(index, 0, -1);							
						}
						
						for(var i = 0; i < data.length; i++) {
							var d1 = data[i].data;
							for(var k = 0; k < d1.length; k++) {
								if(d1[k]!=0 && d1[k]!=-1){
									if(values.indexOf(d1[k]) === -1){
										values.push(d1[k]);
									}
								}
							}
						}
						
						
						//$("#filter").prop('disabled', false);
						
						
						//var colorTestColors = ['greenyellow','orange','red'];
						var colorTestColors = ['skyblue','red','black'];
						
						if(values.length>4){
							var options={
								responsive: false,
								colors: colorTestColors,
								colorInterpolation: 'gradient',
								showLabels: true, 
								rounded: true,
								roundedRadius: 0.1,
								paddingScale: 0.1,
								tooltipTemplate: "<%if (value==-1){} else {%> Seat <%= xLabel %><%= yLabel %> - <%= value %> <%}%>",
							};	
						}else{
							var options={
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
						if(tableWidth<100){
							/*$(".scrollChart").width(tableWidth+50);
							$(".scrollChart").css('min-width',tableWidth+50);
							$(".scrollChart").css('min-height',tableHeight+20);
							tableWidth=tableWidth+50;
							tableHeight=tableHeight+20;*/
							$(".scrollChart").width(tableWidth);
							$(".scrollChart").css('min-width',tableWidth);
							$(".scrollChart").css('min-height',tableHeight);
							tableHeight=tableHeight;
						}else{
							$(".scrollChart").width(tableWidth);
							$(".scrollChart").css('min-width',tableWidth);
							$(".scrollChart").css('min-height',tableHeight);
							tableHeight=tableHeight;
						}
						var heatmapData= {labels: label,datasets: data};												
						var scrollChart = new Chart(ctx1,tableHeight,tableWidth).HeatMap(heatmapData, options);		
						ctx1.canvas.onclick = function(evt){
						  var activeBox = scrollChart.getBoxAtEvent(evt);						  
						  var seatConcat=activeBox.label+activeBox.datasetLabel;
						  seatSelected(seatConcat,activeBox.value);
						};

						 ctx1.stroke();
						 setTimeout(function(){ $.unblockUI() },800);
						 $("#" + table).show();		
					}
				},
				error: function (err) {
					console.log('Error', err);
				}
			});
		
		}
		
	$('#resetslbl').click(function(event) {				
		panelDisplay('resetsLopaPanel','activeFailuresLopaPanel','allFailuresLopaPanel','allFaultsLopaPanel','impactedServicesPanel','eventsPanel','resetslbl','allFailureslbl','activeFailureslbl','allFaultslbl','impactedServiceslbl','eventslbl');
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}	
	});
	
	function panelDisplay(panel1,panel2,panel3,panel4,panel5,panel6,label1,label2,label3,label4,label5,label6){
		$('#'+panel1).show();
		$('#'+panel2).hide();
		$('#'+panel3).hide();
		$('#'+panel4).hide();
		$('#'+panel5).hide();
		$('#'+panel6).hide();	
		$('#seatModal').hide();		
		
		$('#'+label1).addClass('active');
		$('#'+label2).removeClass('active');
		$('#'+label3).removeClass('active');
		$('#'+label4).removeClass('active');		
		$('#'+label5).removeClass('active');
		$('#'+label6).removeClass('active');
	
	}
	
	$('#activeFailureslbl').click(function(event) {	
		panelDisplay('activeFailuresLopaPanel','resetsLopaPanel','allFailuresLopaPanel','allFaultsLopaPanel','impactedServicesPanel','eventsPanel','activeFailureslbl','allFailureslbl','resetslbl','allFaultslbl','impactedServiceslbl','eventslbl');	
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}
				
	});
	
	$('#allFaultslbl').click(function(event) {	
		panelDisplay('allFaultsLopaPanel','resetsLopaPanel','allFailuresLopaPanel','activeFailuresLopaPanel','impactedServicesPanel','eventsPanel','allFaultslbl','allFailureslbl','resetslbl','activeFailureslbl','impactedServiceslbl','eventslbl');	
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}
	});
	
	$('#allFailureslbl').click(function(event) {
		panelDisplay('allFailuresLopaPanel','resetsLopaPanel','allFaultsLopaPanel','activeFailuresLopaPanel','impactedServicesPanel','eventsPanel','allFailureslbl','allFaultslbl','resetslbl','activeFailureslbl','impactedServiceslbl','eventslbl');	
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}				
	});
	
	$('#impactedServiceslbl').click(function(event) {	
		panelDisplay('impactedServicesPanel','resetsLopaPanel','allFaultsLopaPanel','activeFailuresLopaPanel','allFailuresLopaPanel','eventsPanel','impactedServiceslbl','allFaultslbl','resetslbl','activeFailureslbl','allFailureslbl','eventslbl');
		if(document.getElementById('lopaRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}				
	});
	
	$('#eventslbl').click(function(event) {	
		panelDisplay('eventsPanel','resetsLopaPanel','allFaultsLopaPanel','activeFailuresLopaPanel','impactedServicesPanel','allFailuresLopaPanel','eventslbl','allFailureslbl','resetslbl','activeFailureslbl','impactedServiceslbl','allFaultslbl');
		if(document.getElementById('lopaRadioBtn').checked) {		  
		  $('#flightPhases').multiselect('enable');
		  filterCategory('lopa');
		}else if(document.getElementById('heatmapRadioBtn').checked) {
		  $('#flightPhases').multiselect('enable');
		  filterCategory('heatmap');
		}else if(document.getElementById('perHourRadioBtn').checked) {
		  $('#flightPhases').multiselect('disable');
		  filterCategory('perhour');
		}		
	});	
	
	function filterCategory(category) {	
		
		if ($('#resetslbl').hasClass('active')) {
			if(category=='heatmap'){							    
				$('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate,tailsign);
				
				displayPanel('resetsLopaPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				
				//reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate,tailsign);				
				
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='lopa'){
			    $('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				//tableHeightnWidth=document.getElementById("resetsLopa");
				//$("#resetsLopa").bootstrapTable('destroy');				
				reloadLopa('reset', 'loadingResetsLopa', 'resetsLopa', fromDate, toDate,tailsign);			
				displayPanel('resetsHeatmapPanel','resetsLopaPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				
		
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){
				$('#seatModal').hide();
				//$('#resetsPerHourData').bootstrapTable('destroy');
				$('#flightPhases').multiselect('disable');
				displayPanel('resetsHeatmapPanel','resetsPerHourDataPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsLopaPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//reloadPerHourData('reset', 'loadingResetsLopa', 'resetsPerHourData', fromDate, toDate,tailsign);
			}	
		}else if ($('#activeFailureslbl').hasClass('active')) {
			if(category=='heatmap'){
			    $('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('activeFailures', 'loadingResetsLopa', 'activeFailuresHeatmap', fromDate, toDate,tailsign);
				displayPanel('activeFailuresLopaPanel','activeFailuresHeatmapPanel','resetsHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				//setTimeout(function(){ $.unblockUI() },800);
				//
				//reloadHeatmapLopa('activeFailures', 'loadingResetsLopa', 'activeFailuresHeatmap', fromDate, toDate,tailsign);
			}else if(category=='lopa'){
				$('#flightPhases').multiselect('enable');
				//$("#activeFailuresLopa").bootstrapTable('destroy');								
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadLopa('activeFailures', 'loadingActiveFailuresLopa', 'activeFailuresLopa', fromDate, toDate,tailsign);
				displayPanel('activeFailuresHeatmapPanel','activeFailuresLopaPanel','resetsHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){
				$('#seatModal').hide();
				$('#flightPhases').multiselect('disable');
				//reloadPerHourData('activeFailures', 'loadingResetsLopa', 'activeFailuresPerHourData', fromDate, toDate,tailsign);
				displayPanel('activeFailuresHeatmapPanel','activeFailuresPerHourDataPanel','resetsHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresLopaPanel','failuresPerHourDataPanel');								
			}	
		}else if ($('#allFailureslbl').hasClass('active')) {
			if(category=='heatmap'){
				$('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('failures', 'loadingResetsLopa', 'failuresHeatmap', fromDate, toDate,tailsign);				
				displayPanel('allFailuresLopaPanel','failuresHeatmapPanel','faultsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				//setTimeout(function(){ $.unblockUI() },800);
				//
				//reloadHeatmapLopa('failures', 'loadingResetsLopa', 'failuresHeatmap', fromDate, toDate,tailsign);
			}else if(category=='lopa'){
				$('#flightPhases').multiselect('enable');
				//$("#failuresLopa").bootstrapTable('destroy');	
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadLopa('failures', 'loadingFailuresLopa', 'failuresLopa', fromDate, toDate,tailsign);
				displayPanel('failuresHeatmapPanel','allFailuresLopaPanel','faultsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){
				$('#seatModal').hide();
				$('#flightPhases').multiselect('disable');
				//reloadPerHourData('failures', 'loadingResetsLopa', 'failuresPerHourData', fromDate, toDate,tailsign);
				displayPanel('failuresHeatmapPanel','failuresPerHourDataPanel','faultsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','allFailuresLopaPanel');								
			}	
		}else if ($('#allFaultslbl').hasClass('active')) {
			if(category=='heatmap'){
				$('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('faults', 'loadingResetsLopa', 'faultsHeatmap', fromDate, toDate,tailsign);
				displayPanel('allFaultsLopaPanel','faultsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				//setTimeout(function(){ $.unblockUI() },800);
				//
				//reloadHeatmapLopa('faults', 'loadingResetsLopa', 'faultsHeatmap', fromDate, toDate,tailsign);
				
			}else if(category=='lopa'){
				$('#flightPhases').multiselect('enable');
				//$("#faultsLopa").bootstrapTable('destroy');	
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadLopa('faults', 'loadingFaultsLopa', 'faultsLopa', fromDate, toDate,tailsign);				
				displayPanel('faultsHeatmapPanel','allFaultsLopaPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){				
				$('#seatModal').hide();	
				$('#flightPhases').multiselect('disable');
				//reloadPerHourData('faults', 'loadingResetsLopa', 'faultsPerHourData', fromDate, toDate,tailsign);
				displayPanel('faultsHeatmapPanel','faultsPerHourDataPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','allFaultsLopaPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
			}	
		}else if ($('#impactedServiceslbl').hasClass('active')) {
			if(category=='heatmap'){
				$('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('impactedServices', 'loadingResetsLopa', 'impactedServicesHeatmap', fromDate, toDate,tailsign);
				displayPanel('impactedServicesPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				//setTimeout(function(){ $.unblockUI() },800);
				//
				//reloadHeatmapLopa('impactedServices', 'loadingResetsLopa', 'impactedServicesHeatmap', fromDate, toDate,tailsign);
		
			}else if(category=='lopa'){
				$('#flightPhases').multiselect('enable');				
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				//$("#impactedServicesLopa").bootstrapTable('destroy');	
				reloadLopa('impactedServices', 'loadingImpactedServicesLopa', 'impactedServicesLopa', fromDate, toDate,tailsign);
				displayPanel('impactedServicesHeatmapPanel','impactedServicesPanel','eventsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){
				$('#seatModal').hide();
				$('#flightPhases').multiselect('disable');
				//reloadPerHourData('impactedServices', 'loadingResetsLopa', 'impactedServicesPerHourData', fromDate, toDate,tailsign);
				displayPanel('impactedServicesHeatmapPanel','impactedServicesPerHourDataPanel','eventsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','eventsPerHourDataPanel','impactedServicesPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				
				
			}	
		}else if ($('#eventslbl').hasClass('active')) {
			if(category=='heatmap'){
				$('#flightPhases').multiselect('enable');
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadHeatmapLopa('applications', 'loadingResetsLopa', 'eventsHeatmap', fromDate, toDate,tailsign);
				displayPanel('eventsPanel','eventsHeatmapPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				$('#seatModal').hide();
				//setTimeout(function(){ $.unblockUI() },800);
				//
				//reloadHeatmapLopa('applications', 'loadingResetsLopa', 'eventsHeatmap', fromDate, toDate,tailsign);
			}else if(category=='lopa'){
				$('#flightPhases').multiselect('enable');
				//$("#eventsLopa").bootstrapTable('destroy');	
				$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading..." });
				reloadLopa('applications', 'loadingEventsLopa', 'eventsLopa', fromDate, toDate,tailsign);
				displayPanel('eventsHeatmapPanel','eventsPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsPerHourDataPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				//setTimeout(function(){ $.unblockUI() },800);
			}else if(category=='perhour'){
				$('#seatModal').hide();
				$('#flightPhases').multiselect('disable');
				//reloadPerHourData('applications', 'loadingResetsLopa', 'eventsPerHourData', fromDate, toDate,tailsign);
				displayPanel('eventsHeatmapPanel','eventsPerHourDataPanel','resetsHeatmapPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsPanel','impactedServicesPerHourDataPanel','faultsPerHourDataPanel','resetsPerHourDataPanel','activeFailuresPerHourDataPanel','failuresPerHourDataPanel');
				
			}		
		}else{
			displayPanel('resetsHeatmapPanel','resetsLopaPanel','activeFailuresHeatmapPanel','failuresHeatmapPanel','faultsHeatmapPanel','impactedServicesHeatmapPanel','eventsHeatmapPanel');
			//reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate,tailsign);				
			//reloadHeatmapLopa('reset', 'loadingResetsLopa', 'resetsHeatmap', fromDate, toDate,tailsign);
		}		
	}
	
	function displayPanel(panel1,panel2,panel3,panel4,panel5,panel6,panel7,panel8,panel9,panel10,panel11,panel12,panel13){
		$('#'+panel1).hide();
		$('#'+panel2).show();
		$('#'+panel3).hide();
		$('#'+panel4).hide();
		$('#'+panel5).hide();
		$('#'+panel6).hide();
		$('#'+panel7).hide();
		$('#'+panel8).hide();
		$('#'+panel9).hide();
		$('#'+panel10).hide();
		$('#'+panel11).hide();
		$('#'+panel12).hide();
		$('#'+panel13).hide();
	}
	
	function displayClassforLopaPanel(panel1,panel2,panel3,panel4,panel5,panel6,panel7,tab1,tab2,tab3,tab4,tab5,tab6,tab7){
	
		$('#'+panel1).removeClass('active');				
			$('#'+panel2).removeClass('active');				
			$('#'+panel3).removeClass('active');				
			$('#'+panel4).removeClass('active');				
			$('#'+panel5).removeClass('active');				
			$('#'+panel6).removeClass('active');	
			
			$('#'+tab1).removeClass('active');
			$('#'+tab2).removeClass('active');
			$('#'+tab3).removeClass('active');
			$('#'+tab4).removeClass('active');
			$('#'+tab5).removeClass('active');
			$('#'+tab6).removeClass('active');
			
			$('#'+panel7).addClass('active');
			$('#'+tab7).addClass('active');	
	
	}

	var jsonData; 
	var seatValue;	
	var countV;
	function seatSelected(seat,count){	
		//$.blockUI({ message: "<img src='../img/loadingicon1.gif' style='height:50px;'><br/>Loading Data..." });	
		getAircraftId();
		$('#seatModal').show();
		$('#tabularData').hide();
		$('#loadingTabData').show();
		//$('#loadingTabData').hide();
		faultCodes=$("#faultCode").val();
		failureCodes=$("#failureCode").val();
		ImpactedServicesCodes=$("#impactedServicesCode").val();
		commandedResets=$("#commandedResets").val();
		unCommandedResets=$("#unCommandedResets").val();
		flightPhases=$("#flightPhases").val();
		resetsEvent=$("#resetsValue").val();
		var filteredData;
		var faultCodesarray=[];
		var failureCodessarray=[];
		var ImpactedServicesCodesarray=[];
		var flightPhasessarray=[];
		var resetsarray=[];
		
		if(faultCodes!=null){			
			/*for(code in faultCodes){
				var str=faultCodes[code].split(':');
				faultCodesarray.push(str[0]);
			}*/
			faultCodesarray=faultCodes;
		}
		if(failureCodes!=null){
			/*for(code in failureCodes){
				var str=failureCodes[code].split(':');
				failureCodessarray.push(str[0]);
			}	*/	
			failureCodessarray=failureCodes;			
		}
		if(ImpactedServicesCodes!=null){
			/*for(code in ImpactedServicesCodes){
				var str=ImpactedServicesCodes[code].split(':');
				ImpactedServicesCodesarray.push(str[0]);
			}*/
			ImpactedServicesCodesarray=ImpactedServicesCodes;
		}
		if(flightPhases!=null){
			for(code in flightPhases){
				var str=flightPhases[code].split(':');
				flightPhasessarray.push(str[0]);
			}
		}
		
		if(resetsEvent!=null){
			for(r in resetsEvent){
				var str=resetsEvent[r].split(':');
				var str1="'"+str[0]+"'";
				resetsarray.push(str1);
			}
		}
		
		if($('#allFaultsLopaPanel').css('display') == 'block' || $('#faultsPerHourDataPanel').css('display') == 'block' || $('#faultsHeatmapPanel').css('display') == 'block')
		{	
			displayClassforLopaPanel('summaryPanel','resetsPanel','failuresPanel','activeFailuresPanel','impactPanel','appeventsPanel','faultsPanel','summaryTab','resetsTab','activeFailuresTab','failuresTab','impactServicesTab','eventsTab','faultsTab');
					
		}
		if($('#resetsLopaPanel').css('display') == 'block' || $('#resetsPerHourDataPanel').css('display') == 'block' || $('#resetsHeatmapPanel').css('display') == 'block')
		{	
			displayClassforLopaPanel('summaryPanel','faultsPanel','failuresPanel','activeFailuresPanel','impactPanel','appeventsPanel','resetsPanel','summaryTab','faultsTab','activeFailuresTab','failuresTab','impactServicesTab','eventsTab','resetsTab');
					
		}
		if($('#activeFailuresLopaPanel').css('display') == 'block' || $('#activeFailuresPerHourDataPanel').css('display') == 'block' || $('#activeFailuresHeatmapPanel').css('display') == 'block')
		{		
			displayClassforLopaPanel('summaryPanel','faultsPanel','failuresPanel','resetsPanel','impactPanel','appeventsPanel','activeFailuresPanel','summaryTab','faultsTab','resetsTab','failuresTab','impactServicesTab','eventsTab','activeFailuresTab');
			
		}
		if($('#allFailuresLopaPanel').css('display') == 'block' || $('#failuresPerHourDataPanel').css('display') == 'block' || $('#failuresHeatmapPanel').css('display') == 'block')
		{	
			displayClassforLopaPanel('summaryPanel','faultsPanel','activeFailuresPanel','resetsPanel','impactPanel','appeventsPanel','failuresPanel','summaryTab','faultsTab','resetsTab','activeFailuresTab','impactServicesTab','eventsTab','failuresTab');
			
		}
		if($('#impactedServicesPanel').css('display') == 'block' || $('#impactedServicesPerHourDataPanel').css('display') == 'block' || $('#impactedServicesHeatmapPanel').css('display') == 'block')
		{	
			displayClassforLopaPanel('summaryPanel','faultsPanel','failuresPanel','resetsPanel','activeFailuresPanel','appeventsPanel','impactPanel','summaryTab','faultsTab','resetsTab','failuresTab','activeFailuresTab','eventsTab','impactServicesTab');
		}
		if($('#eventsPanel').css('display') == 'block' || $('#eventsPerHourDataPanel').css('display') == 'block' || $('#eventsHeatmapPanel').css('display') == 'block')
		{	
			displayClassforLopaPanel('summaryPanel','faultsPanel','failuresPanel','resetsPanel','impactPanel','activeFailuresPanel','appeventsPanel','summaryTab','faultsTab','resetsTab','failuresTab','impactServicesTab','activeFailuresTab','eventsTab');
			
		}
		
		$('#seatModal').show();
		/*$('#allFailureslbl').removeClass('active');
		$('#activeFailureslbl').removeClass('active');
		$('#allFaultslbl').removeClass('active');
		$('#resetsLopaPanel').addClass('active');
		$('#myTabs a:first').tab('show');*/
		seatValue=seat;
		countV=count;
		document.getElementById("seatHeader").innerHTML = "Seat " +seat;
		
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

		fromDate = new Date( $("#startDateTimePicker").val() );
		toDate = new Date ( $("#endDateTimePicker").val() );
		tailsign=$("#selectedTailsign").val();
		
		data = {
			aircraftId: tailsign,		  
		    startDateTime: fromDate.getFullYear() + '-' + (fromDate.getMonth() + 1) + '-' + fromDate.getDate(),
		    endDateTime: toDate.getFullYear() + '-' + (toDate.getMonth() + 1) + '-' + toDate.getDate(),
		    seat: seat,
		    faultCode:faultCodesarray.toString(),
			failureCode:failureCodessarray.toString(),
			ImpactedServicesCode:ImpactedServicesCodesarray.toString(),
			flightPhases:flightPhasessarray.toString(),
			resetCode:resetsarray.toString(),
		    countValue:count
		};

		$.ajax({
		  type: "GET",
		  url: "../ajax/getSeatDetailsforLopa.php",
		  data: data,
		  success: function(data) {
			
			// Need to convert from json string to json object to we can pass it to the table
			//jsonData = $.parseJSON(data);
			jsonData= JSON.parse(data);
			if(jsonData.svduDetails.length>0){
				$('#svduSerialNumber').html(jsonData.svduDetails[0].serialNumber);
				$('#svduLruType').html(jsonData.svduDetails[0].lruType);
				$('#svduHwPartNumber').html(jsonData.svduDetails[0].hwPartNumber);
				$('#svduRevision').html(jsonData.svduDetails[0].revision);
				$('#svduMod').html(jsonData.svduDetails[0].mod);
				$('#svduMacAddress').html(jsonData.svduDetails[0].macAddress);
				$('#svduLastUpdate').html(jsonData.svduDetails[0].lastUpdate);
			}
			if(jsonData.pcuDetails.length>0){			
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
			if(count>30){
				setTimeout(function(){ $('#loadingTabData').hide() },600);
			}else{
				$('#loadingTabData').hide();
			}
			//$("#loading").hide();
		  },
		  error: function (err) {
			console.log('Error', err);
		  }
		});
	}
	
	function exportOffloads(data){	
			faultCodes=$("#faultCode").val();
		failureCodes=$("#failureCode").val();
		ImpactedServicesCodes=$("#impactedServicesCode").val();
		commandedResets=$("#commandedResets").val();
		unCommandedResets=$("#unCommandedResets").val();
		flightPhases=$("#flightPhases").val();
		resetsEvent=$("#resetsValue").val();
		var filteredData;
		var faultCodesarray=[];
		var failureCodessarray=[];
		var ImpactedServicesCodesarray=[];
		var flightPhasessarray=[];
		var resetsarray=[];
		
		if(faultCodes!=null){			
			/*for(code in faultCodes){
				var str=faultCodes[code].split(':');
				faultCodesarray.push(str[0]);
			}*/
			faultCodesarray=faultCodes;
		}
		if(failureCodes!=null){
			/*for(code in failureCodes){
				var str=failureCodes[code].split(':');
				failureCodessarray.push(str[0]);
			}	*/	
			failureCodessarray=failureCodes;			
		}
		if(ImpactedServicesCodes!=null){
			/*for(code in ImpactedServicesCodes){
				var str=ImpactedServicesCodes[code].split(':');
				ImpactedServicesCodesarray.push(str[0]);
			}*/
			ImpactedServicesCodesarray=ImpactedServicesCodes;
		}
		if(flightPhases!=null){
			for(code in flightPhases){
				var str=flightPhases[code].split(':');
				flightPhasessarray.push(str[0]);
			}
		}
		
		if(resetsEvent!=null){
			for(r in resetsEvent){
				var str=resetsEvent[r].split(':');
				var str1="'"+str[0]+"'";
				resetsarray.push(str1);
			}
		}
		
			fromDate = new Date( $("#startDateTimePicker").val() );
			toDate = new Date ( $("#endDateTimePicker").val() );
			tailsign=$("#selectedTailsign").val();
			var startDT= fromDate.getFullYear() + '-' + (fromDate.getMonth() + 1) + '-' + fromDate.getDate();
			var endDT= toDate.getFullYear() + '-' + (toDate.getMonth() + 1) + '-' + toDate.getDate();
			var formatType=data;
			data = {
				startDateTime: fromDate,
				endDateTime:toDate,
				seatValue:seatValue,
				faultCode:faultCodesarray.toString(),
				failureCode:failureCodessarray.toString(),
				ImpactedServicesCode:ImpactedServicesCodesarray.toString(),
				flightPhases:flightPhasessarray.toString(),
				resetCode:resetsarray.toString(),
				countValue:countV
			};	

			var url = "../engineering/exportLopaViewData.php?tailsign="+tailsign+"&startDateTime="+startDT+"&endDateTime="+endDT+"&seat="+seatValue+"&formatType="+formatType+"&faultCode="+faultCodesarray.toString()+"&failureCode="+failureCodessarray.toString()+"&ImpactedServicesCode="+ImpactedServicesCodesarray.toString()+"&flightPhases="+flightPhasessarray.toString()+"&resetCode="+resetsarray.toString()+"&countValue="+countV;
			window.location = url;
			
	}
	
	function exportLopaSeatViewData(data){	
		faultCodes=$("#faultCode").val();
		failureCodes=$("#failureCode").val();
		ImpactedServicesCodes=$("#impactedServicesCode").val();
		commandedResets=$("#commandedResets").val();
		unCommandedResets=$("#unCommandedResets").val();
		flightPhases=$("#flightPhases").val();
		resetsEvent=$("#resetsValue").val();
		var filteredData;
		var faultCodesarray=[];
		var failureCodessarray=[];
		var ImpactedServicesCodesarray=[];
		var flightPhasessarray=[];
		var resetsarray=[];
		
		if(faultCodes!=null){			
			/*for(code in faultCodes){
				var str=faultCodes[code].split(':');
				faultCodesarray.push(str[0]);
			}*/
			faultCodesarray=faultCodes;
		}
		if(failureCodes!=null){
			/*for(code in failureCodes){
				var str=failureCodes[code].split(':');
				failureCodessarray.push(str[0]);
			}	*/	
			failureCodessarray=failureCodes;			
		}
		if(ImpactedServicesCodes!=null){
			/*for(code in ImpactedServicesCodes){
				var str=ImpactedServicesCodes[code].split(':');
				ImpactedServicesCodesarray.push(str[0]);
			}*/
			ImpactedServicesCodesarray=ImpactedServicesCodes;
		}
		if(flightPhases!=null){
			for(code in flightPhases){
				var str=flightPhases[code].split(':');
				flightPhasessarray.push(str[0]);
			}
		}
		
		if(resetsEvent!=null){
			for(r in resetsEvent){
				var str=resetsEvent[r].split(':');
				var str1="'"+str[0]+"'";
				resetsarray.push(str1);
			}
		}	
			fromDate = new Date( $("#startDateTimePicker").val() );
			toDate = new Date ( $("#endDateTimePicker").val() );
			tailsign=$("#selectedTailsign").val();
			var startDT= fromDate.getFullYear() + '-' + (fromDate.getMonth() + 1) + '-' + fromDate.getDate();
			var endDT= toDate.getFullYear() + '-' + (toDate.getMonth() + 1) + '-' + toDate.getDate();
			var formatType=data;
			data = {
				startDateTime: fromDate,
				endDateTime:toDate,
				seatValue:seatValue
			};	
			
			var type="all";
			var url = "../engineering/exportLopaSeatViewData.php?tailsign="+tailsign+"&startDateTime="+startDT+"&endDateTime="+endDT+"&seat="+seatValue+"&formatType="+formatType+"&faultCode="+faultCodesarray.toString()+"&failureCode="+failureCodessarray.toString()+"&ImpactedServicesCode="+ImpactedServicesCodesarray.toString()+"&flightPhases="+flightPhasessarray.toString()+"&resetCode="+resetsarray.toString()+"&countValue="+countV+"&dataType="+type;
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
				if(data) {
				var flightPhaseslist = JSON.parse(data);
					for (var i = 0; i < flightPhaseslist.length; i++) {
						var ts = flightPhaseslist[i];
						$("#flightPhases").append('<option value=' + ts + '>' + ts + '</option>');
					}
					$('#flightPhases').val(['4:Climb', '5:Cruise']);
					$('#flightPhases').multiselect('rebuild');
		        }
				
			},
			error: function (err) {
				console.log('Error', err);
			}
		});
	}

	getResets();
	function getResets() {	
		$.ajax({
			type: "GET",
			url: "../ajax/getBiteData.php",
			data: { 
	        		'action': 'getResets'
	            },
			success: function(data) {
				if(data) {
				var resetlist = JSON.parse(data);
					for (var i = 0; i < resetlist.length; i++) {
						var ts = resetlist[i];
						$("#resetsValue").append('<option value=' + ts + '>' + ts + '</option>');
					}
					$('#resetsValue').multiselect('rebuild');
		        }
				
			},
			error: function (err) {
				console.log('Error', err);
			}
		});
	}
	
	var aircraftId='';
	function getAircraftId() {
		tailsign=$("#selectedTailsign").val();	
			$.ajax({
					type: "GET",
					url: "../ajax/getBiteData.php",
					data: { 
							'action': 'getAircraftId',
							'tailsign' :tailsign
						},
					success: function(data) {					
							aircraftId=data;							
					},
					error: function (err) {
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
							'airlineId' :selectedAirline.id,
							'config' :selectedConfigType.configType,
							'platform' :selectedPlatform
						},
					success: function(data) {					
							tailsignList=JSON.parse(data);
						for (var i = 0; i < tailsignList.length; i++) {
							var ts = tailsignList[i];
							$("#selectedTailsign").append('<option value=' + ts + '>' + ts + '</option>');
						}
					$('#selectedTailsign').multiselect('rebuild');							
					},
					error: function (err) {
						console.log('Error', err);
					}
				});
	}
	
	
	
	function formatFlightLeg(value, row, index, field) {	
		if(value) {
			
			//value = value.replace(/,/g, ", ");						
				var method = "javascript:analyzeFlightLegs(" + value + "," + aircraftId + ")";
				return "<a onclick='" + method + "'>" + value + "</a>";
			
		} else {
			return '-';
		}
	}

	function analyzeFlightLegs(flightLegIds, aircraftId) {		
		var url = "flightSummary.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds;		
		var win = window.open(url, '_blank');
		win.focus();
	}
	
	function toggleIcon(e) {
        $(e.target)
            .prev('.panel-heading')
            .find(".more-less")
            .toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
    }
    $('.panel-group').on('hidden.bs.collapse', function toggleEvent(e){
		document.getElementById("collapseOne").style.display = "none";
	});
    $('.panel-group').on('shown.bs.collapse', function toggleEvent(e){
		document.getElementById("collapseOne").style.display = "block";
	});
	$(".collapse").on('show.bs.collapse', function(){
		document.getElementById("advancedFilter").style.display = "block";
        $(this).parent().find(".glyphicon-chevron-right").removeClass("glyphicon-chevron-right").addClass("glyphicon-chevron-down");
    }).on('hide.bs.collapse', function(){
		document.getElementById("advancedFilter").style.display = "none";
        $(this).parent().find(".glyphicon-chevron-down").removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-right");
    });
	
	function getInfoModal(){
		console.log('image clicked');
	}

</script> 
    
</body>
</html>
