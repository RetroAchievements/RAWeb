<?php

namespace RA;

// class that contains server-side feed functionality
class Feed
{
    /*
     The retrieveNewMessages method retrieves the new messages that have
     been posted to the server.
     - the $id parameter is sent by the client and it
     represents the id of the last message received by the client. Messages
     more recent by $id will be fetched from the database and returned to
     the client in XML format.
    */
    public function retrieveNewMessages($id = 0, $user = null)
    {
        // escape the variable data
        //$id = $this->mMysqli->real_escape_string($id);
        // compose the SQL query that retrieves new messages

        $mode = 'global';
        if (isset($user)) {
            $mode = 'friends';
        }

        $numMessages = getFeed($user, 40, 0, $dataOut, $id, $mode);

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
                $feedUser = $row['User'];
                $feedUserPoints = $row['RAPoints'];
                $feedUserMotto = $row['Motto'];
                $feedData = $row['data'];
                $feedData2 = $row['data2'];
                $feedGameTitle = htmlspecialchars($row['GameTitle']);
                $feedGameID = $row['GameID'];
                $feedGameIcon = $row['GameIcon'];
                $feedConsoleName = $row['ConsoleName'];
                $feedAchTitle = $row['AchTitle'];
                $feedAchDesc = $row['AchDesc'];
                $feedAchBadge = $row['AchBadge'];
                $feedAchPoints = $row['AchPoints'];
                $feedLBTitle = htmlspecialchars($row['LBTitle']);
                $feedLBDesc = htmlspecialchars($row['LBDesc']);
                $feedLBFormat = $row['LBFormat'];
                $feedCommentUser = $row['CommentUser'];
                $feedCommentUserPoints = $row['CommentPoints'];
                $feedCommentUserMotto = htmlspecialchars($row['CommentMotto']);
                $feedComment = htmlspecialchars($row['Comment']);
                $feedCommentPostedAt = $row['CommentPostedAt'];

                $response .= '<feedID>' . $feedID . '</feedID>' .
                    '<feedTimestamp><![CDATA[' . $feedTimestamp . ']]></feedTimestamp>' .
                    '<feedActType>' . $feedActType . '</feedActType>' .
                    '<feedUser><![CDATA[' . $feedUser . ']]></feedUser>' .
                    '<feedUserPoints><![CDATA[' . $feedUserPoints . ']]></feedUserPoints>' .
                    '<feedUserMotto><![CDATA[' . $feedUserMotto . ']]></feedUserMotto>' .
                    '<feedData><![CDATA[' . $feedData . ']]></feedData>' .
                    '<feedData2><![CDATA[' . $feedData2 . ']]></feedData2>' .
                    '<feedGameTitle><![CDATA[' . $feedGameTitle . ']]></feedGameTitle>' .
                    '<feedGameID><![CDATA[' . $feedGameID . ']]></feedGameID>' .
                    '<feedGameIcon><![CDATA[' . $feedGameIcon . ']]></feedGameIcon>' .
                    '<feedConsoleName><![CDATA[' . $feedConsoleName . ']]></feedConsoleName>' .
                    '<feedAchTitle><![CDATA[' . $feedAchTitle . ']]></feedAchTitle>' .
                    '<feedAchDesc><![CDATA[' . $feedAchDesc . ']]></feedAchDesc>' .
                    '<feedAchBadge><![CDATA[' . $feedAchBadge . ']]></feedAchBadge>' .
                    '<feedAchPoints><![CDATA[' . $feedAchPoints . ']]></feedAchPoints>' .
                    '<feedLBTitle><![CDATA[' . $feedLBTitle . ']]></feedLBTitle>' .
                    '<feedLBDesc><![CDATA[' . $feedLBDesc . ']]></feedLBDesc>' .
                    '<feedLBFormat><![CDATA[' . $feedLBFormat . ']]></feedLBFormat>' .
                    '<feedCommentUser><![CDATA[' . $feedCommentUser . ']]></feedCommentUser>' .
                    '<feedCommentUserPoints><![CDATA[' . $feedCommentUserPoints . ']]></feedCommentUserPoints>' .
                    '<feedCommentUserMotto><![CDATA[' . $feedCommentUserMotto . ']]></feedCommentUserMotto>' .
                    '<feedComment><![CDATA[' . $feedComment . ']]></feedComment>' .
                    '<feedCommentPostedAt><![CDATA[' . $feedCommentPostedAt . ']]></feedCommentPostedAt>';
            }
            // close the database connection as soon as possible
            //$result->close();
        }

        // finish the XML response and return it
        $response = $response . '</response>';
        return $response;
    }
}
