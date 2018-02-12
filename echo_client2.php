<pre><?php
//header( 'Content-type: text/html; charset=utf-8' );
set_time_limit( 60 * 5 );	//	( 60 * 60 * 24 );

ob_implicit_flush( TRUE );
define('SOCK_MSG_DELIM',"_*_");
$myUID = 51;
$maxDuration = 30.0;

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
	//echoAndFlush( "[ SND to $socketName ]:$msgData" );
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

$i = 15;
$conn = stream_socket_client( "tcp://127.0.0.1:45456", $errno, $errstr );
echoAndFlush( "Connected to: " . stream_socket_get_name( $conn, TRUE ) . " as " . stream_socket_get_name( $conn, FALSE ) );

echoAndFlush( "I am UID $myUID" );
SocketWrite( $conn, "HELO", $myUID );

$start = microtime( true );
$end = $start + $maxDuration;

$lastPingSent = 0.0;
			
do
{
	$now = microtime( true );
			
	if( ( $now - $lastPingSent ) > 1.0 )
	{
		SocketWrite( $conn, "PING" );
		$lastPingSent = $now;
	}
	
	//	Test sending a msg:
	if( $i++ % 18 == 0 )
	{
		SocketWrite( $conn, "MSG", "Time is $now" );
	}
	
	stream_set_blocking( $conn, 0 );
	$msg = fread( $conn, 1024 );
	
	$msgsToParse = explode( SOCK_MSG_DELIM, $msg );
	foreach( $msgsToParse as $nextMessage )
	{
		if( strlen( $nextMessage ) < 2 )
			continue;
		
		SplitPacket( $nextMessage, $msgType, $msgUID, $msgData );
		
		if( strncmp( $msgType, 'PONG', 4 ) == 0 )
		{
			//	Ignore!
		}
		else if( strncmp( $msgType, 'MSG', 3 ) == 0 )
		{
			//	Ignore!
			echoAndFlush( $msgData );
		}
		else
		{
			//	Ignore?
		}
	}
	
	usleep( 70000 );	//	0.1s
}
while( $now < $end );

fclose( $conn );
echoAndFlush( "end!" );

?></pre>