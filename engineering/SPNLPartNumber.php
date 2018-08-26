<!DOCTYPE html>
<?php
session_start();

$menu ='SPNLPartNumberMatching';

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';

require_once ("checkEngineeringPermission.php");
$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");

// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

?>
<html lang="en" data-ng-app="myApp">

<head>
<!-- META SECTION -->
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
<title>BITE Analytics</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->
<!-- END META SECTION -->
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">

<!-- EOF CSS INCLUDE -->
<script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>

<style>
.dragover {
	height: 260px;
	border: 2px solid #97bbcd;
	border-style: dashed;
	cursor: pointer;
	border-radius: 10px;
}

.dragover:hover {
	background: #def0f9;
	border-style: solid;
}

.transitionTime {
	transition: all ease-in 0.5s;
}

.width100 {
	width: 100%;
	transition: all ease-in 0.5s;
	padding-left: 10px;
	padding-right: 10px;
}
.dateChange{
        background-color:#F9F9F9 !important;
        color:#000000 !important;
        cursor: auto !important;
    }
</style>
</head>

<body id="ctrldiv" ng-controller="uploadSoftwareConfigController">
	<!-- START PAGE CONTAINER -->
	<div id="container" class="page-container">
		<!-- START PAGE SIDEBAR -->
	<?php include("SideNavBar.php"); ?>
		<!-- END PAGE SIDEBAR -->
		<!-- PAGE CONTENT -->
		<div class="page-content">
			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel"
				id="containerDiv">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span> </a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SEARCH 
            <li class="xn-search">
            	<form role="form">
            		<input type="text" name="search" placeholder="Search..." />
            	</form>
            </li>-->
				<!-- END SEARCH -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span> </a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">SPNL Upload</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					SPNL Upload
				</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-heading ui-draggable-handle">
								<h2 class="panel-title">
									<span class="fa fa-upload"></span> Upload Software
									Configurations
								</h2>
							</div><br/><br/>
							<div class="panel-body" style="margin-bottom: -45px;">								
								<form role="form" class="form-horizontal"
									action="../ajax/uploadSWConfigFile.php" method="POST"
									enctype="multipart/form-data" target="upload_target"
									id="fileUploadForm" >
									
									<div class="width100 dropzone dropzone-mini ng-pristine ng-valid dz-clickable" id="dropParentDiv">
										<div id="drop" >
											<div class="dz-message"
												style="margin-top: 66px;text-align: center; position: relative; top: 50%; transform: perspective(1px) translateY(-50%);">
												Drop Excel files here or click to upload
												<!-- <i class="fa fa-file-excel-o fa-5x"></i> -->
											</div>
										</div>
										<input type="file" name="xlfile" id="xlf"
											style="display: none" accept=".xls, .xlsx" />
									</div>
									<div id="fileInputDiv">
										<div class="col-md-6" hidden id="configInputDiv">
											<div class="form-group">
												<label class="col-md-3 control-label">Customer</label>
												<div class="col-md-9">
													<select class="form-control" style="width: 100%"
														data-live-search="true"
														data-dropup-auto="false" 
														ng-change="populateDropdowns('customer')"
														ng-model="customer">
														<option ng-repeat="cust in airlineAcronymList"
															value="{{cust}}">{{cust}}</option>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label class="col-md-3 control-label">Aircraft Type</label>
												<div class="col-md-9">
													<select class="form-control" style="width: 100%"
														data-live-search="true"
														data-dropup-auto="false" 
														ng-change="populateDropdowns('type')" ng-model="type">
														<option ng-repeat="type in aircraftType" value="{{type}}">{{type}}</option>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label class="col-md-3 control-label">Platform</label>
												<div class="col-md-9">
													<select class="form-control" style="width: 100%"
														data-live-search="true" data-dropup-auto="false" ng-model="platform">
														<option ng-repeat="platform in aircraftPlatform"
															value="{{platform}}">{{platform}}</option>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label class="col-md-3 control-label">Software Version</label>
												<div class="col-md-9">
													<input type="text" class="form-control"
														placeholder="The software version" ng-model="version" />
												</div>
											</div>
											<div class="form-group">
												<label class="col-md-3 control-label">Software Baseline</label>
												<div class="col-md-9">
													<input type="text" class="form-control"
														placeholder="The software baseline" ng-model="baseline" />
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-12" style="text-align: center;">
													<!-- <button type="submit" class="btn btn-info">Upload</button> -->
													<button type="submit" class="btn btn-info mb-control"
														data-box="#message-box-info">Upload</button>
												</div>
											</div>
										</div>
									</div>
									<div class="message-box message-box-info animated fadeIn"
										id="message-box-info">
										<div class="mb-container">
											<div class="mb-middle">
												<div class="mb-title">
													<span class="fa fa-info"></span> Information
												</div>
												<div class="mb-content">
													<br>
													<h3 style="color: white;">Please confirm the configuration
														details before uploading.</h3>
													<br>
													<h5 style="margin-left: 20px; color: white;">Customer :
														{{customer}}</h5>
													<h5 style="margin-left: 20px; color: white;">Aircraft Type
														: {{type}}</h5>
													<h5 style="margin-left: 20px; color: white;">Platform :
														{{platform}}</h5>
													<h5 style="margin-left: 20px; color: white;">Software
														Version : {{version}}</h5>
													<h5 style="margin-left: 20px; color: white;">Software
														Baseline : {{baseline}}</h5>
												</div>
												<div class="mb-footer">
													<button
														class="btn btn-danger btn-clean pull-right mb-control-close"
														id="button-1" style="margin-left: 5px;">Cancel</button>
													<button
														class="btn btn-success btn-clean pull-right mb-control-close"
														id="button-0" style="margin-left: 0px;"
														onclick="$('#fileUploadForm').submit()">Ok</button>

													<!-- <button class="btn btn-default btn-lg pull-right mb-control-close">Close</button>
												<button class="btn btn-default btn-lg pull-right mb-control-close">Close</button> -->
												</div>
											</div>
										</div>
									</div>
								</form>
								<iframe id="upload_target" name="upload_target" src="#"
									style="width: 0px; height: 0px; border: 1px solid #fff;"></iframe>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default" id="dataTable"
							style="margin-top: 10px;">
							<div class="panel-heading ui-draggable-handle">
								<h2 class="panel-title">
									<span class="glyphicon glyphicon-th"></span> Upload History
								</h2>
							</div>
							<div class="panel-body" >
								<div class="row">
								<input type="hidden" id="airlineIds" ng-model="airlineIds"
                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
                				<div class="row">
                					<div class="col-md-2">
                						<label for="airline">Airline</label>
                						<div>
                							<select id="airline" class="selectpicker show-tick" data-live-search="true" data-dropup-auto="false" data-width="100%" multiple title="All" ></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="platform">Platform</label>
                						<div>
                							<select id="platform" class="selectpicker show-tick" data-width="100%" multiple title="All" data-dropup-auto="false" data-live-search="true"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="configType">Configuration</label>
                						<div>
                							<select id="configType" class="selectpicker show-tick" data-width="100%" multiple title="All" data-dropup-auto="false" data-live-search="true"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="software">Software</label>
                						<div>
                							<select id="software" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" multiple title="All" data-dropup-auto="false" data-live-search="true"></select>
                						</div>
                					</div>
                					<!-- <div class="col-md-2">
                						<label for="tailsign">Tailsign</label>
                						<div>
                							<select id="tailsign" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" multiple title="All"></select>
                						</div>
                					</div> 
                					<div class="col-md-1">
                						<label for="status">Status</label>
                						<div>
                							<select id="status"
													class="form-control selectpicker show-tick"
													value="selectedStatus" multiple title="All">													
													<option value="PROCESSED">PROCESSED</option>
													<option value="UNPROCESSED">UNPROCESSED</option>
												</select>
                						</div>
                					</div>-->
                					<div class="col-md-2">
                						<label for="startDateTimePicker">From</label>
                						<div>
                							<input class="form-control dateChange" id="startDateTimePicker"
                								type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
                						</div>
                					</div>
                					<div class="col-md-2">
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
                						<button id="filter" class="btn btn-primary"
                							data-ng-click="filter()">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
                					</div>
                				</div>
								<div class="row" style="margin-left: -25px; margin-right: -25px;">
									<div class="col-md-12 text-left">
										<hr style="border-top: 1px solid #E5E5E5;">
									</div>
								</div>
                				<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading
									data...
								</div>
								<div class="table-responsive" style="overflow: auto;">

									<table id="fileUploadHistoryTable" data-classes="table"
										data-pagination="true" data-page-list="[25, 50, 100, All]"
										data-page-size="25" data-striped="true" data-search="true"
										data-search-align="left" data-show-export="true">
										<thead>
											<tr>
												<th data-field="project" data-sortable="true">Project</th>
												<th data-field="release_tag" data-sortable="true">Release
													Tag</th>
												<th data-field="customer" data-sortable="true">Customer</th>
												<th data-field="aircraft_type" data-sortable="true">Aircraft
													Type</th>
												<th data-field="platform" data-sortable="true">Platform</th>
												<th data-field="software_baseline" data-sortable="true">Software
													Baseline</th>
												<th data-field="software_version" data-sortable="true">Software
													Version</th>
												<th data-field="baseline_media_partnumber"
													data-sortable="true">Baseline Media P/N</th>
												<th data-field="current_release_media_partnumber"
													data-sortable="true">Current Release Media P/N</th>
												<th data-field="upload_date" data-sortable="true">Upload
													Date</th>
												<th data-field="aircrafts_affected_id" data-sortable="true">Aircrafts
													Affected</th>
												<th data-field="filename" data-sortable="true">File Name</th>
												<th data-field="status" data-sortable="true">Status</th>
											</tr>
										</thead>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- END PAGE CONTENT -->
		</div>
		<!-- END PAGE CONTAINER -->
		</div>
		
    	<!-- Logout page -->
    	<?php include("../logout.php"); ?>
    	<!-- END Logout page-->
    	
    <div class="modal" data-sound="alert" id="modalTable">
		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 54px; border-radius: 6px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">SPNL FILE</h4>
				</div> 
				<div class="modal-body">
					<u>Rules for uploading SPNL Files:</u> <br> <br>
					<ul>
						<li>Only <b>ONE FILE</b> can be uploaded at a time. <br>To upload
											another file you have to reload the page.
						</li>
						<li>Excel file should be uploaded.</li>									
					</ul>
    				<br/>					
				</div>
			</div>
		</div>
	</div>    	
    	
		<!-- START PRELOADS -->
		<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
		<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
		<!-- END PRELOADS -->
		<!-- START SCRIPTS -->
		<!-- START PLUGINS -->
		<script type="text/javascript"
			src="../js/plugins/jquery/jquery.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/jquery/jquery-ui.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		<!-- END PLUGINS -->

		<!-- THIS PAGE PLUGINS -->
		<script type='text/javascript'
			src='../js/plugins/icheck/icheck.min.js'></script>
		<script type="text/javascript"
			src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>

    	<script src="../js/jquery.datetimepicker.js"></script>
    	<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap-colorpicker.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap-file-input.js"></script>
		<script type="text/javascript"
			src="../js/plugins/tagsinput/jquery.tagsinput.min.js"></script>
		<script
			src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.6.4/angular.min.js"></script>
		<script
			src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.4/angular-animate.js"></script>
		<script type="text/javascript"
			src="../controllers/uploadSoftwareConfigController.js"></script>
		<script src="../js/xlsx.full.min.js"></script>
		<script type="text/javascript" src="../js/plugins/noty/jquery.noty.js"></script>
		<script type="text/javascript"
			src="../js/plugins/noty/layouts/topCenter.js"></script>
		<script type="text/javascript"
			src="../js/plugins/noty/layouts/topLeft.js"></script>
		<script type="text/javascript"
			src="../js/plugins/noty/layouts/topRight.js"></script>
		<script type="text/javascript"
			src="../js/plugins/noty/themes/default.js"></script>
		<!-- END THIS PAGE PLUGINS -->

		<!-- START TEMPLATE -->
		<!-- <script type="text/javascript" src="../js/settings.js"></script> -->

		<script type="text/javascript" src="../js/plugins.js"></script>
		<script type="text/javascript" src="../js/actions.js"></script>
		<script src="../js/bootstrap-table.js"></script>
		<script src="../js/bootstrap-table-export.js"></script>
		<script src="../js/tableExport.js"></script>
		<!-- END TEMPLATE -->
	<script type="text/javascript">
		$(document).ready(function(){

			$('#airline').selectpicker({                              
		        size: 6
		  	});
		  	
		    $('#platform').selectpicker({                              
					 size: 6
		      	});
		      	
		    $('#configType').selectpicker({                              
					 size: 6
		      	});
		      	
		    $('#software').selectpicker({                              
					 size: 6
		      	});
		      	
		    $('#tailsign').selectpicker({                              
					 size: 6
		  	});

		    $('#reportBy').selectpicker({                              
					 size: 6
		  	});

		    $('#period').selectpicker({                              
					 size: 6
		  	});

			$('#airline').on('change', function(){
			    angular.element($("#ctrldiv")).scope().loadPlatforms();
			  });

			$('#platform').on('change', function(){
				angular.element($("#ctrldiv")).scope().loadConfigTypes();
			  });

			$('#configType').on('change', function(){
				angular.element($("#ctrldiv")).scope().loadSoftwares();
			  });

		});
	</script>
</body>
</html>
