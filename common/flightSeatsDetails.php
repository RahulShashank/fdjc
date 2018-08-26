<?php

function getFlightSeats($firstClassSeats) {
	
	//1A,1E,1F,1K,2A,2E,2F,2K
	$seats = array ();
	$f_cSeats = explode(",",$firstClassSeats);
	$arrlength = count ( $f_cSeats );
	for($i = 0; $i < $arrlength; $i ++) {
		
		$seats[$i] = "SVDU".$f_cSeats[$i];
		
	}
	return $seats;
}

?>