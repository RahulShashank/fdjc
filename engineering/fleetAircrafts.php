<?php
session_start ();
$menu = 'Aircraft';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- CSS INCLUDE -->
<!-- <link href="../css/styles.css" rel="stylesheet"> -->
<!-- <link href="../css/dashboard.css" rel="stylesheet"> -->
<link href="../css/jquery/jquery-ui-1.11.2.css" rel="stylesheet">
<link rel="stylesheet" href="../css/fontawesome/font-awesome.min.css">
<link rel="stylesheet" href="../css/chosen/chosen.min.css">

<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<!-- <script src="../js/bootstrap.min.js"></script> -->
<script src="../js/angular.js"></script>
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<script src="../js/chosen/chosen.jquery.min.js"></script>

<script src="../js/plugins/bootstrap/bootstrap.min.js"></script> 
<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<style>
.modal-backdrop {
  z-index: -1;
}

.rounded {
    -webkit-border-radius: 13px;
}

.search2 {
    padding: 6px 15px 6px 30px;
    margin: 3px;
    background: url(../img/search.png) no-repeat 8px 6px;
    outline: none;
    border: 1px solid #d0d0d0;
}
</style>
</head>
<body data-ng-controller="aircraftsCtrl">
	<!-- START PAGE CONTAINER -->
	<div class="page-container" >

		<!-- START PAGE SIDEBAR -->
            <?php include("SideNavBar.php"); ?>
            <!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div id="ctrldiv" class="page-content" >

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
				<li class="active">Aircrafts Dashboard</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Aircrafts Dashboard</h2>
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
                							<select id="platform" class="selectpicker show-tick" multiple data-live-search="true"  title="All" data-width="100%" ></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="aircraft type">Configuration</label>
                						<div>
                							<select id="configType" class="selectpicker show-tick" multiple data-live-search="true"  title="All" data-width="100%" ></select>
                						</div>
                					</div>
                					
                					<div class="col-md-2">
                						<label for="status">Status</label>
                						<div>
                        					<select id="status" class="selectpicker show-tick" data-live-search="true" data-width="100%" data-value="selectedStatus">
												<option value="">All</option>													
                                				<option value="No Status" data-icon="glyphicon-unchecked">&nbsp;No Status</option>
                                				<option value="Ground" data-icon="glyphicon-road">&nbsp;Ground</option>
												<option value="In Air" data-icon="glyphicon-plane">&nbsp;In Air</option>
                                    			<option value="OK" data-icon="glyphicon-ok">&nbsp;OK</option>
												<option value="Warning" data-icon="glyphicon-warning-sign">&nbsp;Warning</option>
												<option value="Watch" data-icon="glyphicon-flag">&nbsp;Watch</option>
                                				<option value="New Software" data-icon="glyphicon-hdd">&nbsp;New Software</option>
											</select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="buttons">&nbsp;&nbsp;</label>
            							<div>
            								<button id="filter" class="btn btn-primary">Filter</button>
                							&nbsp;&nbsp;&nbsp;
                							<button id="reset" type="button" class="btn btn-primary">Reset</button>
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
							<div class="panel-body">
								<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
								</div>
    							<div class="lighter text-center">
                    				<span><input type="text" class="search2 rounded" placeholder="Search..." size="50" data-ng-model="searchKeyword"></span>
                    				<br><br>
                    			</div>
								<div class="row">
                    				<div class="col-xs-12 col-md-4" data-ng-repeat="aircraft in aircrafts | filter: actype | filter: platform | filter:filterStatus | filter: searchKeyword | orderBy: ['-status','tailsign'] ">
                    					<div class="card" ng-class="{'card': (aircraft.lastStatusComputed >= 2), 'cardDanger': (aircraft.status >= 2), 'cardWarning': (aircraft.status == 1), 'cardOK': (aircraft.status == 0)}"}>
                    						 <div class="cardHeaderFooter" style="padding: 7px">
                    							<img src="../img/sr{{aircraft.systemResetStatus}}.png" style="vertical-align:middle" title="System Reset Status">&nbsp;&nbsp;&nbsp;<img src="../img/he{{aircraft.headEndStatus}}.png" style="vertical-align:middle" title="Head-End Status">&nbsp;&nbsp;&nbsp;<img src="../img/fc{{aircraft.firstClassStatus}}.png" style="vertical-align:middle" title="First Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/bc{{ aircraft.businessClassStatus }}.png" style="vertical-align:middle" title="Business Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/ec{{ aircraft.economyClassStatus }}.png" style="vertical-align:middle" title="Economy Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/cn{{ aircraft.connectivityStatus }}.png" style="vertical-align:middle" title="Connectivity Status">
                    						</div>
                    						<div class="cardBody">
                    							<div style="font-size: 12px">
                    								<div style="float:left" class="cardHeaderFooter">
                    									&nbsp;&nbsp;{{ aircraft.type }} / {{ aircraft.msn }}
                    								</div>
                    								<div style="float:right" class="cardHeaderFooter">
                    									{{ aircraft.platform }} / {{ aircraft.software }}&nbsp;&nbsp;
                    								</div>
                    								<div style="clear:both">
                    								</div>
                    							</div>
                    							<strong>{{ aircraft.tailsign }} <span ng-if="aircraft.nose"> ({{ aircraft.nose }})</span></strong>
                    							<div style="font-size: 12px">
                    								<div style="float:left" class="cardHeaderFooter">
                    									&nbsp;&nbsp;
                    									<span ng-click="editMaintenanceStatus(aircraft)" style="cursor: pointer;">
                        									<span class="glyphicon glyphicon-unchecked" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'No Status')"></span>
                        									<span class="glyphicon glyphicon-road" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Ground')"></span>
                        									<span class="glyphicon glyphicon-plane" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'In Air')"></span>
                        									<span class="glyphicon glyphicon-flag" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Watch')"></span>
                        									<span class="glyphicon glyphicon-ok" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'OK')"></span>
                        									<span class="glyphicon glyphicon-warning-sign" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Warning')"></span>
                        									<span class="glyphicon glyphicon-hdd" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'New Software')"></span>
                        									<span ng-if="(aircraft.maintenanceStatus == 'No Status')"><i>No status</i></span>
                        									<span ng-if="(aircraft.maintenanceStatus != 'No Status')">{{ aircraft.maintenanceStatus }}</span>
                    									</span>
                    								</div>
                    								<div style="float:right" class="cardHeaderFooter">
                    									<span ng-if="!aircraft.Content"><i>No content</i></span>{{ aircraft.Content }}&nbsp;&nbsp;
                    								</div>
                    								<div style="clear:both">
                    								</div>
                    							</div>
                    						</div>
                    						<div class="cardHeaderFooter">
                    							<a href="AircraftReport.php?aircraftId={{ aircraft.id }}"><i class="fa fa-file-text-o fa-fw" aria-hidden="true" title="Report"></i></a>
                    							&nbsp;
                    							<a href="HardwareRevisionsMods.php?aircraftId={{ aircraft.id }}"><i class="fa fa-tasks fa-fw" aria-hidden="true" title="H/W Rev & Mod"></i></a>
                    							&nbsp;
                    							<a href="MaintenanceActivities.php?aircraftId={{ aircraft.id }}"><i class="fa fa-wrench fa-fw" aria-hidden="true" title="Maintenance Activities"></i></a>
                    							&nbsp;
                    							<a href="lopa.php?aircraftId={{ aircraft.id }}&lopaVisited=false"><i class="fa fa-th" aria-hidden="true" title="LOPA"></i></a>
                    							&nbsp;
                    						</div>
                    					</div>
                    				</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Modal : Start -->
				<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
						<div class="modal-content" style="border-radius: 5px; border-width: 0px;">
							<div class="modal-header">
								<button id="closeModal" type="button" class="close" data-dismiss="modal">&times;</button>
								<h4 class="modal-title" id="myModalLabel">Update Aircraft Status / {{tailsign}}</h4>
							</div>
							<form class="form-horizontal" ng-submit="updateStatusVersion();">
								<div class="modal-body">
									<div id="error"></div>
									<br>
									<div class="form-group">
										<label for="currentStatus" class="col-sm-4 control-label">Current Status: </label>
										<div class="col-sm-6">
											<p class="form-control-static">
												<span class="glyphicon glyphicon-unchecked" aria-hidden="true" ng-if="(originalStatus == 'No Status')"></span>
												<span class="glyphicon glyphicon-road" aria-hidden="true" ng-if="(originalStatus == 'Ground')"></span>
												<span class="glyphicon glyphicon-plane" aria-hidden="true" ng-if="(originalStatus == 'In Air')"></span>
												<span class="glyphicon glyphicon-flag" aria-hidden="true" ng-if="(originalStatus == 'Watch')"></span>
												<span class="glyphicon glyphicon-ok" aria-hidden="true" ng-if="(originalStatus == 'OK')"></span>
												<span class="glyphicon glyphicon-warning-sign" aria-hidden="true" ng-if="(originalStatus == 'Warning')"></span>
												<span class="glyphicon glyphicon-hdd" aria-hidden="true" ng-if="(originalStatus == 'New Software')"></span>
												<span ng-if="(originalStatus == 'No Status')"><i>No status</i></span>
												<span ng-if="(originalStatus != 'No Status')">{{ originalStatus }}</span>
											</p>
										</div>
									</div>
									<div class="form-group">
										<label for="newStatus" class="col-sm-4 control-label">New Status: </label>
										<div class="col-sm-6">
											<select class="selectpicker show-tick" id="newStatus" title="Select a status..." data-width="100%">
												<!-- <option value="">Select a status...</option> -->
												<option value="No Status" data-icon="glyphicon-unchecked">&nbsp;No Status</option>
												<option value="Ground" data-icon="glyphicon-road">&nbsp;Ground</option>
												<option value="In Air" data-icon="glyphicon-plane">&nbsp;In Air</option>
												<option value="OK" data-icon="glyphicon-ok">&nbsp;OK</option>
												<option value="Warning" data-icon="glyphicon-warning-sign">&nbsp;Warning</option>
												<option value="Watch" data-icon="glyphicon-flag">&nbsp;Watch</option>
												<option value="New Software" data-icon="glyphicon-hdd">&nbsp;New Software</option>
											</select>
										</div>
									</div>
								</div>
								<input type="hidden" id="aircraftId" value="{{ aircraftId }}" />
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									<button id="updateAircraft" class="btn btn-primary">Update</button>
								</div>
							</form>
						</div>
					</div>
				</div>
				<!-- Modal : End -->
				
			</div>
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->

	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.3.0/ui-bootstrap-tpls.min.js"></script>
    <script src="../controllers/aircraftsCtrl.js"></script>
    <script src="../js/FileSaver.min.js"></script>
    <script src="../js/canvas-toBlob.js"></script>
</body>
</html>