<?php
session_start();
$menu = 'OFFLOADS_COVERAGE';
require_once "../database/connecti_database.php";
include("checkEngineeringPermission.php");
ini_set('memory_limit', '-1');

$airlineIds = $_SESSION['airlineIds'];
$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");
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
        <link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
        <!-- EOF CSS INCLUDE -->
        <script src="../js/jquery-1.11.2.js"></script>
        <script src="../js/jquery.datetimepicker.js"></script>
        
        <style>
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
    </head>
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="offloadsCoverageController">
    
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
    				<li class="active">Offloads Coverage</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title">
    				<h2>Offloads Coverage</h2>
    			</div>
    
    			<!-- PAGE CONTENT WRAPPER -->
    			<div class="page-content-wrap">
    
                    <div class="row">
                    	<div class="col-md-12">
                    		<div class="panel panel-default">
                    			<div class="panel-body">
                    				<input type="hidden" id="airlineIds" ng-model="airlineIds" ng-init="airlineIds=<?php echo "'" . $airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
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
                    					<div class="col-md-2">
    										<label for="tailsign">Tailsign</label>
    										<div>
    											<select id="tailsign" class="selectpicker show-tick" data-width="100%" data-live-search="true" multiple title="All"></select>
    											<input type="hidden" id="acronym" name="acronym">
    										</div>
    									</div>
                    					<div class="col-md-2">
                    						<label for="startDateTimePicker">From</label>
                    						<div>
                    							<input class="form-control read-only-datepicker" id="startDateTimePicker"
                    								type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>
                    						</div>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="endDateTimePicker">To</label>
                    						<div>
                    							<input class="form-control read-only-datepicker" id="endDateTimePicker"
                    								type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>
                    						</div>
                    					</div>
                    				</div>
                    				<br /> 
                    				<div class="row">
                    					<div class="col-md-12 text-left">
                    						<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>
                    						&nbsp;&nbsp;&nbsp;
                    						<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>
    				
    				<div class="row" id="dataDiv">
    					<div class="col-md-12">
    						<div class="panel panel-default">
    							<div class="panel-body">
                					<div id="loadingDiv" style="text-align: center">
                						<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
                					</div>
            						<div id="noDataDiv" class="container-fluid text-center">
                						<label class="noData-label">No data available for the selected duration or filters</label>
                					</div>
									<div id="offloadsCoverageTableDiv" class="table-responsive">
    									<table id="offloadsCoverageTable" data-classes="table"
    										data-search="true" data-search-align="left" 
    										data-show-export="true" data-export-data-type= "all" 
    										data-row-style="rowStyle" data-height='700'>
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
        <script src="../js/angular.js"></script>
        <script src="../js/plugins/jquery/jquery-ui.min.js"></script>
        <script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
        <script src="../js/bootstrap-table.js"></script>
        <script src="../js/bootstrap-table-export.js"></script>
        <script src="../js/tableExport.js"></script>
        <script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
    	<script src="../js/ui-bootstrap-tpls.min.js"></script>
    	<script src="../controllers/offloadsCoverageController.js"></script>    	
    	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
    	<script type="text/javascript" src="../js/plugins.js"></script>
    	<script type="text/javascript" src="../js/actions.js"></script>
    <!-- END SCRIPTS -->         
    </body>
    <script type="text/javascript">
    	var airlineIdfromAirlines='<?php echo $_REQUEST ['airlineId'];?>';
    	if(airlineIdfromAirlines!=''){		
			var airId='<?php echo $_REQUEST ['airlineId'];?>';		
		}	 
    </script>
</html>