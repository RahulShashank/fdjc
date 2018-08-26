<?php
session_start ();
$menu = 'BITE_UPLOAD_MATRIX';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
ini_set('memory_limit', '-1');

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

//get current date
$endDateTime=date('Y-m-d');
$startDate = date_create("$endDateTime");
date_sub($startDate,date_interval_create_from_date_string("2 days"));
$startDateTime = date_format($startDate,"Y-m-d");
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
        <link href="../css/jquery.datetimepicker.css" rel="stylesheet" />
        <!-- EOF CSS INCLUDE -->
        <script src="../js/jquery-1.11.2.js"></script>
        <style>
        	canvas {
        		-moz-user-select: none;
        		-webkit-user-select: none;
        		-ms-user-select: none;
        	}
    	</style>
    </head>
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="biteUploadMatrixController">
    
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
    				<li class="active">BITE Upload Matrix</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title">
    				<h2>BITE Upload Matrix</h2>
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
                    						<label for="startDate">From</label>
                    						<div>
                    							<input id="startDate" type="text" name="startDate" size="15" class="form-control read-only-datepicker" readonly='true'>
                    						</div>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="endDate">To</label>
                    						<div>
                    							<input id="endDate" type="text" name="endDate" size="15" class="form-control read-only-datepicker" readonly='true'>
                    						</div>
                    					</div>
                    					<div class="col-md-4">
                    						<label for="buttons">&nbsp;&nbsp;</label>
                    						<div>
                    							<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    						</div>
                    					</div>
                    				</div><br/>
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
                    
                    <div class="row" id="chartDiv" style="display: none;">
    					<div class="col-md-12">
    						<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title"><span class="fa fa-bar-chart-o"></span> BITE Upload Matrix Chart</h2>
								</div>
    							<div class="panel-body">
    								<div style="height: 100%; max-height: 400px; overflow-y: auto; overflow-x: auto;">
    								<div id="matrixBarChartDiv" style="position: relative; height:80vh;">
    									<canvas id="matrixBarChart"></canvas>
    								</div>
    								</div>
								</div>
							</div>
						</div>
					</div>
					
                    <div class="row" id="dataDiv" style="display: none;">
    					<div class="col-md-12">
    						<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title"><span class="fa fa-table"></span> BITE Upload Matrix Details</h2>
								</div>
    							<div class="panel-body">
    								<div class="table-editable" id="biteUploadMartixTableDiv">
    									<div><label for="notAssignedFiles" style="font-weight: bold;">Un Assigned files #: </label>&nbsp;&nbsp;<span id="notAssignedFiles"></span></div>
    									<table id="biteUploadMartixTable" data-classes="table" data-export-data-type= "all" data-show-export="true" 
    									data-row-style="rowStyle" data-show-footer="true">
    										<thead>
    											<tr>
                        							<th data-field="airline_name" data-sortable="true" data-footer-formatter="totalText">Airline Name</th>
                        							<th data-field="manual_processed_count" data-sortable="true" data-class="text-center" data-footer-formatter="manualProcessedFormatter">Manual Processed</th>
                        							<th data-field="manual_rejected_count" data-sortable="true" data-class="text-center" data-footer-formatter="manualRejectedFormatter">Manual Rejected</th>
                        							<th data-field="automatic_processed_count" data-sortable="true" data-class="text-center" data-footer-formatter="automaticProcessedFormatter">Automatic Processed</th>
                        							<th data-field="automatic_rejected_count" data-sortable="true" data-class="text-center" data-footer-formatter="automaticRejectedFormatter">Automatic Rejected</th>
                        							<th data-field="total_count" data-sortable="true" data-class="text-center" data-footer-formatter="totalFormatter">Total</th>
    											</tr>
    										</thead>
    									</table><br/>
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
    	<script src="../js/ui-bootstrap-tpls.min.js"></script>
    	<script src="../js/jquery.datetimepicker.js"></script>
		<script src="../js/chart/Chart.js"></script>
		<script src="../js/chart/chartjs-plugin-datalabels.min.js"></script>
    	<script src="../controllers/biteUploadMatrixController.js"></script>
    <!-- END SCRIPTS -->         
        <script>
            var startDateTime= "<?php echo "$startDateTime";?>"; 
            var endDateTime= "<?php echo "$endDateTime";?>";
            var airlineIds = "<?php echo "$airlineIds"?>";
        </script>
    </body>
</html>
