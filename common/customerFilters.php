<?php
	function getFailuresToKeep() {
		return [];

		// return [
		// 		10042007006, // PID Missing (reported by a single SVDU-G4)
		// 	];
	}

	function getFailuresToRemove() {
		//return [];
		
		return [
				10042209001, // The SVDU-G4 reported a link failure on the network switch.
				10042007003, // Failed to stream AVOD from all servers/sources (reported by a single SVDU-G4)
				10042007006, // PID Missing (reported by a single SVDU-G4)
				10042007007, // AVOD Content not found (reported by a single SVDU-G4)
				10045308002, // CIDS Failure to communicate
				10042007004, // Failed to retrieve AVBS,VA,VOR parameters (reported by a single SVDU-G4)
				10042008002, // The 3D-MAP application reported a Server Connection failure on the SVDU-G4.
				// 10044046001, // Unexpected software part number reported on the DSU-D4.
				// 10044047001, // Expected software part number not reported by Config Check for DSU-D4.
				// 10042047001, // Expected software part number not reported by Config Check for SVDU-G4.
			];
	}

	function getFaultsToKeep() {
		return [];
	}

	function getFaultsToRemove() {
		 return [420209, 42007003];
		
		return [
				// 47, // SW PN Missing Error
				401, // Matching status DEAD LRU Host error
				402, // Dead link error
				42007003, // Communication fault between server and client
			];
	}
?>
