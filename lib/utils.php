<?php
//	Interrogates $_GET
function seekGET( $key, $default = NULL )
{
    if( $_GET !== FALSE && array_key_exists( $key, $_GET ) )
        return $_GET[ $key ];
    else
        return $default;
}

function seekPOST( $key, $default = NULL )
{
    if( $_POST !== FALSE && array_key_exists( $key, $_POST ) )
        return $_POST[ $key ];
    else
        return $default;
}

function seekPOSTorGET( $key, $default = NULL, $type = NULL )
{
    if( $_POST !== FALSE && array_key_exists( $key, $_POST ) )
    {
        if( isset( $type ) )
            settype( $_POST[ $key ], $type );
        return $_POST[ $key ];
    }
    else if( $_GET !== FALSE && array_key_exists( $key, $_GET ) )
    {
        if( isset( $type ) )
            settype( $_GET[ $key ], $type );
        return $_GET[ $key ];
    }
    else
    {
        if( isset( $type ) )
            settype( $default, $type );
        return $default;
    }
}

function debug_string_backtrace()
{
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // Remove first item from backtrace as it's this function which
    // is redundant.
    $trace = preg_replace( '/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1 );

    //  PHP warning?
    // Renumber backtrace items.
    //$trace = preg_replace( '/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace );

    return $trace;
}

function log_email( $logMessage )
{
    $fullmsg = $logMessage . "\n" . debug_string_backtrace();
    error_log( $fullmsg );

    //if( !IsAtHome() )
    //mail_utf8( "Scott@retroachievements.org", "RetroAchievements.org", "noreply@retroachievements.org", "Error Log", $fullmsg );
}

function log_sql( $logMessage )
{
    if( IsAtHome() )
        error_log( $logMessage . "\n", 3, "storage/logs/queries.log" );
    else
        error_log( $logMessage . "\n", 3, getenv('DOC_ROOT')."storage/logs/queries.log" );
}

function log_sql_fail()
{
    global $db;

    error_log( mysqli_errno($db) . ": " . mysqli_error( $db ), 3, "storage/logs/queries.log" );
    error_log( "SQL failed: " . mysqli_error( $db ) );
    log_email( "SQL failed: " . mysqli_error( $db ) );
}

function SQL_ASSERT( $dbResult )
{
    if( $dbResult == FALSE )
    {
        global $db;
        error_log( "query failed:" . mysqli_error( $db ) );
        log_sql_fail();
    }
}

function sanitiseSQL( $query )
{
    if( strrchr( $query, ';' ) !== false )
    {
        error_log( __FUNCTION__ . " failed(;): query:$query" );
        return false;
    }
    else if( strrchr( $query, '/' ) !== false )
    {
        error_log( __FUNCTION__ . " failed(/): query:$query" );
        return false;
    }
    else if( strrchr( $query, '\\' ) !== false )
    {
        error_log( __FUNCTION__ . " failed(\\): query:$query" );
        return false;
    }
    else if( strstr( $query, "--" ) !== false )
    {
        error_log( __FUNCTION__ . " failed(--): query:$query" );
        return false;
    }
    else
    {
        return true;
    }
}

/**
 * @param $query
 * @return bool|mysqli_result
 */
function s_mysql_query( $query )
{
    global $db;
    if( sanitiseSQL( $query ) )
    {
        global $g_numQueries;
        $g_numQueries++;

        if( DUMP_SQL )
        {
            echo "$query<br/><br/>";
        }

        if( PROFILE_SQL )
        {
            ProfileStamp( $query );
        }

        return mysqli_query( $db, $query );
    }
    else
    {
        return FALSE;
    }
}

function utf8ize($d)
{
    if (is_array($d) || is_object($d))
        foreach ($d as &$v) $v = utf8ize($v);
    else
        return utf8_encode($d);

    return $d;
}

function rand_string( $length )
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = strlen( $chars );
    $str = '';
    for( $i = 0; $i < $length; $i++ )
        $str .= $chars[ rand( 0, $size - 1 ) ];

    return $str;
}

function getNiceTime( $timestamp, $locale = 'EN-GB' )
{
    setlocale( LC_ALL, $locale );
    return strftime( "%H:%M", $timestamp );
}

function getNiceDate( $timestamp, $justDay = FALSE, $locale = 'EN-GB' )
{
    setlocale( LC_ALL, $locale );

    $todayTimestampDate = strtotime( date( 'F j, Y' ) );
    $yesterdayTimestampDate = strtotime( date( "F j, Y", time() - 60 * 60 * 24 ) );

    //	Convert timestamp to day
    $timestampDate = strtotime( date( 'F j, Y' . $timestamp ) );

    if( $timestampDate == $todayTimestampDate )
        $dateOut = 'Today';
    else if( $timestampDate == $yesterdayTimestampDate )
        $dateOut = 'Yesterday';
    else
        $dateOut = strftime( "%d %b %Y", $timestamp );

    if( $justDay == FALSE )
        $dateOut .= strftime( ", %H:%M", $timestamp );

    return $dateOut;
}

function ConvertBMP2GD( $src, $dest = false )
{
    if( !($src_f = fopen( $src, "rb" )) )
    {
        return false;
    }
    if( !($dest_f = fopen( $dest, "wb" )) )
    {
        return false;
    }
    $header = unpack( "vtype/Vsize/v2reserved/Voffset", fread( $src_f, 14 ) );
    $info = unpack( "Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", fread( $src_f, 40 ) );

    extract( $info );
    extract( $header );

    if( $type != 0x4D42 )
    { // signature "BM"
        return false;
    }

    $palette_size = $offset - 54;
    $ncolor = $palette_size / 4;
    $gd_header = "";
    // true-color vs. palette
    $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
    $gd_header .= pack( "n2", $width, $height );
    $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
    if( $palette_size )
    {
        $gd_header .= pack( "n", $ncolor );
    }
    // no transparency
    $gd_header .= "\xFF\xFF\xFF\xFF";

    fwrite( $dest_f, $gd_header );

    if( $palette_size )
    {
        $palette = fread( $src_f, $palette_size );
        $gd_palette = "";
        $j = 0;
        while( $j < $palette_size )
        {
            $b = $palette{$j++};
            $g = $palette{$j++};
            $r = $palette{$j++};
            $a = $palette{$j++};
            $gd_palette .= "$r$g$b$a";
        }
        $gd_palette .= str_repeat( "\x00\x00\x00\x00", 256 - $ncolor );
        fwrite( $dest_f, $gd_palette );
    }

    $scan_line_size = (($bits * $width) + 7) >> 3;
    $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

    for( $i = 0, $l = $height - 1; $i < $height; $i++, $l-- )
    {
        // BMP stores scan lines starting from bottom
        fseek( $src_f, $offset + (($scan_line_size + $scan_line_align) * $l) );
        $scan_line = fread( $src_f, $scan_line_size );
        if( $bits == 24 )
        {
            $gd_scan_line = "";
            $j = 0;
            while( $j < $scan_line_size )
            {
                $b = $scan_line{$j++};
                $g = $scan_line{$j++};
                $r = $scan_line{$j++};
                $gd_scan_line .= "\x00$r$g$b";
            }
        }
        else if( $bits == 8 )
        {
            $gd_scan_line = $scan_line;
        }
        else if( $bits == 4 )
        {
            $gd_scan_line = "";
            $j = 0;
            while( $j < $scan_line_size )
            {
                $byte = ord( $scan_line{$j++} );
                $p1 = chr( $byte >> 4 );
                $p2 = chr( $byte & 0x0F );
                $gd_scan_line .= "$p1$p2";
            }
            $gd_scan_line = substr( $gd_scan_line, 0, $width );
        }
        else if( $bits == 1 )
        {
            $gd_scan_line = "";
            $j = 0;
            while( $j < $scan_line_size )
            {
                $byte = ord( $scan_line{$j++} );
                $p1 = chr( ( int ) (($byte & 0x80) != 0) );
                $p2 = chr( ( int ) (($byte & 0x40) != 0) );
                $p3 = chr( ( int ) (($byte & 0x20) != 0) );
                $p4 = chr( ( int ) (($byte & 0x10) != 0) );
                $p5 = chr( ( int ) (($byte & 0x08) != 0) );
                $p6 = chr( ( int ) (($byte & 0x04) != 0) );
                $p7 = chr( ( int ) (($byte & 0x02) != 0) );
                $p8 = chr( ( int ) (($byte & 0x01) != 0) );
                $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
            }
            $gd_scan_line = substr( $gd_scan_line, 0, $width );
        }

        fwrite( $dest_f, $gd_scan_line );
    }
    fclose( $src_f );
    fclose( $dest_f );
    return true;
}

function imagecreatefrombitmap( $filename )
{
    $tmp_name = tempnam( "/tmp", "GD" );
    if( ConvertBMP2GD( $filename, $tmp_name ) )
    {
        $img = imagecreatefromgd( $tmp_name );
        unlink( $tmp_name );
        return $img;
    }
    return false;
}

function CurrentPageURL()
{
    //$pageURL = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $pageURL = 'https://';
    $pageURL .= $_SERVER[ 'SERVER_PORT' ] != '80' ? $_SERVER[ "SERVER_NAME" ] . ":" . $_SERVER[ "SERVER_PORT" ] . $_SERVER[ "REQUEST_URI" ] : $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ];
    return $pageURL;
}

$_profileTimer = microtime( true );
$_loadDuration = 0;
function ProfileStamp( $message = NULL, $echo = FALSE )
{
    global $_profileTimer;
    global $_loadDuration;
    if( $_loadDuration != 0 )
    {
        $newTime = microtime( true );
        $_loadDuration = $newTime - $_profileTimer;
        $_profileTimer = $newTime;
        error_log( "PROFILE - " . CurrentPageURL() . " - took " . sprintf( '%1.4f', ($_loadDuration ) ) . "s..." );
        if( $echo )
            echo "PROFILE - " . CurrentPageURL() . " - took " . sprintf( '%1.4f', ($_loadDuration ) ) . "s...";

        if( isset( $message ) && strlen( $message ) > 0 )
            error_log( " - " . $message );
        //return " <span style='font-size:x-small;'>(Generated in " . sprintf( '%1.4f', ($_loadDuration) ) . "s)</span>";
    }
    else
    {
        $_loadDuration = microtime( true ) - $_profileTimer;
    }
}

ProfileStamp(); //Start ticking
class XMLSerializer
{
    // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/

    public static function generateValidXmlFromObj( stdClass $obj, $node_block = 'nodes', $node_name = 'node' )
    {
        $arr = get_object_vars( $obj );
        return self::generateValidXmlFromArray( $arr, $node_block, $node_name );
    }

    public static function generateValidXmlFromArray( $array, $node_block = 'nodes', $node_name = 'node' )
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';
        $xml .= self::generateXmlFromArray( $array, $node_name );
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray( $array, $node_name )
    {
        $xml = '';

        if( is_array( $array ) || is_object( $array ) )
        {
            foreach( $array as $key => $value )
            {
                if( is_numeric( $key ) )
                {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' . self::generateXmlFromArray( $value, $node_name ) . '</' . $key . '>';
            }
        }
        else
        {
            $xml = htmlspecialchars( $array, ENT_QUOTES );
        }

        return $xml;
    }

}

function var_dump_errorlog( $var )
{
    ob_start();
    var_dump( $var );
    $contents = ob_get_contents();
    ob_end_clean();
    error_log( "ErrorLog Dump: " . $contents );
}

function BitSet( $value, $flagBit )
{
    return ( ( $value & (1 << $flagBit) ) !== 0 );
}

function IsMobileBrowser()
{
    $mobile_browser = '0';

    if( isset( $_SERVER[ 'HTTP_ACCEPT' ] ) )
    {
        if( ( strpos( strtolower( $_SERVER[ 'HTTP_ACCEPT' ] ), 'application/vnd.wap.xhtml+xml' ) > 0) ||
                ( (isset( $_SERVER[ 'HTTP_X_WAP_PROFILE' ] ) || isset( $_SERVER[ 'HTTP_PROFILE' ] )) ) )
        {
            $mobile_browser++;
        }
    }

    if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) )
    {
        if( preg_match( '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) )
        {
            $mobile_browser++;
        }

        $mobile_ua = strtolower( substr( $_SERVER[ 'HTTP_USER_AGENT' ], 0, 4 ) );
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-' );

        if( in_array( $mobile_ua, $mobile_agents ) )
        {
            $mobile_browser++;
        }
    }

    //if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
    //    $mobile_browser++;
    //}

    if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) && ( strpos( strtolower( $_SERVER[ 'HTTP_USER_AGENT' ] ), 'windows' ) > 0) )
    {
        $mobile_browser = 0;
    }

    if( $mobile_browser > 0 )
    {
        // do something
        return true;
    }
    else
    {
        // do something else
        return false;
    }
}

function ParseCURLGetImage( $url )
{
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_URL, getenv('APP_URL')."/$url" );
    curl_setopt( $ch, CURLOPT_HEADER, FALSE );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $ch, CURLOPT_BINARYTRANSFER, TRUE );

    $result = curl_exec( $ch );
    curl_close( $ch );

    return $result;
}

function ParseCURLPage( $url, $postString = "" )
{
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, getenv('APP_URL')."/$url" );
    curl_setopt( $ch, CURLOPT_POST, TRUE );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $postString );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

    $result = curl_exec( $ch );
    curl_close( $ch );

    return $result;
}

function IsValidUsername( $userTest )
{
    if( ctype_alnum( $userTest ) == FALSE )
    {
        //error_log( "requestcreateuser.php failed 1 - $user $pass $email $email2 " );
        //echo "Username ($user) must consist only of letters or numbers. Please retry.<br/>";
        return FALSE;
    }

    if( strlen( $userTest ) > 20 )
    {
        //error_log( "requestcreateuser.php failed 2 - $user $pass $email $email2 " );
        //echo "Username can be a maximum of 20 characters. Please retry.<br/>";
        //log_sql_fail();
        return FALSE;
    }

    if( strlen( $userTest ) < 2 )
        return FALSE;

    return TRUE;
}
