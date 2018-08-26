<?php
session_start ();
$menu = 'MAINTENANCE_ACTION';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
ini_set('memory_limit', '-1');

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
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
        <link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
        <link rel="stylesheet" href="../css/bootstrap-table.css" rel="stylesheet" />
        <link href="../css/bootstrap-table.css" rel="stylesheet" />
        <!-- EOF CSS INCLUDE -->
        <script src="../js/jquery-1.11.2.js"></script>
        <link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
        <script src="../js/jquery.datetimepicker.js"></script>
    </head>
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="maintenanceActionController">
    
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
    				<li class="active">Maintenance Action</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title">
    				<h2>Maintenance Action</h2>
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
                							<select id="airline" class="selectpicker show-tick" data-live-search="true" data-width="100%"></select>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="platform">Platform</label>
                							<select id="platform" class="selectpicker show-tick" data-width="100%"></select>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="configType">Configuration</label>
                							<select id="configType" class="selectpicker show-tick" data-width="100%"></select>
                    					</div>
                    					<div class="col-md-2 form-group">
    										<label for="tailsign">Tailsign</label>
											<select id="tailsign" class="selectpicker show-tick" data-width="100%" data-live-search="true" multiple title="All"></select>
    									</div>
		                				<div class="col-md-2 from-group">
		                					<label for="failureCode">Failure Code</label>
		                					<div>
		                						<select id="failureCode" class="selectpicker show-tick" data-width="100%" multiple title="All" data-size="6" 
		                						data-live-search="true" data-selected-text-format="count > 3"></select>
	                						</div>
                						</div>
    									<div class="col-md-1 form-group">
    										<label for="startDateTimePicker">From</label>
    										<input class="form-control read-only-datepicker" id="startDateTimePicker" type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>
    									</div>
    									<div class="col-md-1 form-group">
    										<label for="endDateTimePicker">To</label>
    										<div>
    											<input class="form-control read-only-datepicker" id="endDateTimePicker" type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>
    										</div>
    									</div>
            						</div>
            						<div class="row">
                    					<div class="col-md-12 text-left">
                    						<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    					</div>
                					</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>
                    
                    <div class="row" id="loadingDiv">
    					<div class="col-md-12">
    						<div class="panel panel-default">
    							<div class="panel-body">
    								<div style="text-align: center">
    									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
    								</div>
            					</div>
        					</div>
    					</div>
					</div>
                    
                    <div class="row" id="noDataDiv" style="display: none;">
    					<div class="col-md-12">
    						<div class="panel panel-default">
    							<div class="panel-body">
            						<div class="container-fluid text-center">
                						<label class="noData-label">No data available for the selected duration or filters</label>
                					</div>
            					</div>
        					</div>
    					</div>
					</div>
                    
                    <div class="row" id="dataDiv" style="display: none;">
    					<div class="col-md-12">
    						<div class="panel panel-default">
    							<div class="panel-body">
    								<div class="table-editable">
                        				<table id="failureTable" data-classes="table" data-pagination="true" data-page-list="[25, 50, 100, All]" 
                        				data-page-size="25" data-striped="true" data-search="true" data-search-align="left" data-show-export="true">
                        					<thead>
                    							<tr> 
                    								<th data-field="tailsign" data-sortable="true">Tail Name</th>
                    								<th data-field="accusedHostName" data-sortable="true">Host Name</th>
                    								<th data-field="failureCode" data-sortable="true">Failure Code</th>
                    								<th data-field="failureDesc" data-sortable="true">Failure Description</th>
                    								<th data-field="recommendation">Recommendation</th>
                        							<th data-field="idFlightLeg" data-sortable="true">Flight Leg #</th>
                    								<th data-field="correlationDate" data-sortable="true">Correlation Date</th>
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
    
    <!-- START SCRIPTS -->
        <script src="../js/jquery.blockUI.js"></script>
        <script src="../js/angular.js"></script>
        <script src="../js/plugins/jquery/jquery-ui.min.js"></script>
        <script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
        <script src="../js/bootstrap-table.js"></script>
        <script src="../js/tableExport.js"></script>
        <script src="../js/bootstrap-table-export.js"></script>
        <script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
    	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
    	<script type="text/javascript" src="../js/plugins.js"></script>
    	<script type="text/javascript" src="../js/actions.js"></script>
    	<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.3.0/ui-bootstrap-tpls.min.js"></script>
    	<script src="../controllers/maintenanceActionController.js"></script>
    <!-- END SCRIPTS -->         
    </body>
    <script>
        var startDateTime= "<?php echo "$startDateTime";?>"; 
        var endDateTime= "<?php echo "$endDateTime";?>";
    </script>
</html>