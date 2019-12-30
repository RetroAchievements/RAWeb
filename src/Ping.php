<?php

namespace RA;

// class that contains server-side chat functionality
class Ping
{
    /*
     The retrieveNewMessages method retrieves the new messages that have
     been posted to the server.
     - the $id parameter is sent by the client and it
     represents the id of the last message received by the client. Messages
     more recent by $id will be fetched from the database and returned to
     the client in XML format.
    */
    public function retrieveNewMessages($id = 0)
    {
        // escape the variable data
        $id = $this->mMysqli->real_escape_string($id);
        // compose the SQL query that retrieves new messages

        $numMessages = getGlobalFeed(40, 0, $dataOut, $id);

        // build the XML response
        $response = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $response .= '<response>';
        // check to see if we have any results
        if ($numMessages > 0) {
            // loop through all the fetched messages to build the result message
            for ($i = 0; $i < $numMessages; $i++) {
                $row = $dataOut[$i];

                $feedID = $row['ID'];
                $feedTimestamp = $row['timestamp'];
                $feedActType = $row['activitytype'];
                $feedUser = $row['user'];
                $feedData = $row['data'];
                $feedData2 = $row['data2'];
                $feedGameTitle = htmlspecialchars($row['GameTitle']);
                $feedGameID = $row['GameID'];
                $feedAchTitle = $row['AchTitle'];
                $feedAchBadge = $row['AchBadge'];
                $feedAchPoints = $row['AchPoints'];
                $feedLBTitle = htmlspecialchars($row['LBTitle']);
                $feedLBFormat = $row['LBFormat'];
                $feedCommentUser = $row['CommentUser'];
                $feedComment = htmlspecialchars($row['Comment']);
                $feedCommentPostedAt = $row['CommentPostedAt'];

                $response .= '<feedID>' . $feedID . '</feedID>' .
                    '<feedTimestamp><![CDATA[' . $feedTimestamp . ']]></feedTimestamp>' .
                    '<feedActType>' . $feedActType . '</feedActType>' .
                    '<feedUser><![CDATA[' . $feedUser . ']]></feedUser>' .
                    '<feedData><![CDATA[' . $feedData . ']]></feedData>' .
                    '<feedData2><![CDATA[' . $feedData2 . ']]></feedData2>' .
                    '<feedGameTitle><![CDATA[' . $feedGameTitle . ']]></feedGameTitle>' .
                    '<feedGameID><![CDATA[' . $feedGameID . ']]></feedGameID>' .
                    '<feedAchTitle><![CDATA[' . $feedAchTitle . ']]></feedAchTitle>' .
                    '<feedAchBadge><![CDATA[' . $feedAchBadge . ']]></feedAchBadge>' .
                    '<feedAchPoints><![CDATA[' . $feedAchPoints . ']]></feedAchPoints>' .
                    '<feedLBTitle><![CDATA[' . $feedLBTitle . ']]></feedLBTitle>' .
                    '<feedLBFormat><![CDATA[' . $feedLBFormat . ']]></feedLBFormat>' .
                    '<feedCommentUser><![CDATA[' . $feedCommentUser . ']]></feedCommentUser>' .
                    '<feedComment><![CDATA[' . $feedComment . ']]></feedComment>' .
                    '<feedCommentPostedAt><![CDATA[' . $feedCommentPostedAt . ']]></feedCommentPostedAt>';
            }
            // close the database connection as soon as possible
            $result->close();
        }

        // finish the XML response and return it
        $response = $response . '</response>';
        return $response;
    }
}
