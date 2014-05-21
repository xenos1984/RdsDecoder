<?php
include_once('tmcdecode.php');
include_once('tmclocation.php');

function nonempty($s)
{
	return($s !== "");
}

function array_desc($a)
{
	if($a['class'] == 'P')
		$data = array($a['junctionnumber'], $a['rnid'], $a['n1id'], $a['n2id']);
	else if($a['class'] == 'L')
		$data = array($a['roadnumber'], $a['rnid'], $a['n1id'], $a['n2id']);
	else
		$data = array($a['nid']);
	return implode(" ", array_filter($data, "nonempty"));
}

function location_link($lcd)
{
	global $cid, $tabcd;

	return "<a href=\"/tmc/tmcview.php?cid=$cid&amp;tabcd=$tabcd&amp;lcd=$lcd\">$cid:$tabcd:$lcd</a>";
}

$units = array('', '', 'm', '%', 'km/h', 'min', '°C', 'min', 't', 'm', 'mm', 'MHz', 'kHz');
$urgencies = array(0 => 'normal', 1 => 'urgent', 2 => 'extremely urgent');

$cid = (array_key_exists('cid', $_REQUEST) ? (int)$_REQUEST['cid'] : 58);
$tabcd = (array_key_exists('tabcd', $_REQUEST) ? (int)$_REQUEST['tabcd'] : 1);

$ecd = (array_key_exists('ecd', $_REQUEST) ? (int)$_REQUEST['ecd'] : 0);
$lcd = (array_key_exists('lcd', $_REQUEST) ? (int)$_REQUEST['lcd'] : 0);
$dir = (array_key_exists('dir', $_REQUEST) ? (int)$_REQUEST['dir'] : 0);
$ext = (array_key_exists('ext', $_REQUEST) ? (int)$_REQUEST['ext'] : 0);
$dur = (array_key_exists('dur', $_REQUEST) ? (int)$_REQUEST['dur'] : null);
$div = (array_key_exists('div', $_REQUEST) ? (int)$_REQUEST['div'] : null);
$bits = (array_key_exists('bits', $_REQUEST) ? $_REQUEST['bits'] : "");

$time = (array_key_exists('time', $_REQUEST) ? (int)$_REQUEST['time'] : time());

ob_start();
$message = decode_message($ecd, $lcd, $dir, $ext, $dur, $div, $bits);
$raw = ob_get_contents();
ob_end_clean();

$primary = find_place($cid, $tabcd, $lcd);
if($primary['class'] == 'P')
	$secondary = find_offsets($cid, $tabcd, $lcd, $ext, $dir);
else
	$secondary = array();

foreach($message['iblocks'] as $iblock)
{
	foreach($iblock['events'] as $event)
	{
		switch($event['code'])
		{
		case 101: // stationary traffic
			$opquery = "(";
			foreach($secondary as $location)
			{
				$opquery .= "relation[\"type\"=\"tmc:point\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"{$location['lcd']}\"];";
				if(array_key_exists('pos_off_lcd', $location) && array_key_exists($location['pos_off_lcd'], $secondary))
					$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"neg_lcd\"=\"{$location['lcd']}\"][\"pos_lcd\"=\"{$location['pos_off_lcd']}\"];";
				if(array_key_exists('neg_off_lcd', $location) && array_key_exists($location['neg_off_lcd'], $secondary))
					$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"pos_lcd\"=\"{$location['lcd']}\"][\"neg_lcd\"=\"{$location['neg_off_lcd']}\"];";
			}
			$opquery .= ")->.rels;";
			$opquery .= "(.rels;(";
			if($message['direction'] || ($message['directions'] == 2))
				$opquery .= "way(r.rels:\"positive\");";
			if((!$message['direction']) || ($message['directions'] == 2))
				$opquery .= "way(r.rels:\"negative\");";
			$opquery .= "way(r.rels:\"both\");";
			$opquery .= "way(r.rels:\"\");";
			$opquery .= ");node(w););out meta;";
			break;
		default:
			break;
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="rds.css"/>
<title>TMC message viewer</title>
<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
<script src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>
<script src="tmcmsgmap.js"></script>
<script type="text/javascript">
opurl = "http://overpass-api.de/api/interpreter?data=<?php echo rawurlencode($opquery); ?>";
</script>
</head>
<body onload="init();">
<div id="map" style="position: fixed; top: 12px; bottom: 12px; left: 480px; right: 12px"></div>
<div id="list" style="position: absolute; left: 12px; top: 12px; bottom: 12px; width: 456px; overflow: auto">
<h1>TMC message viewer</h1>
<?php
echo "<h3>Raw message data</h3>\n";
echo "<pre>$raw</pre>\n";

echo "<h3>Interpreted message data</h3>\n";
echo "<ul>\n";

echo "<li>Primary location: " . location_link($lcd) . " - " . array_desc($primary) . "</li>\n";
echo "<li>Extent: {$message['extent']}</li>\n";
echo "<li>Direction: " . ($message['direction'] ? "negative" : "positive") . "</li>\n";

if(($primary['class'] == 'P') && ($ext > 0))
{
	echo "<li>Affected locations:<ul>\n";
	foreach($secondary as $key => $value)
		echo "<li>" . location_link($key) . " - " . array_desc($value) . "</li>\n";
	echo "</ul></li>\n";
}

echo "<li>Affected directions: {$message['directions']}</li>\n";
echo "<li>Urgency: {$urgencies[$message['urgency']]}</li>\n";

if(array_key_exists('duration', $message))
	echo "<li>Duration: " . decode_duration($message['duration'], $message['durtype'], $message['nature']) . "</li>\n";
if(array_key_exists('start', $message))
	echo "<li>Start time: " . decode_time($message['start'], $time) . "</li>\n";
if(array_key_exists('stop', $message))
	echo "<li>Stop time: " . decode_time($message['stop'], $time) . "</li>\n";

echo "<li>Information blocks:<ul>\n";
foreach($message['iblocks'] as $iblock)
{
	echo "<li>Events / quantifier:<ul>\n";
	foreach($iblock['events'] as $event)
	{
		echo "<li>" . $event['code'] . " - ";
		if(array_key_exists('quant', $event))
			echo preg_replace('/\(([^\)]*)Q([^\)]*)\)/', '${1}' . find_quantifier($event['quantifier'], $event['quant']) . $units[$event['quantifier']] . '${2}', $event['text']);
		else
			echo trim(preg_replace('/\([^\)]*Q[^\)]*\)/', '', $event['text']));
		echo "</li>\n";
	}
	echo "</ul></li>\n";
}
echo "</ul></li>\n";

if(count($message['supps']))
{
	echo "<li>Supplements:<ul>\n";
	foreach($message['supps'] as $supp)
		echo "<li>{$supp['code']} - {$supp['text']}</li>\n";
	echo "</ul></li>\n";
}

if(count($message['diversions']))
{
	echo "<li>Diversions:<ul>\n";
	foreach($message['diversions'] as $diversion)
	{
		echo "<li>";
		if(array_key_exists('destinations', $diversion))
			echo "Diversion to " . implode(", ", array_map("location_link", $diversion['destinations']));
		else
			echo "General diversion";
		echo " via " . implode(", ", array_map("location_link", $diversion['route'])) . "</li>\n";
	}
	echo "</ul></li>\n";
}

if(array_key_exists('cross', $message))
	echo "<li>Cross-linked location: " . location_link($message['cross']) . "</li>\n";

echo "</ul>\n";

echo "<h3>OSM linked data</h3>\n";
echo "<ul>\n";
echo "<li><a href=\"http://overpass-turbo.eu/map.html?Q=" . rawurlencode($opquery) . "\">Show as Overpass-Turbo map</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=xml&amp;data=" . rawurlencode($opquery) . "\">Convert to XML</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=mapql&amp;data=" . rawurlencode($opquery) . "\">Convert to pretty Overpass QL</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=compact&amp;data=" . rawurlencode($opquery) . "\">Convert to compact Overpass QL</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=ol_fixed&amp;data=" . rawurlencode($opquery) . "\">Show as auto-centered overlay</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=ol_bbox&amp;data=" . rawurlencode($opquery) . "\">Show as slippy overlay</a></li>\n";
echo "</ul>\n";
?>
</div>
</body>
</html>
