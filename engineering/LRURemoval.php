									<div class="row">
										<div class="col-md-12">						
											<div class="panel panel-default" style="border-top: 0px solid #E5E5E5;">						
												<div id="ctrldiv" class="panel-body" >
													<input type="hidden" id="airlineIds" ng-model="airlineIds"
														ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
													<div class="row">
														<div class="col-md-2 from-group">
															<label for="airline">Airline</label>
															<select id="airline" class="selectpicker show-tick airline"	data-live-search="true" data-width="100%" style="max-width: 150px;"></select>
														</div>
														<div class="col-md-2 from-group">
															<label for="platform">Platform</label>
																<select id="platform" class="selectpicker show-tick platform" multiple title="All" data-width="100%" data-max-width="120px;"></select>
														</div>
														<div class="col-md-2 from-group">
															<label for="configType">Configuration</label>
																<select id="configType" class="selectpicker show-tick configType" multiple title="All"	data-width="100%">
																</select>
														</div>
														<div class="col-md-2 from-group">
															<label for="tailsign">Tailsign</label>
																<select id="tailsign" class="selectpicker show-tick tailsign"
																	data-width="100%" 
																	data-live-search="true" multiple title="All"
																	data-selected-text-format="count > 3"></select>
														</div>
														<div class="col-md-2 from-group">
															<label for="startDateTimePicker">From</label>
																<input class="form-control dateChange" id="startDateTimePicker"
																	type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
														</div>
														<div class="col-md-2 from-group">
															<label for="endDateTimePicker">To</label>
																<input class="form-control dateChange" id="endDateTimePicker"
																	type="text" name="endDateTimePicker" ng-model="endDate"  style="width: 100%;" readonly='true'>
														</div>
													</div><br/>
													<div class="row" style="padding-left: 10px;">
														<a data-toggle="collapse" href="#advancedFilter" role="button" aria-expanded="false"
														aria-controls="advancedFilter"><font style="font-weight: bold;">Advanced Filter</font>&nbsp;<span
														class="glyphicon glyphicon-chevron-right"></span></a>
													</div>
													<div class="collapse" id="advancedFilter">
													<br/>
														<div class="row">
															<div class="col-md-2 from-group">
																<label for="serialNumberRemoval">Serial Number </label>
																	<input id="serialNumberRemoval" class="form-control" ></input>
															</div>
															<div class="col-md-2 from-group">
																<label for="hostnameRemoval"">Hostname </label>
																	<input id="hostnameRemoval" class="form-control" ></input>
															</div>
															<div class="col-md-2 form-group">
																<label for="hwPartNumberRemoval">HW Part Number </label>
																	<input id="hwPartNumberRemoval" class="form-control" ></input>
															</div>
														</div>
													</div>
													<br />
													<div class="row">
														<div class="col-md-12 text-left">
															<button id="filter" class="btn btn-primary"	>Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset" ng-click="resetMaintenance()">Reset</button>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>	
												
									<div id="dataInfo" class="row">
										<div class="col-md-12">
											<div class="panel panel-default">
												<div class="panel-body">	
													<div id="loadingTimeline" align="center"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div>
                            						<div id="errorInfo" class="container-fluid text-center">
                                						<label class="noData-label"> No data available for the selected duration or selected filters </label>
                                					</div>
													
													<div id="tableInfo" class="table-responsive">
														<table id="removalsTable" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="10" data-search="true" data-search-align="left" data-striped="true"  data-show-export="true">
															<thead>
																<tr> 
																	<th data-field="removalDate" data-sortable="true">Removal Date</th>
																	<th data-field="hostname" data-sortable="true">Hostname</th>
																	<th data-field="idFlightLeg" data-sortable="true" data-formatter="formatFlightLeg">FlightLeg</th>
																	<th data-field="tailsign" data-sortable="true">Tailsign</th>
																	<th data-field="hwPartNumber" data-sortable="true">HWPartNumber</th>
																	<th data-field="serialNumber" data-sortable="true">Previous S/N</th>
																	<th data-field="newSerialNumber" data-sortable="true">New S/N</th>
																</tr> 
															</thead>
														</table>
													</div>						
												</div>
											</div>
										</div>
									</div>
<!-- 									<div id="errorInfo" class="row"> -->
<!-- 										<div class="col-md-12"> -->
<!-- 											<div class="panel panel-default"> -->
<!-- 												<div class="panel-body text-center">													 -->
<!-- 													No data available for the selected duration or selected	filters -->
<!-- 												</div> -->
<!-- 											</div> -->
<!-- 										</div> -->
<!-- 									</div> -->
									
<script type="text/javascript">
	function formatFlightLeg(value, row, index, field) {
		if(value) {
			//value = value.replace(/,/g, ", ");
			var method = "return analyzeFlightLegs(" + value + "," + row['aircraftId'] + ")";
	
			return "<a onclick='"+method+"'>" + value + "</a>";
		} else {
			return '-';
		}
	}
	
	function analyzeFlightLegs(flightLegIds, aircraftId) {
	    // console.log('Flight Leg Ids: ' + flightLegIds);
	    // console.log('aircraftId: ' + aircraftId);
	
		var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds+"&mainmenu=MaintenanceActivities&submenu=lruRemoval";
		// console.log(url);
	    var win = window.open(url, '_self');
	    win.focus();
	}  

</script>
