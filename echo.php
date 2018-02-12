<pre><?php
//header( 'Content-type: text/html; charset=utf-8' );
set_time_limit( 60 * 5 );	//	( 60 * 60 * 24 );

ob_implicit_flush( TRUE );
define('SOCK_MSG_DELIM',"_*_");

$testServer = stream_socket_server( 'tcp://127.0.0.1:45456', $errno, $errstr );
stream_set_blocking( $testServer, 0 );

function echoAndFlush( $str )
{
	echo $str . "\n";
	ob_flush();
}

function FormatSocketMessage( $type, $myUID, $content )
{
	return "$type,$myUID,$content" . SOCK_MSG_DELIM;
}

function SocketWrite( $connection, $type, $msg = '' )
{
	$socketName = stream_socket_get_name( $connection, TRUE );
	$msgData = "$type,0,$msg";
	echoAndFlush( "[ SND to $socketName ]:$msgData" );
	fputs( $connection, $msgData . SOCK_MSG_DELIM );
}

function SplitPacket( $packetData, &$msgType, &$msgUID, &$msgData )
{
	//echoAndFlush( $packetData );
	
	$splitData = explode( ',', $packetData );
	$msgType = $splitData[0];	//	Type
	$msgUID = $splitData[1];	//	UID
	$msgData = $splitData[2];	//	Data (concat tbd?)
}

$connections = Array();
$chatQueue = new SplQueue();

while( true )
{
	$selfName = stream_socket_get_name( $testServer, FALSE );
	$numConnections = count( $connections );
	echoAndFlush( "[ Ready ]: ($numConnections) at $selfName" );
		
	//	Read any new connections!
	if( $conn = @stream_socket_accept( $testServer, 0.0 ) )
	{
		stream_set_blocking( $conn, 0 );
		$socketName = stream_socket_get_name( $conn, TRUE );
		
		if( !isset( $connections[ $socketName ] ) )
		{
			$connections[ $socketName ][ 'Connection' ] = $conn;
			$connections[ $socketName ][ 'LastPing' ] = microtime( true );
			$connections[ $socketName ][ 'UserID' ] = 0;
			$chatQueue->push( "[SERVER]: $socketName has joined" );
		}
	}
	
	//	Receive messages from all attached clients
	foreach( $connections as $nextConnName => &$nextConn )
	{	
		$conn = $nextConn[ 'Connection' ];
		$message = fread( $conn, 1024 );
		
		$now = microtime( true );
		if( strlen( $message ) > 1 )
		{
			echoAndFlush( "[ RCV from $nextConnName ]:$message" );
			
			$msgsToParse = explode( SOCK_MSG_DELIM, $message );
			foreach( $msgsToParse as $nextMsg )
			{
				if( strlen( $nextMsg ) < 2 )
					continue;
				
				SplitPacket( $nextMsg, $msgType, $msgUID, $msgData );
				
				if( strncmp( $msgType, "PING", 4 ) == 0 )
				{
					$nextConn[ 'LastPing' ] = $now;
					SocketWrite( $conn, "PONG" );
				}
				else if( strncmp( $msgType, "HELO", 4 ) == 0 )
				{
					//	UID Decl
					$nextConn[ 'UserID' ] = $msgData;
					echoAndFlush( "[ Storing UID ]:$nextConnName is $msgData" );
				}
				else if( strncmp( $msgType, "MSG", 3 ) == 0 )
				{
					$newChatMsg = $nextConn[ 'UserID' ] . ": $msgData";
					echoAndFlush( "[ Adding Chat Msg ]:$newChatMsg" );
					$chatQueue->push( $newChatMsg );
				}
				else
				{
					//	Unrecognised msg type?
					echoAndFlush( "[ Warning ]:Unrecognised msg type '$msgType'" );
				}
			}
		}
		
		if( ( $now - $nextConn[ 'LastPing' ] ) > 8.0 )
		{
			//	Drop: too long has passed since last heartbeat
			fclose( $conn );
			echoAndFlush( "[ Dropping Connection ]:$nextConnName" );
			unset( $connections[ $nextConnName ] );	//	safe?
			continue;
		}
	}
	
	//	Push out any required messages to all listeners
	while( !$chatQueue->IsEmpty() )
	{
		//	Push this message to all listeners
		foreach( $connections as $nextConnName => &$nextConn )
		{
			$conn = $nextConn[ 'Connection' ];
			SocketWrite( $conn, "MSG", $chatQueue->top() );
		}
			
		$chatQueue->pop();
	}
	
	$timeInterval = 0.033;	//	0.033s
	usleep( 1000000 * $timeInterval );
}

?></pre>
