<div>
	<div class="row">		
					<?php
						error_log('Flight Leg Array Length..'.$flightLegsCount);
						if($flightLegsCount==1){
							error_log('Inside loop');
							if ($displayMap) {
								echo "<div class=\"col-md-12\">";
								echo "<div class=\"panel panel-default\">";
								echo "<div class=\"panel-body\" style=\"height: 318px; padding: 8px;\">";
								echo "<div id=\"map\" style=\"height: 300px\"></div>";
								echo "<br><br>";
								echo "</div>";
								echo "</div>";
								echo "</div>";
							}
						}
					?>			
	</div>
	<div class="row" ng-controller="flightStatusController">
		<div class="col-md-4" data-ng-repeat="status in statuses">
			<div class="card"
				ng-class="{'cardDanger': (status.value == 2), 'cardWarning': (status.value == 1), 'cardOK': (status.value == 0)}"}>
				<div class="cardStatus">{{ status.name }}</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-body">
					<div id="flightTimeline" class="flightTimeline"></div>
					<div id="loadingTimeline">
						<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
					</div>

				</div>
			</div>
		</div>
	</div>
</div>

