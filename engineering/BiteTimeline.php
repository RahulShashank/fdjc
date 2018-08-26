<div class="row" style="margin-top: -21px;"> 
	<a href="#" class="info" data-toggle="modal" data-target="#modalTable" title="Reboot Information">					 
		<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
		</i>
	</a>
</div>
<div class="row" > 
	<div class="panel-group accordion">
		<br/>
		<div class="panel panel-info" style="box-shadow: 0px 1px 1px 0px rgba(0, 0, 0, 0.2);border-top-color: #c1c0c0;">
			<div class="panel-heading" style="padding: 0px; height: 30px; cursor: pointer;" id="filterHeading">
				<h4 class="panel-title"
					style="padding: 0px !important; font-size: 13px;width: 100%; padding-right: 15px !important;">
					<a  id="filterIconHref" href="#accOneColTwog" style="padding: 0px !important;"> <i
						class="fa fa-filter fa-fw" aria-hidden="true" ></i>
						&nbsp;&nbsp;Filter &nbsp;&nbsp;
						<span id="filterIcon" class="glyphicon glyphicon-chevron-right"  style="float:right;padding-top: 6px;"></span> 
						</a>
				</h4>
			</div>
			<div class="panel-body" id="accOneColTwog"
				style="border: 1px solid #E8E8E8;">
				<div class="col-md-12">
					<form action="BiteTimeline.php" role="form" class="form-horizontal" method="get">
						<?php
							if($aircraftId != '') {
								echo "<input type=\"hidden\" name=\"aircraftId\" value=\"$aircraftId\">";
							} else {
								echo "<input type=\"hidden\" name=\"sqlDump\" value=\"$dbName\">";
							}
							echo "<input type=\"hidden\" name=\"flightLegs\" value=\"$flightLegId\">";
							echo "<input type=\"hidden\" name=\"filter\" value=\"true\">";
						?>
						
						<div class="row">
							<div class="col-md-3" style="padding: 0px; width: 217px;">
								<b>Head-End</b>
							</div>
							<div class="col-md-3" style="padding: 0px; width: 205px;">
								<b>Seat-End</b>
							</div>
							<div class="col-md-3" style="padding: 0px; width: 215px;">
								<b>Distribution</b>
							</div>
							<div class="col-md-3" style="padding: 0px;">
								<b>Other LRU Types</b>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4 filterBox">
								<div class="row col-md-12">
									<label class="check"><input type="checkbox" class="icheckbox" id="dsu_checkbox" name="showDSU" value="on" checked="checked" /> DSU</label>
								</div>
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox" id="avcd_laic_checkbox" type="checkbox" name="showAVCD_LAIC" value="on" checked="checked" /> AVCD / LAIC</label>
								</div>
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox" id="icmt_checkbox" type="checkbox" name="showICMT" value="on" checked="checked" /> ICMT</label>
								</div>
							</div>
							<div class="col-md-4 filterBox" style="width: 190px;">
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox"id="svdu_checkbox" type="checkbox" name="showSVDU" value="on" checked="checked" /> SVDU</label>
								</div>
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox" id="tpmu_checkbox" type="checkbox" name="showTPMU" value="on"  /> TPMU</label>
								</div>
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox"id="qseb_sdb_vcssbd_checkbox" type="checkbox" name="showQSEB_SDB_VCSSDB" value="on" checked="checked" /> QSEB / SDB / VCSSDB</label>
								</div>
							</div>
							<div class="col-md-4 filterBox">
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox"id="adb_adbg_checkbox" type="checkbox" name="showADBG" value="on" checked="checked"/> ADB / ADBG</label>
								</div>
								<div class="row col-md-12">
									<label class="check"><input class="icheckbox" id="spb_checkbox" type="checkbox" name="showSPB" value="on" checked="checked" /> SPB</label>
								</div>
							</div>
							<div class="col-md-4 form-group" style="margin-top: 4px;">
								<select multiple class="form-control" id="otherLruType" name="otherLruType[]" size="7"
									style="height: 101px !important;width: 200px;border-radius: 0px;margin-left: 6px;">
									<option value="A429" name="A429"
									<?php if($lruTypeFilter && in_array('A429',$otherLruType)) echo "selected";?>>A429</option>
									<option value="ACARS" name="ACARS"
									<?php if($lruTypeFilter && in_array('ACARS',$otherLruType)) echo "selected";?>>ACARS</option>
									<option value="APM" name="APM"
									<?php if($lruTypeFilter && in_array('APM',$otherLruType)) echo "selected";?>>APM</option>
									<option value="AFDX" name="AFDX"
									<?php if($lruTypeFilter && in_array('AFDX',$otherLruType)) echo "selected";?>>AFDX</option>
									<option value="BTS" name="BTS"
									<?php if($lruTypeFilter && in_array('BTS',$otherLruType)) echo "selected";?>>BTS</option>
									<option value="Camera" name="Camera"
									<?php if($lruTypeFilter && in_array('Camera',$otherLruType)) echo "selected";?>>Camera</option>
									<option value="CIDSCSS" name="CIDSCSS"
									<?php if($lruTypeFilter && in_array('CIDSCSS',$otherLruType)) echo "selected";?>>CIDS/CSS</option>
									<option value="CWLU" name="CWLU"
									<?php if($lruTypeFilter && in_array('CWLU',$otherLruType)) echo "selected";?>>CWLU</option>
									<option value="DCPS" name="DCPS"
									<?php if($lruTypeFilter && in_array('DCPS',$otherLruType)) echo "selected";?>>DCPS</option>
									<option value="DVD" name="DVD"
									<?php if($lruTypeFilter && in_array('DVD',$otherLruType)) echo "selected";?>>DVD</option>
									<option value="FSA" name="FSA"
									<?php if($lruTypeFilter && in_array('FSA',$otherLruType)) echo "selected";?>>FSA</option>
									<option value="KANDU" name="KANDU"
									<?php if($lruTypeFilter && in_array('KANDU',$otherLruType)) echo "selected";?>>KANDU</option>
									<option value="KRFU" name="KRFU"
									<?php if($lruTypeFilter && in_array('BTS',$otherLruType)) echo "selected";?>>KRFU</option>
									<option value="IPM" name="IPM"
									<?php if($lruTypeFilter && in_array('IPM',$otherLruType)) echo "selected";?>>IPM</option>
									<option value="MODMAN" name="MODMAN"
									<?php if($lruTypeFilter && in_array('MODMAN',$otherLruType)) echo "selected";?>>MODMAN</option>
									<option value="NCU" name="NCU"
									<?php if($lruTypeFilter && in_array('NCU',$otherLruType)) echo "selected";?>>NCU</option>
									<option value="NFC" name="NFC"
									<?php if($lruTypeFilter && in_array('NFC',$otherLruType)) echo "selected";?>>NFC</option>
									<option value="OAE" name="OAE"
									<?php if($lruTypeFilter && in_array('OAE',$otherLruType)) echo "selected";?>>OAE</option>
									<option value="PRINTER" name="PRINTER"
									<?php if($lruTypeFilter && in_array('PRINTER',$otherLruType)) echo "selected";?>>PRINTER</option>
									<option value="SDU" name="SDU"
									<?php if($lruTypeFilter && in_array('SDU',$otherLruType)) echo "selected";?>>SDU</option>
									<option value="SAC" name="SAC"
									<?php if($lruTypeFilter && in_array('SAC',$otherLruType)) echo "selected";?>>SAC</option>
									<option value="TU" name="TU"
									<?php if($lruTypeFilter && in_array('TU',$otherLruType)) echo "selected";?>>TU</option>
								</select>
							</div>
						</div><br/>
						<div>
							<div class="row col-md-6" style="padding: 0px;padding-bottom: 3px;">
								<b>HostNames</b><br/>
								<div class="col-md-8" style="padding: 0px;">
								<input type="text" class="form-control" id="hostnameInput" name="hostnameInput" value="<?php echo $hostnameInput; ?>" title="Enter one or several Hostnames separated by comma"/></div>
								
							</div>
							<div class="row  col-md-6" style="padding: 0px;padding-bottom: 3px;">
								<b style="background-color: white; position: absolute;">&nbsp;&nbsp;Event Type&nbsp;&nbsp;</b><br/>
								<div class="form-group" style="margin: -9px;padding: 9px;border: 1px solid #d5d5d5;">
									<label class="check"><input class="icheckbox" type="checkbox" name="showFailures" value="on"  /> Failures</label> 
									<label class="check"><input class="icheckbox" type="checkbox" name="showFaults" value="on"  checked="checked"/> Faults</label>
									<label class="check"><input class="icheckbox" type="checkbox" name="showReboots" value="on"   /> Reboots</label> 
									<label class="check"><input	class="icheckbox" type="checkbox" name="showAppEvents" value="on"   /> App.Events</label> 
									<label class="check"><input class="icheckbox" type="checkbox" name="showImpServices" value="on"   /> Impacted Services</label>
								</div>
							</div>							  
						</div>
						<br/>
						<div>
							
							<div class="row col-md-6" style="padding: 0px;margin-top: 20px;padding-bottom: 3px;">
								<b>BITE Codes</b><br/>
								<div class="col-md-8" style="padding: 0px;"><input type="text" class="form-control" id="biteCode" name="biteCode" value="<?php echo $biteCode; ?>" title="Enter one or several BITE code separated by comma" /></div>
								
							</div>
							<div class="row col-md-6" style="padding: 0px;margin-top: 20px;padding-bottom: 3px;margin-left: -11px;">
								<b>BITE Codes (Don't Display)</b><br/>
								<div class="col-md-8" style="padding: 0px;"><input type="text" class="form-control" id="notBiteCode" name="notBiteCode" value="<?php echo $notBiteCode; ?>" title="Enter one or several BITE code separated by comma" /></div>
								
							</div>							
						</div>						
						<br/>
						<div style="margin-left: 9px;">							
							<div class="row col-md-5" style="padding: 0px;margin-top: 12px;padding-bottom: 3px;">
								<b style="background-color: white; position: absolute;">&nbsp;&nbsp;Severity&nbsp;&nbsp;</b><br/>
								<div class="form-group" style="margin: -9px;padding: 9px;border: 1px solid #d5d5d5;">
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="allSeverity" type="radio" name="severity" value="all" checked/> All</label>
									</div>
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="criticalSeverity" type="radio" name="severity" value="critical" /> Critical</label>
									</div>
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="not_criticalSeverity" type="radio" name="severity" value="not_critical" /> Not critical</label>
									</div>
								</div>
							</div>
							<div class="row col-md-5" style="padding: 0px;margin-top: 12px;margin-left: 68px;padding-bottom: 3px;">
								<b style="background-color: white; position: absolute;">&nbsp;&nbsp;Monitor State&nbsp;&nbsp;</b><br/>
								<div class="form-group" style="margin: -9px;padding: 9px;border: 1px solid #d5d5d5;">
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="allmonitorState" type="radio" name="monitor" value="1,3" checked/> All</label>
									</div>
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="activeMonitorState" type="radio" name="monitor" value="3" /> Active</label>
									</div>
									<div class="col-md-4">
										<label class="check"><input class="iradio" id="inactmonitorState" type="radio" name="monitor" value="1" /> Inactive</label>
									</div>
								</div>
							</div>													
						</div>
						<div>
							<div class="row col-md-2" style="padding: 0px;margin-top: 20px;padding-bottom: 3px;">
								<b>Min. Duration</b><br/>
								<div class="col-md-4" style="padding: 0px;">
									<input type="text" class="form-control"  type="number" id="min" name="min" value=""  min="0" max="600"/>
								</div>
								<div class="col-md-1" style="padding-top: 6px;">
									<b>minutes</b>
								</div>
							</div>	
						</div>
						<br/><br/>
						<div class="row col-md-12 form-group" style="padding-top: 21px;">
							<button type="submit" class="btn btn-primary" id="filter" value="Filter" style="width: 115px;">Filter</button>
					  	</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div>
			<div class="panel-body">
				<div class="mainflightDetailedTimeline">
					<div id="biteTimelineLoading" class="text-center">
						<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...
					</div>
					<div id="flightDetailedTimeline" class="flightTimeline"></div>
					<div style="text-align: center; padding: 20px;">
						<button class="btn btn-primary" id="more">Load More</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

    <div class="modal" data-sound="alert" id="modalTable">
		<div class="modal-dialog modal-lg" style="background-color: #f5f5f5;margin-top: 92px; border-radius: 6px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;width: 800px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Reboot Information</h4>
				</div> 
				<div class="modal-body" style="height:198px;width:857px;">
					<div class="col-md-12">
						<ul class="mailList">
							<li class="commandedli"><b>Commanded Reboot</b>(<img
								src="../img/commandedReset.png" class="rebootImg"
								style="width: 16px; height: 16px">)
								<ul style="list-style-type: none" class="commadedul">
									<li class="col-md-3 lli listPadding"><img
										src="../img/swInstallReset.png"
										style="width: 16px; height: 16px">SW INSTALL RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/powerBtnReset.png"
										style="width: 16px; height: 16px">POWER BUTTON RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/crewReset.png" style="width: 16px; height: 16px">CREW
										RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/defaultResetReason.png"
										style="width: 16px; height: 16px">DEFAULT RESET</li>
								</ul>
							</li>
						</ul>
						<br /> <br />
						<ul class="mailList">
							<li class="uncommandedli"><b>Uncommanded Reboot</b>(<img
								src="../img/uncommandedReset.png" class="rebootImg"
								style="width: 16px; height: 16px">)
								<ul style="list-style-type: none" class="uncommadedul">
									<li class="col-md-3 lli listPadding"><img
										src="../img/systemReboot.png" style="width: 16px; height: 16px">SYSTEM
										REBOOT</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/skColdReset.png" style="width: 16px; height: 16px">SK
										COLD RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/powerColdReset.png"
										style="width: 16px; height: 16px">POWER COLD RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/glibcReset.png" style="width: 16px; height: 16px">GLIBC
										RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/ducatiReset.png" style="width: 16px; height: 16px">DUCATI
										RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/fsCheckReset.png" style="width: 16px; height: 16px">FS
										CHECK RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/kernelPanicReset.png"
										style="width: 16px; height: 16px">KERNEL PANIC RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/adbRebootReset.png"
										style="width: 16px; height: 16px">ADB REBOOT RESET</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/unknownReset.png" style="width: 16px; height: 16px">UNKNOWN
										RESET REASON</li>
									<li class="col-md-3 lli listPadding"><img
										src="../img/defaultResetReason.png"
										style="width: 16px; height: 16px">DEFAULT RESET</li>
								</ul>
							</li>
						</ul>
					</div>
    				<br/>					
				</div>
			</div>
		</div>
	</div>
