<?php
	date_default_timezone_set("GMT");
	
	include("../database/connecti_database.php");
	include("../common/functions.php");
	require_once('../engineering/checkEngineeringPermission.php');
	
	$msg = "error";
	
	if($_POST['event'] != null) {
		$db = $_POST['db'];

		$type  = strtok($_POST['event'], '/');
		$query = "";

		if($type == 'FAIL_INFO') {
			$id = strtok('/');
			$query = "SELECT failureCode, failureDesc, severity, caText1, caProb1, caTime1, caText2, caProb2, caTime2, caText3, caProb3, caTime3 FROM $mainDB.sys_failureinfo WHERE failureCode = $id";
		} else if($type == 'FAI') {
			$id = strtok('/');
			$query = "SELECT a.failureCode, a.monitorState, b.failureDesc, b.severity, b.caText1, b.caProb1, b.caTime1, b.caText2, b.caProb2, b.caTime2, b.caText3, b.caProb3, b.caTime3 FROM $db.BIT_failure a, $mainDB.sys_failureinfo b WHERE idFailure = $id AND a.failureCode = b.failureCode";
		} else if($type == 'FAIS') {
			$id = strtok('/');
			$query = "SELECT a.failureCode, a.monitorState, b.failureDesc, b.failureImpact, b.severity, b.caText1, b.caText2,b.caText3 FROM $db.BIT_serviceFailure a, $mainDB.sys_serviceFailureInfo b WHERE idFailure = $id AND a.failureCode = b.failureCode";
		}
		
		if($query != '') {
			$selected = mysqli_select_db($dbConnection,$db)
				or die("Could not select ".$db);
			$result = mysqli_query($dbConnection, $query);

			if($result){
				if (mysqli_num_rows ($result) > 0) {
					$row = mysqli_fetch_array($result);
					$failureCode = $row['failureCode'];
					$failureDesc = $row['failureDesc'];
					$failureImpact = $row['failureImpact'];
					$severity = $row['severity'];
					$monitorState = getMonitorStateDesc($row['monitorState']);
					$caText1 = $row['caText1'];
					$caProb1 = $row['caProb1'];
					$caTime1 = $row['caTime1'];
					$caText2 = $row['caText2'];
					$caText3 = $row['caText3'];

					$msg = "<div align=\"left\" style=\"width: 100%; margin-left: auto; margin-right: auto;\">"
						."<span style=\"border-bottom: 1px dotted;\"><b>Failure code:</b></span> $failureCode"
						."<br><br><span style=\"border-bottom: 1px dotted;\"><b>Failure description</b></span>: $failureDesc"
						."<br><br><span style=\"border-bottom: 1px dotted;\"><b>Failure description</b></span>: $failureImpact"
						."<br><br><span style=\"border-bottom: 1px dotted;\"><b>Severity</b></span>: $severity";
					
					if($row['monitorState'] != '') {
						$msg .= "<br><br><span style=\"border-bottom: 1px dotted;\"><b>Monitor State</b></span>: $monitorState";
					}
					
					if($type == 'FAIS'){
						$msg .=	"<br>"
							."<br><b><span style=\"border-bottom: 1px dotted;\">Corrective action 1</span>:</b><br>$caText1";
					}else{
						$msg .=	"<br>"
							."<br><b><span style=\"border-bottom: 1px dotted;\">Corrective action 1</span> [Duration: $caTime1 min / Recovery: $caProb1%]:</b><br>$caText1";
					}
					
					if($type == 'FAIS'){
						$msg .= "<br>"
								."<br><b><span style=\"border-bottom: 1px dotted;\">Corrective action 2</span>:</b><br>$caText2";
					}else{
						if($caText2 != '') {
							$caProb2 = $row['caProb2'];
							$caTime2 = $row['caTime2'];
							$msg .= "<br>"
								."<br><b><span style=\"border-bottom: 1px dotted;\">Corrective action 2</span> [Duration: $caTime2 min / Recovery: $caProb2%]:</b><br>$caText2";
						}
					}

					if($type == 'FAIS'){
						$msg .= "<br>"
								."<br><b><span style=\"border-bottom: 1px dotted;\">Corrective action 3</span>:</b><br>$caText3";
					}else{
						if($caText3 != '') {
							$caProb3 = $row['caProb3'];
							$caTime3 = $row['caTime3'];
							$msg .= "<br>"
									."<br><span style=\"border-bottom: 1px dotted;\"><b>Corrective action 3</span> [Duration: $caTime3 min / Recovery: $caProb3%]:</b><br>$caText3";
						}
					}
					
					$msg .= "</div>";
				} else {
					$msg = "No description has been found in the database for failure code $id.";
				}
			}else{
				$msg = "Error in $query : " .mysqli_error();
			}
		} else {
			$msg = "";
		}

		echo $msg;
	}
?>
