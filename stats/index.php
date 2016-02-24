<?PHP
session_start();
require_once('../other/config.php');
require_once('../other/session.php');

if($language == "de") {
	require_once('../languages/nations_de.php');
} elseif($language == "en") {
	require_once('../languages/nations_en.php');
}

if(!isset($_SESSION['tsuid'])) {
	$hpclientip = ip2long($_SERVER['REMOTE_ADDR']);
	set_session_ts3($hpclientip, $ts['voice'], $mysqlcon, $dbname);
}

function human_readable_size($bytes) {
	$size = array(' B',' KiB',' MiB',' GiB',' TiB',' PiB',' EiB',' ZiB',' YiB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.2f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

$sql = $mysqlcon->query("SELECT * FROM $dbname.stats_server");
$sql_res = $sql->fetchAll();

$server_usage_sql = $mysqlcon->query("SELECT * FROM $dbname.server_usage ORDER BY(timestamp) DESC LIMIT 0, 47");
$server_usage_sql_res = $server_usage_sql->fetchAll();

if(isset($_GET['usage'])) {
	if ($_GET["usage"] == 'week') {
		$usage = 'week';
	} elseif ($_GET["usage"] == 'month') {
		$usage = 'month';
	} else {
		$usage = 'day';
	}
} else {
	$usage = 'day';
}
require_once('nav.php');
?>
		<div id="infoModal" class="modal fade">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title">Server Statistics - Page Content</h4>
					</div>
					<div class="modal-body">
						<p>This page contains a overall summary about the user statistics and data on the server.</p>
						<p>&nbsp;</p>
						<p>This page receives its values out of a database. So the values might be delayed a bit.</p>
						<p>&nbsp;</p>
						<p>The sum inside of the donut charts may differ to the amount of 'Total user'. The reason is that this data weren't collect with older version of the Ranksystem.</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		<div id="page-wrapper">
<?PHP if(isset($err_msg)) error_handling($err_msg, 3); ?>
			<div class="container-fluid">

				<!-- Page Heading -->
				<div class="row">
					<div class="col-lg-12">
						<h1 class="page-header">
							Server Statistics
						<div class="btn-group">
							<a href="#infoModal" data-toggle="modal" class="btn btn-primary">
								<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
							</a>
						</div>
						<div class="pull-right"><small><font color="#000000">TS3 Address: </font><a href="ts3server://<?PHP echo ($ts['host']=='localhost' ? $_SERVER['HTTP_HOST'] : $ts['host']).':'.$ts['voice']; ?>"><?PHP echo ($ts['host']=='localhost' ? $_SERVER['HTTP_HOST'] : $ts['host']).':'.$ts['voice']; ?></a></small></div>
						</h1>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-6">
						<div class="panel panel-primary">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-users fa-5x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?PHP echo $sql_res[0]['total_user'] ?></div>
										<div>Total Users</div>
									</div>
								</div>
							</div>
							<a href="list_rankup.php">
								<div class="panel-footer">
									<span class="pull-left">View Details</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="panel panel-green">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-clock-o fa-5x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?PHP echo round(($sql_res[0]['total_online_time'] / 86400)). ' <small>days</small>';?></div>
										<div>Online Time / Total</div>
									</div>
								</div>
							</div>
							<a href="top_all.php">
								<div class="panel-footer">
									<span class="pull-left">View Top Of All Time</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="panel panel-yellow">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-clock-o fa-5x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?PHP echo round(($sql_res[0]['total_online_month'] / 86400)). ' <small>days</small>';?></div>
										<div><?PHP echo ($sql_res[0]['total_online_month'] == 0 ? 'not enough data yet...' : 'Online Time / Last Month') ?></div>
									</div>
								</div>
							</div>
							<a href="top_month.php">
								<div class="panel-footer">
									<span class="pull-left">View Top Of The Month</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="panel panel-red">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-clock-o fa-5x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?PHP echo round(($sql_res[0]['total_online_week'] / 86400)). ' <small>days</small>';?></div>
										<div><?PHP echo ($sql_res[0]['total_online_week'] == 0 ? 'not enough data yet...' : 'Online Time / Last Week') ?></div>
									</div>
								</div>
							</div>
							<a href="top_week.php">
								<div class="panel-footer">
									<span class="pull-left">View Top Of The Week</span>
									<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
				</div>
				<!-- /.row -->
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-primary">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-9">
										<h3 class="panel-title"><i class="fa fa-bar-chart-o"></i> Server Usage <i><?PHP if($usage == 'week') { echo 'In The Last 7 Days'; } elseif ($usage == 'month') { echo 'In The Last 30 Days'; } else { echo 'In The Last 24 Hours'; } ?></i></h3>
									</div>
									<div class="col-xs-3">
										<div class="btn-group dropup pull-right">
										  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											select period <span class="caret"></span>
										  </button>
										  <ul class="dropdown-menu">
											<li><a href="<?PHP echo "?usage=day"; ?>">Last Day</a></li>
											<li><a href="<?PHP echo "?usage=week"; ?>">Last Week</a></li>
											<li><a href="<?PHP echo "?usage=month"; ?>">Last Month</a></li>
										  </ul>
										</div>
									</div>
								</div>
							</div>
							<div class="panel-body">
								<div id="server-usage-chart"></div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.row -->

				<div class="row">
					<div class="col-lg-3">
						<div class="panel panel-primary">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-long-arrow-right"></i> Client Active / Inactive Time</h3>
							</div>
							<div class="panel-body">
								<div id="time-gap-donut"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="panel panel-green">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-long-arrow-right"></i> Client Versions</h3>
							</div>
							<div class="panel-body">
								<div id="client-version-donut"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="panel panel-yellow">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-long-arrow-right"></i> Client Nationalities</h3>
							</div>
							<div class="panel-body">
								<div id="user-descent-donut"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-3">
						<div class="panel panel-red">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-long-arrow-right"></i> Client Platforms</h3>
							</div>
							<div class="panel-body">
								<div id="user-platform-donut"></div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.row -->
				<div class="row">
					<div class="col-lg-6">
						<h2>Current Statistics</h2>
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead>
									<tr>
										<th>Requested Information</th>
										<th>Result</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Server Status</td>
										<td><?PHP echo ($sql_res[0]['server_status'] == 1 || $sql_res[0]['server_status'] == 3) ? '<span class="text-success">Online</span>' : '<span class="text-danger">Offline</span>'; ?></td>
									</tr>
									<tr>
										<td>Clients (Online / Max)</td>
										<td><?PHP echo ($sql_res[0]['server_status'] == 0) ? '0' :  $sql_res[0]['server_used_slots'] , ' / ' ,($sql_res[0]['server_used_slots'] + $sql_res[0]['server_free_slots']); ?></td>
									</tr>
									<tr>
										<td>Amount Of Channels</td>
										<td><?PHP echo $sql_res[0]['server_channel_amount']; ?></td>
									</tr>
									<tr>
										<td>Average Server Ping</td>
										<td><?PHP echo ($sql_res[0]['server_status'] == 0) ? '-' : $sql_res[0]['server_ping'] . ' ms'; ?></td>
									</tr>
									<tr>
										<td>Total Bytes Received</td>
										<td><?PHP echo human_readable_size($sql_res[0]['server_bytes_down']); ?></td>
									</tr>
									<tr>
										<td>Total Bytes Sent</td>
										<td><?PHP echo human_readable_size($sql_res[0]['server_bytes_up']); ?></td>
									</tr>
									<tr>
										<td>Server Uptime</td>
										<td><?PHP echo ($sql_res[0]['server_status'] == 0) ? '-&nbsp;&nbsp;&nbsp;(<i>before offline: '.(new DateTime("@0"))->diff(new DateTime("@".$sql_res[0]['server_uptime']))->format($timeformat).')</i>' : '<text id="days">00</text> Days, <text id="hours">00</text> Hours, <text id="minutes">00</text> Mins, <text id="seconds">00</text> Secs'; ?></td>
									</tr>
									<tr>
										<td>Average Packet Loss</td>
										<td><?PHP echo ($sql_res[0]['server_status'] == 0) ? '-' : $sql_res[0]['server_packet_loss'] * 100 .' %'; ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<div class="col-lg-6">
						<h2>Overall Statistics</h2>
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead>
									<tr>
										<th>Requested Information</th>
										<th>Result</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Server Name</td>
										<td><?PHP echo (file_exists("../icons/servericon.png") ? $sql_res[0]['server_name'] .'<div class="pull-right"><img src="../icons/servericon.png" alt="servericon"></div>' : $sql_res[0]['server_name']) ?></td>
									</tr>
									<tr>
										<td>Server Address (Host Address : Port)</td>
										<td><?PHP echo ($ts['host']=='localhost' ? $_SERVER['HTTP_HOST'] : $ts['host']) .':' .$_SESSION['serverport'] ?></td>
									</tr>
									<tr>
										<td>Server Password</td>
										<td><?PHP echo ($sql_res[0]['server_pass'] == '0') ? 'No (Server is Public)' : 'Yes (Server Is Private)' ?></td>
									</tr>
									<tr>
										<td>Server ID</td>
										<td><?PHP echo $sql_res[0]['server_id'] ?></td>
									</tr>
									<tr>
										<td>Server Platform</td>
										<td><?PHP echo $sql_res[0]['server_platform'] ?></td>
									</tr>
									<tr>
										<td>Server Version</td>
										<td><?PHP echo substr($sql_res[0]['server_version'], 0, strpos($sql_res[0]['server_version'], ' ')); ?></td>
									</tr>
									<tr>
										<td>Server Creation Date (dd/mm/yyyy)</td>
										<td><?PHP echo date('d/m/Y', $sql_res[0]['server_creation_date']) ?></td>
									</tr>
									<tr>
										<td>Report To Server List</td>
										<td><?PHP echo ($sql_res[0]['server_weblist'] == 1) ? '<a href="https://www.planetteamspeak.com/serverlist/result/server/ip/' .($ts['host']=='localhost' ? $_SERVER['HTTP_HOST'] : $ts['host']).':'.$ts['voice'] .'" target="_blank">Activated</a>' : 'Not Activated' ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>  
			<!-- /.container-fluid -->

		</div>
		<!-- /#page-wrapper -->

	</div>
	<!-- /#wrapper -->
	<!-- Scripts -->
	<script>
		Morris.Donut({
		  element: 'time-gap-donut',
		  data: [
			{label: "Active Time (in Days)", value: <?PHP echo round(($sql_res[0]['total_active_time'] / 86400)); ?>},
			{label: "Inactive Time (in Days)", value: <?PHP echo round(($sql_res[0]['total_inactive_time'] / 86400)); ?>},
		  ]
		});
		Morris.Donut({
			element: 'client-version-donut',
			data: [
			   {label: "<?PHP echo $sql_res[0]['version_name_1'] ?>", value: <?PHP echo $sql_res[0]['version_1'] ?>},
			   {label: "<?PHP echo $sql_res[0]['version_name_2'] ?>", value: <?PHP echo $sql_res[0]['version_2'] ?>},
			   {label: "<?PHP echo $sql_res[0]['version_name_3'] ?>", value: <?PHP echo $sql_res[0]['version_3'] ?>},
			   {label: "<?PHP echo $sql_res[0]['version_name_4'] ?>", value: <?PHP echo $sql_res[0]['version_4'] ?>},
			   {label: "<?PHP echo $sql_res[0]['version_name_5'] ?>", value: <?PHP echo $sql_res[0]['version_5'] ?>},
			   {label: "Others", value: <?PHP echo $sql_res[0]['version_other'] ?>},
			],
			colors: [
				'#5cb85c',
				'#73C773',
				'#8DD68D',
				'#AAE6AA',
				'#C9F5C9',
				'#E6FFE6'
		  ]
		});
		Morris.Donut({
		  element: 'user-descent-donut',
		  data: [
			   {label: "<?PHP echo $nation[$sql_res[0]['country_nation_name_1']] ?>", value: <?PHP echo $sql_res[0]['country_nation_1'] ?>},
			   {label: "<?PHP echo $nation[$sql_res[0]['country_nation_name_2']] ?>", value: <?PHP echo $sql_res[0]['country_nation_2'] ?>},
			   {label: "<?PHP echo $nation[$sql_res[0]['country_nation_name_3']] ?>", value: <?PHP echo $sql_res[0]['country_nation_3'] ?>},
			   {label: "<?PHP echo $nation[$sql_res[0]['country_nation_name_4']] ?>", value: <?PHP echo $sql_res[0]['country_nation_4'] ?>},
			   {label: "<?PHP echo $nation[$sql_res[0]['country_nation_name_5']] ?>", value: <?PHP echo $sql_res[0]['country_nation_5'] ?>},
			   {label: "Others", value: <?PHP echo $sql_res[0]['country_nation_other'] ?>},
		  ],
			colors: [
				'#f0ad4e',
				'#ffc675',
				'#fecf8d',
				'#ffdfb1',
				'#fce8cb',
				'#fdf3e5'
		  ]
		});
		Morris.Donut({
			element: 'user-platform-donut',
			data: [
				{label: "Windows", value: <?PHP echo $sql_res[0]['platform_1'] ?>},
				{label: "Linux", value: <?PHP echo $sql_res[0]['platform_3'] ?>},
				{label: "Android", value: <?PHP echo $sql_res[0]['platform_4'] ?>},
				{label: "iOS", value: <?PHP echo $sql_res[0]['platform_2'] ?>},
				{label: "OS X", value: <?PHP echo $sql_res[0]['platform_5'] ?>},
				{label: "Others", value: <?PHP echo $sql_res[0]['platform_other'] ?>},
			],
			colors: [
				'#d9534f',
				'#FF4040',
				'#FF5050',
				'#FF6060',
				'#FF7070',
				'#FF8080'
		  ]
		});
		Morris.Area({
		  element: 'server-usage-chart',
		  data: [
			<?PHP
				$chart_data = '';
				$trash_string = $mysqlcon->query("SET @a:=0");
				if($usage == 'week') { 
					$server_usage = $mysqlcon->query("SELECT u1.timestamp, u1.clients, u1.channel FROM (SELECT @a:=@a+1,mod(@a,4) AS test,timestamp,clients,channel FROM $dbname.server_usage) AS u2, $dbname.server_usage AS u1 WHERE u1.timestamp=u2.timestamp AND u2.test='1' ORDER BY u2.timestamp DESC LIMIT 672");
				} elseif ($usage == 'month') {
					$server_usage = $mysqlcon->query("SELECT u1.timestamp, u1.clients, u1.channel FROM (SELECT @a:=@a+1,mod(@a,16) AS test,timestamp,clients,channel FROM $dbname.server_usage) AS u2, $dbname.server_usage AS u1 WHERE u1.timestamp=u2.timestamp AND u2.test='1' ORDER BY u2.timestamp DESC LIMIT 2880");
				} else {
					$server_usage = $mysqlcon->query("SELECT u1.timestamp, u1.clients, u1.channel FROM (SELECT timestamp,clients,channel FROM $dbname.server_usage) AS u2, $dbname.server_usage AS u1 WHERE u1.timestamp=u2.timestamp ORDER BY u2.timestamp DESC LIMIT 96");
				}
				$server_usage = $server_usage->fetchAll(PDO::FETCH_ASSOC);
				foreach($server_usage as $chart_value) {
					$chart_time = date('Y-m-d H:i:s',$chart_value['timestamp']);
					$chart_data = $chart_data . '{ y: \''.$chart_time.'\', a: '.$chart_value['clients'].', b: '.$chart_value['channel'].' }, ';
				}
				$chart_data = substr($chart_data, 0, -2);
				echo $chart_data;
			?>
		  ],
		  xkey: 'y',
		  ykeys: ['a', 'b'],
		  labels: ['Clients', 'Channel']
		});
	</script>
	<script type="text/javascript">
		var daysLabel = document.getElementById("days");
		var hoursLabel = document.getElementById("hours");
		var minutesLabel = document.getElementById("minutes");
		var secondsLabel = document.getElementById("seconds");
		var totalSeconds = <?PHP echo $sql_res[0]['server_uptime'] ?>;
		setInterval(setTime, 1000);

		function setTime()
		{
			++totalSeconds;
			secondsLabel.innerHTML = pad(totalSeconds%60);
			minutesLabel.innerHTML = pad(parseInt(totalSeconds/60)%60);
			hoursLabel.innerHTML = pad(parseInt(totalSeconds/3600)%24)
			daysLabel.innerHTML = pad(parseInt(totalSeconds/86400))
		}

		function pad(val)
		{
			var valString = val + "";
			if(valString.length < 2)
			{
				return "0" + valString;
			}
			else
			{
				return valString;
			}
		}
	</script>
</body>

</html>