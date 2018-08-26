
    							<div class="lighter text-center">
                    				<span><input type="text" class="search2 rounded" placeholder="Search..." size="50" data-ng-model="searchKeyword"></span>
                    				<br><br>
                    			</div>
								<div class="row">
                    				<div class="col-xs-12 col-md-4" data-ng-repeat="aircraft in aircrafts | filter: actype | filter: platform | filter:filterStatus | filter: searchKeyword | orderBy: ['-status','tailsign'] ">
                    					<div class="card" ng-class="{'card': (aircraft.lastStatusComputed >= 2), 'cardDanger': (aircraft.status >= 2), 'cardWarning': (aircraft.status == 1), 'cardOK': (aircraft.status == 0)}"}>
                    						 <div class="cardHeaderFooter" style="padding: 7px">
                    							<img src="../img/sr{{aircraft.systemResetStatus}}.png" style="vertical-align:middle" title="System Reset Status">&nbsp;&nbsp;&nbsp;<img src="../img/he{{aircraft.headEndStatus}}.png" style="vertical-align:middle" title="Head-End Status">&nbsp;&nbsp;&nbsp;<img src="../img/fc{{aircraft.firstClassStatus}}.png" style="vertical-align:middle" title="First Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/bc{{ aircraft.businessClassStatus }}.png" style="vertical-align:middle" title="Business Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/ec{{ aircraft.economyClassStatus }}.png" style="vertical-align:middle" title="Economy Class Status">&nbsp;&nbsp;&nbsp;<img src="../img/cn{{ aircraft.connectivityStatus }}.png" style="vertical-align:middle" title="Connectivity Status">
                    						</div>
                    						<div class="cardBody">
                    							<div style="font-size: 12px">
                    								<div style="float:left" class="cardHeaderFooter">
                    									&nbsp;&nbsp;{{ aircraft.type }} / {{ aircraft.msn }}
                    								</div>
                    								<div style="float:right" class="cardHeaderFooter">
                    									{{ aircraft.platform }} / {{ aircraft.software }}&nbsp;&nbsp;
                    								</div>
                    								<div style="clear:both">
                    								</div>
                    							</div>
                    							<a></a>
												<a href="AircraftTimeline.php?aircraftVisited=false&aircraftId={{ aircraft.id }}" style="text-decoration: none;">
													<strong>{{ aircraft.tailsign }} <span ng-if="aircraft.nose"> ({{ aircraft.nose }})</span></strong>
												</a>
                    							<div style="font-size: 12px">
                    								<div style="float:left" class="cardHeaderFooter">
                    									&nbsp;&nbsp;
                    									<span ng-click="editMaintenanceStatus(aircraft)" style="cursor: pointer;">
                        									<span class="glyphicon glyphicon-unchecked" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'No Status')"></span>
                        									<span class="glyphicon glyphicon-road" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Ground')"></span>
                        									<span class="glyphicon glyphicon-plane" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'In Air')"></span>
                        									<span class="glyphicon glyphicon-flag" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Watch')"></span>
                        									<span class="glyphicon glyphicon-ok" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'OK')"></span>
                        									<span class="glyphicon glyphicon-warning-sign" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'Warning')"></span>
                        									<span class="glyphicon glyphicon-hdd" aria-hidden="true" ng-if="(aircraft.maintenanceStatus == 'New Software')"></span>
                        									<span ng-if="(aircraft.maintenanceStatus == 'No Status')"><i>No status</i></span>
                        									<span ng-if="(aircraft.maintenanceStatus != 'No Status')">{{ aircraft.maintenanceStatus }}</span>
                    									</span>
                    								</div>
                    								<div style="float:right" class="cardHeaderFooter">
                    									<span ng-if="!aircraft.Content"><i>No content</i></span>{{ aircraft.Content }}&nbsp;&nbsp;
                    								</div>
                    								<div style="clear:both">
                    								</div>
                    							</div>
                    						</div>
                    						<div class="cardHeaderFooter">
                    							<a href="AircraftReport.php?aircraftId={{ aircraft.id }}"><i class="fa fa-file-text-o fa-fw" aria-hidden="true" title="Report"></i></a>
                    							&nbsp;
                    							<a href="HardwareRevisionsMods.php?aircraftId={{ aircraft.id }}"><i class="fa fa-tasks fa-fw" aria-hidden="true" title="H/W Rev & Mod"></i></a>
                    							&nbsp;
                    							<a href="MaintenanceActivities.php?aircraftId={{ aircraft.id }}"><i class="fa fa-wrench fa-fw" aria-hidden="true" title="Maintenance Activities"></i></a>
                    							&nbsp;
                    							<a href="lopa.php?aircraftId={{ aircraft.id }}&lopaVisited=false"><i class="fa fa-th" aria-hidden="true" title="LOPA"></i></a>
                    							&nbsp;
                    						</div>
                    					</div>
                    				</div>
								</div>
				<!-- Modal : Start -->
				<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
						<div class="modal-content" style="border-radius: 5px; border-width: 0px;">
							<div class="modal-header">
								<button id="closeModal" type="button" class="close" data-dismiss="modal">&times;</button>
								<h4 class="modal-title" id="myModalLabel">Update Aircraft Status / {{tailsign}}</h4>
							</div>
							<form class="form-horizontal" ng-submit="updateStatusVersion();">
								<div class="modal-body">
									<div id="error"></div>
									<br>
									<div class="form-group">
										<label for="currentStatus" class="col-sm-4 control-label">Current Status: </label>
										<div class="col-sm-6">
											<p class="form-control-static">
												<span class="glyphicon glyphicon-unchecked" aria-hidden="true" ng-if="(originalStatus == 'No Status')"></span>
												<span class="glyphicon glyphicon-road" aria-hidden="true" ng-if="(originalStatus == 'Ground')"></span>
												<span class="glyphicon glyphicon-plane" aria-hidden="true" ng-if="(originalStatus == 'In Air')"></span>
												<span class="glyphicon glyphicon-flag" aria-hidden="true" ng-if="(originalStatus == 'Watch')"></span>
												<span class="glyphicon glyphicon-ok" aria-hidden="true" ng-if="(originalStatus == 'OK')"></span>
												<span class="glyphicon glyphicon-warning-sign" aria-hidden="true" ng-if="(originalStatus == 'Warning')"></span>
												<span class="glyphicon glyphicon-hdd" aria-hidden="true" ng-if="(originalStatus == 'New Software')"></span>
												<span ng-if="(originalStatus == 'No Status')"><i>No status</i></span>
												<span ng-if="(originalStatus != 'No Status')">{{ originalStatus }}</span>
											</p>
										</div>
									</div>
									<div class="form-group">
										<label for="newStatus" class="col-sm-4 control-label">New Status: </label>
										<div class="col-sm-6">
											<select class="selectpicker show-tick" id="newStatus" title="Select a status..." data-width="100%">
												<!-- <option value="">Select a status...</option> -->
												<option value="No Status" data-icon="glyphicon-unchecked">&nbsp;No Status</option>
												<option value="Ground" data-icon="glyphicon-road">&nbsp;Ground</option>
												<option value="In Air" data-icon="glyphicon-plane">&nbsp;In Air</option>
												<option value="OK" data-icon="glyphicon-ok">&nbsp;OK</option>
												<option value="Warning" data-icon="glyphicon-warning-sign">&nbsp;Warning</option>
												<option value="Watch" data-icon="glyphicon-flag">&nbsp;Watch</option>
												<option value="New Software" data-icon="glyphicon-hdd">&nbsp;New Software</option>
											</select>
										</div>
									</div>
								</div>
								<input type="hidden" id="aircraftId" value="{{ aircraftId }}" />
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									<button id="updateAircraft" class="btn btn-primary">Update</button>
								</div>
							</form>
						</div>
					</div>
				</div>
				<!-- Modal : End -->