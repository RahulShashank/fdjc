<?php
session_start ();
$menu = 'LRU_COUNT';
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
<body data-ng-controller="LRUCountController">
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
				<li class="active">LRU Count</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>LRU Count</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
                            	<div id="successAlertDiv" class="alert alert-success text-center"></div>
								<div id="toolbar">
                            		<button id="add" class="btn btn-success" data-toggle="modal" data-target="#addLRUCountModal">
                            			Add LRU Count
                            		</button>
                            	</div>
								<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading data...
								</div>
								<div class="table-editable">
									<table id="lruCountTable" data-toolbar="#toolbar"></table>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- ADD LRU COUNT MODAL -->
				<div class="modal fade" id="addLRUCountModal" tabindex="-1" role="dialog"
					aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 80px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="myModalLabel">Add LRU Count</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="addLRUCountAlertDiv"></div>
								<form>
									<input type="hidden" name="index" id="index">
									<input type="hidden" name="id" id="id">
									<div class="form-group">
                                  		<label for="airlinename" class="col-sm-5 control-label text-right">Airline Name<span style="color:red;"> *</span></label>
                                        <select class="selectpicker show-tick" id="airline" name="airline" name="selectedAirline" data-size="6" 
                                        data-live-search="true" data-selected-text-format="count > 3" title="Select">
                    					</select>
                                  	</div>
                                    <div class="form-group">
                    	                <label for="platform" class="col-sm-5 control-label text-right">Platform<span style="color:red;"> *</span></label>
                                        <select id="platform" name="platform" class="selectpicker show-tick" data-size="6" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                        	            <label for="configName" class="col-sm-5 control-label text-right">Config<span style="color:red;"> *</span></label>
                                        <select id="configuration" class="selectpicker show-tick" name="configuration" data-size="6" title="Select">
                                        </select>
                                    </div>
                                    <div class="form-group">
                        	            <label for="dsuCount" class="col-sm-5 control-label text-right">DSU Count<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="dsuCount" name="dsuCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="laicCount" class="col-sm-5 control-label text-right">LAIC Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="laicCount" name="laicCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="icmtCount" class="col-sm-5 control-label text-right">ICMT Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="icmtCount" name="icmtCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="adbgCount" class="col-sm-5 control-label text-right">ADBG Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="adbgCount" name="adbgCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="qsebCount" class="col-sm-5 control-label text-right">QSEB Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="qsebCount" name="qsebCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="sdbCount" class="col-sm-5 control-label text-right">SDB Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="sdbCount" name="sdbCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="svducount" class="col-sm-5 control-label text-right">SVDU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="svduCount" name="svduCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="tpmuCount" class="col-sm-5 control-label text-right">TPMU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="tpmuCount" name="tpmuCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="tpcuCount" class="col-sm-5 control-label text-right">TPCU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="tpcuCount" name="tpcuCount" style="width: 100px;" required>
                                    </div>
								</form>
							</div>
							<div class="modal-footer">
								<span class="text-muted" style="float: left"><em><span style="color: red;">*</span>Indicates required field</em></span>
								<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
								<button id="addLRUCount" type="button" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- END ADD LRU COUNT MODAL -->
				
				<!-- EDIT LRU COUNT MODAL -->
				<div class="modal fade" id="editLRUCountModal" tabindex="-1" role="dialog"
					aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 80px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="myModalLabel">Edit LRU Count</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="editLRUCountAlertDiv"></div>
								<form>
									<input type="hidden" name="index" id="index">
									<input type="hidden" name="id" id="id">
									<div class="form-group">
                                  		<label for="airlinename" class="col-sm-5 control-label text-right">Airline Name<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="airline" name="airline" style="width: 250px;" readonly>
                                  	</div>
                                    <div class="form-group">
                    	                <label for="platform" class="col-sm-5 control-label text-right">Platform<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="platform" name="platform" style="width: 250px;" readonly>
                                    </div>
                                    <div class="form-group">
                        	            <label for="configName" class="col-sm-5 control-label text-right">Config<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="configuration" name="configuration" style="width: 250px;" readonly>
                                    </div>
                                    <div class="form-group">
                        	            <label for="dsuCount" class="col-sm-5 control-label text-right">DSU Count<span style="color:red;"> *</span></label>
                                        <input type="text" class="form-control" id="dsuCount" name="dsuCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="laicCount" class="col-sm-5 control-label text-right">LAIC Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="laicCount" name="laicCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="icmtCount" class="col-sm-5 control-label text-right">ICMT Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="icmtCount" name="icmtCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="adbgCount" class="col-sm-5 control-label text-right">ADBG Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="adbgCount" name="adbgCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="qsebCount" class="col-sm-5 control-label text-right">QSEB Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="qsebCount" name="qsebCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="sdbCount" class="col-sm-5 control-label text-right">SDB Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="sdbCount" name="sdbCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="svducount" class="col-sm-5 control-label text-right">SVDU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="svduCount" name="svduCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="tpmuCount" class="col-sm-5 control-label text-right">TPMU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="tpmuCount" name="tpmuCount" style="width: 100px;" required>
                                    </div>
                                    <div class="form-group">
                        	            <label for="tpcuCount" class="col-sm-5 control-label text-right">TPCU Count<span style="color:red;"> *</span></label>
                            	    	<input type="text" class="form-control" id="tpcuCount" name="tpcuCount" style="width: 100px;" required>
                                    </div>
								</form>
							</div>
							<div class="modal-footer">
								<span class="text-muted" style="float: left"><em><span style="color: red;">*</span>Indicates required field</em></span>
								<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
								<button id="editLRUCount" type="button" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- END EDIT LRU COUNT MODAL -->
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
<script src="../controllers/LRUCountController.js"></script>
</html>