<?php

function getConsoleList()
{
    $query = "SELECT ID, Name FROM Console";
    $dbResult = s_mysql_query($query);

    $consoleList = [];

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $consoleList[$db_entry['ID']] = $db_entry['Name'];
        }
    }

    return $consoleList;
}

function getConsoleIDs()
{
    $retVal = [];

    $query = "SELECT ID, Name FROM Console";
    $dbResult = s_mysql_query($query);

    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $retVal[] = $db_entry;
    }

    return $retVal;
}
