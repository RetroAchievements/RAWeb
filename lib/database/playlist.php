<?php

function requestModifyVid($author, &$id, $title, $link): int
{
    sanitize_sql_inputs($author, $title, $link);
    $title = str_replace("'", "''", $title);

    if (isset($id) && $id != 0) {
        $query = "UPDATE PlaylistVideo SET Title='$title', Author='$author', Link='$link' WHERE ID='$id'";
        global $db;
        $dbResult = mysqli_query($db, $query);

        if (!$dbResult) {
            log_sql_fail();
        }
    } else {
        $query = "INSERT INTO PlaylistVideo VALUES ( NULL, '$title', '$author', '$link', NOW() )";
        global $db;
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
        }
    }

    return $id;
}
