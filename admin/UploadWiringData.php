<?php
session_start();
$menu = 'UPLOAD_WIRING_DATA';
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
<script type='text/javascript' src='../js/plugins/noty/layouts/center.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topCenter.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topLeft.js'></script>
<script type='text/javascript' src='../js/plugins/noty/layouts/topRight.js'></script>            
<script type='text/javascript' src='../js/plugins/noty/layouts/topRight.js'></script>            
<script type='text/javascript' src='../js/plugins/noty/themes/default.js'></script>

<script src="../js/plugins/dropzone/dropzone.min.js"></script>
</head>
<style>
.dropdown-menu{
	min-width: 103px;
}
</style>
<body data-ng-controller="UploadWiringDataController">
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
				<li class="active">Upload Wiring Data</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Upload Wiring Data</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="row">
									<div class="col-md-2 form-group">
										<label for="airline">Airline</label>
										<div>
											<select id="airline" class="selectpicker show-tick"
												data-live-search="true" data-width="100%"
												style="max-width: 150px;" data-size="6" title="Select"></select>
										</div>
									</div>
									<div class="col-md-2 form-group">
										<label for="platform">Platform</label>
										<div>
											<select id="platform" class="selectpicker show-tick"
												data-width="100%" data-max-width="120px;" data-size="6" title="Select"></select>
										</div>
									</div>
									<div class="col-md-2 form-group">
										<label for="configType">Configuration</label>
										<div>
											<select id="configType" class="selectpicker show-tick"
												data-width="100%" data-size="6" title="Select">
											</select>
										</div>
									</div>
									<div class="col-md-2 form-group">
										<label for="software">Software</label>
										<div>
											<select id="software" class="selectpicker show-tick"
												data-width="100%" data-size="6" title="Select"></select>
										</div>
									</div>
									<div class="col-md-4 form-group text-center">
										<br/>
										<div class="alert alert-danger vcenter" role="alert" id="alertDiv"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div>
								<div class="panel-body">
									<div class="col-md-3">
										<h4><b>Wiring Data File</b></h4>
									</div>
									<div class="col-md-6 text-center"></div>
									<div class="col-md-12">
									<u>Rules for uploading Wiring Data File:</u> <br> <br>
									<ul>
										<li>Only <b>ONE FILE</b> can be uploaded at a time. To upload
											another file you have to reload the page.
										</li>
									</ul>
									</div>
									<div class="col-md-12" align="center">
										<div class="block push-up-10" id="wiringDataDropzoneDiv">
											<form id="wiringDataDropzone" action="../ajax/UploadWiringDataDAO.php"
												method="post" class="dropzone dropzone-mini">
												<div class="dz-message">Drop Wiring data file here or click to
													upload.</div>
											</form>
										</div>
									</div>
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
	<!-- MESSAGE BOX-->
	<?php include("../logout.php"); ?>
	<!-- END MESSAGE BOX-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
</body>
<script src="../controllers/UploadWiringDataController.js"></script>
</html>