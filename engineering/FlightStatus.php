<div>
	<div class="row">
		<div class="col-xs-3" style="padding-left: 0px;margin-bottom: 13px;">
			<div class="form-group">
				<select id="statics" class="form-control selectpicker show-tick "	onchange="showStaticsData();" value="selectedStatics" >					
					<option value="headend">Head-End</option>					
					<option value="pcus">PCUs</option>
					<option value="qsebs">QSEB-SDB</option>
					<option value="svdus">SVDUs</option>
				</select>
			</div>
		</div>
	</div>
	<!-- Tab panes -->
	<div >
		<div class="panel panel-default">											
			<div class="panel-body">
			<!-- Head-End Tab -->
			<div id="headend">
				<br>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Resets Hostnames</h5>
						<div class="chart-panel">
							<div id="headEndResetHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndResetHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Resets Types</h5>
						<div class="chart-panel">
							<div id="headEndResetCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndResetCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failure Hostnames</h5>
						<div class="chart-panel">
							<div id="headEndFailureHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndFailureHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failure Codes</h5>
						<div class="chart-panel">
							<div id="headEndFailureCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndFailureCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Fault Hostnames</h5>
						<div class="chart-panel">
							<div id="headEndFaultHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndFaultHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Fault Codes</h5>
						<div class="chart-panel">
							<div id="headEndFaultCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndFaultCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top App. Events Hostnames</h5>
						<div class="chart-panel">
							<div id="headEndExtAppHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndExtAppHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top App. Events Codes</h5>
						<div class="chart-panel">
							<div id="headEndExtAppCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="headEndExtAppCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- SVDUs Tab -->
			<div id="svdus">
				<br>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Resets Hostnames</h5>
						<div class="chart-panel">
							<div id="svduResetHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduResetHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Resets Types</h5>
						<div class="chart-panel">
							<div id="svduResetCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduResetCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failures Hostnames</h5>
						<div class="chart-panel">
							<div id="svduFailureHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduFailureHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failure Codes</h5>
						<div class="chart-panel">
							<div id="svduFailureCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduFailureCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Faults Hostnames</h5>
						<div class="chart-panel">
							<div id="svduFaultHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduFaultHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Fault Codes</h5>
						<div class="chart-panel">
							<div id="svduFaultCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduFaultCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top App. Events Hostnames</h5>
						<div class="chart-panel">
							<div id="svduExtAppHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduExtAppHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top App. Events Codes</h5>
						<div class="chart-panel">
							<div id="svduExtAppCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="svduExtAppCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- PCUs Tab -->
			<div id="pcus">
				<br>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Resets Hostnames</h5>
						<div class="chart-panel">
							<div id="tpmuResetHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuResetHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Resets Types</h5>
						<div class="chart-panel">
							<div id="tpmuResetCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuResetCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failures Hostnames</h5>
						<div class="chart-panel">
							<div id="tpmuFailureHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuFailureHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failure Codes</h5>
						<div class="chart-panel">
							<div id="tpmuFailureCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuFailureCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Faults Hostnames</h5>
						<div class="chart-panel">
							<div id="tpmuFaultHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuFaultHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Fault Codes</h5>
						<div class="chart-panel">
							<div id="tpmuFaultCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuFaultCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Applications Hostnames</h5>
						<div class="chart-panel">
							<div id="tpmuExtAppHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuExtAppHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Applications Codes</h5>
						<div class="chart-panel">
							<div id="tpmuExtAppCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="tpmuExtAppCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- QSEB-SDB Tab -->
			<div id="qsebs">
				<br>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Resets Hostnames</h5>
						<div class="chart-panel">
							<div id="qsebResetHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebResetHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Resets Types</h5>
						<div class="chart-panel">
							<div id="qsebResetCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebResetCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failures Hostnames</h5>
						<div class="chart-panel">
							<div id="qsebFailureHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebFailureHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Failure Codes</h5>
						<div class="chart-panel">
							<div id="qsebFailureCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebFailureCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Faults Hostnames</h5>
						<div class="chart-panel">
							<div id="qsebFaultHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebFaultHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Fault Codes</h5>
						<div class="chart-panel">
							<div id="qsebFaultCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebFaultCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row placeholders">
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Applications Hostnames</h5>
						<div class="chart-panel">
							<div id="qsebExtAppHostnamesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebExtAppHostnamesChart"></canvas>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 placeholder">
						<h5 class="chart-title">Top Applications Codes</h5>
						<div class="chart-panel">
							<div id="qsebExtAppCodesLoading">
								<img src="../img/ajaxLoading.gif"> Loading...
							</div>
							<div>
								<canvas id="qsebExtAppCodesChart"></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div></div>
	</div>
</div>
