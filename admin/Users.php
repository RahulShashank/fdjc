<?php
session_start ();
$menu = 'USERS';
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
<body ng-controller="UserController">
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
				<li>Admin</li>
				<li class="active">Users</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Users</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div id="loadingDiv" style="text-align: center">
									<img src="../img/loadingicon1.gif" style="height: 30px;"><br />Loading
									data...
								</div>
								<div id="toolbar">
                            		<button id="remove" class="btn btn-danger" disabled>
                            			Delete Selected
                            		</button>
                            		<button id="add" class="btn btn-success" data-toggle="modal" data-target="#addUserModal">
                            			Add User
                            		</button>
                            	</div>
                            	<div id="successAlertDiv" class="alert alert-success text-center"></div>
								<div class="table-editable">
									<table id="table" data-toolbar="#toolbar"></table>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog"
					aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="myModalLabel">Add User</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="addUserAlertDiv"></div>
								<form>
									<div class="form-group">
										<label for="email" class="control-label">Email<span
											style="color: red;"> *</span></label> <input type="email"
											class="form-control" id="email">
									</div>
									<div class="form-group">
										<label for="password" class="control-label">Password<span
											style="color: red;"> *</span></label> <input type="password"
											class="form-control" id="password">
									</div>
									<div class="form-group">
										<label for="confirmpassword" class="control-label">Confirm
											Password<span style="color: red;"> *</span>
										</label> <input type="password" class="form-control"
											id="confirmpassword">
									</div>
									<label class="control-label">Select Role and Airline(s)<span
										style="color: red;"> *</span></label>
									<div class="form-group">
										<select id="role" class="selectpicker show-tick" name="role" title="Select Role" >
											<!-- <option value="None" disabled selected="selected">Select Role</option> -->
											<option value="Admin">Admin</option>
											<option value="Manager">Manager</option>
											<option value="Engineer">Engineer</option>
											<option value="Customer">Customer</option>
										</select>
										<select id="airline" name="airline" class="selectpicker show-tick" data-size="6" data-actions-box="true" 
										data-live-search="true" title="All" data-selected-text-format="count > 3" data-dropup-auto="false" multiple>
                        				<?php
                                            foreach ($airlinesCodesArray as $airline) {
                                                echo "<option value=\"" . $airline['id'] . "\">" . $airline['name'] . " (" . $airline['acronym'] . ")" . "</option>";
                                            }
                                        ?>
                			 			</select>
									</div>
								</form>
							</div>
							<div class="modal-footer">
								<span class="text-muted" style="float: left"><em><span
										style="color: red;">*</span>Indicates required field</em></span>
								<button type="button" class="btn btn-default"
									data-dismiss="modal">Cancel</button>
								<button id="addUser" type="button" class="btn btn-primary">Add
									User</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Reset password modal -->
				<div class="modal fade" id="resetPasswordModal" tabindex="1" role="dialog"
					aria-labelledby="myModal">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="passwordModal">Reset Password</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="resetPasswordAlertDiv"></div>
								<form>
									<input type="hidden" name="uid" id="uid">
									<div class="form-group">
										<label for="password" class="control-label">New Password<span
											style="color: red;"> *</span></label> <input type="password"
											class="form-control" id="password">
									</div>
									<div class="form-group">
										<label for="confirmpassword" class="control-label">Confirm
											Password<span style="color: red;"> *</span>
										</label> <input type="password" class="form-control"
											id="confirmpassword">
									</div>
								</form>
							</div>
							<div class="modal-footer">
								<span class="text-muted" style="float: left"><em><span
										style="color: red;">*</span>Indicates required field</em></span>
								<button type="button" class="btn btn-default"
									data-dismiss="modal">Cancel</button>
								<button id="resetPass" type="button" class="btn btn-primary">Reset
									Password</button>
							</div>
						</div>
					</div>
				</div>
				<!-- EOF Reset password modal -->

				<!-- Edit User modal -->
				<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog"
					aria-labelledby="myModalLabel1">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
						<div class="modal-content"  style="border-radius: 5px;border-width:0px;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"
									aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
								<h4 class="modal-title" id="myModalLabel1">Edit User</h4>
							</div>
							<div class="modal-body">
								<div class="alert alert-danger" role="alert" id="editUserAlertDiv"></div>
								<form>
									<input type="hidden" name="index" id="index"> <input
										type="hidden" name="uid" id="uid">
									<div class="form-group">
										<label for="email" class="control-label">Email: </label> <input
											type="text" class="form-control" id="email">
									</div>
									<label class="control-label">Select Role and Airline(s)</label>
									<div class="form-group">
										<select id="editRole" class="selectpicker show-tick" name="editRole" title="Select Role" >
											<option value="Admin">Admin</option>
											<option value="Manager">Manager</option>
											<option value="Engineer">Engineer</option>
											<option value="Customer">Customer</option>
										</select>
										<select id="editAirline" name="editAirline" class="selectpicker show-tick" data-size="6" data-actions-box="true" 
										data-live-search="true" title="All" data-selected-text-format="count > 3" data-dropup-auto="false" multiple>
                            				 <?php
                                				 foreach ($airlinesCodesArray as $airline) {
                                				     echo "<option value=\"" . $airline['id'] . "\">" . $airline['name'] . " (" . $airline['acronym'] . ")" . "</option>";
                                				 }
                            				 ?>
                            			 </select>
									</div>
									<div class="form-group">
										<label for="isactive" class="control-label">Account Status: </label>
										<!-- <select id="isactive" class="form-control" name="account"> -->
										<select id="isactive" class="form-control selectpicker show-tick" name="account">
											<option value=1>Active</option>
											<option value=0>Inactive</option>
										</select>
									</div>
								</form>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default"
									data-dismiss="modal">Cancel</button>
								<button id="submit" type="button" class="btn btn-primary">Save
									changes</button>
							</div>
						</div>
					</div>
				</div>
				<!-- EOF Edit User modal -->

			</div>
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	<!-- MESSAGE BOX-->
	<!-- <div class="message-box animated fadeIn" id="mb-signout"> -->
		<?php include("../logout.php"); ?>
	<!-- </div> -->
	<!-- END MESSAGE BOX-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
</body>
<script src="../controllers/UserController.js"></script>
</html>