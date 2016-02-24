<?PHP
session_start();
$starttime = microtime(true);

require_once('../other/config.php');
if (isset($_GET['lang'])) {
    $language = $_GET['lang'];
}

if(isset($_POST['username'])) {
	$_GET["search"] = $_POST['usersuche'];
	$_GET["seite"] = 1;
}
if(isset($_GET["search"])) {
	$getstring = $_GET["search"]; 
	$searchmysql = 'WHERE uuid LIKE \'%'.$getstring.'%\' OR cldbid LIKE \'%'.$getstring.'%\' OR name LIKE \'%'.$getstring.'%\'';
} else {
	$getstring = '';
	$searchmysql = '';
}
if(!isset($_GET["seite"])) {
	$seite = 1;
} else {
    $seite = $_GET["seite"];
}
$adminlogin = 0;
if ($mysqlprob === false) {
	echo '<span class="wncolor">',$sqlconerr,'</span><br>';
	exit;
}
$keysort  = '';
$keyorder = '';
if (isset($_GET['sort'])) {
    $keysort = $_GET['sort'];
}
if ($keysort != 'name' && $keysort != 'uuid' && $keysort != 'cldbid' && $keysort != 'lastseen' && $keysort != 'count' && $keysort != 'idle' && $keysort != 'active') {
    $keysort = 'nextup';
}
if (isset($_GET['order'])) {
    $keyorder = $_GET['order'];
}
$keyorder = ($keyorder == 'desc' ? 'desc' : 'asc');
if (isset($_GET['admin'])) {
    if($_GET['admin'] == "true" && isset($_SESSION['username'])) {
		$adminlogin = 1;
	}
}
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>TS-N.NET Ranksystem</title>
  <meta charset="utf-8"/>
  <link rel="icon" href="../icons/rs.png">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
  <script type="text/javascript">
  ;(function($) {
   $.fn.fixMe = function() {
      return this.each(function() {
         var $this = $(this),
            $t_fixed;
         function init() {
            $this.wrap('<div class="confix" />');
            $t_fixed = $this.clone();
            $t_fixed.find("tbody").remove().end().addClass("fixed").insertBefore($this);
            resizeFixed();
         }
         function resizeFixed() {
            $t_fixed.find("th").each(function(index) {
               $(this).css("width",$this.find("th").eq(index).outerWidth()+"px");
            });
         }
         function scrollFixed() {
            var offset = $(this).scrollTop(),
            tableOffsetTop = $this.offset().top,
            tableOffsetBottom = tableOffsetTop + $this.height() - $this.find("thead").height();
            if(offset < tableOffsetTop || offset > tableOffsetBottom)
               $t_fixed.hide();
            else if(offset >= tableOffsetTop && offset <= tableOffsetBottom && $t_fixed.is(":hidden"))
               $t_fixed.show();
         }
         $(window).resize(resizeFixed);
         $(window).scroll(scrollFixed);
         init();
      });
   };
})(jQuery);

$(document).ready(function(){
   $("table").fixMe();
   $(".up").click(function() {
      $('html, body').animate({
      scrollTop: 0
   }, 2000);
 });
});
</script>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<a href="<?PHP echo 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]; ?>" class="navbar-brand">Ranksystem - Userlist</a>
		</div>
		<div class="navbar-form navbar-right dropdown">
			<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
				Limit entries
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
				<li role="presentation"><a role="menuitem" href="<?PHP echo "?sort=$keysort&amp;order=$keyorder&amp;user=50&amp;lang=$language&amp;search=$getstring"; ?>">50</a></li>
				<li role="presentation"><a role="menuitem" href="<?PHP echo "?sort=$keysort&amp;order=$keyorder&amp;user=100&amp;lang=$language&amp;search=$getstring"; ?>">100</a></li>
				<li role="presentation"><a role="menuitem" href="<?PHP echo "?sort=$keysort&amp;order=$keyorder&amp;user=250&amp;lang=$language&amp;search=$getstring"; ?>">250</a></li>
				<li role="presentation"><a role="menuitem" href="<?PHP echo "?sort=$keysort&amp;order=$keyorder&amp;user=500&amp;lang=$language&amp;search=$getstring"; ?>">500</a></li>
				<li role="separator" class="divider"></li>
				<li role="presentation"><a role="menuitem" href="<?PHP echo "?sort=$keysort&amp;order=$keyorder&amp;user=all&amp;lang=$language&amp;search=$getstring"; ?>">all</a></li>
			</ul>
		</div>
		<div class="navbar-form navbar-right">
			<form method="post">
				<div class="form-group">
					<input class="form-control" type="text" name="usersuche" placeholder="Search"<?PHP if(isset($getstring)) echo 'value="'.$getstring.'"'; ?>>
				</div>
				<button class="btn btn-primary" type="submit" name="username"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></button>
			</form>
		</div>
	</div>
</nav>
<div style="margin: 50px 0px;"></div>
<?PHP
$countentries = 0;
$dbdata_full = $mysqlcon->query("SELECT * FROM $dbname.user $searchmysql");
$sumentries = $dbdata_full->rowCount();

if(!isset($_GET["user"])) {
	$user_pro_seite = 50;
} elseif($_GET['user'] == "all") {
	$user_pro_seite = $sumentries;
} else {
    $user_pro_seite = $_GET["user"];
}

$start = $seite * $user_pro_seite - $user_pro_seite;

if ($keysort == 'active' && $keyorder == 'asc') {
    $dbdata = $mysqlcon->query("SELECT * FROM $dbname.user $searchmysql ORDER BY (count - idle) LIMIT $start, $user_pro_seite");
} elseif ($keysort == 'active' && $keyorder == 'desc') {
    $dbdata = $mysqlcon->query("SELECT * FROM $dbname.user $searchmysql ORDER BY (idle - count) LIMIT $start, $user_pro_seite");
} else {
    $dbdata = $mysqlcon->query("SELECT * FROM $dbname.user $searchmysql ORDER BY $keysort $keyorder LIMIT $start, $user_pro_seite");
}
$seiten_anzahl_gerundet = ceil($sumentries / $user_pro_seite);

function pagination($keysort,$keyorder,$user_pro_seite,$seiten_anzahl_gerundet,$seite,$language,$getstring) {
	?>
	<nav>
		<div class="text-center">
			<ul class="pagination">
				<li>
					<a href="<?PHP echo '?sort='.$keysort.'&amp;order='.$keyorder.'&amp;seite=1&amp;user='.$user_pro_seite.'&lang='.$language.'&amp;search='.$getstring; ?>" aria-label="backward">
						<span aria-hidden="true"><span class="glyphicon glyphicon-step-backward" aria-hidden="true"></span>&nbsp;</span>
					</a>
				</li>
				<?PHP
				for($a=0; $a < $seiten_anzahl_gerundet; $a++) {
					$b = $a + 1;
					if($seite == $b) {
						echo '<li class="active"><a href="">'.$b.'<span class="sr-only">(aktuell)</span></a></li>';
					} elseif ($b > $seite - 5 && $b < $seite + 5) {
						echo '<li><a href="?sort='.$keysort.'&amp;order='.$keyorder.'&amp;seite='.$b.'&amp;user='.$user_pro_seite.'&lang='.$language.'&amp;search='.$getstring.'">'.$b.'</a></li>';
					}
				}
				?>
				<li>
					<a href="<?PHP echo '?sort='.$keysort.'&amp;order='.$keyorder.'&amp;seite='.$seiten_anzahl_gerundet.'&amp;user='.$user_pro_seite.'&lang='.$language.'&amp;search='.$getstring; ?>" aria-label="forward">
						<span aria-hidden="true">&nbsp;<span class="glyphicon glyphicon-step-forward" aria-hidden="true"></span></span>
					</a>
				</li>
			</ul>
		</div>
	</nav>
	<?PHP
}
if(isset($_GET['user']) != "all") {
	pagination($keysort,$keyorder,$user_pro_seite,$seiten_anzahl_gerundet,$seite,$language,$getstring);
}
$uuids = $dbdata->fetchAll();
foreach($uuids as $uuid) {
	$sqlhis[$uuid['uuid']] = array(
		"cldbid" => $uuid['cldbid'],
		"count" => $uuid['count'],
		"name" => $uuid['name'],
		"idle" => $uuid['idle'],
		"cldgroup" => $uuid['cldgroup'],
		"online" => $uuid['online'],
		"nextup" => $uuid['nextup'],
		"lastseen" => $uuid['lastseen'],
		"ip" => $uuid['ip'],
		"grpid" => $uuid['grpid']
	);
	$uidarr[]              = $uuid['uuid'];
	$countentries          = $countentries + 1;
}
if(!$dbdata = $mysqlcon->query("SELECT * FROM $dbname.lastscan")) {
	echo '<span class="wncolor">',$mysqlcon->errorCode(),'</span><br>';
	exit;
}

$lastscan = $dbdata->fetchAll();
$scantime = $lastscan[0]['timestamp'];
$livetime = time() - $scantime;
$dbgroups = $mysqlcon->query("SELECT * FROM $dbname.groups");
$servergroups = $dbgroups->fetchAll(PDO::FETCH_ASSOC);
foreach($servergroups as $servergroup) {
	$sqlhisgroup[$servergroup['sgid']] = $servergroup['sgidname'];
	if(file_exists('images/'.$servergroup['sgid'].'.png')) {
		$sqlhisgroup_file[$servergroup['sgid']] = true;
	} else {
		$sqlhisgroup_file[$servergroup['sgid']] = false;
	}
}
if($adminlogin == 1) {
	switch ($keyorder) {
		case "asc":
			$keyorder2 = "desc&amp;admin=true";
			break;
		case "desc":
			$keyorder2 = "asc&amp;admin=true";
	}
} else {
	switch ($keyorder) {
		case "asc":
			$keyorder2 = "desc";
			break;
		case "desc":
			$keyorder2 = "asc";
	}
}
?>
<div class="confix">
	<table class="table table-striped">
		<thead>
			<tr>
<?PHP
if ($showcolrg == 1 || $adminlogin == 1)
    echo '<th class="text-center">' , $lang['listrank'] , '</th>';
if ($showcolcld == 1 || $adminlogin == 1)
    echo ($keysort == 'name') ? '<th class="text-center"><a href="?sort=name&amp;order=' . $keyorder2 . '&amp;seite=' . $seite . '&amp;user=' . $user_pro_seite . '&amp;lang=' . $language . '&amp;search=' . $getstring . '"><span class="hdcolor">' . $lang['listnick'] . '</span></a></th>' : '<th class="text-center"><a href="?sort=name&amp;order=' . $keyorder2 . '&amp;seite=' . $seite . '&amp;user=' . $user_pro_seite . '&amp;lang=' . $language . '&amp;search=' . $getstring . '"><span class="hdcolor">' . $lang['listnick'] . '</span></a></th>';
if ($showcoluuid == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=uuid&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listuid'] , '</span></a></th>';
if ($showcoldbid == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=cldbid&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listcldbid'] , '</span></a></th>';
if ($adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=ip&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listip'] , '</span></a></th>';
if ($showcolls == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=lastseen&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listseen'] , '</span></a></th>';
if ($showcolot == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=count&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listsumo'] , '</span></a></th>';
if ($showcolit == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=idle&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listsumi'] , '</span></a></th>';
if ($showcolat == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=active&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listsuma'] , '</span></a></th>';
if ($showcolas == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=grpid&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listacsg'] , '</span></a></th>';
if ($showcolnx == 1 || $adminlogin == 1)
    echo ($keysort == 'nextup') ? '<th class="text-center"><a href="?sort=nextup&amp;order=' . $keyorder2 . '&amp;seite=' . $seite . '&amp;user=' . $user_pro_seite . '&amp;lang=' . $language . '&amp;search=' . $getstring . '"><span class="hdcolor">' . $lang['listnxup'] . '</span></a></th>' : '<th class="text-center"><a href="?sort=nextup&amp;order=' . $keyorder2 . '&amp;seite=' . $seite . '&amp;user=' . $user_pro_seite . '&amp;lang=' . $language . '&amp;search=' . $getstring . '"><span class="hdcolor">' . $lang['listnxup'] . '</span></a></th>';
if ($showcolsg == 1 || $adminlogin == 1)
    echo '<th class="text-center"><a href="?sort=nextsgrp&amp;order=' , $keyorder2 , '&amp;seite=' , $seite , '&amp;user=' , $user_pro_seite , '&amp;lang=' , $language , '&amp;search=' , $getstring , '"><span class="hdcolor">' , $lang['listnxsg'] , '</span></a></th>';
echo '</tr></thead><tbody>';
ksort($grouptime);
$countgrp = count($grouptime);
if ($countentries > 0) {
	$countrank=($seite-1)*$user_pro_seite;
	$exceptgrp=0;
	$exceptcld=0;
	$highest=0;
	$countallsum=0;
    foreach ($uidarr as $uid) {
        $cldgroup = $sqlhis[$uid]['cldgroup'];
		$lastseen =	$sqlhis[$uid]['lastseen'];
        $count    = $sqlhis[$uid]['count'];
        $idle     = $sqlhis[$uid]['idle'];
        $status   = $sqlhis[$uid]['online'];
		$nextup   = $sqlhis[$uid]['nextup'];
        $sgroups  = explode(",", $cldgroup);
        $active   = $count - $idle;
        if ($substridle == 1) {
            $activetime = $count - $idle;
        } else {
            $activetime = $count;
        }
		$grpcount=0;
		$countallsum++;
        foreach ($grouptime as $time => $groupid) {
			$grpcount++;
            if (array_intersect($sgroups, $exceptgroup) && $showexgrp != 1 && $adminlogin != 1) {
                $exceptgrp++;
				break;
            }
            if (in_array($uid, $exceptuuid) && $showexcld != 1 && $adminlogin != 1) {
				$exceptcld++;
                break;
            }
            if ($activetime < $time || $grpcount == $countgrp && $nextup == 0 && $showhighest == 1 || $grpcount == $countgrp && $nextup == 0 && $adminlogin == 1) {
				if($nextup == 0 && $grpcount == $countgrp) {
					$neededtime = 0;
				} elseif ($status == 1) {
                    $neededtime = $time - $activetime - $livetime;
                } else {
                    $neededtime = $time - $activetime;
                }
                echo '<tr>';
                if ($showcolrg == 1 || $adminlogin == 1) {
					$countrank++;
                    echo '<td class="text-center">' , $countrank , '</td>';
				}
                if ($adminlogin == 1) {
                    echo '<td class="text-center"><a href="http://www.tsviewer.com/index.php?page=search&action=ausgabe_user&nickname=' , $sqlhis[$uid]['name'] , '" target="_blank">' , $sqlhis[$uid]['name'] , '</a></td>';
				} elseif ($showcolcld == 1) {
					 echo '<td class="text-center">' , $sqlhis[$uid]['name'] , '</td>';
				}
                if ($adminlogin == 1) {
					echo '<td class="text-center"><a href="http://ts3index.com/?page=searchclient&uid=' , $uid , '" target="_blank">' , $uid , '</a></td>';
				} elseif ($showcoluuid == 1) {
					echo '<td class="text-center">' , $uid , '</td>';
				}
                if ($showcoldbid == 1 || $adminlogin == 1)
					echo '<td class="text-center">' , $sqlhis[$uid]['cldbid'] , '</td>';
				if ($adminlogin == 1)
                    echo '<td class="center"><a href="http://myip.ms/info/whois/' , long2ip($sqlhis[$uid]['ip']) , '" target="_blank">' , long2ip($sqlhis[$uid]['ip']) , '</a></td>';
				if ($showcolls == 1 || $adminlogin == 1) {
					echo '<td class="text-center">' , date('Y-m-d H:i:s',$lastseen);
					echo '</td>';
				}
                if ($showcolot == 1 || $adminlogin == 1) {
                    echo '<td class="text-center">';
                    $dtF       = new DateTime("@0");
                    $dtT       = new DateTime("@$count");
                    $timecount = $dtF->diff($dtT)->format($timeformat);
                    echo $timecount;
                }
                if ($showcolit == 1 || $adminlogin == 1) {
                    echo '<td class="text-center">';
                    $dtF       = new DateTime("@0");
                    $dtT       = new DateTime("@$idle");
                    $timecount = $dtF->diff($dtT)->format($timeformat);
                    echo $timecount;
                }
                if ($showcolat == 1 || $adminlogin == 1) {
                    echo '<td class="text-center">';
                    $dtF       = new DateTime("@0");
                    $dtT       = new DateTime("@$active");
                    $timecount = $dtF->diff($dtT)->format($timeformat);
                    echo $timecount;
                }
				if ($showcolas == 1 || $adminlogin == 1) {
					if ($sqlhis[$uid]['grpid'] == 0) {
						echo '<td class="text-center"></td>';
					} elseif ($sqlhisgroup_file[$sqlhis[$uid]['grpid']]==true) {
						echo '<td class="text-center"><img src="images/'.$sqlhis[$uid]['grpid'].'.png">&nbsp;&nbsp;' , $sqlhisgroup[$usergroupid] , '</td>';
					} else {
						$usergroupid = $sqlhis[$uid]['grpid'];
						echo '<td class="text-center">' , $sqlhisgroup[$usergroupid] , '</td>';
					}
				}
                if ($showcolnx == 1 || $adminlogin == 1) {
                    echo '<td class="text-center">';
                    $dtF       = new DateTime("@0");
                    $dtT       = new DateTime("@$neededtime");
                    $timecount = $dtF->diff($dtT)->format($timeformat);
                    if (!in_array($uid, $exceptuuid) && !array_intersect($sgroups, $exceptgroup) && $neededtime > 0) {
                        echo $timecount , '</td>';
                    } elseif (!in_array($uid, $exceptuuid) && !array_intersect($sgroups, $exceptgroup)) {
                        $timecount = 0;
                        echo $timecount , '</td>';
                    } elseif (in_array($uid, $exceptuuid)) {
                        echo $lang['listexuid'] , '</td>';
                    } elseif (array_intersect($sgroups, $exceptgroup)) {
                        echo $lang['listexgrp'] , '</td>';
                    } else {
                        echo $lang['errukwn'], '</td>';
                    }
                }
				if ($showcolsg == 1 || $adminlogin == 1) {
					if ($grpcount == $countgrp && $nextup == 0 && $showhighest == 1 || $grpcount == $countgrp && $nextup == 0 && $adminlogin == 1) {
						echo '<td class="text-center">',$lang['highest'],'</td>';
						$highest++;
					} elseif ($sqlhisgroup_file[$groupid]==true) {
						echo '<td class="text-center"><img src="images/'.$groupid.'.png">&nbsp;&nbsp;' , $sqlhisgroup[$groupid] , '</td>';
					} else {
						echo '<td class="text-center">' , $sqlhisgroup[$groupid] , '</td>';
					}
				}
				echo '</tr>';
                break;
            } elseif ($grpcount == $countgrp && $nextup == 0) {
				$highest++;
			}
        }
    }
} else {
    echo '<tr><td colspan="6">' , $lang['noentry'] , '</td></tr>';
}
echo '</tbody></table></div>';
if(isset($_GET['user']) != "all") {
	pagination($keysort,$keyorder,$user_pro_seite,$seiten_anzahl_gerundet,$seite,$language,$getstring);
}
if ($showgen == 1 || $adminlogin == 1) {
	$except = $exceptgrp + $exceptcld;
	$notvisible = 0;
	if ($showexgrp != 1) { $notvisible = $exceptgrp; }
	if ($showexcld != 1) { $notvisible = $notvisible + $exceptcld; }
	if ($showhighest != 1) { $notvisible = $notvisible + $highest; }
	$displayed = $countallsum - $notvisible;
    $buildtime = microtime(true) - $starttime;
	?>
	<nav>
		<ul class="pager">
			<li class="previous"><span class="glyphicon glyphicon-chevron-up up scrollMore" aria-hidden="true"></span></li>
			<li class="next"><span class="glyphicon glyphicon-chevron-up up scrollMore" aria-hidden="true"></span></li>
		</ul>
	</nav>
	<div style="margin: 100px 0px;"></div>
	<nav class="navbar-fixed-bottom">
		<div class="alert alert-info" role="alert">
			<div class="text-center" style="margin-bottom: 25px;">
				<?PHP echo '<span class="tabledefault">' , sprintf($lang['sitegenl'], $buildtime, $sumentries, $displayed, $except, $highest) , '</span>';?>
			</div>
		</div>
	</nav>
    <?PHP
}
?>
<div style="margin: 50px 0px;"></div>
<nav class="navbar navbar-inverse navbar-fixed-bottom">
        <div class="container">
            <div class="navbar-header">
            </div>
            <div class="collapse navbar-collapse">
			    <ul class="nav navbar-nav navbar-left">
				<li style="margin-left: 5px; margin-right: 5px;"><a style="color: #337ab7;" href="ts3server://<?PHP echo ($ts['host']=='localhost' ? $_SERVER['HTTP_HOST'] : $ts['host']).':'.$ts['voice']; ?>">Connect to TS3 Server</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><p class="navbar-text navbar-right"><a style="color: #337ab7;" href="http://ts-n.net/" target="blank_">Ranksystem by TS-N.NET</a> &copy; <?PHP echo date('Y', time()); ?> - Benjamin Frost, Newcomer1989</p></li>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>