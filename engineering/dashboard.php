<!DOCTYPE html>
<?php
	/*session_start();
	require_once("../common/validateUser.php");
	$approvedRoles = [$roles["admin"]];
	$auth->checkPermission($hash, $approvedRoles);	*/
	require_once "../database/connecti_database.php";
	require_once "../common/functions.php";
	require_once "../common/checkPermission.php";
	require_once '../libs/PHPExcel/PHPExcel.php';
	require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';

	require_once("checkEngineeringPermission.php");
	$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
	$airlineIds = rtrim($airlineIds, ",");
	
	$query = "SELECT a.id, a.acronym FROM airlines a WHERE id IN ($airlineIds) ORDER BY a.acronym";

	$result = mysqli_query ($dbConnection, $query);
	$airlines = array();
	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$airlines[] = $row['acronym'];		
		}
	}

	$query = "SELECT a.id FROM airlines a WHERE id IN ($airlineIdList) ORDER BY a.acronym limit 1";

	$result = mysqli_query ($dbConnection, $query);
	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$airlineId = $row['id'];		
		}
	}
	
	// Existing Flight Phases

	$flightPhases = array("1:Pre-flight ground",
							"2:Taxi out", 
							"3:Take off", 
							"4:Climb", 
							"5:Cruise", 
							"6:Descent", 
							"7:Landed", 
							"8:Taxi in", 
							"9:Post-flight");

	// Get all Fault Codes.
	$query = "SELECT a.idFaultInfo, a.faultCode, a.faultDesc FROM sys_faultinfo a";
	$result = mysqli_query($dbConnection, $query);
	$faultInfos = array();
	$faultInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			array_push($faultInfos, $row); 
			array_push($faultInfosForAutoSuggest, $row['faultCode'] . ':' . $row['faultDesc']);
		}
	}

	// Get all Failure Codes.
	$query = "SELECT a.idFailureInfo, a.failureCode, a.failureDesc FROM sys_failureinfo a";
	$result = mysqli_query($dbConnection, $query);
	$failureInfos = array();
	$failureInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			array_push($failureInfos, $row); 
			array_push($failureInfosForAutoSuggest, $row['failureCode'] . ':' . $row['failureDesc']);
		}
	}
	
	// Get all Failure Codes.
	$query = "SELECT a.idFailureInfo, a.failureCode, a.failureDesc FROM sys_servicefailureinfo a";
	$result = mysqli_query($dbConnection, $query);
	$impactedServicesInfos = array();
	$impactedServicesInfosForAutoSuggest = array();
	if($result){
		while($row = mysqli_fetch_array($result)){
			array_push($impactedServicesInfos, $row); 
			array_push($impactedServicesInfosForAutoSuggest, $row['failureCode'] . ':' . $row['failureDesc']);
		}
	}

?>
<html lang="en" data-ng-app="myApp">
    <head>        
        <!-- META SECTION -->
		<link rel="shortcut icon" href="../img/globe-icon.ico">
        <title>BITE Analytics</title>            
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
       
       <!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->
        
        <!-- CSS INCLUDE -->        
        <link rel="stylesheet" type="text/css" id="theme" href="../css/theme-blue.css"/>
		<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css"/>
        <!-- EOF CSS INCLUDE -->  
		<script src="../js/jquery-1.11.2.js"></script>
		
		<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
		<script src="../js/jquery.datetimepicker.js"></script>
		<script src="../js/jquery.blockUI.js"></script>
		
		
		<script src="../js/angular.js"></script>
		<script src="../js/angular-route.js"></script>
		<script src="../js/angular-cookies.js"></script>
		<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
		<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
		<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		
		<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
		<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
	
		<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
		<script src="../js/bootstrap-multiselect.js"></script>
		
		<script src="../js/bootstrap-table.js"></script>
		<link href="../css/bootstrap-table.css" rel="stylesheet" />
		
		<script src="../js/Chart.HeatMap.S.js"></script>
		
		
    </head>
    <body ng-controller="lopaDataController">
        <!-- START PAGE CONTAINER -->
        <div class="page-container">
            
            <!-- START PAGE SIDEBAR -->
            <div class="page-sidebar">
                <!-- START X-NAVIGATION -->
                <ul class="x-navigation">
                    <li class="xn-logo">
                        <a href="index.html">BITE Analytics</a>
                        <a href="#" class="x-navigation-control"></a>
                    </li>
                    <li class="xn-profile">
                        <a href="#" class="profile-mini">
                            <img src="../img/globe-icon.png" alt="BITE Analytics"/>
                        </a>
                                                                                               
                    </li>
					 
                    <li class="xn-title">Navigation</li> 
					<li>
                        <a href="lopa.php"><span class="fa fa-th"></span> <span class="xn-text">LOPA</span></a>
                    </li>
                    <li>
                        <a href="index.html"><span class="fa fa-desktop"></span> <span class="xn-text">Dashboard</span></a>
                    </li>           
                </ul>
                <!-- END X-NAVIGATION -->
            </div>
            <!-- END PAGE SIDEBAR -->
            
            <!-- PAGE CONTENT -->
            <div class="page-content">
                
                <!-- START X-NAVIGATION VERTICAL -->
                <ul class="x-navigation x-navigation-horizontal x-navigation-panel">
                    <!-- TOGGLE NAVIGATION -->
                    <li class="xn-icon-button">
                        <a href="#" class="x-navigation-minimize"><span class="fa fa-dedent"></span></a>
                    </li>
                    <!-- END TOGGLE NAVIGATION -->
                    <!-- SEARCH -->
                    <li class="xn-search">
                        <form role="form">
                            <input type="text" name="search" placeholder="Search..."/>
                        </form>
                    </li>   
                    <!-- END SEARCH -->
                    <!-- SIGN OUT -->
                    <li class="xn-icon-button pull-right">
                        <a href="#" class="mb-control" data-box="#mb-signout"><span class="fa fa-sign-out"></span></a>                        
                    </li> 
                    <!-- END SIGN OUT -->
                    
                    
                </ul>
                <!-- END X-NAVIGATION VERTICAL -->                     
                
                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="#">Home</a></li>
                    <li class="active">LOPA</li>
                </ul>
                <!-- END BREADCRUMB -->                
                
                <!-- PAGE TITLE -->
                <div class="page-title">                    
                    <h2><span class="fa fa-arrow-circle-o-left"></span> LOPA</h2>
                </div>
                <!-- END PAGE TITLE -->                
                
                <!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">                
                
                    <div class="row">
						<div class="panel panel-default">
							<div class="panel-body">
								<h5>No data available </h5>
								
							</div>
						</div>
                    </div>
					
                </div>
                <!-- PAGE CONTENT WRAPPER -->                                
            </div>            
            <!-- END PAGE CONTENT -->
        </div>
        <!-- END PAGE CONTAINER -->
        
        <!-- MESSAGE BOX-->
        <div class="message-box animated fadeIn" data-sound="alert" id="mb-signout">
            <div class="mb-container">
                <div class="mb-middle">
                    <div class="mb-title"><span class="fa fa-sign-out"></span> Log <strong>Out</strong> ?</div>
                    <div class="mb-content">
                        <p>Are you sure you want to log out?</p>                    
                        <p>Press No if youwant to continue work. Press Yes to logout current user.</p>
                    </div>
                    <div class="mb-footer">
                        <div class="pull-right">
                            <a href="../common/logout_user.php" class="btn btn-success btn-lg">Yes</a>
                            <button class="btn btn-default btn-lg mb-control-close">No</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END MESSAGE BOX-->

        <!-- START PRELOADS -->
        <audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
        <audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
        <!-- END PRELOADS -->                

    <!-- START SCRIPTS -->
        <!-- START PLUGINS 
        <script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../js/plugins/jquery/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/plugins/bootstrap/bootstrap.min.js"></script>  -->      
        <!-- END PLUGINS -->

        <!-- START THIS PAGE PLUGINS-->        

        <script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js'></script>
        <script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js'></script>
        <script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-europe-mill-en.js'></script>
        <script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-us-aea-en.js'></script>

        <script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
        <script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>       
        <!-- END THIS PAGE PLUGINS-->        

        <!-- START TEMPLATE -->
       <!-- <script type="text/javascript" src="../js/settings.js"></script>-->
        
        <script type="text/javascript" src="../js/plugins.js"></script>        
        <script type="text/javascript" src="../js/actions.js"></script>
        <!-- END TEMPLATE -->
		
		
		
    <!-- END SCRIPTS -->          
        
    </body>
</html>






