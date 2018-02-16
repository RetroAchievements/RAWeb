<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$nextBadgeFilename = file_get_contents( "BadgeIter.txt" );
//file_put_contents( "BadgeIter.txt", $nextBadgeFilename );
//echo $nextBadgeFilename;

$count = seekGET( 'c', 100 );
$offset = seekGET( 'o', $nextBadgeFilename-$count );

//$offset -= $count;

echo "Last $count uploads...</br>";
echo "<table><tbody>";
echo "<tr>";

$fromID = $offset;
$toID = $fromID+$count;

$written = 0;
for( $i = $fromID; $i < $toID; $i++ )
{
	if( ( $written%10 ) == 0 )
		echo "</tr><tr>";
	
	$filename = sprintf( "%05d", $i );
	
	echo "<td><img src='http://i.retroachievements.org/Badge/" . $filename . ".png'><br/>$filename</td>";
	
	$written++;
}
echo "</tr>";
echo "<tbody><table>";

echo "<h2>Request Upload Badge</h2>";
echo "<form method=post action='requestuploadbadge.php' enctype='multipart/form-data'>";
echo "<label for='file'>New badge:</label>";
echo "<input type='file' name='file' /><br/>";
echo "<input type='submit' value='Submit' />";
echo "</form>";

?>
