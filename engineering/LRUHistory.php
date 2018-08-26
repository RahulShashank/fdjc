<div class="row">
		<div class="col-md-12">						
			
				<div id="dropdowndiv" class="form-group" style="width:255px;margin-bottom: 10px;margin-top: 10px;">
						<select id="lruHistoryData" class="form-control selectpicker show-tick"	value="selectedData">
							<option value="serialNumber">Serial Number</option>
							<option value="hwPartNumber">Hostname</option>
						</select>
				</div>
			
		</div>
</div>
<div id="serialNumberDiv">
	<div  class="row">
		<div class="col-md-12">						
			<div class="panel panel-default">							
					<div id="ctrldivlru" class="panel-body" >
						<input type="hidden" id="airlineIdslru" ng-model="airlineIds" ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
						<div class="row" style="padding-bottom: 12px;">
							<div class="col-md-2 from-group">
								<label for="airlinelru">Airline</label>
							<div>
								<select id="airlinelru" class="selectpicker show-tick airline"	data-live-search="true" data-width="100%" style="max-width: 150px;"></select>
							</div>
						</div>
						
						<div class="col-md-2 from-group">
							<label for="serialNumberlru">Serial Number </label>
							<div>
								<input id="serialNumberlru" class="form-control" ></input>
							</div>
							<label id="errormsgForSerial" for="serialNumberlru" style="color:red;margin-top: 4px;display: none;">Please Enter Serial Number </label>
						</div>
						<div class="col-md-2 from-group">
							<label for="startDateTimePickerlru">From</label>
							<div>
								<input class="form-control dateChange" id="startDateTimePickerlru" type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
							</div>
						</div>
						<div class="col-md-2 from-group">
							<label for="endDateTimePickerlru">To</label>
							<div>
								<input class="form-control dateChange" id="endDateTimePickerlru" type="text" name="endDateTimePicker" ng-model="endDate"  style="width: 100%;" readonly='true'>
							</div>
						</div>		
    					<div class="col-md-4">
    						<label for="buttons">&nbsp;&nbsp;</label>
    						<div>
    							<button id="search" class="btn btn-primary">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="resetlru" type="button" class="btn btn-reset" data-ng-click="resetActiveMaintenance()">Reset</button>
    						</div>
    					</div>
					</div>
<!-- 				<div class="row"> -->
<!-- 					<div class="col-md-12 text-left"> -->
<!-- 						<button id="search" class="btn btn-primary"	>Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="resetlru" type="button" class="btn btn-reset" ng-click="resetActiveMaintenance()">Reset</button> -->
<!-- 					</div> -->
<!-- 				</div> -->
			</div>
			</div>
		</div>
	</div>
	<div id="ErrorDiv" class="row">
		<div class="col-md-12">						
			<div class="panel panel-default">							
					<div class="panel-body text-center" >
						<label class="noData-label"> No data available for the selected duration or selected filters </label>
					</div>
			</div>
		</div>
	</div>
	
	<div id="dataDiv" >
		<div id="loadingHistoryTimeline" class="row col-md-12"><div class="panel panel-default panel-body" align="center"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div></div>
	
		<div id="dataHistoryDiv">
			<div  class="row">
				<div class="col-md-12">						
					<div class="panel panel-default">							
							<div class="panel-body" >
								<div id="tableInfo" class="table-responsive">
									<table id="serialTable" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="10" data-search="true" data-search-align="left" data-striped="true"  data-show-export="true">
										<thead>
											<tr> 
												<th data-field="hostName" data-sortable="true">Hostname</th>
												<th data-field="hwPartNumber" data-sortable="true">HW PartNumber</th>
												<th data-field="serialNumber" data-sortable="true">SerialNumber</th>
												<th data-field="tailsign" data-sortable="true">Tailsign</th>
												<th data-field="idFlightLeg"  data-sortable="true" data-formatter="formatFlightLeg">FlightLeg</th>
												<th data-field="lastUpdate" data-sortable="true">LastUpdate</th>
												<th data-field="macAddress" data-sortable="true">Mac Address</th>
												<th data-field="ipAddress" data-sortable="true">IP Address</th>
											</tr> 
										</thead>
									</table>
								</div>	
							</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
									
<div id="partNumberDiv">
	<div  class="row">
		<div class="col-md-12">						
			<div class="panel panel-default">							
					<div id="ctrldivhw" class="panel-body" >
						<input type="hidden" id="airlineIdslru" ng-model="airlineIds" ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
						<div class="row" style="padding-bottom: 12px;">
							<div class="col-md-2 from-group">
								<label for="airlinehw">Airline</label>
							<div>
								<select id="airlinehw" class="selectpicker show-tick"	data-live-search="true" data-width="100%" style="max-width: 150px;"></select>
							</div>
						</div>
						<div class="col-md-2 from-group">
							<label for="platformhw">Platform</label>
							<div>
								<select id="platformhw" class="selectpicker show-tick" data-width="100%" data-max-width="120px;" multiple title="All"></select>
							</div>
						</div>
						<div class="col-md-2 from-group">
							<label for="configTypehw">Configuration</label>
							<div>
								<select id="configTypehw" class="selectpicker show-tick" data-width="100%" data-max-width="120px;" multiple title="All"></select>
								
							</div>
						</div>					
						<div class="col-md-2 from-group" >
							<label for="tailsignhw">Tailsign</label>
							<div>
								<select id="tailsignhw" class="selectpicker show-tick" data-width="100%" data-live-search="true" data-selected-text-format="count > 3" multiple title="All"></select>
							</div>
						</div>
						<div class="col-md-2 from-group">
							<label for="hostname">Hostname </label>
							<div>
								<input id="hostname" class="form-control" ></input>
							</div>
							<label id="errormsgForhostname" for="hostname" style="color:red;margin-top: 4px;display: none;">Please Enter Hostname </label>
						</div>	
						<div class="col-md-1 from-group">
							<label for="startDateTimePickerhw">From</label>
							<div>
								<input class="form-control dateChange" id="startDateTimePickerhw" type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
							</div>
						</div>
						<div class="col-md-1 from-group">
							<label for="endDateTimePickerhw">To</label>
							<div>
								<input class="form-control dateChange" id="endDateTimePickerhw" type="text" name="endDateTimePicker" ng-model="endDate"  style="width: 100%;" readonly='true'>
							</div>
						</div>	
					</div>
					<div class="row">
						<div class="col-md-12 text-left">
							<button id="searchSerial" class="btn btn-primary">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="resetsrc" type="button" class="btn btn-reset" >Reset</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
		<div id="ErrorDivhw" class="row">
		<div class="col-md-12">						
			<div class="panel panel-default">							
					<div class="panel-body text-center" >
						<label class="noData-label"> No data available for the selected duration or selected filters</label>
					</div>
			</div>
		</div>
	</div>
	
	<div id="dataDivhw" >
		<div id="loadingHistoryTimelinehw" class="row col-md-12"><div class="panel panel-default panel-body" align="center"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div></div>
	
		<div id="dataHistoryDivHW" class="row">
			<div class="col-md-12">						
				<div class="panel panel-default">							
						<div class="panel-body" >
							<div id="tableInfoData" class="table-responsive">
								<table id="hwTable" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="10" data-search="true" data-search-align="left" data-striped="true"  data-show-export="true">
									<thead>
										<tr> 
											<th data-field="hostName" data-sortable="true">Hostname</th>
											<th data-field="hwPartNumber" data-sortable="true">HW PartNumber</th>
											<th data-field="serialNumber" data-sortable="true">SerialNumber</th>
											<th data-field="tailsign" data-sortable="true">Tailsign</th>
											<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">FlightLeg</th>
											<th data-field="lastUpdate" data-sortable="true">LastUpdate</th>
											<th data-field="macAddress" data-sortable="true">Mac Address</th>
											<th data-field="ipAddress" data-sortable="true">IP Address</th>
										</tr> 
									</thead>
								</table>
							</div>
						</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function formatFlightLeg(value, row, index, field) {
		if(value) {
			//value = value.replace(/,/g, ", ");
			var method = "return analyzeFlightLegs1(" + value + "," + row['aircraftId'] + ")";
	
			return "<a onclick='"+method+"'>" + value + "</a>";
		} else {
			return '-';
		}
	}
	
	function analyzeFlightLegs1(flightLegIds, aircraftId) {
	    // console.log('Flight Leg Ids: ' + flightLegIds);
	    // console.log('aircraftId: ' + aircraftId);
	
		var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds+"&mainmenu=MaintenanceActivities&submenu=lruHistory";
		// console.log(url);
	    var win = window.open(url, '_self');
	    win.focus();
	}  

</script>
