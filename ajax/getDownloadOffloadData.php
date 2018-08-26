<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";

/**
 * Handles all the requests from DownloadoffloadData.php
 *
 * @author
 *
 */
class DownloadoffloadDAO {
	public $dbConnection;
	public $action = '';
	public $airlineId = '';
	public $airlineName = '';
	public $platform = '';
	public $configType = '';
	public $aircraftId = '';
	public $tailsign = '';
	public $airIds = '';
	
	/**
	 * Constructor which creates the database connectivity
	 */
	public function __construct($dbConnection) {
		$this->dbConnection = $dbConnection;
		$this->action = $_REQUEST ['action'];
		$this->airlineId = $_REQUEST ['airlineId'];
		$this->airlineName = $_REQUEST ['airlineName'];
		$this->platform = $_REQUEST ['platform'];
		$this->configType = $_REQUEST ['configType'];
		$this->tailsign = $_REQUEST ['tailsign'];
		$this->aircraftId = $_REQUEST ['aircraftId'];
		$this->airIds = $_REQUEST ['airIds'];
		$this->status = $_REQUEST ['status'];
		
		$this->exportstatus = $_GET ['status'];
		$this->exportformat = $_GET ['format'];
		
		$this->status = $_REQUEST ['status'];
		$this->tailsign = $_REQUEST ['tailsign'];
		$this->depAirport = $_REQUEST ['depAirport'];
		$this->arrAirport = $_REQUEST ['arrAirport'];
		$this->depStartDate = $_REQUEST ['depStartDate'];
		$this->depEndDate = $_REQUEST ['depEndDate'];
		$this->arrStartDate = $_REQUEST ['arrStartDate'];
		$this->arrEndDate = $_REQUEST ['arrEndDate'];
		$this->offloadStartDate = $_REQUEST ['offloadStartDate'];
		$this->offloadEndDate = $_REQUEST ['offloadEndDate'];
		$this->uploadStartDate = $_REQUEST ['uploadStartDate'];
		$this->uploadEndDate = $_REQUEST ['uploadEndDate'];
		$this->source = $_REQUEST ['source'];
		$this->failureReason = $_REQUEST ['failureReason'];
		// $this->airlineIds=rtrim(implode(",", $_SESSION['airlineIds']), ",");
		// $this->airlineIds=rtrim($this->airlineIds, ",");
	}
	// $airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
	// $airlineIds = rtrim($airlineIds, ",");
	
	// public $airlineQuery = "SELECT id, acronym FROM airlines where id IN ($this->airlineIds) order by acronym";
	// public $airlineQuery = "SELECT id, acronym FROM airlines where id IN ($this->airIds) order by acronym";
	
	/**
	 * Main method called to start the process
	 */
	public function hadleRequest() {
		if ($this->action == 'GET_AIRLINES') {
			$this->getAirlines ();
		} elseif ($this->action == 'GET_PLATFORMS') {
			$this->getPlatformsForAirline ();
		} elseif ($this->action == 'GET_CONFIG_TYPE') {
			$this->getConfigTypesForAirlineAndPlatform ();
		} elseif ($this->action == 'GET_TAILSIGN') {
			$this->getTailsignForAirline ();
		} elseif ($this->action == 'getDepArrAirportList') {
			$this->getDepArrAirportList ();
		} elseif ($this->action == 'getOffloads') {
			$this->getDowdloadOffloads ();
		} elseif ($this->action == 'exportOffloads') {
			$this->getExportOffloads ();
		}
	}
	
	/**
	 * Retrieves distinct platform for an airline
	 */
	public function getAirlines() {
		if ($this->airIds == - 1 || $this->airIds=="") {
			$airlineQuery = "SELECT id, name FROM airlines order by name";
		} else {
			$airlineQuery = "SELECT id, name FROM airlines where id IN ($this->airIds) order by name";
		}
		$result = mysqli_query ( $this->dbConnection, $airlineQuery );
		
		$platforms = array ();
		if ($result) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$platforms [] = $row;
			}
		}
		
		echo $json_response = json_encode ( $platforms );
	}
	
	/**
	 * Retrieves distinct platform for an airline
	 */
	public function getPlatformsForAirline() {
		if ($this->airlineId != '') {
			$query = "select distinct platform from aircrafts where airlineID=$this->airlineId order by platform";
		} else {
			$query = "select distinct platform from aircrafts  order by platform";
		}
		
		$result = mysqli_query ( $this->dbConnection, $query );
		
		$platforms = array ();
		if ($result) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$platforms [] = $row;
			}
		}
		
		echo $json_response = json_encode ( $platforms );
	}
	
	/**
	 * Retrieves distinct Config Type for an airline and Platform
	 */
	public function getConfigTypesForAirlineAndPlatform() {
		if ($this->airlineId != '' && $this->platform != '') {
			$query = "select distinct Ac_Configuration as configType from aircrafts where airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration <> '' order by Ac_Configuration";
		} else if ($this->airlineId != '' && $this->platform == '') {
			$query = "select distinct Ac_Configuration as configType from aircrafts where airlineID=$this->airlineId and Ac_Configuration <> '' order by Ac_Configuration";
		} else {
			$query = "select distinct Ac_Configuration as configType from aircrafts where Ac_Configuration <> '' order by Ac_Configuration";
		}
		
		$result = mysqli_query ( $this->dbConnection, $query );
		
		$platforms = array ();
		if ($result) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$platforms [] = $row;
			}
		}
		
		echo $json_response = json_encode ( $platforms );
	}
	
	/**
	 * Retrieves the Tailsign for an aircraft, platform and configType combination.
	 */
	public function getTailsignForAirline() {
		if ($this->airlineId != '' && $this->platform != '' && $this->configType != '') {
			$query = "select distinct(tailsign) from aircrafts where airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' order by tailsign";
		} else if ($this->airlineId != '' && $this->platform == '' && $this->configType == '') {
			$query = "select distinct(tailsign) from aircrafts where airlineID=$this->airlineId order by tailsign";
		} else if ($this->airlineId != '' && $this->platform == '' && $this->configType != '') {
			$query = "select distinct(tailsign) from aircrafts where airlineID=$this->airlineId and Ac_Configuration='$this->configType' order by tailsign";
		} else if ($this->airlineId != '' && $this->platform != '' && $this->configType == '') {
			$query = "select distinct(tailsign) from aircrafts where airlineID=$this->airlineId and platform='$this->platform' order by tailsign";
		} else {
			$query = "select distinct(tailsign) from aircrafts  order by tailsign";
		}
		
		$result = mysqli_query ( $this->dbConnection, $query );
		
		$platforms = array ();
		if ($result) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$platforms [] = $row;
			}
		}
		
		echo $json_response = json_encode ( $platforms );
	}
	
	/**
	 * Retrieves the Tailsign for an aircraft, platform and configType combination.
	 */
	function getDepArrAirportList() {
		if ($this->airlineId != '' && $this->tailsign != '') {
			$query = "select (SELECT group_concat(DISTINCT depAirport) FROM $mainDB.offloads_master WHERE airlineID=$this->airlineId AND tailsignFound='$this->tailsign' order by depAirport) as depAirportList, (SELECT group_concat(DISTINCT arrAirport) FROM $mainDB.offloads_master WHERE airlineID=$this->airlineId AND tailsignFound='$this->tailsign' order by arrAirport) as arrAirportList";
		} elseif ($this->airlineId == '' && $this->tailsign != '') {
			$query = "select (SELECT group_concat(DISTINCT depAirport) FROM $mainDB.offloads_master WHERE tailsignFound='$this->tailsign' order by depAirport) as depAirportList, (SELECT group_concat(DISTINCT arrAirport) FROM $mainDB.offloads_master WHERE tailsignFound='$this->tailsign' order by arrAirport) as arrAirportList";
		} elseif ($this->airlineId != '' && $this->tailsign == '') {
			$query = "select (SELECT group_concat(DISTINCT depAirport) FROM $mainDB.offloads_master WHERE airlineID=$this->airlineId order by depAirport) as depAirportList, (SELECT group_concat(DISTINCT arrAirport) FROM $mainDB.offloads_master WHERE airlineID=$this->airlineId order by arrAirport) as arrAirportList";
		} else {
			$query = "select (SELECT group_concat(DISTINCT depAirport) FROM $mainDB.offloads_master order by depAirport) as depAirportList, (SELECT group_concat(DISTINCT arrAirport) FROM $mainDB.offloads_master order by arrAirport) as arrAirportList";
		}
		
		$result = mysqli_query ( $this->dbConnection, $query );
		
		$arr = array ();
		if ($result and mysqli_num_rows ( $result ) > 0) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$arr [] = $row;
			}
		}
		// JSON-encode the response
		$json_response = json_encode ( $arr );
		
		// # Return the response
		echo $json_response;
	}
	function getDowdloadOffloads() {
		$finalArray = array ();
		
		error_log ( $this->status );
		if ($this->IsNullOrEmptyString ( $this->status )) {
			$processedData = $this->getProcessedData ( $this->airlineId, $this->dbConnection, $mainDB, $_REQUEST );
			$rejectedData = $this->getRejectedData ( $this->airlineId, $this->dbConnection, $mainDB, $_REQUEST );
			$finalArray = array_merge ( $processedData, $rejectedData );
		} else if (! $this->IsNullOrEmptyString ( $this->status ) and $this->status == 'Processed') {
			$finalArray = $this->getProcessedData ( $this->airlineId, $this->dbConnection, $mainDB, $_REQUEST );
		} else if (! $this->IsNullOrEmptyString ( $this->status ) and $this->status == 'Rejected') {
			$finalArray = $this->getRejectedData ( $this->airlineId, $this->dbConnection, $mainDB, $_REQUEST );
		}
		
		// JSON-encode the response
		$json_response = json_encode ( $finalArray );
		
		// # Return the response
		echo $json_response;
	}
	function getExportOffloads() {
		$finalArray = array ();
		
		if ($this->IsNullOrEmptyString ( $this->exportstatus )) {
			$processedData = $this->getProcessedDataForExport ( $this->airlineId, $this->dbConnection, $mainDB, $_GET );
			$rejectedData = $this->getRejectedDataForExport ( $this->airlineId, $this->dbConnection, $mainDB, $_GET );
			$finalArray = array_merge ( $processedData, $rejectedData );
		} else if (! $this->IsNullOrEmptyString ( $this->exportstatus ) and $this->exportstatus == 'Processed') {
			$finalArray = $this->getProcessedDataForExport ( $this->airlineId, $this->dbConnection, $mainDB, $_GET );
		} else if (! $this->IsNullOrEmptyString ( $this->exportstatus ) and $this->exportstatus == 'Rejected') {
			$finalArray = $this->getRejectedDataForExport ( $this->airlineId, $this->dbConnection, $mainDB, $_GET );
		}
		
		switch ($this->exportformat) {
			case "xls" :
				header ( "Content-Type: application/vnd.ms-excel" );
				header ( "Content-Disposition: attachment; filename=\"OffloadsExport.xls\"" );
				$this->ExportXLSFile ( $finalArray );
				exit ();
			case "csv" :
				header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header ( "Content-type: text/csv" );
				header ( "Content-Disposition: attachment; filename=\"OffloadsExport.csv\"" );
				$this->ExportCSVFile ( $finalArray );
				exit ();
			default :
				die ( "Unknown format : " . $this->exportformat );
				break;
		}
	}
	
	/**
	 * Retrieves the data from offloads_master with status as Processed.
	 *
	 * @param unknown $alId        	
	 * @param unknown $dbConnection        	
	 * @param unknown $mainDB        	
	 * @param unknown $request        	
	 */
	function getProcessedData($alId, $dbConnection, $mainDB, $request) {
		error_log ( "inside getProcessedData method" );
		
		error_log ( 'status: ' . $this->status );
		error_log ( 'tailsign: ' . $this->tailsign );
		error_log ( 'depAirport: ' . $this->depAirport );
		error_log ( 'arrAirport: ' . $this->arrAirport );
		error_log ( 'depStartDate: ' . $this->depStartDate );
		error_log ( 'depEndDate: ' . $this->depEndDate );
		error_log ( 'arrStartDate: ' . $this->arrStartDate );
		error_log ( 'arrEndDate: ' . $this->arrEndDate );
		error_log ( 'offloadStartDate: ' . $this->offloadStartDate );
		error_log ( 'offloadEndDate: ' . $this->offloadEndDate );
		error_log ( 'uploadStartDate: ' . $this->uploadStartDate );
		error_log ( 'uploadEndDate: ' . $this->uploadEndDate );
		error_log ( 'airlineId...' . $this->airlineId );
		$query = "SELECT om.id, om.fileName, om.fileSize, om.status, om.tailsignInFile, om.tailsignFound as tailsign, om.flightNumber, om.depTime, om.arrTime, om.depAirport, om.arrAirport, om.failureReason, om.offloadDate, om.uploadedTime, om.remarks, om.flightLegIds, om.source, ac.id as aircraftId";
		$query .= " FROM offloads_master om, aircrafts ac WHERE ";
		if ($this->airlineId != '') {
			$query .= "om.airlineId=$this->airlineId AND ";
		}
		$query .= "om.tailsignFound=ac.tailsign AND om.status='Processed'";
		
		if (! empty ( $this->tailsign )) {
			// $this->tailsign = array_filter ( $this->tailsign );
			$query .= " AND om.tailsignFound = '$this->tailsign'";
			/*
			 * if (! empty ( $this->tailsign )) {
			 * $query .= " AND (om.tailsignInFile in (";
			 * foreach ( $this->tailsign as $ts ) {
			 * $query .= "'$ts',";
			 * }
			 * $query = rtrim ( $query, ',' );
			 * $query .= ") OR ";
			 *
			 * $query .= " om.tailsignFound in (";
			 * foreach ( $this->tailsign as $ts ) {
			 * $query .= "'$ts',";
			 * }
			 * $query = rtrim ( $query, ',' );
			 * $query .= "))";
			 * }
			 */
		}
		
		if (! $this->IsNullOrEmptyString ( $this->depAirport )) {
			$query .= " AND om.depAirport='$this->depAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->arrAirport )) {
			$query .= " AND om.arrAirport='$this->arrAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->depStartDate ) and ! $this->IsNullOrEmptyString ( $this->depEndDate )) {
			$query .= " AND (om.depTime between '$this->depStartDate 00:00:00' and '$this->depEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->arrStartDate ) and ! $this->IsNullOrEmptyString ( $this->arrEndDate )) {
			$query .= " AND (om.arrTime between '$this->arrStartDate 00:00:00' and '$this->arrEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->offloadStartDate ) and ! $this->IsNullOrEmptyString ( $this->offloadEndDate )) {
			$query .= " AND (om.offloadDate between '$this->offloadStartDate 00:00:00' and '$this->offloadEndDate 23:59:59')";
		}		
		
		if (! $this->IsNullOrEmptyString ( $this->uploadStartDate ) and ! $this->IsNullOrEmptyString ( $this->uploadEndDate )) {
			$query .= " AND (om.uploadedTime between '$this->uploadStartDate 00:00:00' and '$this->uploadEndDate 23:59:59')";
		} else {
			// $query .= " AND om.uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
		}
		if (! $this->IsNullOrEmptyString ( $this->source ) and ! $this->IsNullOrEmptyString ( $this->source )) {
			$query .= " AND om.source = '$this->source'";
		}
		if (! $this->IsNullOrEmptyString ( $this->failureReason ) and ! $this->IsNullOrEmptyString ( $this->failureReason )) {
			$query .= " AND om.failureReason = '$this->failureReason'";
		}
		
		$query .= " ORDER BY om.uploadedTime DESC";
		$result = mysqli_query ( $this->dbConnection, $query );
		error_log ( "Query to get Processed Data: " . $query, 0 );
		
		$arr = array ();
		if ($result and mysqli_num_rows ( $result ) > 0) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				if ($row ['status'] == "Processed" && empty ( $row ['flightNumber'] )) {
					$flightLegId = getFirstToken ( $row ['flightLegIds'] );
					$row ['flightNumber'] = $flightLegId;
				}
				
				$arr [] = $row;
			}
		}
		return $arr;
	}
	
	/**
	 * Retrieves the data from offloads_master with status as Processed.
	 *
	 * @param unknown $alId        	
	 * @param unknown $dbConnection        	
	 * @param unknown $mainDB        	
	 * @param unknown $request        	
	 * @return unknown[]
	 */
	function getRejectedData($alId, $dbConnection, $mainDB, $request) {
		error_log ( 'inside getRejectedData method' );
		
		/*
		 * $depAirport = $_REQUEST ['depAirport'];
		 * $arrAirport = $_REQUEST ['arrAirport'];
		 * $depStartDate = $_REQUEST ['depStartDate'];
		 * $depEndDate = $_REQUEST ['depEndDate'];
		 * $arrStartDate = $_REQUEST ['arrStartDate'];
		 * $arrEndDate = $_REQUEST ['arrEndDate'];
		 * $offloadStartDate = $_REQUEST ['offloadStartDate'];
		 * $offloadEndDate = $_REQUEST ['offloadEndDate'];
		 * $uploadStartDate = $_REQUEST ['uploadStartDate'];
		 * $uploadEndDate = $_REQUEST ['uploadEndDate'];
		 */
		
		error_log ( 'status: ' . $status );
		error_log ( 'tailsign: ' . $this->tailsign );
		error_log ( 'depAirport: ' . $depAirport );
		error_log ( 'arrAirport: ' . $arrAirport );
		error_log ( 'depStartDate: ' . $depStartDate );
		error_log ( 'depEndDate: ' . $depEndDate );
		error_log ( 'arrStartDate: ' . $arrStartDate );
		error_log ( 'arrEndDate: ' . $arrEndDate );
		error_log ( 'offloadStartDate: ' . $offloadStartDate );
		error_log ( 'offloadEndDate: ' . $offloadEndDate );
		error_log ( 'uploadStartDate: ' . $uploadStartDate );
		error_log ( 'uploadEndDate: ' . $uploadEndDate );
		
		$query = "SELECT om.id, om.fileName, om.fileSize, om.status, om.tailsignInFile, om.tailsignFound as tailsign, om.flightNumber, om.depTime, om.arrTime, om.depAirport, om.arrAirport, om.failureReason, om.offloadDate, om.uploadedTime, om.remarks, om.flightLegIds, om.source";
		$query .= " FROM offloads_master om, aircrafts ac WHERE ";
		if ($this->airlineId != '') {
			$query .= "om.airlineId=$this->airlineId AND ";
		}
		$query .= " om.status='Rejected'";
		if (! empty ( $this->tailsign )) {
			
			$query .= " AND om.tailsignFound = '$this->tailsign'";
		}
		if (! $this->IsNullOrEmptyString ( $this->depAirport )) {
			$query .= " AND om.depAirport='$this->depAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->arrAirport )) {
			$query .= " AND om.arrAirport='$this->arrAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->depStartDate ) and ! $this->IsNullOrEmptyString ( $this->depEndDate )) {
			$query .= " AND (om.depTime between '$this->depStartDate 00:00:00' and '$this->depEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->arrStartDate ) and ! $this->IsNullOrEmptyString ( $this->arrEndDate )) {
			$query .= " AND (om.arrTime between '$this->arrStartDate 00:00:00' and '$this->arrEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->offloadStartDate ) and ! $this->IsNullOrEmptyString ( $this->offloadEndDate )) {
			$query .= " AND (om.offloadDate between '$this->offloadStartDate 00:00:00' and '$this->offloadEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $this->uploadStartDate ) and ! $this->IsNullOrEmptyString ( $this->uploadEndDate )) {
			$query .= " AND (om.uploadedTime between '$this->uploadStartDate 00:00:00' and '$this->uploadEndDate 23:59:59')";
		} else {
			// $query .= " AND om.uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
		}
		if (! $this->IsNullOrEmptyString ( $this->source ) and ! $this->IsNullOrEmptyString ( $this->source )) {
			$query .= " AND om.source = '$this->source'";
		}
		if (! $this->IsNullOrEmptyString ( $this->failureReason ) and ! $this->IsNullOrEmptyString ( $this->failureReason )) {
			$query .= " AND om.failureReason = '$this->failureReason'";
		}
		
		$query .= " ORDER BY om.uploadedTime DESC";
		$result = mysqli_query ( $this->dbConnection, $query );
		error_log ( "Query to get Rejected data: " . $query, 0 );
		
		$arr = array ();
		if ($result and mysqli_num_rows ( $result ) > 0) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				$arr [] = $row;
			}
		}
		return $arr;
	}
	
	/**
	 * Checks if the input string is empty or null
	 */
	function isNullOrEmptyString($str) {
		return (! isset ( $str ) || trim ( $str ) === '');
	}
	
	/**
	 * Prints the message in a line
	 */
	function echoline($msg) {
		echo "<br/>$msg<br/>";
	}
	function ExportXLSFile($records) {
		$heading = false;
		if (! empty ( $records ))
			foreach ( $records as $row ) {
				if (! $heading) {
					// display field/column names as a first row
					echo implode ( "\t", array_keys ( $row ) ) . "\n";
					$heading = true;
				}
				echo implode ( "\t", array_values ( $row ) ) . "\n";
			}
		exit ();
	}
	function ExportCSVFile($records) {
		// create a file pointer connected to the output stream
		$fh = fopen ( 'php://output', 'w' );
		$heading = false;
		if (! empty ( $records ))
			foreach ( $records as $row ) {
				if (! $heading) {
					// output the column headings
					fputcsv ( $fh, array_keys ( $row ) );
					$heading = true;
				}
				// loop over the rows, outputting them
				fputcsv ( $fh, array_values ( $row ) );
			}
		fclose ( $fh );
	}
	function getFirstToken($input) {
		$token = strtok ( $input, "," );
		
		if ($token !== false) {
			return $token;
		}
	}
	
	/**
	 * Retrieves the processed data from offloads_master for export
	 *
	 * @param unknown $alId        	
	 * @param unknown $dbConnection        	
	 * @param unknown $mainDB        	
	 * @param unknown $GET        	
	 */
	function getProcessedDataForExport($alId, $dbConnection, $mainDB, $request) {
		error_log ( 'inside getProcessedDataForExport method' );
		
		// echo $status;
		$airlineId = $request ['airlineId'];
		
		$tailsign = $request ['tailsign'];
		// echo $tailsign;
		
		$depAirport = $request ['depAirport'];
		// echo $depAirport;
		
		$arrAirport = $request ['arrAirport'];
		// echo $arrAirport;
		
		$depStartDate = $request ['depStartDate'];
		// echo $depStartDate;
		
		$depEndDate = $request ['depEndDate'];
		// echo $depEndDate;
		
		$arrStartDate = $request ['arrStartDate'];
		// echo $arrStartDate;
		
		$arrEndDate = $request ['arrEndDate'];
		// echo $arrEndDate;
		
		$offloadStartDate = $request ['offloadStartDate'];
		// echo $offloadStartDate;
		
		$offloadEndDate = $request ['offloadEndDate'];
		// echo $offloadEndDate;
		
		$uploadStartDate = $request ['uploadStartDate'];
		// echo $uploadStartDate;
		
		$uploadEndDate = $request ['uploadEndDate'];
		// echo $uploadEndDate ;
		
		$format = $request ['format'];
		// echo $format;
		
		$source = $request ['source'];
		// 
		
		$failureReason = $request ['failureReason'];
		
		$query = "SELECT fileName as FileName, fileSize as FileSize, status as Status, tailsignFound as TailSign, flightNumber as FlightNumber, depTime as DepartureTime, arrTime as ArrivalTime, depAirport as DepartureAirport, arrAirport as ArrivalAirport, offloadDate as OffloadDate, uploadedTime as UploadTime, failureReason as FailureReason, remarks as Remarks, source as Source";
		//$query .= " FROM $mainDB.offloads_master WHERE airlineId=$this->airlineId AND status='Processed'";
		$query .= " FROM $mainDB.offloads_master WHERE ";
		if (! $this->IsNullOrEmptyString ( $airlineId )) {
			$query .= " airlineId=$airlineId AND ";
		}
		$query .= "  status='Processed'";
		/* 
		if (! empty ( $tailsign ) && $tailsign !== 'null') {
			$tailsign = explode ( ",", $tailsign );
			
			if (! empty ( $tailsign )) {
				$query .= " AND (tailsignInFile in (";
				foreach ( $tailsign as $ts ) {
					$query .= "'$ts',";
				}
				$query = rtrim ( $query, ',' );
				$query .= ") OR ";
				
				$query .= " tailsignFound in (";
				foreach ( $tailsign as $ts ) {
					$query .= "'$ts',";
				}
				$query = rtrim ( $query, ',' );
				$query .= "))";
			}
		} */
		if (! empty ( $this->tailsign )) {
				
			$query .= " AND om.tailsignFound = '$this->tailsign'";
		}
		
		if (! $this->IsNullOrEmptyString ( $depAirport )) {
			$query .= " AND depAirport='$depAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $arrAirport )) {
			$query .= " AND arrAirport='$arrAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $depStartDate ) and ! $this->IsNullOrEmptyString ( $depEndDate )) {
			$query .= " AND (depTime between '$depStartDate 00:00:00' and '$depEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $arrStartDate ) and ! $this->IsNullOrEmptyString ( $arrEndDate )) {
			$query .= " AND (arrTime between '$arrStartDate 00:00:00' and '$arrEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $offloadStartDate ) and ! $this->IsNullOrEmptyString ( $offloadEndDate )) {
			$query .= " AND (offloadDate between '$offloadStartDate 00:00:00' and '$offloadEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $uploadStartDate ) and ! $this->IsNullOrEmptyString ( $uploadEndDate )) {
			$query .= " AND (uploadedTime between '$uploadStartDate 00:00:00' and '$uploadEndDate 23:59:59')";
		} else {
			$query .= " AND uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
		}
		
		if (! $this->IsNullOrEmptyString ( $source ) and ! $this->IsNullOrEmptyString ( $source )) {
			$query .= " AND source = '$source'";
		}
		if (! $this->IsNullOrEmptyString ( $failureReason ) and ! $this->IsNullOrEmptyString ( $failureReason )) {
			$query .= " AND failureReason = '$failureReason'";
		}
		
		$query .= " ORDER BY uploadedTime DESC";
		$result = mysqli_query ( $dbConnection, $query );
		error_log ( "Query to get processed data for export: " . $query, 0 );
		
		$arr = array ();
		if ($result and mysqli_num_rows ( $result ) > 0) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				// echo "FileSize: ". $row['FileSize'] . "<br/>";
				$row ['FileSize'] = strval ( round ( $row ['FileSize'] / 1024, 2 ) ) . " KB";
				$arr [] = $row;
			}
		}
		
		return $arr;
	}
	
	/**
	 * Retrieves the rejected data from offloads_master for export
	 *
	 * @param unknown $alId        	
	 * @param unknown $dbConnection        	
	 * @param unknown $mainDB        	
	 * @param unknown $GET        	
	 */
	function getRejectedDataForExport($alId, $dbConnection, $mainDB, $request) {
		error_log ( 'getRejectedDataForExport method entered' );
		// echo $status;
		$airlineId = $request ['airlineId'];
		
		$tailsign = $request ['tailsign'];
		// echo $tailsign;
		
		$depAirport = $request ['depAirport'];
		// echo $depAirport;
		
		$arrAirport = $request ['arrAirport'];
		// echo $arrAirport;
		
		$depStartDate = $request ['depStartDate'];
		// echo $depStartDate;
		
		$depEndDate = $request ['depEndDate'];
		// echo $depEndDate;
		
		$arrStartDate = $request ['arrStartDate'];
		// echo $arrStartDate;
		
		$arrEndDate = $request ['arrEndDate'];
		// echo $arrEndDate;
		
		$offloadStartDate = $request ['offloadStartDate'];
		// echo $offloadStartDate;
		
		$offloadEndDate = $request ['offloadEndDate'];
		// echo $offloadEndDate;
		
		$uploadStartDate = $request ['uploadStartDate'];
		// echo $uploadStartDate;
		
		$uploadEndDate = $request ['uploadEndDate'];
		// echo $uploadEndDate ;
		
		$format = $request ['format'];
		// echo $format;
		
		$query = "SELECT fileName as FileName, fileSize as FileSize, status as Status, tailsignFound as TailSign, flightNumber as FlightNumber, depTime as DepartureTime, arrTime as ArrivalTime, depAirport as DepartureAirport, arrAirport as ArrivalAirport, offloadDate as OffloadDate, uploadedTime as UploadTime, failureReason as FailureReason, remarks as Remarks, source as Source";
		$query .= " FROM $mainDB.offloads_master WHERE ";
		if (! $this->IsNullOrEmptyString ( $airlineId )) {
			$query .= " AND airlineId=$airlineId";
		}
		 $query .= " AND status='Rejected'";
		
	if (! empty ( $this->tailsign )) {
				
			$query .= " AND om.tailsignFound = '$this->tailsign'";
		}
		
		if (! $this->IsNullOrEmptyString ( $depAirport )) {
			$query .= " AND depAirport='$depAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $arrAirport )) {
			$query .= " AND arrAirport='$arrAirport'";
		}
		
		if (! $this->IsNullOrEmptyString ( $depStartDate ) and ! $this->IsNullOrEmptyString ( $depEndDate )) {
			$query .= " AND (depTime between '$depStartDate 00:00:00' and '$depEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $arrStartDate ) and ! $this->IsNullOrEmptyString ( $arrEndDate )) {
			$query .= " AND (arrTime between '$arrStartDate 00:00:00' and '$arrEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $offloadStartDate ) and ! $this->IsNullOrEmptyString ( $offloadEndDate )) {
			$query .= " AND (offloadDate between '$offloadStartDate 00:00:00' and '$offloadEndDate 23:59:59')";
		}
		
		if (! $this->IsNullOrEmptyString ( $uploadStartDate ) and ! $this->IsNullOrEmptyString ( $uploadEndDate )) {
			$query .= " AND (uploadedTime between '$uploadStartDate 00:00:00' and '$uploadEndDate 23:59:59')";
		} else {
			$query .= " AND uploadedTime between DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW()";
		}
		
		if (! $this->IsNullOrEmptyString ( $source ) and ! $this->IsNullOrEmptyString ( $source )) {
			$query .= " AND source = '$source'";
		}
		if (! $this->IsNullOrEmptyString ( $failureReason ) and ! $this->IsNullOrEmptyString ( $failureReason )) {
			$query .= " AND failureReason = '$failureReason'";
		}
		
		$query .= " ORDER BY uploadedTime DESC";
		$result = mysqli_query ( $dbConnection, $query );
		error_log ( "Query to get processed data for export: " . $query, 0 );
		
		$arr = array ();
		if ($result and mysqli_num_rows ( $result ) > 0) {
			while ( $row = mysqli_fetch_assoc ( $result ) ) {
				// echo "FileSize: ". $row['FileSize'] . "<br/>";
				$row ['FileSize'] = strval ( round ( $row ['FileSize'] / 1024, 2 ) ) . " KB";
				$arr [] = $row;
			}
		}
		
		return $arr;
	}
}

// echo 'Airline Ids: '. $_POST['airIds'];
$downloadoffloadDAO = new DownloadoffloadDAO ( $dbConnection );
$downloadoffloadDAO->hadleRequest ();

?>