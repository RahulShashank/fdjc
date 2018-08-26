	<table id="connectivityDataTable" data-toggle="table" data-classes="table table-no-bordered" data-search="true" data-search-align="left" data-show-export="true" data-row-style="rowStyle" data-height='800'>
						<thead>
							<tr>
							<?php
								// function to help creating the header of the table
								function traverseHeaderArray($array) {
									foreach($array as $key => $value) {
										if( is_array($value) ) {
											echo traverseHeaderArray($value);
										} else {
											if($key != "_id") {
												echo "<th data-sortable=\"true\" data-field=\"$key\" data-align=\"left\"><span class=\"header\" style=\"text-align:left\">$key</span></th>";
											}											
										}
									}
								}
								
								foreach($cursorHeader as $key => $value) {
									// Go through all objects
									traverseHeaderArray($value);
								}
							?>
							</tr>							
						</thead>
						<tbody>
							<?php
							
								// function to help creating the header of the table
								function traverseBodyArray($array) {
									foreach($array as $key => $value) {
										if( is_array($value) ) {
											traverseBodyArray($value);
										} else {
											//BeR 2Apr18: adjusting table display formatting to Ka logs
											$class = "";											
											if($key != "_id") {
												if( ($key == "Standard Pres Alti") && ($value > 10000) ) {
													$class = "success";
												} elseif( ($key == "honAkMaintSatLinkState") && ($value == "inNetwork(1)") ) {
													$class = "success";
												} elseif( ($key == "honAkMaintSatLinkState") && ($value != "inNetwork(1)") ) {
													$class = "danger";
												} elseif( (($key == "honAkModmanHealthStatus") || ($key == "honAkKanduHealthStatus") || ($key == "honAkAesHealthStatus") || ($key == "honAkKrfuHealthStatus") || ($key == "honAkOaeHealthStatus") || ($key == "honAkApmHealthStatus")) && ($value != "normal(1)") ) {
													$class = "danger";
												} elseif( ($key == "honAkDataLinkAvailState") && ($value == "open(2)") ) {
													$class = "danger";
												//} elseif( ($key == "serviceAvailableDetail") && $value == "restrictedAirspaceRegion" ) {
												//	$class = "warning";
												//} elseif(stripos("0.0.0", $value) != False) {
												//	$class = "warning";
												} else {
													switch($value) {
														case "error":
														case "KO":
														case "ko":
														case "false":
														case "noLineOfSite(8)":
														case "structureBlockage(5)":
														case "notLocked(3)":
														case "notReady(8)":
															$class = "danger";
															break;
													}													
												}
													echo "<td class=\"$class\">$value</td>";
											}											
										}
									}
								}
								
								foreach($cursorBody as $key => $value) {
								if(is_array($value) && $value != '-'){
										echo "<tr>";
										// Go through all values and create columns
										traverseBodyArray($value);
										echo "</tr>";
									}
								}
																
							?>	
						</tbody>					
					</table>