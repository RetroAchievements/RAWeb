<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	//	NOW USE requestupdatelb.php
	
	// //	Sanitise!
	// if( !ValidatePOSTChars( "ucimtdfb" ) )
	// {
		// echo "FAILED";
		// return;
	// }
	
	// $user = $_POST["u"];
	// $cookie = $_POST["c"];
	// $lbID = $_POST["i"];
	// $lbMem = $_POST["m"];
	// $lbTitle = $_POST["t"];
	// $lbDescription = $_POST["d"];
	// $lbFormat = $_POST["f"];
	// $lbLowerIsBetter = $_POST["b"];
	
	// //	Somewhat elevated privileges to modify an LB:
	// if( validateUser_cookie( $user, $cookie, 3 ) )
	// {
		// if( submitLBData( $user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter ) )
		// {
			// echo "OK";
			// exit;
		// }
		// else
		// {	
			// echo "FAILED!";
			// exit;
		// }
	// }
	// else
	// {
		// log_email( __FUNCTION__ . " FAILED! Cannot validate $user. Are you a developer?" );
		// echo "FAILED! Cannot validate $user. Are you a developer?";
		// exit;
	// }
	
?>
