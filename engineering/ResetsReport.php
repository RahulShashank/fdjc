<?php
session_start ();
$menu = 'RESETS_REPORT';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "20 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

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
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link rel="stylesheet" href="../css/dataTables/datatables.min.css">	
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.2/css/fixedHeader.dataTables.min.css"/>
<script src="../js/dataTables/datatables.min.js"></script>
<script src="../js/moment/moment.min.js"></script>
</head>
<style>

	.dataTables_length, .dataTables_info { 
		float:left; 
		border-bottom: 0px;
		padding-top: 8px;
	} 
	
	.dataTables_filter label { 
		margin-right: 5px; 
	} 
	
	.dataTables_paginate {
		margin-top: 5px !important;
	}
	
	.html5buttons, .dataTables_paginate {
		float:right;
	}
	
	hc-chart {
		width: 100%;
		display: block;
	}
	
	.loader,
	.loader:before,
	.loader:after {
	  border-radius: 50%;
	  width: 2.5em;
	  height: 2.5em;
	  -webkit-animation-fill-mode: both;
	  animation-fill-mode: both;
	  -webkit-animation: load7 1.8s infinite ease-in-out;
	  animation: load7 1.8s infinite ease-in-out;
	}
	.loader {
	  color: #0080ff;
	  font-size: 10px;
	  margin: 80px auto;
	  position: relative;
	  text-indent: -9999em;
	  -webkit-transform: translateZ(0);
	  -ms-transform: translateZ(0);
	  transform: translateZ(0);
	  -webkit-animation-delay: -0.16s;
	  animation-delay: -0.16s;
	}
	.loader:before,
	.loader:after {
	  content: '';
	  position: absolute;
	  top: 0;
	}
	.loader:before {
	  left: -3.5em;
	  -webkit-animation-delay: -0.32s;
	  animation-delay: -0.32s;
	}
	.loader:after {
	  left: 3.5em;
	}
	@-webkit-keyframes load7 {
	  0%,
	  80%,
	  100% {
		box-shadow: 0 2.5em 0 -1.3em;
	  }
	  40% {
		box-shadow: 0 2.5em 0 0;
	  }
	}
	@keyframes load7 {
	  0%,
	  80%,
	  100% {
		box-shadow: 0 2.5em 0 -1.3em;
	  }
	  40% {
		box-shadow: 0 2.5em 0 0;
	  }
	}
	.search_filter{
	}
	.search_filter *{
		display: inline
	}
	.search_filter div:nth-child(2){
		float: right
	}
	
	th, td{
		white-space: nowrap;
	}
	td:first-child{
		font-weight: bold;
	}
	th:first-child{
		width: 105px;
	}
	.date-Error{
		border: 1px solid red
	}
	.cell_bkg_yellow{
		background: rgba(255, 255, 0, 0.6);
	}
	.cell_bkg_orange{
		background: orange;
	}
	.cell_bkg_red{
		background: red;
	}
	.cell_bkg_light{
		background: #fcf8e3;
	}
	.center_align{
		text-align: center;
	}
	.text_bold{
		font-weight: bold;
	}
	.sorting:before { 
	    content: ""; 
	    opacity: 0; 
	    filter: alpha(opacity = 30); 
	} 
	.sorting_asc:before { 
	    content: ""; 
	} 
	.sorting_desc:before { 
	    content: ""; 
	} 
	.sorting:before, .sorting_desc:before, .sorting_asc:before { 
		height:0px; 
	} 
	.dataTables_filter {
	    width: 30px; 
	    float: left; 
	    padding-left: 6px !important;
	    padding: 0px 0px 0px;
	    border-bottom: 1px solid #ffffff;
	    font-size: 12px;
	}
	.modal-content {
		border-radius:6px;
		border:none;
		margin-top:10%;
	}
	
	.dataTables_scrollHead {
	     width: 100% !important;
	}
	.dataTables_scrollBody {
	     width: 100% !important;
	}
	
	.table_cell{
        display:table-cell;
        min-width:100px;
    }
    
/*     .dropdown-menu {
		min-width: 120px !important;
	}*/
	.dt-button-collection dropdown-menu{
		min-width: 120px !important;
		cursor: pointer;
	}
	.drodownlist{			
		min-width: 120px !important; 
		cursor: pointer !important;   		
	}
	.dropdown-menu > li > a {
		cursor: pointer !important;
	}

	.dataTables_length{
		border-bottom: 0px solid #E5E5E5;
		margin-left: 6px;
		margin-top: 1px;
	}
	.dataTables_length select {
		width: 60px !important;
		line-height: 17px !important;
	}
        
    table.dataTable thead .sorting::after,
    table.dataTable thead .sorting_asc::after {
        display:none;
    }
    
    table.dataTable thead .sorting_desc::after {
        display:none;
    }
    
    table.dataTable thead .sorting {
/*        background-image: url(https://datatables.net/media/images/sort_both.png); */
       background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAQAAADYWf5HAAAAkElEQVQoz7XQMQ5AQBCF4dWQSJxC5wwax1Cq1e7BAdxD5SL+Tq/QCM1oNiJidwox0355mXnG/DrEtIQ6azioNZQxI0ykPhTQIwhCR+BmBYtlK7kLJYwWCcJA9M4qdrZrd8pPjZWPtOqdRQy320YSV17OatFC4euts6z39GYMKRPCTKY9UnPQ6P+GtMRfGtPnBCiqhAeJPmkqAAAAAElFTkSuQmCC");
       background-repeat: no-repeat;
       background-position: center right;
    }
    
    table.dataTable thead .sorting_asc {
/*        background-image: url(https://datatables.net/media/images/sort_asc.png); */
       background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZ0lEQVQ4y2NgGLKgquEuFxBPAGI2ahhWCsS/gDibUoO0gPgxEP8H4ttArEyuQYxAPBdqEAxPBImTY5gjEL9DM+wTENuQahAvEO9DMwiGdwAxOymGJQLxTyD+jgWDxCMZRsEoGAVoAADeemwtPcZI2wAAAABJRU5ErkJggg==");
       background-repeat: no-repeat;
       background-position: center right;
    }
    
    table.dataTable thead .sorting_desc {
/*        background-image: url(https://datatables.net/media/images/sort_desc.png); */
       background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZUlEQVQ4y2NgGAWjYBSggaqGu5FA/BOIv2PBIPFEUgxjB+IdQPwfC94HxLykus4GiD+hGfQOiB3J8SojEE9EM2wuSJzcsFMG4ttQgx4DsRalkZENxL+AuJQaMcsGxBOAmGvopk8AVz1sLZgg0bsAAAAASUVORK5CYII=");
       background-repeat: no-repeat;
       background-position: center right;
    }
    #modalTotalBtn{
        border-radius: 0px !important;
    } 
    .dateChange{
        background-color:#F9F9F9 !important;
        color:#000000 !important;
        cursor: auto !important;
    }
    ul.dt-button-collection.dropdown-menu {
        min-width: 119px !important;        
    }
    
    div.dataTables_wrapper div.dataTables_filter input {
        margin-left: 0em;
    } 
    body.modal-open {
		    overflow: hidden !important;
		    position:fixed !important;
		    width: 100% !important;
	}   
</style>
<body>
	<!-- START PAGE CONTAINER -->
	<div class="page-container" >

		<!-- START PAGE SIDEBAR -->
            <?php include("SideNavBar.php"); ?>
            <!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div id="ctrldiv" class="page-content" data-ng-controller="ResetsReportController">

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
				<li class="active">Resets Report</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Resets Report</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">

                <div class="row">
                	<div class="col-md-12">
                		<div class="panel panel-default">
                			<div class="panel-body">
                				<input type="hidden" id="airlineIds" ng-model="airlineIds"
                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
                				<div class="row">
                					<div class="col-md-2">
                						<label for="airline">Airline</label>
                						<div>
                							<select id="airline" class="selectpicker show-tick" data-live-search="true" data-width="100%"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="platform">Platform</label>
                						<div>
                							<select id="platform" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="configType">Configuration</label>
                						<div>
                							<select id="configType" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-1">
                						<label for="software">Software</label>
                						<div>
                							<select id="software" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-1">
                						<label for="period">Period</label>
                						<div>
                							<select id="period" class="selectpicker show-tick" data-width="100%">
												<option value="daily">Daily</option>
												<option value="weekly">Weekly</option>
												<option value="monthly">Monthly</option>
                							</select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="reportBy">Report By</label>
                						<div>
                							<select id="reportBy" class="selectpicker show-tick" data-width="100%">
												<option value="aircraft">Aircraft</option>
												<option value="platform">Platform</option>
												<option value="actype">Configuration</option>
                							</select>
                						</div>
                					</div>
                					<div class="col-md-1">
                						<label for="startDateTimePicker">From</label>
                						<div>
                							<input class="form-control dateChange" id="startDateTimePicker"
                								type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
                						</div>
                					</div>
                					<div class="col-md-1">
                						<label for="endDateTimePicker">To</label>
                						<div>
                							<input class="form-control dateChange" id="endDateTimePicker"
                								type="text" name="endDateTimePicker" ng-model="endDate"  style="width: 100%;" readonly='true'>
                						</div>
                					</div>
                				</div>
                				<br /> 
                				<div class="row">
                					<div class="col-md-12 text-left">
                						<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
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
								<div id="loadingDiv" style="text-align: center" ng-show="loading">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading
									data...
								</div>
								<div ng-show="!loading">
								<div id="btnInfo" class="container-fluid">
									<div class="text-center">
										<div class="container-fluid">
                    						<div class="btn-group">
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'commandedResets'" style="border-radius: 0px !important; border-color: #f1f5f9;">Commanded</button>
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'uncommandedResets'" style="border-color: #f1f5f9;">Uncommanded</button>
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'totalResets'" style="border-color: #f1f5f9;">Total</button>
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'commandedResetsHour'" style="border-color: #f1f5f9;">Commanded / hour</button>
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'uncommandedResetsHour'" style="border-color: #f1f5f9;">Uncommanded / hour</button>
                    							<button type="button" class="btn btn-primary" ng-model="displayModel" uib-btn-radio="'totalResetsHour'" style="border-color: #f1f5f9;">Total / hour</button>
                    						</div>
										</div>
									</div>
									<br />
								</div>
								<div ng-if="(displayModel == 'commandedResets')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td class="text-nowrap table_cell" >
            										{{ item.tailsign }} &nbsp;(<a href="" ng-click="displayChart($index, 'total')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{ item.totalCommandedReset }}</td>
            									<td class="table_cell text-center" ng-repeat="resetData in item.data">
            										{{ resetData.totalCommandedResets }}
            									</td>
            								</tr>
            							</tbody>
            						</table>
            					</div>
            					<div ng-if="(displayModel == 'uncommandedResets')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td  class="text-nowrap table_cell">
            										{{ item.tailsign }} </a>&nbsp;(<a href="" ng-click="displayChart($index, 'total')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{ item.totalUncommandedReset }}</td>
            									<td ng-repeat="resetData in item.data" class="table_cell">
            										{{ subtract(resetData.totalUncommandedResets, resetData.systemResetsCount) }}
            									</td>
            								</tr>
            							</tbody>
            						</table>
            					</div>
            					<div class="table-responsive" ng-if="(displayModel == 'totalResets')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover ">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td  class="text-nowrap table_cell">
            										{{ item.tailsign }} </a>&nbsp;(<a href="" ng-click="displayChart($index, 'total')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{ item.totalReset }}</td>
            									<td ng-repeat="resetData in item.data" class="table_cell">
            										{{ add(resetData.totalCommandedResets, subtract(resetData.totalUncommandedResets, resetData.systemResetsCount)) }}
            									</td>
            								</tr>
            							</tbody>
            						</table>
            					</div>
            					<div class="table-responsive" ng-if="(displayModel == 'commandedResetsHour')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td  class="text-nowrap table_cell">
            										{{ item.tailsign }} </a>&nbsp;(<a href="" ng-click="displayChart($index, 'perHour')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{totalCommandedResetHour(item.data)}}</td>
            									<td ng-repeat="resetData in item.data" class="table_cell">
            										{{ resetsPerHour(resetData.totalCommandedResets, resetData.totalCruise) }}
            									</td>
            								</tr>
            							</tbody>
            						</table>
            					</div>
            					<div class="table-responsive" ng-if="(displayModel == 'uncommandedResetsHour')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td  class="text-nowrap table_cell">
            										{{ item.tailsign }} </a>&nbsp;(<a href="" ng-click="displayChart($index, 'perHour')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{totalUncommandedResetHour(item.data)}}</td>
            									<td ng-repeat="resetData in item.data" class="table_cell">
            										{{ resetsPerHour(subtract(resetData.totalUncommandedResets, resetData.systemResetsCount), resetData.totalCruise) }}
            									</td>
            								</tr>
            							</tbody>
            						</table>
            					</div>
            					<div class="table-responsive" ng-if="(displayModel == 'totalResetsHour')">
            						<table datatable="ng" dt-options="dtOptions" class="table table-bordered table-hover">
            							<thead>
            							<tr>
            								<th class="table_cell">Report By</th>
            								<th class="table_cell">Total</th>
            								<th ng-repeat="field in fields" class="text-nowrap table_cell">
            									{{ field }}
            								</th>
            							</tr>
            							</thead>
            							<tbody>
            								<tr ng-repeat="item in items">
            									<td  class="text-nowrap table_cell">
            										{{ item.tailsign }} </a>&nbsp;(<a href="" ng-click="displayChart($index, 'perHour')"><i class="fa fa-bar-chart-o" style="margin: 0px 1px 0px 2px"></i></a>)
            									</td>
            									<td>{{totalResetHour(item.data)}}</td>
            									<td ng-repeat="resetData in item.data" class="table_cell">
            										{{ resetsPerHour( add(resetData.totalCommandedResets, subtract(resetData.totalUncommandedResets, resetData.systemResetsCount)), resetData.totalCruise) }}
            									</td>
            								</tr>
            							</tbody>
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

	<script src="../js/dataTables/angular-datatables.min.js"></script>
	<script src="../js/dataTables/angular-datatables.buttons.min.js"></script>	
	<script type="text/javascript" src="https://cdn.datatables.net/fixedcolumns/3.2.2/js/dataTables.fixedColumns.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/fixedheader/3.1.2/js/dataTables.fixedHeader.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.16/sorting/absolute.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.3.0/ui-bootstrap-tpls.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
	<script src="https://cdn.jsdelivr.net/angular.chartjs/latest/angular-chart.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
	<link href="https://code.highcharts.com/css/highcharts.css" rel="stylesheet">
    <script src="../controllers/ResetsReportController.js"></script>
    <!-- <script src="../controllers/SearchPanelController.js"></script> -->
    <script src="../js/FileSaver.min.js"></script>
    <script src="../js/canvas-toBlob.js"></script>
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
<script type="text/javascript" src="../js/plugins.js"></script>
<script type="text/javascript" src="../js/actions.js"></script>
</body>
</html>