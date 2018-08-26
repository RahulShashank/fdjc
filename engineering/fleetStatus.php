<?php
session_start ();
$menu = 'FLEET_STATUS';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
ini_set('memory_limit', '-1');

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
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
        <!-- EOF CSS INCLUDE -->
        <script src="../js/jquery-1.11.2.js"></script>
<!--         <script src="../js/Chart.js"></script> -->
		<script src="../js/chart/Chart.js"></script>
		<script src="../js/chart/Chart.PieceLabel.min.js"></script>
    </head>
    <style>
/*     .chart {
    	display: block;
    	width: 100%;
	}
 */	
	.placeholder {
        margin-bottom: 20px;
    }
    
    .chart-title {
    	color: #808080;
    }
    
    .chart-panel {
        background-color: #FCFCFC;
        border: 1px solid #E8E8E8;
        padding: 10px;
    }
    
    .chart-legend {
        position: absolute;
        top: 5px;
        right: 5px;
    }
    
    .chart-legend ul {
        list-style-type: none;
        padding-left: 20px !important;
        padding-top: 5px !important;
        padding-bottom: 5px !important;
    }
    
    .chart-legend ul li {
        display: inline;
        padding: .2em 1em;
    }
    
    .chart-legend li span {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-right: 5px;
        border-radius: 2px;
    }
    
    h4 {
        display: block;
        -webkit-margin-before: 0.5em;
        -webkit-margin-after: 0.25em;
        -webkit-margin-start: 0px;
        -webkit-margin-end: 0px;
        font-weight: bold;
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
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="fleetStatusController">
    
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
    				<li class="active">Fleet Status</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title" style="padding-right: 12px;">
    				<h2>Fleet Status</h2>
    				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
    					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
    					</i>
    				</a>
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
                    					<div class="col-md-4">
                    						<label for="buttons">&nbsp;&nbsp;</label>
                    						<div>
                    							<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>
                    						&nbsp;&nbsp;&nbsp;
                    						<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    						</div>
                    						
                    					</div>
                    				</div>
                    				<br /> 
                    			</div>
                    		</div>
                    	</div>
                    </div>
    				<div class="row">
    					<div class="col-md-12">
    						<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title"><span class="fa fa-adjust"></span> Fleet Status Chart</h2>
								</div>
    							<div class="panel-body">
    								<div id="loadingDiv" style="text-align: center">
    									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
    								</div>
    								<!--div id="softwareChart"></div-->
									<div id="softwareChart" style="max-height: 500px; overflow-y: auto;"></div>
    							</div>
    						</div>
    					</div>
					</div>
    				<div class="row">
    					<div class="col-md-12">
    						<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title"><span class="fa fa-table"></span> Fleet Status Details</h2>
								</div>
    							<div class="panel-body">
									<div class="table-responsive" id="aircraftsTableDiv">
										<table id="aircraftsTable" data-classes="table table-no-bordered table-hover"  
										data-search="true" data-search-align="left" data-show-export="true" 
										data-row-style="rowStyle" data-height='700'>
            								<thead>
            								<tr>
        										<th data-field="edit"></th>
        										<th data-field="statusIcon" data-align="center"></th>
        										<th data-field="maintenanceStatusLabel" data-align="center">Status</th>
        										<th data-field="newtailsign" data-sortable="true" data-formatter="linkTimeline">Tailsign</th>
        										<th data-field="type" data-sortable="true">Type</th>
        										<th data-field="Ac_Configuration" data-sortable="true">A/C Config</th>
        										<th data-field="msn" data-sortable="true">MSN</th>
        										<th data-field="LF_RF" data-sortable="true">LF/RF</th>
        										<th data-field="newEIS" data-sortable="true">EIS</th>
        										<th data-field="platform" data-sortable="true">Platform</th>
        										<th data-field="SW_Baseline" data-sortable="true">SW BaseLine</th>
        										<th data-field="software" data-sortable="true" data-cell-style="cellStyle">Software</th>
        										<th data-field="SW_PartNo" data-sortable="true">SW Part #</th>
        										<th data-field="newSWinstalled" data-sortable="true">SW Installed</th>										
        										<th data-field="Map_Version" data-sortable="true">Map Version</th>
        										<th data-field="Content" data-sortable="true">Content</th>
            								</tr> 
            								</thead>
            							</table>
            						</div>
									
    							</div>
    						</div>
    					</div>
					</div>
    	<!-- START Modal -->
    	<!-- div class="modal" id="myModal" role="dialog">
    		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">				
    			<div class="modal-content" style="border-radius: 5px;border-width:0px;"-->
            <div class="modal fade" id="editAircraftModal" tabindex="-1" role="dialog" aria-labelledby="editAircraftModal">
              <div class="modal-dialog" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
            	<div class="modal-content" style="border-radius: 5px;border-width:0px;">
    				<div class="modal-header">
    					<button id="closeModal" type="button" class="close" data-dismiss="modal">&times;</button>
    						<h4 class="modal-title">Edit Aircraft</h4>
    				</div> 
    				<div class="modal-body">
    					<form>
							<input type="hidden" name="id" id="id">
    						<div class="alert alert-success" role="alert" id="editAircraftsSuccessDiv"></div>
    						<div class="alert alert-danger" role="alert" id="editAircraftsFailureDiv"></div>
							<div class="form-group">
                          		<label for="tailsign" class="col-sm-5 control-label text-right" style="padding-top: 5px;">Tailsign</label>
                                <input type="text" class="form-control" id="tailsign" name="tailsign" style="width: 220px;">
                          	</div>
							<div class="form-group">
                          		<label for="msn" class="col-sm-5 control-label text-right" style="padding-top: 5px;">MSN</label>
                                <input type="text" class="form-control" id="msn" name="msn" style="width: 220px;">
                          	</div>
							<div class="form-group">
                          		<label for="status" class="col-sm-5 control-label text-right" style="padding-top: 5px;">Status</label>
        						<select class="selectpicker" name="status" id="status" style="width: 220px;">
        							<option value="No Status" data-icon="glyphicon-unchecked">&nbsp;No Status</option>
        							<option value="Ground" data-icon="glyphicon-road">&nbsp;Ground</option>
        							<option value="In Air" data-icon="glyphicon-plane">&nbsp;In Air</option>
            						<option value="OK" data-icon="glyphicon-ok">&nbsp;OK</option>
            						<option value="Warning" data-icon="glyphicon-warning-sign">&nbsp;Warning</option>
            						<option value="Watch" data-icon="glyphicon-flag">&nbsp;Watch</option>
            						<option value="New Software" data-icon="glyphicon-hdd">&nbsp;New Software</option>
        						</select>
                          	</div>
							<div class="form-group">
                          		<label for="swbaseline" class="col-sm-5 control-label text-right" style="padding-top: 5px;">SW BaseLine</label>
                                <input type="text" id="swbaseline" name="swbaseline" class="form-control" style="width: 220px;">
                          	</div>
							<div class="form-group">
                          		<label for="software" class="col-sm-5 control-label text-right" style="padding-top: 5px;">Software</label>
                          		<input type="text" id="software" name="software" class="form-control" style="width: 220px;">
                          	</div>
                            <div class="form-group">
                	            <label for="swpartno" class="col-sm-5 control-label text-right" style="padding-top: 5px;">SW Part#</label>
                                <input type="text" id="swpartno" name="swpartno" class="form-control" style="width: 220px;">
                            </div>
                            <div class="form-group">
                	            <label for="swinstalled" class="col-sm-5 control-label text-right" style="padding-top: 5px;">SW Installed</label>
                                <input type="text" id="swinstalled" name="swinstalled" class="form-control" style="width: 220px;">
                            </div>
                            <div class="form-group">
                	            <label for="mapversion" class="col-sm-5 control-label text-right" style="padding-top: 5px;">Map Version</label>
                                <input type="text" id="mapversion" name="mapversion" class="form-control" style="width: 220px;">
                            </div>
                            <div class="form-group">
                	            <label for="content" class="col-sm-5 control-label text-right" style="padding-top: 5px;">Content</label>
                                <input type="text" id="content" name="content" class="form-control" style="width: 220px;">
                            </div>
						</form>
    				</div>
					<div class="modal-footer">
            			<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            			<button id="editaircraft" class="btn btn-primary">Update Aircraft</button>
					</div>
			
    			</div>
    		</div>
    	</div>
    	<!-- END Modal -->
					
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
    						<h4 class="modal-title" style="font-weight: normal;">Fleet Status Info</h4>
    				</div> 
    				<div class="modal-body">
    					Aircrafts with software highlighted in green are having the latest software installed
    				</div>
    			</div>
    		</div>
    	</div>
    
    <!-- START SCRIPTS -->
        <script src="../js/jquery.blockUI.js"></script>
        <script src="../js/angular.js"></script>
        <script src="../js/plugins/jquery/jquery-ui.min.js"></script>
        <script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
        <script src="../js/bootstrap-table.js"></script>
        <script src="../js/bootstrap-table-export.js"></script>
        <script src="../js/tableExport.js"></script>
        <script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
    	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
    	<script type="text/javascript" src="../js/plugins.js"></script>
    	<script type="text/javascript" src="../js/actions.js"></script>
    	<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.3.0/ui-bootstrap-tpls.min.js"></script>
    	<script src="../controllers/fleetStatusController.js"></script>
    <!-- END SCRIPTS -->         
    </body>
    <script type="text/javascript">
    	var airlineIdfromAirlines='<?php echo $_REQUEST ['airlineId'];?>';
    	if(airlineIdfromAirlines!=''){		
			var airId='<?php echo $_REQUEST ['airlineId'];?>';		
		}	 
    </script>
</html>