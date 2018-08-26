<div data-ng-controller="LRURemovalTimelineController">
	<div id="lruRemovalTimelineLoading" class="text-center">
		<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
	</div>
	<div id="atimeline" ></div>
	<br>
	<div id="tableInfo" class="table-responsive">
		<table id="lruRemovalTimelineTable" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[10, 25, 50, 100, All]" data-page-size="10" data-search="true" data-search-align="left" data-striped="true"  data-show-export="true">
			<thead>
				<tr> 
					<th data-field="idFlightLeg" data-sortable="true">Flight Leg</th>
					<th data-field="removalDate" data-sortable="true">Removal Date</th>
					<th data-field="hostname" data-sortable="true">Hostname</th>
					<th data-field="serialNumber" data-sortable="true">Previous S/N</th>
					<th data-field="newSerialNumber" data-sortable="true">New S/N</th>
				</tr> 
			</thead>
		</table>
	</div>						
</div>
