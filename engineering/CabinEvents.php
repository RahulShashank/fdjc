<div id="cabinflightTimeline" class="flightTimeline">
	<script type="text/javascript">
			// DOM element where the Timeline will be attached
			var container = document.getElementById('cabinflightTimeline');

			var groups = new vis.DataSet([
						  { id: 'OPP', content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Open Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'CL', content: '<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i><br><strong>Closed Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
						  { id: 'FP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold; text-align: center'},						  
						  { id: 'PA', content: '<i class="fa fa-phone fa-fw" aria-hidden="true"></i><br><strong>Passenger<br>Announcement</strong>', subgroupOrder: function (a,b) {return a.content == "ON" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'VA', content: '<i class="fa fa-film fa-fw" aria-hidden="true"></i><br><strong>Video<br>Announcement</strong>', subgroupOrder: function (a,b) {return a.content == "ON" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'PRAM', content: '<i class="fa fa-volume-up fa-fw" aria-hidden="true"></i><br><strong>PRAM</strong>', subgroupOrder: function (a,b) {return a.content == "ON" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'BGM', content: '<i class="fa fa-music fa-fw" aria-hidden="true"></i><br><strong>BGM</strong>', subgroupOrder: function (a,b) {return a.content == "ON" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'},
						  { id: 'VOEVOR', content: '<i class="fa fa-desktop fa-fw" aria-hidden="true"></i><br><strong>VOE / VOR</strong>', subgroupOrder: function (a,b) {return a.content == "ON" ? 1 : 0;}, style: 'font-weight: bold; text-align: center'}
						]);
					  
			// Create a DataSet (allows two way data-binding)
			var items = new vis.DataSet([
				<?php
				
				foreach ($cabindataItems[items] as $dataItem) {
						echo $dataItem;
						echo ",";						
					}
					
				?>
			]);
		
			var startDate=<?php echo "'"; echo $cabindataItems[options][start];  echo "'";?>;
			var endDate=<?php echo "'"; echo $cabindataItems[options][end];  echo "'";?>;
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
