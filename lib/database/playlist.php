<?php

function requestModifyVid($author, &$id, $title, $link)
{
    sanitize_sql_inputs($author, $title, $link);
    $title = str_replace("'", "''", $title);

    if (isset($id) && $id != 0) {
        $query = "UPDATE PlaylistVideo SET Title='$title', Author='$author', Link='$link' WHERE ID='$id'";
        // log_sql($query);
        global $db;
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            // error_log(__FUNCTION__ . " updated by $author! $id, $title, $link");
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed! $id, $title, $link");
        }
    } else {
        $query = "INSERT INTO PlaylistVideo VALUES ( NULL, '$title', '$author', '$link', NOW() )";
        // log_sql($query);
        global $db;
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            // error_log(__FUNCTION__ . " created by $author! $title, $link");
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed2! $title, $link");
        }
    }

    return $id;
}
