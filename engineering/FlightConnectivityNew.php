<div class="row">
	<div class="col-md-12">	
	<br/>
		<?php
				if($displayNoDataAlert) {
					echo "<div class=\"alert alert-warning\" role=\"alert\" style=\"height: 45px;\">
						  <span class=\"glyphicon glyphicon-exclamation-sign\" aria-hidden=\"true\"></span>
						  <span class=\"sr-only\">Error:</span>
						  No connectivity data has been uploaded for this flight.
						</div>
						<br>";
				}
		?>
	</div>
	<div class="row placeholders">
				<div class="col-md-6" id="WifiDataChart" style="height: 150px" >					
					<h4><strong>WIFI Availability</strong></h4>					
					<span id="contentDivWifi"> [Computed Value : 
							
									<?php 										
										if($computedPercentage['computedWifiPercentage'] !== NULL ){
											echo $computedPercentage['computedWifiPercentage'] . "%";
										}else{
											echo "N/A";
										}
									?> / Overridden Value : 
									<?php 
										if($computedPercentage['overriddenWifiPercentage'] !== NULL){
											echo $computedPercentage['overriddenWifiPercentage'] . "%";
										}else{
											echo "N/A";
										}
									?>]</span>
					<!--<img src="../img/edit.png" title="Edit WIFI Availability %" onclick='return updateAvailabilityData(<?php echo "\"WIFI\""; ?>);'>
					<br><br>-->
					<div class="ct-chart"></div>
					<div id="NoDataWifiAvailable">
						<br/>
						<img src="../img/ajaxLoading.gif"> Loading Wifi Availability...
					</div>					
				</div>				
				<div class="col-md-6"  id="OmtsDataChart" style="height: 150px">
					<h4><strong>OMTS Availability</strong></h4>					
					<span  id="contentDivOmts">[Computed Value : 
									<?php 										
										if($computedPercentage['computedOmtsPercentage'] !== NULL){
											echo $computedPercentage['computedOmtsPercentage'] . "%";
										}else{
											echo "N/A";
										}
									?> / Overridden Value : 
									<?php 
										if($computedPercentage['overriddenOmtsPercentage'] !== NULL){
											echo $computedPercentage['overriddenOmtsPercentage'] . "%";
										}else{
											echo "N/A";
										} 
									?>]</span>
					<!--<img src="../img/edit.png" title="Edit OMTS Availability %" onclick='return updateAvailabilityData(<?php echo "\"OMTS\""; ?>);'>
					<br><br>-->
					<div class="ct-chartOmtsOff"></div>
					<div id="NoDataOmtsAvailable">
						<br/>
						<img src="../img/ajaxLoading.gif"> Loading Omts Availability...
					</div>				
				</div>
			</div>
			<div class="row placeholders" id="rootCauseHolder">
				<div class="col-md-6">
					<div class="panel panel-default">
					  <div class="panel-body" style="background:#FCFCFC">						
						<h4 style="color:grey"><strong>Wifi Root Causes</strong></h4>
						<br>
						<div>
							<?php 
								$i = 0;
								if(count($rootCauseStringWifiUnique) > 1){
									foreach($rootCauseStringWifiUnique as $WifiRc){									
										if($WifiRc != ""){
											if($i > 0) {
												echo " / ";
											}
											echo $WifiRc;
											$i++;
										}
									}
								}elseif(count($rootCauseStringWifiUnique) > 0){									
									foreach($rootCauseStringWifiUnique as $WifiRc){
										if($WifiRc == ""){
											echo '<i>No Failure</i>';
										}
									}
								}else{
									echo '<i>No Failure</i>';
								}
							?>
						</div>                    
					  </div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="panel panel-default">
					  <div class="panel-body" style="background:#FCFCFC">
						<h4 style="color:grey"><strong>OMTS Root Causes</strong></h4>
						<br>						
						<div>
							<?php 
								$i = 0;
								if(count($rootCauseStringOmtsUnique) > 1){
									foreach($rootCauseStringOmtsUnique as $OmtsRc){
										if($OmtsRc != ""){
											if($i > 0) {
												echo " / ";
											}
											echo $OmtsRc;
											$i++;
										}									
									}
								}elseif(count($rootCauseStringOmtsUnique) > 0){									
									foreach($rootCauseStringOmtsUnique as $OmtsRc){
										if($OmtsRc == ""){
											echo '<i>No Failure</i>';
										}
									}
								}else{
									echo '<i>No Failure</i>';
								}
							?>
						</div>                    
					  </div>
					</div>
				</div>
			</div>
			<div>
				<div id="flightProfile"></div>
			</div>
			<br>
			<br>
			<?php 
				if($flightLegs!="") {
					echo "<div id=\"connectivityTimeline\"></div>";
					echo "<div id=\"loadingConnectivityTimeline\">";
					echo "	<img src=\"../img/ajaxLoading.gif\"> Loading Timeline...";
					echo " </div>";
					
				}
			?>
</div>
