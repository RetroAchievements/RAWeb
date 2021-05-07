<?php

function GetLatestNewsData($offset, $count)
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

/**
 * @deprecated
 * @param mixed $offset
 * @param mixed $numItems
 * @param mixed $dataOut
 */
function getLatestNewsHeaders($offset, $numItems, &$dataOut)
{
    $dataOut = GetLatestNewsData($offset, $numItems);
    return count($dataOut);
}

function requestModifyNews($author, &$id, $title, $payload, $link, $imageURL)
{
    sanitize_sql_inputs($payload, $link, $imageURL, $title);

    global $db;

    if (isset($id) && $id != 0) {
        $query = "UPDATE News SET Title='$title', Payload='$payload', Link='$link', Image='$imageURL' WHERE ID='$id'";
        // log_sql($query);
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            // log_sql_fail();
            // error_log(__FUNCTION__ . " updated by $author! $id, $title, $payload");
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed! $id, $title, $payload");
        }
    } else {
        $query = "INSERT INTO News (Timestamp, Title, Payload, Author, Link, Image) 
                    VALUES (NOW(), '$title', '$payload', '$author', '$link', '$imageURL')";
        // log_sql($query);
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            // log_sql_fail();
            // error_log(__FUNCTION__ . " created by $author! $title, $payload");
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed2! $title, $payload");
        }
    }

    return $id;
}
