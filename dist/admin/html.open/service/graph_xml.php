<?php

require_once("../includes/auth.php");


//input
$vhost = GUIBase::GrabGoodInput("get","vhost");
$extapp = GUIBase::GrabGoodInput("get","extapp");
$items_original = GUIBase::GrabGoodInput("get","items");
$items = explode(",",$items_original);

$yaxis = GUIBase::GrabGoodInput("get","yaxis");

$titles_original = GUIBase::GrabGoodInput("get","titles");
$titles = explode(',',$titles_original);

$colors_original = GUIBase::GrabGoodInput("get","colors");
$colors = explode(',',$colors_original);


$live = GUIBase::GrabGoodInput("get","live");
$live_interval = GUIBase::GrabGoodInput("get","live_interval","int");

if($live_interval < 10) {
	$live_interval = 10;
}

//get stats
$stats = new STATS();
$stats->parse_litespeed();

//client
$client = CClient::singleton();

//pointer to area of interest
$region = &$stats;
//now make sure input points to valid data

if(strlen($vhost)) {
	if(!isset($stats->vhosts[$vhost])) {
		die('vhost not found');
	}
	else {
		$region = &$stats->vhosts[$vhost];
	}

}

//now make sure input points to valid data
if(strlen($vhost) && strlen($extapp)) {
	if(!isset($stats->vhosts[$vhost]->extapps[$extapp])) {
		echo "<pre>";
		print_r($stats);
		echo "</pre>";
		die('extapp not found');
	}
	else {
		$region = &$stats->vhosts[$vhost]->extapps[$extapp];

	}
}

if($region == NULL) {
	die('invalid region');
}

if(count($items) == 0) {
	die('item not passed');
}

//validate items exist and get new data
//

$newdata = array();
foreach($items as $key) {
	if(!isset($region->$key)) {
		die('item does not exist in region');
	}
	else {
		$newdata[] = $region->$key;
	}
}

//make session key
$skey = "v:{$vhost}.e:{$extapp}.i:{$items_original}";

//store data
$client->addStat($skey, $newdata);
//now get data
$real_data = $client->getStat($skey);
if($real_data == NULL) {
	die('rdata error');
}

$urlgraph = '/service/graph_xml.php?vhost=' . urlencode($vhost)
	. '&extapp=' . urlencode($extapp)
	. '&items=' . urlencode($items_original)
	. '&titles=' . urlencode($titles_original)
	. '&colors=' . urlencode($colors_original)
	. '&yaxis=' . urlencode($yaxis);

$chart = new CHART(TRUE, $live_interval, $urlgraph);
$chart->yaxis_label = $yaxis;

foreach($real_data as $index => $set) {
	$chart->addDataSet($set, $titles[$index], $colors[$index]);
}

echo $chart->render();

