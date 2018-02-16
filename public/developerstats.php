<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
	$playersOnlineCSV = file_get_contents( "./cronjobs/playersonline.log" );
	$playersCSV = preg_split('/\n|\r\n?/', $playersOnlineCSV);
	
	$hoursInADay = 24;
	$numDays = 2;
	
	$numDataPoints = 2 * $hoursInADay * $numDays;	//	2 30segments * 24hrs * 2 days worth
	
	//$playersOnlineArray = Array();
	//for( $i = 0; $i < $numDataPoints; $i++ )
	//	$playersOnlineArray[] = $playersCSV[count($playersCSV)-($i+2)];

	$staticData = getStaticData();
	$errorCode = seekGET( 'e' );
	$type = seekGET( 't', 0 );
	
	RenderDocType();
?>

<head>
	<!--Load the AJAX API-->
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">

		// Load the Visualization API and the piechart package.
		//google.load('visualization', '1.0', {'packages':['corechart']});

		// Set a callback to run when the Google Visualization API is loaded.
		//google.setOnLoadCallback(drawCharts);

		// Callback that creates and populates a data table,
		// instantiates the pie chart, passes in the data and
		// draws it.
//		function drawCharts()
//		{
//			var dataTotalScore = new google.visualization.DataTable();
//
//			// Declare columns
//			dataTotalScore.addColumn('datetime', 'Time');
//			dataTotalScore.addColumn('number', 'Players Online');
//			dataTotalScore.addColumn('number', 'Players Online Yesterday');
//
//			dataTotalScore.addRows([
//				<?php
//					$largestWonByCount = 0;
//					$count = 0;
//					$now = date("Y/m/d G:0:0");
//					//error_log( $now );
//
//					for( $i = 0; $i < $hoursInADay; $i++ )
//					{
//						$numPlayers = $playersOnlineArray[$i];
//						$numPlayersYDay = $playersOnlineArray[$i+$hoursInADay];
//
//						if( $i != 0 )
//							echo ", ";
//
//						$mins = $i * 30;
//
//						$timestamp = strtotime("-$mins minutes", strtotime($now));
//
//						$yr = date("Y", $timestamp);
//						$month = date("m", $timestamp);
//						$day = date("d", $timestamp);
//						$hour = date("G", $timestamp);
//						$min = date("i", $timestamp);
//
//						echo "[ new Date($yr,$month,$day,$hour,$min), {v:$numPlayers, f:\"$numPlayers online\"}, {v:$numPlayersYDay, f:\"$numPlayersYDay online yesterday\"} ] ";
//					}
//				?>
//			]);
//
//			<?php //
//				$numGridlines = $hoursInADay;
//			?>
//
//			var optionsTotalScore = {
//				backgroundColor: 'transparent',
//				//title: 'Achievement Distribution',
//				titleTextStyle: {color: '#186DEE'}, //cc9900
//				//hAxis: {textStyle: {color: '#186DEE'}, gridlines:{count:24, color: '#334433'}, minorGridlines:{count:0}, format:'#', slantedTextAngle:90, maxAlternation:0 },
//				hAxis: {textStyle: {color: '#186DEE'} },
//				vAxis: {textStyle: {color: '#186DEE'}, viewWindow:{min:0}, format: '#' },
//				legend: {position: 'none' },
//				chartArea: {'width': '85%', 'height': '78%'},
//				height: 260,
//				colors: ['#cc9900'],
//				pointSize: 3
//			};
//
//			function resize ()
//			{
//				chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_usersonline'));
//				chartScoreProgress.draw(dataTotalScore, optionsTotalScore);
//
//				//google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
//			}
//
//			window.onload = resize();
//			window.onresize = resize;
//		}
	</script>

<?php
	RenderSharedHeader( $user );
	RenderTitleTag( "Developer Stats", $user );
	RenderGoogleTracking();
?>
</head>

<body>

<?php
	RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode );
	RenderToolbar( $user, $permissions );
?>

<div id='mainpage'>

<div id='leftcontainer'>
<?php
	RenderErrorCodeWarning( 'left', $errorCode );
	RenderDeveloperStats( $user, $type );
	echo "<h3>Users Online</h3>";
	//echo "<div id='chart_usersonline'></div>";
?>	
</div>

<div id='rightcontainer'>
<?php
	RenderStaticDataComponent( $staticData );
	RenderRecentlyUploadedComponent( 10 );
?>	
</div>

</div>

<?php RenderFooter(); ?>

</body>
</html>
