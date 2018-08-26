<table id="connectivityDataTable" data-classes="table table-no-bordered" data-search="true" data-search-align="left" data-striped="true" data-show-export="true" data-row-style="rowStyle" data-height='700'>
									<thead>
										<tr>
											<?php
												if(isset($cursorHeader)){
													// function to help creating the header of the table
													function traverseHeaderArray($array) {
														foreach($array as $key => $value) {
																if( is_array($value) ) {
																	echo traverseHeaderArray($value);
																} else {
																if($key != "_id") {
																	echo "<th data-sortable=\"true\" data-field=\"$key\" data-align=\"left\" ><span class=\"header\" style=\"text-align:left\">$key</span></th>";
																}											
															}
														}
													}
													
													foreach($cursorHeader as $key => $value) {
														// Go through all objects
														traverseHeaderArray($value);
													}					
													
												}
											?>
										</tr>							
									</thead>
									<tbody>
										<?php
											if(isset($cursorBody)){
												// function to help creating the header of the table
												function traverseBodyArray($array) {
													foreach($array as $key => $value) {
															if( is_array($value) ) {
																traverseBodyArray($value);
															} else {
																$class = "";											
																if($key != "_id") {
																	if( ($key == "asdSbbAltitude") || ($key == "altitude") && ($value > 10000) ) {
																			$class = "success";
																	} elseif(($key == "gsmConnexServiceAllowedIndication" || $key == "serviceAvailable") && ($value == "false") ) {
																			$class = "danger";
																	} elseif(($key == "BTS1" || $key == "BTS2" || $key == "SBB-1" || $key == "SBB2") && ($value == "KO") ) {
																			$class = "danger";
																	} elseif( ($key == "serviceAvailableDetail") && $value == "restrictedAirspaceRegion" ) {
																			$class = "warning";
																	} elseif(stripos("0.0.0", $value) != False) {
																			$class = "warning";
																	} else {
																		switch($value) {
																				case "error":
																				case "KO":
																				case "ko":
																				case "false":
																				case "fapdisableService":
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
												
											}
										?>	
											</tbody>					
										</table>