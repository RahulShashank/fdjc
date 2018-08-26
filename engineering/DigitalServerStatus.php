<div id="digitalServerflightTimeline" class="flightTimeline">
				<script type="text/javascript">
					  // DOM element where the Timeline will be attached
					  var container = document.getElementById('digitalServerflightTimeline');
					  
					  // Note: "subgroupOrder: function (a,b) {return true;}" allows to order the DSU correctly as "subgroupOrder: function (a,b) {return a.subgroupOrder > b.subgroupOrder;}" seems to not work...
					  var groups = new vis.DataSet([
						  { id: 'OPP', content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Open Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'CL', content: '<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i><br><strong>Closed Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'FP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder > b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Redundancy', content: '<i class="fa fa-server fa-fw" aria-hidden="true"></i><br><strong>Redundancy Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Status_apache', content: '<img src="../img/apache.png" width="16px" height="16px"/><br><strong>Apache Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Status_rabbitmq', content: '<img src="../img/rabbitmq.png" width="16px" height="16px"/><br><strong>Rabbitmq Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Status_mysql', content: '<img src="../img/mysql.png" width="16px" height="16px"/><br><strong>MySQL Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Status_3dMaps', content: '<img src="../img/globe.png" width="16px" height="16px"/><br><strong>3dMaps Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'Status_os3dMaps', content: '<img src="../img/globe.png" width="16px" height="16px"/><br><strong>os3dMaps Status</strong>', subgroupOrder: function (a,b) {return true;}, style: 'font-weight: bold; text-align: center'}
						]);
					  
					  // Create a DataSet (allows two way data-binding)
					  var items = new vis.DataSet([
						  <?php
							foreach ($digitalServerdataItems[items] as $dataItem) {
								echo $dataItem;
								echo ",";
							}
						?>
					  ]);

					  var startDate=<?php echo "'"; echo $digitalServerdataItems[options][start];  echo "'";?>;
					  var endDate=<?php echo "'"; echo $digitalServerdataItems[options][end];  echo "'";?>;
					  var minDate=<?php echo "'"; echo $cabindataItems[options][min];  echo "'";?>;
					  var maxDate=<?php echo "'"; echo $cabindataItems[options][max];  echo "'";?>;
					
					  // Configuration for the Timeline
					  var options = {
								orientation: 'both',
								clickToUse: true,
								stack: false,
								start: minDate,
						        end: maxDate,
						        min:minDate,
							    max:maxDate
					  };

					  // Create a Timeline
					  var timeline = new vis.Timeline(container, items, groups, options);
					  
					  // Prevent right click
					  timeline.on('contextmenu', function (props) {
						  //alert('Right click!');
						  props.event.preventDefault();
						});
				</script>
			</div>