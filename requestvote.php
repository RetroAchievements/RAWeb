<?php
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "utva" ) )
	{
		echo "FAILED";
		return;
	}
	
	//	Sanitise!
	$user = $_POST["u"];
	$token = $_POST["t"];
	$vote = $_POST["v"];
	$achID = $_POST["a"];
	
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		if( applyVote( $user, $achID, $vote ) )
		{
			$query = "SELECT VotesPos, VotesNeg FROM Achievements WHERE AchievementID='$achID'";
			$dbResult = s_mysql_query( $query );
			if( $dbResult !== FALSE )
			{
				$data = mysqli_fetch_assoc($dbResult);
				echo $data['VotesPos'] . "/" . $data['VotesNeg'];
			}
			else
			{
				echo "FAILED!!!";
			}
			//echo "OK";
		}
		else
		{
			echo "FAILED!!";
		}
	}
	else
	{
		echo "FAILED!";
	}
	
?>