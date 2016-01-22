﻿<?PHP
function calc_serverstats($ts3,$mysqlcon,$lang,$dbname,$slowmode,$serverinfo,$substridle,$grouptime) {
	$starttime = microtime(true);
	$nowtime = time();
	$sqlmsg = '';
	$sqlerr = 0;

	$total_user = 0;
	$total_online_time = 0;
	$total_active_time = 0;
	$total_inactive_time = 0;
	$country_string = '';
	$platform_string = '';
	$server_used_slots = 0;
	$server_channel_amount = 0;
	if(($uuids = $mysqlcon->query("SELECT uuid,count,idle,platform,nation FROM $dbname.user")) === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	$uuids = $uuids->fetchAll();
	foreach($uuids as $uuid) {
		$sqlhis[$uuid['uuid']] = array(
			"uuid" => $uuid['uuid'],
			"count" => $uuid['count'],
			"idle" => $uuid['idle']
		);
		if ($uuid['nation']!=NULL) $country_string .= $uuid['nation'] . ' ';
		if ($uuid['platform']!=NULL) {
			$uuid_platform = str_replace(' ','',$uuid['platform']);
			$platform_string .= $uuid_platform . ' ';
		}
		$total_online_time = $total_online_time + $uuid['count'];
		$total_active_time = $total_active_time + $uuid['count'] - $uuid['idle'];
		$total_inactive_time = $total_inactive_time + $uuid['idle'];
	}

	// Event Handling each 6 hours
	// Duplicate users Table in snapshot Table
	if(($max_entry_usersnap = $mysqlcon->query("SELECT MAX(DISTINCT(timestamp)) AS timestamp FROM $dbname.user_snapshot")) === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	$max_entry_usersnap = $max_entry_usersnap->fetch(PDO::FETCH_ASSOC);
	$diff_max_usersnap = $nowtime - $max_entry_usersnap['timestamp'];
	if($diff_max_usersnap > 21600) {
		if(isset($sqlhis)) {
			$allinsertsnap = '';
			foreach ($sqlhis as $insertsnap) {
				$allinsertsnap = $allinsertsnap . "('$nowtime','" . $insertsnap['uuid'] . "', '" . $insertsnap['count'] . "', '" . $insertsnap['idle'] . "'),";
			}
			$allinsertsnap = substr($allinsertsnap, 0, -1);
			if ($allinsertsnap != '') {
				if($mysqlcon->exec("INSERT INTO $dbname.user_snapshot (timestamp, uuid, count, idle) VALUES $allinsertsnap") === false) {
					echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
					$sqlmsg .= print_r($mysqlcon->errorInfo());
					$sqlerr++;
				}
			}
		}
		//Delete old Entries in user_snapshot
		$deletiontime = $nowtime - 2678400;
		if($mysqlcon->exec("DELETE FROM $dbname.user_snapshot WHERE timestamp=$deletiontime") === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
	}

	if($serverinfo['virtualserver_status']=="online") {
		$server_status = 1;
	} elseif($serverinfo['virtualserver_status']=="offline") {
		$server_status = 2;
	} elseif($serverinfo['virtualserver_status']=="virtual online") {	
		$server_status = 3;
	} else {
		$server_status = 4;
	}
	
	// Calc Values for server stats
	if(($entry_snapshot_count = $mysqlcon->query("SELECT count(DISTINCT(timestamp)) AS timestamp FROM $dbname.user_snapshot")) === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	$entry_snapshot_count = $entry_snapshot_count->fetch(PDO::FETCH_ASSOC);
	if ($entry_snapshot_count['timestamp'] > 27) {
		// Calc total_online_week
		if(($snapshot_count_week = $mysqlcon->query("select (select sum(count) from $dbname.user_snapshot where timestamp=(select max(s2.timestamp) as value1 from (select distinct(timestamp) from $dbname.user_snapshot order by timestamp desc limit 28) as s2, $dbname.user_snapshot as s1 where s1.timestamp=s2.timestamp)) - (select sum(count) from $dbname.user_snapshot where timestamp=(select min(s2.timestamp) as value2 from (select distinct(timestamp) from $dbname.user_snapshot order by timestamp desc limit 28) as s2, $dbname.user_snapshot as s1 where s1.timestamp=s2.timestamp)) as count")) === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
		$snapshot_count_week = $snapshot_count_week->fetch(PDO::FETCH_ASSOC);
		$total_online_week = $snapshot_count_week['count'];
	} else {
		$total_online_week = 0;
	}
	if ($entry_snapshot_count['timestamp'] > 119) {
		// Calc total_online_month
		if(($snapshot_count_month = $mysqlcon->query("select (select sum(count) from $dbname.user_snapshot where timestamp=(select max(s2.timestamp) as value1 from (select distinct(timestamp) from $dbname.user_snapshot order by timestamp desc limit 120) as s2, $dbname.user_snapshot as s1 where s1.timestamp=s2.timestamp)) - (select sum(count) from $dbname.user_snapshot where timestamp=(select min(s2.timestamp) as value2 from (select distinct(timestamp) from $dbname.user_snapshot order by timestamp desc limit 120) as s2, $dbname.user_snapshot as s1 where s1.timestamp=s2.timestamp)) as count")) === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
		$snapshot_count_month = $snapshot_count_month->fetch(PDO::FETCH_ASSOC);
		$total_online_month = $snapshot_count_month['count'];
	} else {
		$total_online_month = 0;
	}

	$country_array = array_count_values(str_word_count($country_string, 1));
	arsort($country_array);
	$country_counter = 0;
	$country_nation_other = 0;
	foreach ($country_array as $k => $v) {
		$country_counter++;
		if ($country_counter == 1) {
			$country_nation_name_1 = $k;
			$country_nation_1 = $v;
		} elseif ($country_counter == 2) {
			$country_nation_name_2 = $k;
			$country_nation_2 = $v;
		} elseif ($country_counter == 3) {
			$country_nation_name_3 = $k;
			$country_nation_3 = $v;
		} elseif ($country_counter == 4) {
			$country_nation_name_4 = $k;
			$country_nation_4 = $v;
		} elseif ($country_counter == 5) {
			$country_nation_name_5 = $k;
			$country_nation_5 = $v;
		} else {
			$country_nation_other = $country_nation_other + $v;
		}
	}

	$platform_array = array_count_values(str_word_count($platform_string, 1));
	$platform_other = 0;
	$platform_1 = 0;
	$platform_2 = 0;
	$platform_3 = 0;
	$platform_4 = 0;
	$platform_5 = 0;
	foreach ($platform_array as $k => $v) {
		if ($k == "Windows") {
			$platform_1 = $v;
		} elseif ($k == "iOS") {
			$platform_2 = $v;
		} elseif ($k == "Linux") {
			$platform_3 = $v;
		} elseif ($k == "Android") {
			$platform_4 = $v;
		} elseif ($k == "OSX") {
			$platform_5 = $v;
		} else {
			$platform_other = $platform_other + $v;
		}
	}

	$version_1 = 0;
	$version_2 = 0;
	$version_3 = 0;
	$version_4 = 0;
	$version_5 = 0;
	$version_name_1 = 0;
	$version_name_2 = 0;
	$version_name_3 = 0;
	$version_name_4 = 0;
	$version_name_5 = 0;
	$client_versions = $mysqlcon->query("SELECT version, COUNT(version) AS count FROM user GROUP BY version ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
	$count_version = 0;
	$version_other = $mysqlcon->query("SELECT COUNT(version) AS count FROM user ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);

	foreach($client_versions as $version) {
		$count_version++;
		if ($count_version == 1) {
			$version_1 = $version['count'];
			$version_name_1 = $version['version'];
		} elseif ($count_version == 2) {
			$version_2 = $version['count'];
			$version_name_2 = $version['version'];
		} elseif ($count_version == 3) {
			$version_3 = $version['count'];
			$version_name_3 = $version['version'];
		} elseif ($count_version == 4) {
			$version_4 = $version['count'];
			$version_name_4 = $version['version'];
		} elseif ($count_version == 5) {
			$version_5 = $version['count'];
			$version_name_5 = $version['version'];
		}
	}
	$version_other = $version_other[0]['count'] - $version_1 + $version_2 + $version_3 + $version_4 + $version_5;

	$total_user = count($sqlhis);
	$server_used_slots = $serverinfo['virtualserver_clientsonline'] - $serverinfo['virtualserver_queryclientsonline'];
	$server_free_slots = $serverinfo['virtualserver_maxclients'] - $server_used_slots;
	$server_channel_amount = $serverinfo['virtualserver_channelsonline'];
	$server_ping = $serverinfo['virtualserver_total_ping'];
	$server_packet_loss = $serverinfo['virtualserver_total_packetloss_total'];
	$server_bytes_down = $serverinfo['virtualserver_total_bytes_downloaded'];
	$server_bytes_up = $serverinfo['virtualserver_total_bytes_uploaded'];
	$server_uptime = $serverinfo['virtualserver_uptime'];
	$server_id = $serverinfo['virtualserver_id'];
	$server_name = $serverinfo['virtualserver_name'];
	$server_pass = $serverinfo['virtualserver_flag_password'];
	$server_creation_date = $serverinfo['virtualserver_created'];
	$server_platform = $serverinfo['virtualserver_platform'];
	$server_weblist = $serverinfo['virtualserver_weblist_enabled'];
	$server_version = $serverinfo['virtualserver_version'];
	
	if($mysqlcon->exec("UPDATE $dbname.stats_server SET total_user='$total_user', total_online_time='$total_online_time', total_online_month='$total_online_month', total_online_week='$total_online_week', total_active_time='$total_active_time', total_inactive_time='$total_inactive_time', country_nation_name_1='$country_nation_name_1', country_nation_name_2='$country_nation_name_2', country_nation_name_3='$country_nation_name_3', country_nation_name_4='$country_nation_name_4', country_nation_name_5='$country_nation_name_5', country_nation_1='$country_nation_1', country_nation_2='$country_nation_2', country_nation_3='$country_nation_3', country_nation_4='$country_nation_4', country_nation_5='$country_nation_5', country_nation_other='$country_nation_other', platform_1='$platform_1', platform_2='$platform_2', platform_3='$platform_3', platform_4='$platform_4', platform_5='$platform_5', platform_other='$platform_other', version_name_1='$version_name_1', version_name_2='$version_name_2', version_name_3='$version_name_3', version_name_4='$version_name_4', version_name_5='$version_name_5', version_1='$version_1', version_2='$version_2', version_3='$version_3', version_4='$version_4', version_5='$version_5', version_other='$version_other', version_name_1='$version_name_1', server_status='$server_status', server_free_slots='$server_free_slots', server_used_slots='$server_used_slots', server_channel_amount='$server_channel_amount', server_ping='$server_ping', server_packet_loss='$server_packet_loss', server_bytes_down='$server_bytes_down', server_bytes_up='$server_bytes_up', server_uptime='$server_uptime', server_id='$server_id', server_name='$server_name', server_pass='$server_pass', server_creation_date='$server_creation_date', server_platform='$server_platform', server_weblist='$server_weblist', server_version='$server_version'") === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}

	// Stats for Server Usage
	if(($max_entry_serverusage = $mysqlcon->query("SELECT MAX(timestamp) AS timestamp FROM $dbname.server_usage")) === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	$max_entry_serverusage = $max_entry_serverusage->fetch(PDO::FETCH_ASSOC);
	$diff_max_serverusage = $nowtime - $max_entry_serverusage['timestamp'];
	if ($max_entry_serverusage['timestamp'] == 0 || $diff_max_serverusage > 899) { // every 15 mins
		if($mysqlcon->exec("INSERT INTO $dbname.server_usage (timestamp, clients, channel) VALUES ($nowtime,$server_used_slots,$server_channel_amount)") === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
	}

	//Calc time next rankup
	$upnextuptime = $nowtime - 86400;
	if(($uuidsoff = $mysqlcon->query("SELECT uuid,idle,count FROM $dbname.user WHERE online<>1 AND lastseen>$upnextuptime")) === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	if ($uuidsoff->rowCount() != 0) {
		$uuidsoff = $uuidsoff->fetchAll(PDO::FETCH_ASSOC);
		foreach($uuidsoff as $uuid) {
			$idle     = $uuid['idle'];
			$count    = $uuid['count'];
			if ($substridle == 1) {
				$activetime = $count - $idle;
				$dtF        = new DateTime("@0");
				$dtT        = new DateTime("@$activetime");
			} else {
				$activetime = $count;
				$dtF        = new DateTime("@0");
				$dtT        = new DateTime("@$count");
			}
			foreach ($grouptime as $time => $groupid) {
				if ($activetime > $time) {
					$nextup = 0;
				} else {
					$nextup = $time - $activetime;
				}
			}
			$updatenextup[] = array(
				"uuid" => $uuid['uuid'],
				"nextup" => $nextup
			);
		}
	}

	if (isset($updatenextup)) {
		$allupdateuuid   = '';
		$allupdatenextup = '';
		foreach ($updatenextup as $updatedata) {
			$allupdateuuid   = $allupdateuuid . "'" . $updatedata['uuid'] . "',";
			$allupdatenextup = $allupdatenextup . "WHEN '" . $updatedata['uuid'] . "' THEN '" . $updatedata['nextup'] . "' ";
		}
		$allupdateuuid = substr($allupdateuuid, 0, -1);
		if ($mysqlcon->exec("UPDATE $dbname.user set nextup = CASE uuid $allupdatenextup END WHERE uuid IN ($allupdateuuid)") === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
	}

	//Calc Rank
	if($mysqlcon->exec("SET @a:=0") === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
	if($mysqlcon->exec("UPDATE $dbname.user u INNER JOIN (SELECT @a:=@a+1 nr,uuid FROM $dbname.user ORDER BY count DESC) s USING (uuid) SET u.rank=s.nr") === false) {
		echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}

	if ($sqlerr == 0) {
		if(isset($_SERVER['argv'][1])) {
			$jobid = $_SERVER['argv'][1];
			if($mysqlcon->exec("UPDATE $dbname.job_log SET status='0', runtime='$buildtime' WHERE id='$jobid'") === false) {
				echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			}
		}
	} else {
		if(isset($_SERVER['argv'][1])) {
			$jobid = $_SERVER['argv'][1];
			if($mysqlcon->exec("UPDATE $dbname.job_log SET status='1', err_msg='$sqlmsg', runtime='$buildtime' WHERE id='$jobid'") === false) {
				echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['error'],print_r($mysqlcon->errorInfo());
			}
		}
	}
}
?>