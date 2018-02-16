<?php
namespace RA;

// load error handling module
//require_once('error_handler.php');

// class that contains server-side chat functionality
class Chat
{
	// database handler
	private $mMysqli;

	// constructor opens database connection
	function __construct()
	{
		// connect to the database
//error_log( "Log: " . $_SERVER["SERVER_NAME"] );
		//if( stristr( $_SERVER["SERVER_NAME"], "localhost" ) == FALSE )
		//	$this->mMysqli = new mysqli("racoredb.cnqhi42h5bsx.eu-west-1.rds.amazonaws.com", "immensegames", "74747474", 'RACore');  //	Live
		//else
		$this->mMysqli = new mysqli("localhost", "Scott", "74747474", 'RACore');    //	Home/dev
	}

	// destructor closes database connection
	public function __destruct()
	{
		$this->mMysqli->close();
	}

	/*
	 The postMessages method inserts a message into the database
	 - $name represents the name of the user that posted the message
	 - $messsage is the posted message
	 - $color contains the color chosen by the user
	*/
	public function postMessage( $name, $message )
	{
		// escape the variable data for safely adding them to the database
		$name = $this->mMysqli->real_escape_string($name);
		$message = $this->mMysqli->real_escape_string($message);
		//$message = preg_replace('/[^(\x20-\x7F)]*/','', $message );

		// build the SQL query that adds a new message to the server
		$query = "INSERT INTO Chat(Submitted, User, Message)
			  VALUES (NOW(), \"$name\", \"$message\" )";

		//error_log( $query );

		// execute the SQL query
		$result = $this->mMysqli->query($query);
	}

	/*
	 The retrieveNewMessages method retrieves the new messages that have
	 been posted to the server.
	 - the $id parameter is sent by the client and it
	 represents the id of the last message received by the client. Messages
	 more recent by $id will be fetched from the database and returned to
	 the client in XML format.
	*/
	public function retrieveNewMessages( $id = 0, $maxMessages = 50 )
	{
		// escape the variable data
		$id = $this->mMysqli->real_escape_string($id);

		// compose the SQL query that retrieves new messages
		if ($id > 0) {
			// retrieve messages newer than $id
			$query = "
			SELECT ch.ID, ua.User, ua.RAPoints, ua.Motto, Message, UNIX_TIMESTAMP(ch.Submitted) AS Submitted
			FROM Chat AS ch
			LEFT JOIN UserAccounts AS ua ON ua.User = ch.User
			WHERE ch.ID > $id
			ORDER BY ch.ID ASC
			LIMIT 50";
		} else {
			// on the first load only retrieve the last $maxMessages messages from server
			$query = "
			SELECT ChatHistory.ID, ua.User, ua.RAPoints, ua.Motto, Message, Submitted
			FROM (
					SELECT ID, User, Message, UNIX_TIMESTAMP(Submitted) AS Submitted
					FROM Chat 
					ORDER BY ID DESC 
					LIMIT $maxMessages
					) AS ChatHistory
			LEFT JOIN UserAccounts AS ua ON ua.User = ChatHistory.User
					
			ORDER BY ChatHistory.ID ASC ";
		}

		// execute the query
		$result = $this->mMysqli->query($query);

		//if( ( $this->mMysqli->error !== NULL ) && ( strlen( $this->mMysqli->error ) > 2 ) )
		//error_log( $query );

		// build the XML response
		$response = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$response .= '<response>';

		// output the clear flag
		$response .= "<clear>false</clear>";

		// check to see if we have any results
		if ($result && $result->num_rows) {
			// loop through all the fetched messages to build the result message
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$id = $row['ID'];
				$user = htmlspecialchars($row['User']);
				$time = htmlspecialchars($row['Submitted']);
				$userPoints = htmlspecialchars($row['RAPoints']);
				$userMotto = htmlspecialchars($row['Motto']);
				$message = htmlspecialchars($row['Message']);

				$response .= "<id>$id</id>
                     <time>$time</time>
                     <name><![CDATA[$user]]></name>
                     <points>$userPoints</points>
                     <motto><![CDATA[$userMotto]]></motto>
                     <message><![CDATA[$message]]></message>";
			}

			//error_log( $response );

			// close the database connection as soon as possible
			$result->close();
		}

		// finish the XML response and return it
		$response = $response . '</response>';
		return $response;
	}

}
