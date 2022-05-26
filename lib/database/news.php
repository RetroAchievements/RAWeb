<?php

function GetLatestNewsData($offset, $count): array
{
    sanitize_sql_inputs($offset, $count);

    $retVal = [];

    $query = "SELECT ID, UNIX_TIMESTAMP(Timestamp) AS TimePosted, Title, Payload, Author, Link, Image
              FROM News
              ORDER BY TimePosted DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        settype($nextData['ID'], 'integer');
        settype($nextData['TimePosted'], 'integer');
        $retVal[] = $nextData;
    }

    return $retVal;
}

function getLatestNewsHeaders($offset, $numItems, &$dataOut): int
{
    $dataOut = GetLatestNewsData($offset, $numItems);
    return count($dataOut);
}

function requestModifyNews($author, &$id, $title, $payload, $link, $imageURL): int
{
    sanitize_sql_inputs($payload, $link, $imageURL, $title);

    global $db;

    if (isset($id) && $id != 0) {
        $query = "UPDATE News SET Title='$title', Payload='$payload', Link='$link', Image='$imageURL' WHERE ID='$id'";
        $dbResult = mysqli_query($db, $query);

        if (!$dbResult) {
            log_sql_fail();
        }
    } else {
        $query = "INSERT INTO News (Timestamp, Title, Payload, Author, Link, Image) 
                    VALUES (NOW(), '$title', '$payload', '$author', '$link', '$imageURL')";
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
        }
    }

    return $id;
}
