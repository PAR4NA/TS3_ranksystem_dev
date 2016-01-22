<?PHP
require_once('dbconfig.php');
if(isset($db['type']) === false) {
	$db['type']="mysql";
}
$dbname = $db['dbname'];
$dbserver  = $db['type'].':host='.$db['host'].';dbname='.$db['dbname'];
if ($db['type'] == 'mysql') {
	$dboptions = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
} else {
	$dboptions = array();
}

try {
	$mysqlcon = new PDO($dbserver, $db['user'], $db['pass'], $dboptions);
} catch (PDOException $e) {
	$sqlconerr = "SQL Connection failed: ".$e->getMessage()."\n";
	echo $sqlconerr;
	// open function mail here and try to ts3 msg (perhaps uuid out of text file; mysqlconf?)
	exit;
}
if (($config = $mysqlcon->query("SELECT * FROM config"))  === false) {
    $bgcolor         = '#101010';
    $hdcolor         = '#909090';
    $txcolor         = '#707070';
    $hvcolor         = '#FFFFFF';
    $ifcolor         = '#3366CC';
    $wncolor         = '#CC0000';
    $sccolor         = '#008000';
    $showgen         = '1';
} else {
    $config          = $config->fetchAll();
    $ts['host']      = $config[0]['tshost'];
    $ts['query']     = $config[0]['tsquery'];
    $ts['voice']     = $config[0]['tsvoice'];
    $ts['user']      = $config[0]['tsuser'];
    $ts['pass']      = $config[0]['tspass'];
	$webuser         = $config[0]['webuser'];
	$webpass         = $config[0]['webpass'];
    $language        = $config[0]['language'];
    $queryname       = $config[0]['queryname'];
    $queryname2      = $config[0]['queryname2'];
	$slowmode        = $config[0]['slowmode'];
	if(empty($config[0]['grouptime'])) {
		$grouptime == $config[0]['grouptime'];
	} else {
		$grouptimearr = explode(',', $config[0]['grouptime']);
		foreach ($grouptimearr as $entry) {
			list($key, $value) = explode('=>', $entry);
			$grouptime[$key] = $value;
		}
	}
	if(empty($config[0]['boost'])) {
		$boost == $config[0]['boost'];
	} else {
		$boostexp = explode(',', $config[0]['boost']);
		foreach ($boostexp as $entry) {
			list($key, $value1, $value2) = explode('=>', $entry);
			$boostarr[$key] = array("group"=>$key,"factor"=>$value1,"time"=>$value2);
		}
	}
    $resetbydbchange = $config[0]['resetbydbchange'];
    $msgtouser       = $config[0]['msgtouser'];
    $update          = $config[0]['upcheck'];
    $uniqueid        = explode(',', $config[0]['uniqueid']);
    $updateinfotime  = $config[0]['updateinfotime'];
    $currvers        = $config[0]['currvers'];
    $substridle      = $config[0]['substridle'];
    $exceptuuid      = explode(',', $config[0]['exceptuuid']);
    $exceptgroup     = explode(',', $config[0]['exceptgroup']);
    $timeformat      = $config[0]['dateformat'];
    $showexgrp       = $config[0]['showexgrp'];
    $showexcld       = $config[0]['showexcld'];
	$showhighest     = $config[0]['showhighest'];
	$showcolrg       = $config[0]['showcolrg'];
    $showcolcld      = $config[0]['showcolcld'];
    $showcoluuid     = $config[0]['showcoluuid'];
    $showcoldbid     = $config[0]['showcoldbid'];
	$showcolls       = $config[0]['showcolls'];
    $showcolot       = $config[0]['showcolot'];
    $showcolit       = $config[0]['showcolit'];
    $showcolat       = $config[0]['showcolat'];
	$showcolas       = $config[0]['showcolas'];
    $showcolnx       = $config[0]['showcolnx'];
    $showcolsg       = $config[0]['showcolsg'];
    $bgcolor         = $config[0]['bgcolor'];
    $hdcolor         = $config[0]['hdcolor'];
    $txcolor         = $config[0]['txcolor'];
    $hvcolor         = $config[0]['hvcolor'];
    $ifcolor         = $config[0]['ifcolor'];
    $wncolor         = $config[0]['wncolor'];
    $sccolor         = $config[0]['sccolor'];
    $showgen         = $config[0]['showgen'];
	$cleanclients    = $config[0]['cleanclients'];
	$cleanperiod     = $config[0]['cleanperiod'];
}
?>