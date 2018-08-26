<?php
require_once ("../common/validateUser.php");

$uid = $auth->getSessionUID ( $hash );
$user = $auth->getUser ( $uid );
$userArray = $user ['email'];
$userRole = $user['role'];
$userArray = explode ( "@", $userArray );
$usr = explode ( ".", $userArray [0] );
$str = str_replace ( ".", " ", $userArray [0] );
$str = ucwords ( $str );
?>
<script	type="text/javascript" src="../js/alertify/alertify.js"></script>
<script	type="text/javascript" src="../js/alertify/alertify.min.js"></script>
<link rel="stylesheet" href="../css/alertify/alertify.min.css" />
<!-- Default theme -->
<link rel="stylesheet" href="../css/alertify/default.min.css" />
<!-- Semantic UI theme -->
<link rel="stylesheet" href="../css/alertify/semantic.min.css" />

<script	src="../controllers/sessionExpires.js"></script>

<style>
.x-navigation li.submenuActive > a {
	background: #67a2d4 !important;
    color: #fff !important;
}

.x-navigation li.submenuActive > a .fa, .x-navigation li.submenuActive > a .glyphicon {
	color: #fff !important;
}
</style>
<!-- START PAGE SIDEBAR -->
<div id="sideNavBar" class="page-sidebar page-sidebar-fixed scroll">
	<!-- START X-NAVIGATION -->
	<ul class="x-navigation">
		<li class="xn-logo"><a href="#"
			style="font-size: 18px; padding-top: 3px;"><img
				src="../img/thales.png"
				style="height: 24px; padding-bottom: 5px; width: 120px;" /><br />BITE
				Analytics</a> <a href="#" class="x-navigation-control"></a></li>
		<li class="xn-profile"><a href="#" class="profile-mini"><img
				src="../img/Thales_A.png" alt="BITE Analytics" /> </a>
			<div class="profile">
				<div class="profile-image">
					<div class="icon-preview" style="height: 60px; line-height: 1px;">
						<i class="fa fa-user"></i>
					</div>
				</div>
				<div class="profile-data">
					<div class="profile-data-name">
					<?php echo $str;?>
					</div>

					<div class="dropdown">
						<a class="dropdown-toggle" href="#" data-toggle="dropdown"
							id="profileDropdown" style="text-decoration: none;"><div
								style="text-align: center">
								<?php echo $user['role'];?>
								<b class="caret"> </b>
							</div> </a>
						<ul class="dropdown-menu animated flipInX m-t-xs" id="profilemenu"
							style="display: none;">
							<li><a href="profile.php"><span class="fa fa-user"></span>Profile</a>
							</li>
							<li><a href="ChangeUserPassword.php"><span class="fa fa-cog"></span>Change
									Password</a></li>
							<li><a href="#" class="mb-control" data-box="#mb-signout"><span
									class="fa fa-sign-out"></span>Logout</a></li>
						</ul>
					</div>
				</div>
			</div></li>
		<!-- <li class="xn-title">Navigation</li> -->
		<li class="xn-openable <?php if($menu =='UploadnDownloadOffload' || $menu =='SPNLPartNumberMatching' || $menu =='ConnectivityUpload' || $menu == 'BITE_UPLOAD_MATRIX'){echo 'active';}?>">
			<a href="#" title="Uploads
			BITE Upload
			BITE Upload Matrix
			<?php
    		if($userRole !== 'Customer') {
    		?>
SPNL Upload
   Connectivity Upload
    		<?php
    		}
	        ?>
			"><span class="fa fa-upload"></span><span class="xn-text">Uploads</span></a>                        
			<ul>
				<li	<?php if($menu =='UploadnDownloadOffload'){echo 'class="submenuActive"';}?>><a
						href="UploadnDownloadOffload.php"><span class="fa fa-cloud"></span>BITE Upload</a></li>                           
				<li	<?php if($menu =='BITE_UPLOAD_MATRIX'){echo 'class="submenuActive"';}?>><a
						href="biteUploadMatrix.php"><span class="fa fa-bar-chart-o"></span>BITE Upload Matrix</a></li>                           
		<?php
    		if($userRole !== 'Customer') {
		?>
				<li	<?php if($menu =='SPNLPartNumberMatching'){echo 'class="submenuActive"';}?>><a
						href="SPNLPartNumber.php"><span class="fa fa-puzzle-piece"></span>SPNL Upload</a></li>
				<li	<?php if($menu =='ConnectivityUpload'){echo 'class="submenuActive"';}?>><a
						href="ConnectivityUpload.php"><span class="fa fa-rss"></span>Connectivity Upload</a></li>
		<?php 
            }
		?>
            </ul>
		</li>
		<?php
			if($userRole !== 'Customer') {
		?>
			<li	<?php if($menu =='Airlines'){echo 'class="active"';}?> title="Airline Dashboard">
				<a href="airlines.php"><span class="fa fa-globe fa-fw"></span><span class="xn-text">Airline Dashboard</span></a>
			</li>
		<?php 
	        }
		?>
		<li <?php if($menu =='DASHBOARD'){echo 'class="active"';}?> title="Aircraft Dashboard">
			<a href="AirlineDashboard.php?dashboardVisited=false"><span class="fa fa-th-large fa-fw"></span><span class="xn-text">Aircraft Dashboard</span></a>
		</li>
		<li	<?php if($menu =='OFFLOADS_COVERAGE'){echo 'class="active"';}?> title="Offload Coverage">
			<a href="offloadsCoverage.php"><span class="fa fa-globe fa-fw"></span><span class="xn-text">Offloads Coverage</span></a>
		</li>
		<li <?php if($menu =='timeline'){echo 'class="active"';}?> title="Timeline">
			<a href="AircraftTimeline.php?aircraftVisited=false"><span class="fa fa-signal fa-fw"></span><span class="xn-text">Timeline</span></a>
		</li>				
		<li <?php if($menu =='lopa'){echo 'class="active"';}?> title="LOPA"><a
			href="lopa.php?lopaVisited=false"><span class="fa fa-th"></span><span class="xn-text">LOPA</span></a>
		</li>
	<?php
		if($userRole !== 'Customer') {
	?>
		<li <?php if($menu =='flightscore'){echo 'class="active"';}?> title="Flight Score"><a
			href="FlightScore.php?flightScoreVisited=false"><span class="fa fa-bar-chart-o"></span><span class="xn-text">Flight Score</span></a></li>		
	<?php 
        }
	?>
		<li <?php if($menu =='activeMaintenance'){echo 'class="active"';}?> title="Active Maintenance"><a
			href="ActiveMaintenance.php"><span class="fa fa-wrench fa-fw"></span><span class="xn-text">Active Maintenance</span></a></li>

		<li <?php if($menu =='RESETS_REPORT'){echo 'class="active"';}?> title="Resets Report"><a
			href="ResetsReport.php"><span class="fa fa-power-off fa-fw"></span><span class="xn-text">Resets Report</span></a></li>
				
		<li	<?php if($menu =='BITECodes'){echo 'class="active"';}?> title="BITE Codes"><a
			href="BITECodes.php"><span class="fa fa-cubes fa-fw"></span><span class="xn-text">BITE Codes</span></a></li>
		
		<li	<?php if($menu =='MaintenanceActivities'){echo 'class="active"';}?> title="Maintenance Activities"><a
			href="MaintenanceActivities.php?maintenanceActivitiesVisited=false"><span class="fa fa-wrench fa-fw"></span><span class="xn-text">Maintenance Activities</span></a></li>
		<li	<?php if($menu =='AircraftReport'){echo 'class="active"';}?> title="Report"><a
			href="AircraftReport.php"><span class="fa fa-file-text-o fa-fw"></span><span class="xn-text">Report</span></a></li>
				
	<?php
		if($userRole !== 'Customer') {
	?>
		<li	<?php if($menu =='ConnectivityStatus'){echo 'class="active"';}?> title="Connectivity Status">
			<a href="ConnectivityStatus.php"><span class="fa fa-rss-square"></span><span class="xn-text">Connectivity Status</span></a>
		</li>
	<?php 
        }
	?>
	
	<?php
		if($userRole !== 'Customer') {
	?>
		<li	<?php if($menu =='ConnectivityLogs'){echo 'class="active"';}?> title="Connectivity Logs">
			<a href="ConnectivityLogs.php"><span class="fa fa-list-alt"></span><span class="xn-text">Connectivity Logs</span></a>
		</li>
	<?php 
        }
	?>
				
		<li class="xn-openable <?php if($menu =='FLEET_STATUS' || $menu =='HARDWARE_REV_MOD' || $menu =='FleetConfiguration'){echo 'active';}?>" title="Config Management\ntest">
			<a href="#" title="Config Management
			Fleet Status
			Hardware Rev & Mod
			Fleet Configuration"><span class="fa fa-cogs"></span><span class="xn-text">Config Management</span></a>                        
			<ul>
        		<li	<?php if($menu =='FLEET_STATUS'){echo 'class="submenuActive"';}?>>
        			<a href="fleetStatus.php"><span class="fa fa-tasks fa-fw"></span>Fleet Status</a>
        		</li>
        		<li	<?php if($menu =='HARDWARE_REV_MOD'){echo 'class="submenuActive"';}?>>
        			<a href="HardwareRevisionsMods.php"><span class="fa fa-tasks fa-fw"></span>Hardware Rev & Mod</a>
        		</li>
        		<li	<?php if($menu =='FleetConfiguration'){echo 'class="submenuActive"';}?> title="Fleet Configuration">
        			<a href="FleetConfiguration.php"><span class="fa fa-link"></span><span class="xn-text">Fleet Configuration</span></a>
        		</li>
            </ul>
		</li>
		<li	<?php if($menu =='MAINTENANCE_ACTION'){echo 'class="active"';}?> title="Maintenance Action">
			<a href="maintenanceAction.php"><span class="fa fa-wrench"></span><span class="xn-text">Maintenance Action</span></a>
		</li>
		<li></li>
	</ul>
	<!-- END X-NAVIGATION -->
</div>
<!-- END PAGE SIDEBAR -->

<script>
$("#profileDropdown").on("click", function() {
	var el = document.getElementById('profilemenu');
	if(el.style.display=='block'){
		el.style.display = 'none';
	}else{
		el.style.display = 'block';
	}
});
$(document).click(function(){                    		  
	  var el = document.getElementById('profilemenu');
		if(el.style.display=='block'){
			el.style.display = 'none';
    	}
	});

</script>
