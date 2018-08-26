<div id="passengerflightTimeline" class="flightTimeline">
				<script type="text/javascript">
					  // DOM element where the Timeline will be attached
					  var container = document.getElementById('passengerflightTimeline');

					  var groups = new vis.DataSet([
						  { id: 'OPP', content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Open Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'CL', content: '<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i><br><strong>Closed Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'FP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'FSA', content: '<i class="fa fa-file-code-o fa-fw" aria-hidden="true"></i><br><strong>Flight Script<br>Application</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'Services', content: '<i class="fa fa-film fa-fw" aria-hidden="true"></i>&nbsp;&nbsp;<i class="fa fa-music fa-fw" aria-hidden="true"></i>&nbsp;&nbsp;<i class="fa fa-gamepad fa-fw" aria-hidden="true"></i>&nbsp;&nbsp;<i class="fa fa-globe fa-fw" aria-hidden="true"></i><br><strong>Services</strong>', style: 'font-weight: bold; text-align: center'},						  
						]);
					  
					  // Create a DataSet (allows two way data-binding)
					  var items = new vis.DataSet([
						  <?php
							foreach ($passengerDataItems[items] as $dataItem) {
								echo $dataItem;
								echo ",";
							}
						?>
					  ]);

					  var startDate=<?php echo "'"; echo $passengerDataItems[options][start];  echo "'";?>;
					  var endDate=<?php echo "'"; echo $passengerDataItems[options][end];  echo "'";?>;
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