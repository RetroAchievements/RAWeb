<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$_MAX_CORE_POINTS = 400;
	$_MAX_CORE_ACHIEVEMENTS = 25;
	
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidatePOSTChars( "bdfghimptuwxyz" ) )
	{
		//	ALL required!!
		echo "FAILED";
		return;
	}
	
	$user 		= $_POST["u"];
	$token		= $_POST["p"];
	$id			= $_POST["i"];
	$gameID 	= $_POST["g"];
	$title 		= $_POST["t"];
	$desc 		= $_POST["d"];
	$memory 	= $_POST["m"];
	$points 	= $_POST["z"];
	$type 		= $_POST["f"];
	$badge		= $_POST["b"];
	$progress	= $_POST["w"];
	$progressMax= $_POST["x"];
	$progressFmt= $_POST["y"];
	$hash		= $_POST["h"];
	
	$testHash = md5( $user . "SECRET" . $id . "SEC" . $memory . $points . "RE2" . $points*3 );
	if( $hash !== $testHash )
	{
		//	malformed request
		error_log( "requestuploadachievement.php - malformed request by $user/$token/$id/$checksum/$title/$desc/$memory/$points/$type/$badge/$progress/$progressMax/$progressFmt/$hash" );
		error_log( "$hash given should be $testHash" );
		echo "FAILED";
		return;
	}
	
	//	sanity:
	settype( $points, 'integer' );
	settype( $type, 'integer' );
	settype( $id, 'integer' );
	
	if( $points > 200 || $points <= 0 )
	{
		//	Don't let this slip through
		echo "FAILED: Pick a new points value! $points is not appropriate.";
		return;
	}
	else
	{
		if( $type == 3 )
		{
			//	Do some Core-set checks...
			
			getAllocatedForGame( $gameID, $pointsAllocated, $numAchievements );
			
			
			if( $id != 0 )
			{
				//	An ID has already been given; we aren't introducing more achievements to this game, we are
				//	 adjusting existing achievements.
				$achData = GetAchievementData( $id );
				$existingPoints = $achData['Points'];
				$suggestedNewPoints = ( $pointsAllocated - $existingPoints ) + $points;
			}
			else
			{
				//	Introducing a brand new achievement to this game.
				if( $numAchievements >= $_MAX_CORE_ACHIEVEMENTS )
				{
					//	This will not be accepting submissions...
					echo "FAILED: Maximum amount of Core achievements allowed for a game is $_MAX_CORE_ACHIEVEMENTS, there is no more space in the Core set.";
					return;
				}
				
				$suggestedNewPoints = $pointsAllocated + $points ;
			}
			
			if( $suggestedNewPoints > $_MAX_CORE_POINTS )
			{
				//	Adding this on would push the total above 
				$leftToAlloc = $_MAX_CORE_POINTS - $pointsAllocated;
				if( $leftToAlloc < 0 )
					$leftToAlloc = 0;
				
				echo "FAILED: Maximum amount of Core points allowed for a game is $_MAX_CORE_POINTS, there are $leftToAlloc points left to allocate.";
				return;
			}
			
		}
	}

	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
                $errorOut = "";
		if( uploadNewAchievement( $user, $gameID, $title, $desc, $progress, $progressMax, $progressFmt, $points, $memory, $type, $id, $badge, $errorOut ) )
		{
			//	Great
			echo "OK:" . $id;
		}
		else
		{
			echo "FAILED";
		}
	}
	else
	{
		echo "INVALID USER/PASS!";
	}
?>
