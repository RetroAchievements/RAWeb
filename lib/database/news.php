<?php
function GetLatestNewsData($offset, $count)
{
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
    global $db;
    $payload = mysqli_real_escape_string($db, $payload);
    $link = mysqli_real_escape_string($db, $link);
    $imageURL = mysqli_real_escape_string($db, $imageURL);
    $title = mysqli_real_escape_string($db, $title);

    if (isset($id) && $id != 0) {
        $query = "UPDATE News SET Title='$title', Payload='$payload', Link='$link', Image='$imageURL' WHERE ID='$id'";
        log_sql($query);
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            error_log($query);
            error_log(__FUNCTION__ . " updated by $author! $id, $title, $payload");
        } else {
            error_log($query);
            error_log(__FUNCTION__ . " failed! $id, $title, $payload");
        }
    } else {
        $query = "INSERT INTO News (Timestamp, Title, Payload, Author, Link, Image) 
                    VALUES (NOW(), '$title', '$payload', '$author', '$link', '$imageURL')";
        log_sql($query);
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            error_log($query);
            error_log(__FUNCTION__ . " created by $author! $title, $payload");
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
            error_log($query);
            error_log(__FUNCTION__ . " failed2! $title, $payload");
        }
    }

    return $id;
}
