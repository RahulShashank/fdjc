<div id="systemEventsflightTimeline" class="flightTimeline">
				<script type="text/javascript">
					  // DOM element where the Timeline will be attached
					  var container = document.getElementById('systemEventsflightTimeline');

					  var groups = new vis.DataSet([
						  { id: 'OPP', content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Open Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'CL', content: '<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i><br><strong>Closed Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'FP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},						  
						  { id: 'FDSFP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Data Service<br>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'CSWFP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Core SW<br>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'MODE', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>System Mode</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'HB', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>HB Master</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'BIT', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>BIT Master</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'DOORS', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Doors</strong>', subgroupOrder: function (a,b) {return a.content == "OPEN" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'LNDGR', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Landing Gear<br>Down Locked</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'WOW', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Weight On Wheel</strong>', subgroupOrder: function(a,b) {return a.content == "OFF" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'DEC', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Decompression</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'OFF', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Offload triggered</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'GRD', content: '<i class="fa fa-cog fa-fw" aria-hidden="true"></i><br><strong>Ground Message<br>sent</strong>', style: 'font-weight: bold; text-align: center'}
						]);
					  
					  // Create a DataSet (allows two way data-binding)
					  var items = new vis.DataSet([
						  <?php
							foreach ($SystemEventsdataItems[items] as $dataItem) {
								echo $dataItem;
								echo ",";
							}
						?>
					  ]);

					  var startDate=<?php echo "'"; echo $SystemEventsdataItems[options][start];  echo "'";?>;
					  var endDate=<?php echo "'"; echo $SystemEventsdataItems[options][end];  echo "'";?>;
					  var minDate=<?php echo "'"; echo $cabindataItems[options][min];  echo "'";?>;
					  var maxDate=<?php echo "'"; echo $cabindataItems[options][max];  echo "'";?>;
					
					  // Configuration for the Timeline
					  var options = {
								orientation: 'both',
								clickToUse: true,
								stack: false,
								start: minDate,
						        end: maxDate
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