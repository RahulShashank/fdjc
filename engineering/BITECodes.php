<!DOCTYPE html>
<?php
// session_start();
session_start();

$menu ='BITECodes';
include ("../common/getAircraftCodes.php");
require_once ("../common/validateUser.php");
$approvedRoles = [$roles["all"]
];
$auth->checkPermission($hash, $approvedRoles);
$airlinesCodesArray = aircraftCodesArray();
$uid = $auth->getSessionUID($hash);
$user = $auth->getUser($uid);
$userArray = $user['email'];
$userArray = explode("@", $userArray);
$usr = explode(".", $userArray[0]);
$str = str_replace(".", " ", $userArray[0]);
$str = ucwords($str);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';

require_once ("checkEngineeringPermission.php");
$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");

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
</style>
</head>

<body>
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
				<li class="active">BITE Codes</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					BITE Codes
				</h2>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default tabs">							
							<!-- Nav tabs -->
							<ul class="nav nav-tabs" role="tablist">
								<li role="presentation" id="faultPanel" class="active"><a
									href="#faultTab" aria-controls="faultTab" role="tab"
									data-toggle="tab">Faults</a>
								</li>
								<li role="presentation" id="failurePanel"><a href="#failureTab"
									aria-controls="failureTab" role="tab" data-toggle="tab">Failures</a>
								</li>
								<li role="presentation" id="impactedServicePanel"><a href="#impactedServiceTab"
									aria-controls="impactedServiceTab" role="tab" data-toggle="tab">ImpactedServices</a></li>											
							</ul>
							<div class="panel-body tab-content">
								<div class="tab-pane active" id="faultTab">												
									<table id="faultTable" data-toggle="table" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[25, 50, 100, All]" data-page-size="25" data-striped="true" data-search="true"	data-search-align="left" data-export-data-type= "all" data-show-export="true">
			                        <thead>
			                            <th data-sortable="true">Fault Code</th>
			                            <th data-sortable="true">Description</th>
			                            <th data-sortable="true">Severity</th>
			                        </thead>
			                        <tbody>
			                          <?php
			                            if ($stmt = $dbConnection->prepare("SELECT faultCode, severity, faultDesc FROM sys_faultinfo ORDER BY length(faultCode), faultCode")) {
			                                // Execute the statement.
			                                $stmt->execute();
			                             
			                                // Get the variables from the query.
			                                $stmt->bind_result($faultCode, $severity, $faultDesc);
			                             
			                                // Fetch the data.
			                                $stmt->fetch();
			                             
			                                while ($stmt->fetch()) {
			                                  //echo "$faultCode - $severity - $faultDesc<br>";
			                                  echo "<tr>";
			                                  echo "<td>$faultCode</td>";
			                                  echo "<td>$faultDesc</td>";
			                                  echo "<td>$severity</td>";
			                                  echo "</tr>";
			                                }
			                             
			                                // Close the prepared statement.
			                                $stmt->close();
			                             
			                            } else {
			                              echo "Error while preparing query to get fault codes..."; exit;
			                            }
			                          ?>
			                        </tbody>
			                      </table>
								</div>
								<div role="tabpanel" class="tab-pane" id="failureTab">												
									<table  id="failureTable" data-toggle="table" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[25, 50, 100, All]" data-page-size="25" data-striped="true" data-search="true"	data-search-align="left" data-export-data-type= "all" data-show-export="true">
				                        <thead>
				                            <th data-sortable="true">Failure Code</th>
				                            <th data-sortable="true">Description</th>
				                            <th data-sortable="true">Severity</th>
				                            <th data-sortable="true">Corrective Action #1</th>
				                            <th data-sortable="true">Time #1</th>
				                            <th data-sortable="true">Prob. #1</th>
				                            <th data-sortable="true">Corrective Action #2</th>
				                            <th data-sortable="true">Time #2</th>
				                            <th data-sortable="true">Prob. #2</th>
				                            <th data-sortable="true">Corrective Action #3</th>
				                            <th data-sortable="true">Time #3</th>
				                            <th data-sortable="true">Prob. #3</th>
				                        </thead>
				                        <tbody>
				                          <?php
				                            if ($stmt = $dbConnection->prepare("SELECT failureCode, severity, failureDesc, caText1, caProb1, caTime1, caText2, caProb2, caTime2, caText3, caProb3, caTime3 FROM sys_failureinfo ORDER BY length(failureCode), failureCode")) {
				                                // Execute the statement.
				                                $stmt->execute();
				                             
				                                // Get the variables from the query.
				                                $stmt->bind_result($failureCode, $severity, $failureDesc, $caText1, $caProb1, $caTime1, $caText2, $caProb2, $caTime2, $caText3, $caProb3, $caTime3);
				                             
				                                // Fetch the data.
				                                $stmt->fetch();
				                             
				                                while ($stmt->fetch()) {
				                                  //echo "$faultCode - $severity - $faultDesc<br>";
				                                  echo "<tr>";
				                                  echo "<td>$failureCode</td>";
				                                  echo "<td>$failureDesc</td>";
				                                  echo "<td>$severity</td>";
				                                  echo "<td>$caText1</td>";
				                                  echo "<td>$caTime1 min</td>";
				                                  echo "<td>$caProb1%</td>";
				                                  echo "<td>$caText2</td>";
				                                  echo "<td>$caTime2 min</td>";
				                                  echo "<td>$caProb2%</td>";
				                                  echo "<td>$caText3</td>";
				                                  echo "<td>$caTime3 min</td>";
				                                  echo "<td>$caProb3%</td>";
				                                  echo "</tr>";
				                                }
				                             
				                                // Close the prepared statement.
				                                $stmt->close();
				                             
				                            } else {
				                              echo "Error while preparing query to get fault codes..."; exit;
				                            }
				                          ?>
				                        </tbody>
				                      </table>
								</div>
								<div role="tabpanel" class="tab-pane" id="impactedServiceTab">
									<table id="impactedServiceTable"  data-toggle="table" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[25, 50, 100, All]" data-page-size="25" data-striped="true" data-search="true"	data-search-align="left" data-export-data-type= "all" data-show-export="true">
				                        <thead>
				                            <th data-sortable="true">Failure Code</th>
				                            <th data-sortable="true">Failure Impact</th>
				                            <th data-sortable="true">Description</th>									                            
				                            <th data-sortable="true">Corrective Action #1</th>
				                            <th data-sortable="true">Corrective Action #2</th>
				                            <th data-sortable="true">Corrective Action #3</th>
				                            <th data-sortable="true">Severity</th>
				                        </thead>
				                        <tbody>
				                          <?php
				                            if ($stmt = $dbConnection->prepare("SELECT failureCode, failureImpact, failureDesc, caText1, caText2, caText3, severity FROM banalytics.sys_servicefailureinfo ORDER BY length(failureCode), failureCode")) {
				                                // Execute the statement.
				                                $stmt->execute();
				                             
				                                // Get the variables from the query.
				                                $stmt->bind_result($failureCode, $failureImpact, $failureDesc, $caText1, $caText2, $caText3, $severity);
				                             
				                                // Fetch the data.
				                                $stmt->fetch();
				                             
				                                while ($stmt->fetch()) {
				                                  //echo "$faultCode - $severity - $faultDesc<br>";
				                                  echo "<tr>";
				                                  echo "<td>$failureCode</td>";
				                                  echo "<td>$failureImpact</td>";
				                                  echo "<td>$failureDesc</td>";
				                                  echo "<td>$caText1</td>";
				                                  echo "<td>$caText2</td>";
				                                  echo "<td>$caText3</td>";
				                                  echo "<td>$severity</td>";
				                                  echo "</tr>";
				                                }
				                             
				                                // Close the prepared statement.
				                                $stmt->close();
				                             
				                            } else {
				                              echo "Error while preparing query to get fault codes..."; exit;
				                            }
				                          ?>
				                        </tbody>
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
    	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
		<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>		
		<script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="../js/plugins/jquery/jquery-ui.min.js"></script>
		<script type="text/javascript" src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
		<script type="text/javascript" src="../js/plugins.js"></script>
		<script type="text/javascript" src="../js/actions.js"></script>
		<script src="../js/bootstrap-table.js"></script>
		<link href="../css/bootstrap-table.css" rel="stylesheet" />
		<script src="../js/bootstrap-table-export.js"></script>
		<script src="../js/tableExport.js"></script>
		<script type="text/javascript">
			$('#impactedServiceTable').bootstrapTable({			
				exportOptions: {
					fileName: 'ImpactedServices_BITECodes'
				}});
			$('#failureTable').bootstrapTable({			
				exportOptions: {
					fileName: 'Failure_BITECodes'
				}});
			$('#faultTable').bootstrapTable({			
				exportOptions: {
					fileName: 'Fault_BITECodes'
				}});			
		</script>
		
</body>
</html>
