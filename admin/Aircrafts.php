<?php
session_start ();
$menu = 'AIRCRAFTS';

require_once("../common/validateUser.php");
include("../common/getAircraftTypes.php");
include("../common/getAircraftPlatforms.php");
include("../common/getAircraftCodes.php");
$approvedRoles = [$roles["admin"]];
$auth->checkPermission($hash, $approvedRoles);
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
<!-- EOF META SECTION -->

<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->

<script src="../js/jquery-1.11.2.js"></script>

<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-cookies.js"></script>
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>

<script type='text/javascript' src='../js/plugins/noty/jquery.noty.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topCenter.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topLeft.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topRight.js'></script>            
<script type='text/javascript' src='../js/plugins/noty/themes/default.js'></script>

</head>
<style>
.dropdown-menu{
	min-width: 103px;
}
</style>
<body data-ng-controller="AircraftsController">
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
				<li>Home</li>
				<li class="active">Aircrafts</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Aircrafts</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
                            	<div id="successAlertDiv" class="alert alert-success text-center"></div>
								<div id="toolbar">
                            		<button id="add" class="btn btn-success" data-toggle="modal" data-target="#addAircraftsModal">
                            			Add Aircrafts
                            		</button>
                            	</div>
								<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
								</div>
								<div class="table-editable">
									<table id="aircraftsTable" data-toolbar="#toolbar" data-pagination="true" 
									data-page-list="[25, 50, 100, All]" data-page-size="25" data-striped="true" 
									data-search="true" data-search-align="right" ></table>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- ADD AIRCRAFTS MODAL -->
				<div class="modal fade" id="addAircraftsModal" tabindex="-1" role="dialog"
					aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 80px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="myModalLabel">Add Aircrafts</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="addAircraftsAlertDiv"></div>
								<form>
									<!-- <input type="hidden" name="id" id="id"> -->
									<div class="form-group">
                                  		<label for="tailsign" class="col-sm-5 control-label text-right">Tailsign<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="tailsign" name="tailsign" style="width: 220px;" required>
                                  	</div>
									<div class="form-group">
                                  		<label for="noseNumber" class="col-sm-5 control-label text-right">Nose Number</label>
                                        <input type="text" class="form-control" id="noseNumber" name="noseNumber" style="width: 220px;">
                                  	</div>
									<div class="form-group">
                                  		<label for="airline" class="col-sm-5 control-label text-right">Airline<span style="color:red;"> *</span></label>
                                        <select class="selectpicker show-tick" id="airline" name="airline" name="airline" 
										data-live-search="true"data-selected-text-format="count > 3" data-size="6" data-dropup-auto="false" title="Select">
                    					</select>
                                  	</div>
									<div class="form-group">
                                  		<label for="msn" class="col-sm-5 control-label text-right">MSN<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="msn" name="msn" style="width: 220px;">
                                  	</div>
                                    <div class="form-group">
                        	            <label for="type" class="col-sm-5 control-label text-right">Type<span style="color:red;"> *</span></label>
                                        <select id="type" class="selectpicker show-tick" name="type" data-size="6" data-dropup-auto="false" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                        	            <label for="aircraftSeatConfiguration" class="col-sm-5 control-label text-right">Seat Configuration</label>
                                        <select id="aircraftSeatConfiguration" name="aircraftSeatConfiguration" class="selectpicker show-tick" data-size="6" data-dropup-auto="false" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                        	            <label for="aircraftConfiguration" class="col-sm-5 control-label text-right">Aircraft Configuration<span style="color:red;"> *</span></label>
                                        <select id="aircraftConfiguration" name="aircraftConfiguration" class="selectpicker show-tick" data-size="6" data-dropup-auto="false" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                    	                <label for="platform" class="col-sm-5 control-label text-right">Platform<span style="color:red;"> *</span></label>
                                        <select id="platform" name="platform" class="selectpicker show-tick" data-size="6" data-dropup-auto="false" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                    	                <label for="isp" class="col-sm-5 control-label text-right">ISP<span style="color:red;"> *</span></label>
                                        <select id="isp" name="isp" class="selectpicker show-tick" data-size="6" data-dropup-auto="false" title="Select">
                                        	<option value="NONE">NONE</option>
                                        	<option value="OnAir">OnAir</option>
                                        	<option value="Gogo">Gogo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                        	            <label class="col-sm-5 control-label text-right">EIS</label>
                        	            <div class="input-group" style="width: 220px;">
                                        	<input class="form-control" id="eis" type="text" name="eis"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                                        </div>
                                    </div>
									<div class="form-group">
                                  		<label for="swBaseLine" class="col-sm-5 control-label text-right">Software BaseLine<span style="color:red;"> *</span></label>
                                        <input type="text" id="swBaseLine" name="swBaseLine" class="form-control" style="width: 220px;">
                                  	</div>
									<div class="form-group">
                                  		<label for="customerSw" class="col-sm-5 control-label text-right">Customer Software<span style="color:red;"> *</span></label>
                                        <input type="text" id="customerSw" name="customerSw" class="form-control" style="width: 220px;">
                                  	</div>
                                    <div class="form-group">
                        	            <label class="col-sm-5 control-label text-right">Software Installation</label>
                        	            <div class="input-group" style="width: 220px;">
                                        	<input class="form-control" id="swInstallation" type="text" name="swInstallation"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                                        </div>
                                    </div>
								</form>
							</div>
							<div class="modal-footer">
								<span class="text-muted" style="float: left"><em><span style="color: red;">*</span>Indicates required field</em></span>
								<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
								<button id="addAircrafts" type="button" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- END ADD AIRCRAFTS MODAL -->
			</div>
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	<!-- MESSAGE BOX-->
	<?php include("../logout.php"); ?>
	<!-- END MESSAGE BOX-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
</body>
<script src="../controllers/AircraftsController.js"></script>
</html>