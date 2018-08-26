<div id="flightSeatDetails">
	<div class="row">
		<div class="panel-group accordion">
			<div class="panel panel-info" style="box-shadow: 0px 1px 1px 0px rgba(0, 0, 0, 0.2); border-top-color: #c1c0c0;border-radius: 4px;">
				<div class="panel-heading" style="padding: 0px; height: 30px;">
					<h2 class="panel-title"
						style="padding: 0px !important; width: 100%">
						<a href="#accOneColTwo" style="padding: 0px !important;"> Wiring Diagram</a>
					</h2>
				</div>
				<div class="panel-body" id="accOneColTwo"
					style="padding-bottom: 5px;">
					<div ng-controller="DbFunctions">
                    	<div id="formWrapper">
                    		<form name="dbDisplayDetails" class="form-inline" id="dbDisplayDetailsForm">
                    			<div class="row" data-ng-show="showFiles">
                    				<div class="col-md-6">
                    					<div class="form-group fileList">
                    						<label for="files">File</label>
                    						<select name="files" id="files" class="selectpicker show-tick" data-ng-options="option.filename for option in files track by option.filename" data-ng-model="filename" data-ng-change="enableDisplay()">
                    							<option value="">Select</option>
                    						</select>&nbsp;&nbsp;
                    						<button name="modalView" id="modalView" data-ng-click="fileView()" class="btn btn-primary">View</button>
                    					</div>
                    				</div>
                    			</div>
                    			<div class="row" data-ng-show="!showFiles" >
                    				<div class="col-md-9" style="color: red;"><i class="fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i>&nbsp;This tail has new SW Version. Please contact admin and provide config files.</div>
                    			</div><br/>
	                        	<div id="loadingGraphicalView" style="height: 100%;"><img src="../img/ajaxLoading.gif"> Loading Diagram...</div>
	                        	<div id="dbDisplayGraphicalView" style="min-height: 300px;"></div>
                			</form>
            			</div>
        			</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="form-group" style="width: 255px;">
			<select id="flightSeatData"	class="form-control selectpicker show-tick"	onchange="showFlightSeatData();" value="selectedData">
				<option value="failures">Failures</option>
				<option value="faults">Faults</option>
				<option value="resets">Resets</option>
				<option value="events">Events</option>
			</select>
		</div>
	</div>
	<br/>
	<div class="panel panel-default">
		<div>
			<div class="panel-body">
				<div class="row">
					<!-- Tab panes -->
					<div>
						<div id="failuresSeat">
							<br>
							<div class="text-center" style="overflow: auto">
								<div class="lopa-panel">
									<table id="failuresLopa" align="center">
									</table>
								</div>
							</div>
							<br> 								
							<br>
							<div id="failureTimeline"></div>

						</div>
						
						<div id="faultsSeat">
							<br>
							<div class="text-center" style="overflow: auto">
								<div class="lopa-panel">
									<table id="faultsLopa" align="center">
									</table>
								</div>
							</div>
							<br> <br>
							<div id="faultsTimeline"></div>
						</div>
						<div id="resetsSeat">
							<br>
							<div class="text-center" style="overflow: auto">
								<div class="lopa-panel">
									<table id="resetsLopa" align="center">
									</table>
								</div>
							</div>
							<br> <br>
							<div id="resetsTimeline"></div>
						</div>
						<div id="eventsSeat">
							<br>
							<div class="text-center" style="overflow: auto">
								<div class="lopa-panel">
									<table id="applicationsLopa" align="center">
									</table>
								</div>
							</div>
							<br> <br>
							<div id="applicationsTimeline"></div>
						</div>
						<div id="flightSeatLoading" class="text-center">
			                 <img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
		                </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
