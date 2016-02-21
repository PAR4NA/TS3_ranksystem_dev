﻿<?PHP
function clean($ts3,$mysqlcon,$lang,$dbname,$slowmode,$jobid,$cleanclients,$cleanperiod) {
	$starttime = microtime(true);
	$sqlmsg = '';
	$sqlerr = 0;
	$count_tsuser['count'] = 0;
	$nowtime = time();

	// clean old clients out of the database
	if ($cleanclients == 1 && $slowmode != 1) {
		$cleantime = $nowtime - $cleanperiod;
		if(($lastclean = $mysqlcon->query("SELECT * FROM $dbname.job_check WHERE job_name='check_clean'")) === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 1:",print_r($mysqlcon->errorInfo()),"\n";
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
		$lastclean = $lastclean->fetchAll();
		if(($dbuserdata = $mysqlcon->query("SELECT uuid FROM $dbname.user")) === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 2:",print_r($mysqlcon->errorInfo()),"\n";
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
		$countrs = $dbuserdata->rowCount();
		$uuids = $dbuserdata->fetchAll();
		if ($lastclean[0]['timestamp'] < $cleantime) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['clean'],"\n";
			$start=0;
			$break=200;
			$clientdblist=array();
			$countdel=0;
			$countts=0;
			while($getclientdblist=$ts3->clientListDb($start, $break)) {
				$clientdblist=array_merge($clientdblist, $getclientdblist);
				$start=$start+$break;
				$count_tsuser=array_shift($getclientdblist);
				if ($start == 100000 || $count_tsuser['count'] <= $start) {
					break;
				}
				if ($slowmode == 1) sleep(1);
			}
			foreach($clientdblist as $uuidts) {
				$single_uuid = $uuidts['client_unique_identifier']->toString();
				$uidarrts[$single_uuid]= 1;
			}
			unset($clientdblist);
			
			foreach($uuids as $uuid) {
				if(isset($uidarrts[$uuid[0]])) {
					$countts++;
				} else {
					$deleteuuids[] = $uuid[0];
					$countdel++;
				}
			}

			unset($uidarrts);
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),sprintf($lang['cleants'], $countts, $count_tsuser['count']),"\n";
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),sprintf($lang['cleanrs'], $countrs),"\n";

			if(isset($deleteuuids)) {
				$alldeldata = '';
				foreach ($deleteuuids as $dellarr) {
					$alldeldata = $alldeldata . "'" . $dellarr . "',";
				}
				$alldeldata = substr($alldeldata, 0, -1);
				$alldeldata = "(".$alldeldata.")";
				if ($alldeldata != '') {
					if($mysqlcon->exec("DELETE FROM $dbname.user WHERE uuid IN $alldeldata") === false) {
						echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 3:",print_r($mysqlcon->errorInfo()),"\n";
						$sqlmsg .= print_r($mysqlcon->errorInfo());
						$sqlerr++;
					} else {
						echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),sprintf($lang['cleandel'], $countdel),"\n";
						if($mysqlcon->exec("UPDATE $dbname.job_check SET timestamp='$nowtime' WHERE job_name='check_clean'") === false) {
							echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 4:",print_r($mysqlcon->errorInfo()),"\n";
							$sqlmsg .= print_r($mysqlcon->errorInfo());
							$sqlerr++;
						}
					}
				}
			} else {
				echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),$lang['cleanno'],"\n";
				if($mysqlcon->exec("UPDATE $dbname.job_check SET timestamp='$nowtime' WHERE job_name='check_clean'") === false) {
					echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 5:",print_r($mysqlcon->errorInfo()),"\n";
					$sqlmsg .= print_r($mysqlcon->errorInfo());
					$sqlerr++;
				}
			}
		}
	}
	
	$buildtime = microtime(true) - $starttime;

	if ($sqlerr == 0) {
		if($mysqlcon->exec("UPDATE $dbname.job_log SET status='0', runtime='$buildtime' WHERE id='$jobid'") === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 6:",print_r($mysqlcon->errorInfo()),"\n";
		}
	} else {
		if($mysqlcon->exec("UPDATE $dbname.job_log SET status='1', err_msg='$sqlmsg', runtime='$buildtime' WHERE id='$jobid'") === false) {
			echo DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimeZone(new DateTimeZone('Europe/Berlin'))->format("Y-m-d H:i:s.u "),"clean 7:",print_r($mysqlcon->errorInfo()),"\n";
		}
	}
}
?>