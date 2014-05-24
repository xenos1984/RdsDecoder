<?php
include_once('tmcdecode.php');
include_once('tmclocation.php');
include_once('tmcjson.php');

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

$roles = array('' => array(), 'entry' => array(), 'exit' => array(), 'ramp' => array(), 'parking' => array(), 'fuel' => array(), 'restaurant' => array());

foreach($message['iblocks'] as $iblock)
{
	foreach($iblock['events'] as $event)
	{
		switch($event['code'])
		{
		case 101: // stationary traffic
			$roles[''][] = 'traffic';
			break;
		case 1990: // car park closed (until Q)
			$roles['parking'][] = 'closed';
			break;
		default:
			break;
		}
	}
}

array_map("array_unique", $roles);

$primary = find_place($cid, $tabcd, $lcd);
if($primary['class'] == 'P')
	$secondary = find_offsets($cid, $tabcd, $lcd, $ext, $dir);
else
	$secondary = array($primary);

$opquery = "(";
if($primary['class'] == 'P')
{
	foreach($secondary as $location)
	{
		$opquery .= "relation[\"type\"=\"tmc:point\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"{$location['lcd']}\"];";
		if(array_key_exists('pos_off_lcd', $location) && array_key_exists($location['pos_off_lcd'], $secondary))
			$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"neg_lcd\"=\"{$location['lcd']}\"][\"pos_lcd\"=\"{$location['pos_off_lcd']}\"];";
		if(array_key_exists('neg_off_lcd', $location) && array_key_exists($location['neg_off_lcd'], $secondary))
			$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"pos_lcd\"=\"{$location['lcd']}\"][\"neg_lcd\"=\"{$location['neg_off_lcd']}\"];";
	}
}
else if($primary['class'] == 'A')
{
	$opquery .= "relation[\"type\"=\"tmc:area\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"{$location['lcd']}\"];";
}
$opquery .= ");";
$opquery = "(${opquery}rel(r););";
$opquery = "(${opquery}>;);out meta;";

$opurl = "http://overpass-api.de/api/interpreter?data=" . rawurlencode($opquery);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $opurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$opdata = curl_exec($ch);
curl_close($ch);

if($opdata === false)
	$opdata = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<osm version=\"0.6\" generator=\"Overpass API\">\n</osm>";

$osmxml = new DOMDocument;
$osmxml->formatOutput = false;
$osmxml->loadXML($opdata);
$osmxp = new DOMXPath($osmxml);

$nodes = array();
$osmnodes = $osmxp->query("/osm/node");
foreach($osmnodes as $osmnode)
	$nodes[$osmnode->getAttribute('id')] = $osmnode;

$ways = array();
$osmways = $osmxp->query("/osm/way");
foreach($osmways as $osmway)
	$ways[$osmway->getAttribute('id')] = $osmway;

$rels = array();
$osmrels = $osmxp->query("/osm/relation");
foreach($osmrels as $osmrel)
	$rels[$osmrel->getAttribute('id')] = $osmrel;

$features = array();
foreach($rels as $rel => $osmrel)
{
	$relprops = array('relation' => $rel);
	$reltags = $osmxp->query("tag", $osmrel);
	foreach($reltags as $reltag)
		$relprops[$reltag->getAttribute('k')] = $reltag->getAttribute('v');

	if(!array_key_exists('type', $relprops))
		continue;

	if(substr($relprops['type'], 0, 4) != 'tmc:')
		continue;

	$members = $osmxp->query("member", $osmrel);
	foreach($members as $member)
	{
		$id = $member->getAttribute('ref');
		$type = $member->getAttribute('type');
		$role = $member->getAttribute('role');
		$props = array('id' => $id, 'member' => $type, 'role' => $role);

		if(!preg_match('/(positive|negative|both|):?(entry|exit|ramp|parking|fuel|restaurant|)/', $role, $matches))
			continue;

		//echo "<!--"; print_r($matches); echo "-->\n";

		if(($message['directions'] == 1) && ($matches[1] == ($message['direction'] ? 'negative' : 'positive')))
			continue;

		if(!array_key_exists($matches[2], $roles))
			continue;

		if(!count($roles[$matches[2]]))
			continue;

		$props['message'] = $roles[$matches[2]];

		if($type == 'node')
		{
			$geom = array('type' => 'Point', 'coordinates' => array($nodes[$id]->getAttribute('lon'), $nodes[$id]->getAttribute('lat')));
		}
		else if($type == 'way')
		{
			$wns = $osmxp->query('nd', $ways[$id]);
			$coords = array();
			foreach($wns as $wn)
			{
				$nd = $wn->getAttribute('ref');
				$coords[] = array($nodes[$nd]->getAttribute('lon'), $nodes[$nd]->getAttribute('lat'));
			}
			if(($coords[0][0] == $coords[count($coords) - 1][0]) && ($coords[0][1] == $coords[count($coords) - 1][1]))
				$geom = array('type' => 'Polygon', 'coordinates' => array($coords));
			else
				$geom = array('type' => 'LineString', 'coordinates' => $coords);
		}

		$features[] = array('type' => 'Feature', 'properties' => array_merge($props, $relprops), 'geometry' => $geom);
	}
}

$featcoll = array('type' => 'FeatureCollection', 'features' => $features);
$osmjson = json_string($featcoll);

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
osmdata = <?php echo $osmjson; ?>;
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
		if($event['reference'] != '')
			echo $event['reference'] . ": ";
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
