<?php
session_start ();
$menu = 'HARDWARE_REV_MOD';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
ini_set('memory_limit', '-1');

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

$aircraftId = $_REQUEST['aircraftId'];
error_log("Aircraft Id from another page: " . $aircraftId);

$airlineId = 0;
$platform = "";
$configuration = "";
$software = "";
$tailsign = "";

if($aircraftId > 0) {
    $query = "select airlineId, platform, Ac_Configuration as configuration, software, tailsign from aircrafts where id=$aircraftId";
    
    $result = mysqli_query($dbConnection, $query);
    
    error_log("All Details Query: ".$query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        if ($row = mysqli_fetch_assoc($result)) {
            $airlineId = $row['airlineId'];
            $platform = $row['platform'];
            $configuration = $row['configuration'];
            $software = $row['software'];
            $tailsign = $row['tailsign'];
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
        <link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
        <link rel="stylesheet" href="../css/bootstrap-table.css" rel="stylesheet" />
        <link href="../css/bootstrap-table.css" rel="stylesheet" />
        <!-- EOF CSS INCLUDE -->
        <script src="../js/jquery-1.11.2.js"></script>
    </head>
    <body>
    	<!-- START PAGE CONTAINER -->
    	<div class="page-container" >
    
    		<!-- START PAGE SIDEBAR -->
                <?php include("SideNavBar.php"); ?>
                <!-- END PAGE SIDEBAR -->
    
    		<!-- PAGE CONTENT -->
    		<div id="ctrldiv" class="page-content" data-ng-controller="HardwareRevisionsModsController">
    
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
    				<li class="active">Hardware Rev & Mod</li>
    			</ul>
    			<!-- END BREADCRUMB -->
    
    			<div class="page-title">
    				<h2>Hardware Revisions & Mods</h2>
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
                							<select id="platform" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                    					</div>
                    					<div class="col-md-2">
                    						<label for="configType">Configuration</label>
                							<select id="configType" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                    					</div>
                    					<div class="col-md-2 form-group">
    										<label for="tailsign">Tailsign</label>
											<select id="tailsign" class="selectpicker show-tick" data-width="100%" data-live-search="true" multiple title="All"></select>
    									</div>
                    					<div class="col-md-4">
                    						<label for="buttons">&nbsp;&nbsp;</label>
                    						<div>
                    							<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                    						</div>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <!-- START TABS -->                                
                            <div class="panel panel-default tabs">                            
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"><a href="#tab-first" role="tab" data-toggle="tab">Config Check</a></li>
                                    <li><a href="#tab-second" role="tab" data-toggle="tab">Revs & Mods</a></li>
                                </ul>                            
                				<div id="loadingDiv" style="text-align: center"><br/>
                					<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading data...
                				</div>
                                <div class="panel-body tab-content">
                                    <div class="tab-pane active" id="tab-first">
                						<div id="noConfigDataDiv" class="container-fluid text-center">
                    						<label class="noData-label">No data available for the selected duration or filters</label>
                    					</div>
                                    	
                                    	<div id="configDataTableDiv">
                                            <table id="configDataTable" data-classes="table"
        										data-pagination="true" data-page-list="[25, 50, 100, All]"
        										data-page-size="25" data-striped="true" data-search="true"
        										data-search-align="left" data-show-export="true" data-export-data-type= "all">
        										<thead>
        											<tr>
        												<th data-sortable="true" data-field="tailsign">Tailsign</th>
        												<th data-sortable="true" data-field="hostName">Hostname</th>
        												<th data-sortable="true" data-field="hwPartNumber">HW Part Number</th>
        												<th data-sortable="true" data-field="serialNumber">Serial Number</th>
        												<th data-sortable="true" data-field="revision">Revision</th>
        												<th data-sortable="true" data-field="model">Mod</th>
        												<th data-sortable="true" data-field="swConf" data-class="pre">SW Configuration</th>
        											</tr>
        										</thead>
        									</table>
                                    	</div>
                                    </div>
                                    <div class="tab-pane" id="tab-second">
                						<div id="noRevsAndModsDiv" class="container-fluid text-center">
                    						<label class="noData-label">No data available for the selected duration or filters</label>
                    					</div>
                                    	
                                    	<div id="revsAndModsTableDiv">
                                            <table id="revsAndModsTable" data-classes="table"
        										data-pagination="true" data-page-list="[25, 50, 100, All]"
        										data-page-size="25" data-striped="true" data-search="true"
        										data-search-align="left" data-show-export="true" data-export-data-type= "all">
        										<thead>
        											<tr>
        												<th data-sortable="true" data-field="airline">Airline</th>
        												<th data-sortable="true" data-field="platform">Platform</th>
        												<th data-sortable="true" data-field="configuration">Configuration</th>
        												<th data-sortable="true" data-field="tailsign">Tailsign</th>
        												<th data-sortable="true" data-field="hostName">Hostname</th>
        												<th data-sortable="true" data-field="hwPartNumber">HW Part Number</th>
        												<th data-sortable="true" data-field="serialNumber">Serial Number</th>
        												<th data-sortable="true" data-field="revision">Revision</th>
        												<th data-sortable="true" data-field="model">Mod</th>
        												<th data-sortable="true" data-field="lastUpdate">Last update date</th>
        											</tr>
        										</thead>
        									</table>
                                    	</div>
                                    </div>
                                </div>
                            </div>                                                   
                            <!-- END TABS -->                        
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
    	<script src="../controllers/HardwareRevisionsModsController.js"></script>
    <!-- END SCRIPTS -->         
    </body>
    <script>
        var airlineId_nav = "<?php echo "$airlineId";?>";
        var platform_nav = "<?php echo "$platform";?>";
        var configuration_nav = "<?php echo "$configuration";?>";
        var software_nav = "<?php echo "$software";?>";
        var tailsign_nav = "<?php echo "$tailsign";?>";

        var airlineIdfromAirlines='<?php echo $_REQUEST ['airlineId'];?>';
    	if(airlineIdfromAirlines!=''){		
			var airId='<?php echo $_REQUEST ['airlineId'];?>';		
		}
    </script>
</html>