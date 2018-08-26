<div>
	<div class="form-group" style="width:255px;">
		<select id="biteData" class="form-control selectpicker show-tick"
			onchange="showBiteData();" value="selectedData">
			<option value="failures">Failures</option>
			<option value="faults">Faults</option>
			<option value="resets">Resets</option>
			<option value="events">Events</option>
			<option value="services">Services</option>
		</select>
	</div>

	<!-- Tab panes -->
	<div>
		
		<div class="panel panel-default">
			<div class="panel-body">
				<div id="biteDataLoading" class="text-center">
					<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
				</div>		
				<div id="failures">					
					<div class="table-responsive">
						<!-- <table class="table table-striped table-hover"> -->
						<table id="failuresTable"
							data-classes="table table-no-bordered table-hover"
							data-pagination="true" data-page-list="[10, 25, 50, 100, All]"
							data-page-size="50" data-search="true" data-search-align="left"
							data-striped="true" data-show-export="true">
							<thead>
								<tr>
									<th data-field="idFailure" data-sortable="true">Id</th>
									<th data-field="correlationDate" data-sortable="true">Correlation
										Date</th>
									<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
									<th data-field="accusedHostName" data-sortable="true">Accused
										Hostname</th>
									<th data-field="failureCode" data-sortable="true">Failure Code</th>
									<th data-field="failureDesc" data-sortable="true">Description</th>
									<th data-field="monitorState" data-sortable="true">Monitor State</th>
									<th data-field="param1">Param 1</th>
									<th data-field="legFailureCount" data-sortable="true">Leg Failure
										Count</th>
									<th data-field="lastUpdate" data-sortable="true">Last Update</th>
									<th data-field="duration" data-sortable="true">Duration</th>
									<!-- <th data-field="correctiveAction" align=""n</th> -->
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div id="faults">					
					<div class="table-responsive">
						<!-- <table class="table table-striped table-hover"> -->
						<table id="faultsTable"
							data-classes="table table-no-bordered table-hover"
							data-pagination="true" data-page-list="[10, 25, 50, 100, All]"
							data-page-size="50" data-search="true" data-search-align="left"
							data-striped="true" data-show-export="true">
							<thead>
								<tr>
									<th data-field="idFault" data-sortable="true">Id</th>
									<th data-field="detectionTime" data-sortable="true">Detection
										Time</th>
									<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
									<th data-field="hostName" data-sortable="true">Hostname</th>
									<th data-field="reportingHostName" data-sortable="true">Reporting
										Hostname</th>
									<th data-field="faultCode" data-sortable="true">Fault Code</th>
									<th data-field="faultDesc" data-sortable="true">Description</th>
									<th data-field="monitorState" data-sortable="true">Monitor State</th>
									<th data-field="param1">Param 1</th>
									<th data-field="param2">Param 2</th>
									<th data-field="param3">Param 3</th>
									<th data-field="param4">Param 4</th>
									<th data-field="insertionTime" data-sortable="true">Insertion
										Time</th>
									<th data-field="clearingTime" data-sortable="true">Clearing Time</th>
									<th data-field="lastUpdate" data-sortable="true">Last Update</th>
									<th data-field="duration" data-sortable="true">Duration</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div id="resets">					
					<div class="table-responsive">
						<!-- <table class="table table-striped table-hover"> -->
						<table id="resetsTable"
							data-classes="table table-no-bordered table-hover"
							data-pagination="true" data-page-list="[10, 25, 50, 100, All]"
							data-page-size="50" data-search="true" data-search-align="left"
							data-striped="true" data-show-export="true">
							<thead>
								<tr>
									<th data-field="idEvent" data-sortable="true">Id</th>
									<th data-field="lastUpdate" data-sortable="true">Reset Time</th>
									<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
									<th data-field="eventData" data-sortable="true">Hostname</th>
									<th data-field="eventName" data-sortable="true">Reset Type</th>
									<th data-field="eventInfo" data-sortable="true">Reset Reason</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div id="events">					
					<div class="table-responsive">
						<!-- <table class="table table-striped table-hover"> -->
						<table id="eventsTable"
							data-classes="table table-no-bordered table-hover"
							data-pagination="true" data-page-list="[10, 25, 50, 100, All]"
							data-page-size="50" data-search="true" data-search-align="left"
							data-striped="true" data-show-export="true">
							<thead>
								<tr>
									<th data-field="idExtAppEvent" data-sortable="true">Id</th>
									<th data-field="detectionTime" data-sortable="true">Detection
										Time</th>
									<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
									<th data-field="hostName" data-sortable="true">Hostname</th>
									<th data-field="reportingHostName" data-sortable="true">Reporting
										Hostname</th>
									<th data-field="faultCode" data-sortable="true">Fault Code</th>
									<th data-field="faultDesc" data-sortable="true">Fault Description</th>
									<th data-field="param1">Param 1</th>
									<th data-field="param2">Param 2</th>
									<th data-field="param3">Param 3</th>
									<th data-field="param4">Param 4</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div id="services">
					<br>
					<div class="table-responsive">
						<!-- <table class="table table-striped table-hover"> -->
						<table id="servicesTable"
							data-classes="table table-no-bordered table-hover"
							data-pagination="true" data-page-list="[10, 25, 50, 100, All]"
							data-page-size="50" data-search="true" data-search-align="left"
							data-striped="true" data-show-export="true">
							<thead>
								<tr>
									<th data-field="idFailure" data-sortable="true">Id</th>
									<th data-field="correlationDate" data-sortable="true">Correlation
										Date</th>
									<th data-field="flightPhase" data-sortable="true">Flight Phase</th>
									<th data-field="accusedHostName" data-sortable="true">Accused
										Hostname</th>
									<th data-field="failureCode" data-sortable="true">Failure Code</th>
									<th data-field="failureDesc" data-sortable="true">Failure
										Description</th>
									<th data-field="failureImpact" data-sortable="true">failureImpact</th>
									<th data-field="name" data-sortable="true">Service Name</th>
									<th data-field="description" data-sortable="true">Service
										Description</th>
									<th data-field="monitorState" data-sortable="true">Monitor State</th>
									<th data-field="lastUpdate" data-sortable="true">Last Update</th>
									<th data-field="duration" data-sortable="true">Duration</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
