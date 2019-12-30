<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!isset($_REQUEST['term'])) {
    exit;
}

$searchTerm = mysqli_real_escape_string($db, $_REQUEST['term']);

$source = seekGET('p', "");
if ($source == 'gamecompare' || $source == 'user') {
    //	User only
    $query = "SELECT '3' AS Type, ua.User AS ID, ua.User AS Title FROM UserAccounts AS ua
				  WHERE ua.User LIKE '%$searchTerm%'
				  ORDER BY ua.User
				  LIMIT 0, 10 ";
} else {
    if ($source == 'game') {
        //	Game only
        $query = "SELECT '1' AS Type, gd.ID, CONCAT( gd.Title, \" (\", c.Name, \")\" ) AS Title, gd.ImageIcon AS Icon
					FROM GameData AS gd
					LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID
					LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
					WHERE gd.Title LIKE '%$searchTerm%'
					GROUP BY ach.GameID
					ORDER BY gd.Title
					LIMIT 0, 10";
    } else {
        if ($source == 'achievement') {
            //	Ach only
            $query = "SELECT '2' AS Type, ach.ID, ach.Title, gd.ImageIcon AS Icon
				  FROM Achievements AS ach
				  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
				  WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchTerm%'
				  ORDER BY ach.Title
				  LIMIT 0, 10";
        } else {
            $query = "(
		SELECT '1' AS Type, gd.ID, CONCAT( gd.Title, \" (\", c.Name, \")\" ) AS Title, gd.ImageIcon AS Icon
		FROM GameData AS gd
		LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID
		LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
		WHERE gd.Title LIKE '%$searchTerm%'
		GROUP BY ach.GameID
		ORDER BY gd.Title
		LIMIT 0, 7
		)
		UNION
		(
		SELECT '2' AS Type, ach.ID, ach.Title, gd.ImageIcon AS Icon
		FROM Achievements AS ach
		LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
		WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchTerm%'
		ORDER BY ach.Title
		LIMIT 0, 7
		)
		UNION
		(
		SELECT '3' AS Type, ua.User AS ID, ua.User AS Title, CONCAT( CHAR(47), \"UserPic\", CHAR(47), ua.User, \".png\" ) AS Icon
		FROM UserAccounts AS ua
		WHERE ua.User LIKE '%$searchTerm%'
		ORDER BY ua.User
		LIMIT 0, 7
		) ";
        }
    }
}

$dbResult = s_mysql_query($query);

$dataOut = [];

if ($dbResult !== false && mysqli_num_rows($dbResult) > 0) {
    while ($nextRow = mysqli_fetch_array($dbResult)) {
        $nextTitle = $nextRow['Title'];
        $nextID = $nextRow['ID'];
        $nextIcon = $nextRow['Icon'];

        if ($nextRow['Type'] == 1) {
            $dataOut[] = [
                'label' => $nextTitle,
                'id' => $nextID,
                'icon' => $nextIcon,
                'mylink' => "/Game/$nextID",
                'category' => "Games",
            ];
        } else {
            if ($nextRow['Type'] == 2) {
                $dataOut[] = [
                    'label' => $nextTitle,
                    'id' => $nextID,
                    'icon' => $nextIcon,
                    'mylink' => "/Achievement/$nextID",
                    'category' => "Achievements",
                ];
            } else //	$nextRow['Type'] == 3
            {
                $dataOut[] = [
                    'label' => $nextTitle,
                    'id' => $nextID,
                    'icon' => $nextIcon,
                    'mylink' => "/User/$nextID",
                    'category' => "Users",
                ];
            }
        }
    }
}

echo json_encode($dataOut);
flush();
