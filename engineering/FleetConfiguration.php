<?php
session_start ();
$menu = 'FleetConfiguration';
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
    </head>
    <style>

	</style>
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="fleetConfigurationController">
    
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
    				<li class="active">Fleet Configuration</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title">
    				<h2>Fleet Configuration</h2>
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
                							<input type="hidden" id="acronym" name="acronym">
                    					</div>
                    					<div class="col-md-2">
                    						<label for="platform">Platform</label>
                							<select id="platform" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="configType">Configuration</label>
                							<select id="configType" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                    					</div>
                    					<div class="col-md-4">
                    						<label for="buttons">&nbsp;&nbsp;</label>
                    						<div>
	                							<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    						</div>
                    					</div>
                    				</div>
                    				<br /> 
                    			</div>
                    		</div>
                    	</div>
                    </div>
                    <div id="loadingData" align="center"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div>
    				<div id="tableData" class="row">
    					<div class="col-md-12">
    						<div class="panel panel-default">							
    							<div class="panel-body">
    								<div id="errorInfo" class="container-fluid text-center">
                                		<label class="noData-label"> No data available for the selected duration or selected filters </label>
                                	</div>
													
									<div id="tableInfo">   								
										<table id="configTable" data-classes="table table-no-bordered" data-toggle="table" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="50" data-search="true" data-search-align="left" data-striped="true" data-export-data-type= "all"  data-height="auto" data-show-export="true">
												<thead>
													<tr> 
														<th data-field="edit"></th>
														<th data-field="ConfigName" data-sortable="true">Name</th>
														<th data-field="Platform" data-sortable="true">Platform</th>
														<th data-field="PreviousSW" data-sortable="true">Previous SW Baseline</th>
														<th data-field="PreviousSWVersion" data-sortable="true">Previous SW Version</th>
														<th data-field="LatestSW" data-sortable="true">Latest SW Baseline</th>
														<th data-field="LatestSWVersion" data-sortable="true">Latest SW Version</th>
														<th data-field="FutureSW" data-sortable="true">Future SW Baseline</th>
														<th data-field="newFutureSwDate" data-sortable="true">Future SW Date</th>
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
    	<!-- Modal -->
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
              <div class="modal-dialog" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
            	<div class="modal-content" style="border-radius: 5px;border-width:0px;">
            	
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit Configuration</h4>
                      </div>
                      <form id="userForm" class="form-horizontal" method="post" action="#">
                          <div class="modal-body">
                                <div id="error">
                                </div>
                                <br>
                                <div class="form-group">
                                    <label for="ConfigName" class="col-sm-4 control-label">ConfigName:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="configname" id="configname" disabled="disabled">
                                    </div>
                                  </div>
								  	<div class="form-group">
                                    <label for="Platform" class="col-sm-4 control-label">Platform:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="platform" id="platform" >
                                    </div>
                                  </div>
								<div class="form-group">
                                    <label for="PreviousSW" class="col-sm-4 control-label">Previous SW:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="previoussw" id="previoussw" >
                                    </div>
                                  </div> 
								  <div class="form-group">
                                    <label for="PreviousSWVersion" class="col-sm-4 control-label">Previous SW Version:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="previousswversion" id="previousswversion" >
                                    </div>
                                  </div> 
                                  <div class="form-group">
                                    <label for="LatestSW" class="col-sm-4 control-label">Latest SW:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="latestsw" id="latestsw" >
                                    </div>
                                  </div>
								  <div class="form-group">
                                    <label for="LatestSWVersion" class="col-sm-4 control-label">Latest SW Version:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="latestswversion" id="latestswversion" >
                                    </div>
                                  </div>
								   <div class="form-group">
                                    <label for="FutureSW" class="col-sm-4 control-label">Future SW:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="futuresw" id="futuresw" >
                                    </div>
                                  </div>
								   <div class="form-group">
                                    <label for="FutureSwDate" class="col-sm-4 control-label">Future SW Date:</label>
                                    <div class="col-sm-6">
                                      <input type="text" class="form-control" name="futureswdate" id="futureswdate" >
                                    </div>
                                  </div>
                                  
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button id="editaircraft" class="btn btn-primary">Update Config</button>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>
    	<!-- Logout page -->
    	<?php include("../logout.php"); ?>
    	<!-- END Logout page-->

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
<script src="../controllers/fleetConfigurationController.js"></script>
      
    </body>
</html>