#!/usr/bin/php
<?PHP
set_time_limit(60);
$starttime = microtime(true);

require_once(substr(dirname(__FILE__),0,-4).'other/config.php');
require_once(substr(dirname(__FILE__),0,-4).'lang.php');
require_once(substr(dirname(__FILE__),0,-4).'ts3_lib/TeamSpeak3.php');

$sqlerr = 0;

try {
    $ts3 = TeamSpeak3::factory("serverquery://" . $ts['user'] . ":" . $ts['pass'] . "@" . $ts['host'] . ":" . $ts['query'] . "/?server_port=" . $ts['voice']);
	if (strlen($queryname)>27) $queryname = substr($queryname, 0, -3).'_ug'; else $queryname = $queryname .'_ug';
	if (strlen($queryname2)>26) $queryname2 = substr($queryname2, 0, -4).'_ug2'; else $queryname2 = $queryname2.'_ug2';
    if ($slowmode == 1) sleep(1);
    try {
        $ts3->selfUpdate(array('client_nickname' => $queryname));
    }
    catch (Exception $e) {
        if ($slowmode == 1) sleep(1);
        try {
            $ts3->selfUpdate(array('client_nickname' => $queryname2));
        }
        catch (Exception $e) {
            echo $lang['error'], $e->getCode(), ': ', $e->getMessage();
			$sqlmsg .= $e->getCode() . ': ' . $e->getMessage();
			$sqlerr++;
        }
    }

    $sIconId = $ts3['virtualserver_icon_id'];
	$sIconId   = ($sIconId < 0) ? (pow(2, 32)) - ($sIconId * -1) : $sIconId;
	$sIconFile = 0;
	if($sIconId > 600) {
		$sIconFile = $ts3->iconDownload();
		file_put_contents(substr(dirname(__FILE__),0,-4) . "icons/servericon.png", $sIconFile);
	}
	
	// update groupinformations and download icons
    if(($dbgroups = $mysqlcon->query("SELECT * FROM $dbname.groups")) === false) {
		echo $lang['error'],print_r($mysqlcon->errorInfo());
		$sqlmsg .= print_r($mysqlcon->errorInfo());
		$sqlerr++;
	}
    if ($dbgroups->rowCount() == 0) {
        $sqlhisgroup = "empty";
    } else {
		$servergroups = $dbgroups->fetchAll(PDO::FETCH_ASSOC);
        foreach($servergroups as $servergroup) {
            $sqlhisgroup[$servergroup['sgid']] = $servergroup['sgidname'];
        }
    }
	
	if ($slowmode == 1) sleep(1);
    $ts3groups   = $ts3->serverGroupList();
	
    foreach ($ts3groups as $servergroup) {
		$tsgroupids[] = $servergroup['sgid'];
        $gefunden = 2;
        $iconid   = $servergroup['iconid'];
        $iconid   = ($iconid < 0) ? (pow(2, 32)) - ($iconid * -1) : $iconid;
		$iconfile = 0;
		if($iconid > 600) {
			$iconfile = $servergroup->iconDownload();
		}
        $sgname   = str_replace('\\', '\\\\', htmlspecialchars($servergroup['name'], ENT_QUOTES));
        if ($sqlhisgroup != "empty") {
            foreach ($sqlhisgroup as $sgid => $sname) {
                if ($sgid == $servergroup['sgid']) {
                    $gefunden       = 1;
                    $updategroups[] = array(
                        "sgid" => $servergroup['sgid'],
                        "sgidname" => $sgname,
                        "iconid" => $iconid,
						"icon" => $iconfile
                    );
                    break;
                }
            }
            if ($gefunden != 1) {
                $insertgroups[] = array(
                    "sgid" => $servergroup['sgid'],
                    "sgidname" => $sgname,
                    "iconid" => $iconid,
					"icon" => $iconfile
                );
            }
        } else {
            $insertgroups[] = array(
                "sgid" => $servergroup['sgid'],
                "sgidname" => $sgname,
                "iconid" => $iconid,
				"icon" => $iconfile
            );
        }
    }

    if (isset($insertgroups)) {
        $allinsertdata = '';
        foreach ($insertgroups as $insertarr) {
            $allinsertdata = $allinsertdata . "('" . $insertarr['sgid'] . "', '" . $insertarr['sgidname'] . "', '" . $insertarr['iconid'] . "'),";
			if($insertarr['iconid']!=0 && $updatedata['iconid']>300) {
				file_put_contents(substr(dirname(__FILE__),0,-4) . "icons/" . $insertarr['sgid'] . ".png", $insertarr['icon']);
			}
        }
        $allinsertdata = substr($allinsertdata, 0, -1);
        if ($allinsertdata != '') {
            if($mysqlcon->exec("INSERT INTO $dbname.groups (sgid, sgidname, iconid) VALUES $allinsertdata") === false) {
				echo $lang['error'],print_r($mysqlcon->errorInfo());
				$sqlmsg .= print_r($mysqlcon->errorInfo());
				$sqlerr++;
			}
        }
    }

    if (isset($updategroups)) {
        $allsgids        = '';
        $allupdatesgid   = '';
		$allupdateiconid = '';
        foreach ($updategroups as $updatedata) {
            $allsgids        = $allsgids . "'" . $updatedata['sgid'] . "',";
            $allupdatesgid   = $allupdatesgid . "WHEN '" . $updatedata['sgid'] . "' THEN '" . $updatedata['sgidname'] . "' ";
            $allupdateiconid = $allupdateiconid . "WHEN '" . $updatedata['sgid'] . "' THEN '" . $updatedata['iconid'] . "' ";
			if($updatedata['iconid']!=0 && $updatedata['iconid']>300) {
				file_put_contents(substr(dirname(__FILE__),0,-4) . "icons/" . $updatedata['sgid'] . ".png", $updatedata['icon']);
			}
        }
        $allsgids = substr($allsgids, 0, -1);
        if($mysqlcon->exec("UPDATE $dbname.groups set sgidname = CASE sgid $allupdatesgid END, iconid = CASE sgid $allupdateiconid END WHERE sgid IN ($allsgids)") === false) {
			echo $lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
    }
	
	foreach ($sqlhisgroup as $sgroupid => $sgroupname) {
		if(!in_array($sgroupid, $tsgroupids)) {
			$delsgroupids = $delsgroupids . "'" . $sgroupid . "',";
		}
	}
	
	if(isset($delsgroupids)) {
		$delsgroupids = substr($delsgroupids, 0, -1);
		if($mysqlcon->exec("DELETE FROM groups WHERE sgid IN ($delsgroupids)") === false) {
			echo $lang['error'],print_r($mysqlcon->errorInfo());
			$sqlmsg .= print_r($mysqlcon->errorInfo());
			$sqlerr++;
		}
	}
}
catch (Exception $e) {
    echo $lang['error'] . $e->getCode() . ': ' . $e->getMessage();
	$sqlmsg .= $e->getCode() . ': ' . $e->getMessage();
	$sqlerr++;
}

if ($showgen == 1) {
    $buildtime = microtime(true) - $starttime;
    echo "\n",sprintf($lang['sitegen'], $buildtime, $dbgroups->rowCount()),"\n";
}

if ($sqlerr == 0) {
	if(isset($_SERVER['argv'][1])) {
		$jobid = $_SERVER['argv'][1];
		if($mysqlcon->exec("UPDATE $dbname.job_log SET status='0', runtime='$buildtime' WHERE id='$jobid'") === false) {
			echo $lang['error'],print_r($mysqlcon->errorInfo());
		}
	}
} else {
	if(isset($_SERVER['argv'][1])) {
		$jobid = $_SERVER['argv'][1];
		if($mysqlcon->exec("UPDATE $dbname.job_log SET status='1', err_msg='$sqlmsg', runtime='$buildtime' WHERE id='$jobid'") === false) {
			echo $lang['error'],print_r($mysqlcon->errorInfo());
		}
	}
}
?>