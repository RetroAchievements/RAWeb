<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!requestInput('term')) {
    exit;
}

global $db;
$searchTerm = $_REQUEST['term'];
sanitize_sql_inputs($searchTerm);

$source = requestInputQuery('p', null);

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

if ($source == 'gamecompare' || $source == 'user') {
    $query = "SELECT '3' AS Type, ua.User AS ID, ua.User AS Title FROM UserAccounts AS ua
				  WHERE ua.User LIKE '%$searchTerm%'
				  ORDER BY ua.User
				  LIMIT 0, 10 ";
}
if ($source == 'game') {
    $query = "SELECT '1' AS Type, gd.ID, CONCAT( gd.Title, \" (\", c.Name, \")\" ) AS Title, gd.ImageIcon AS Icon
                FROM GameData AS gd
                LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                WHERE gd.Title LIKE '%$searchTerm%'
                GROUP BY ach.GameID, gd.Title
                ORDER BY gd.Title
                LIMIT 0, 10";
}
if ($source == 'achievement') {
    $query = "SELECT '2' AS Type, ach.ID, ach.Title, gd.ImageIcon AS Icon
          FROM Achievements AS ach
          LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
          WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchTerm%'
          ORDER BY ach.Title
          LIMIT 0, 10";
}

$dbResult = s_mysql_query($query);

$dataOut = [];

if ($dbResult !== false && mysqli_num_rows($dbResult) > 0) {
    while ($nextRow = mysqli_fetch_array($dbResult)) {
        $nextTitle = $nextRow['Title'] ?? null;
        $nextID = $nextRow['ID'] ?? null;
        $nextIcon = $nextRow['Icon'] ?? null;

        if ($nextRow['Type'] == 1) {
            $dataOut[] = [
                'label' => $nextTitle,
                'id' => $nextID,
                'icon' => $nextIcon,
                'mylink' => "/game/$nextID",
                'category' => "Games",
            ];
        } else {
            if ($nextRow['Type'] == 2) {
                $dataOut[] = [
                    'label' => $nextTitle,
                    'id' => $nextID,
                    'icon' => $nextIcon,
                    'mylink' => "/achievement/$nextID",
                    'category' => "Achievements",
                ];
            } else { //	$nextRow['Type'] == 3
                $dataOut[] = [
                    'label' => $nextTitle,
                    'id' => $nextID,
                    'icon' => $nextIcon,
                    'mylink' => "/user/$nextID",
                    'category' => "Users",
                ];
            }
        }
    }
}

echo json_encode($dataOut);
flush();
