<div>
	<div class="form-group" style="width:255px;">
		<select id="serviceData" class="form-control selectpicker show-tick"
			onchange="showServiceData();" value="selectedServiceData">			
			<option value="PassengerServices">PassengerServices</option>
			<option value="CabinEvents">CabinEvents</option>
			<option value="DigitalServerStatus">DigitalServerStatus</option>
			<option value="SystemEvents">SystemEvents</option>
		</select>
	</div>

	<!-- Tab panes -->
	<div>
		<div class="panel panel-default">																							
			<div class="panel-body">
				<div id="serviceLoading" class="text-center">
					<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
				</div>
				<div id="PassengerServices">			
					<?php include("PassengerServices.php");?>
				</div>
				<div id="CabinEvents">
					<?php include("CabinEvents.php");?>
				</div>
				<div id="DigitalServerStatus">			
					<?php include("DigitalServerStatus.php");?>
				</div>
				<div id="SystemEvents">			
					<?php include("SystemEvents.php");?>
				</div>
			</div>
		</div>
	</div>
</div>
