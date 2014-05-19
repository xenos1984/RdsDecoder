<?php
function read_overpass($request)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $opurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$opdata = curl_exec("http://overpass-api.de/api/interpreter?data=" . rawurlencode($request));

	if($opdata === FALSE)
		$opdata = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<osm version=\"0.6\" generator=\"Overpass API\">\n</osm>";

	curl_close($ch);

	$osm = new DOMDocument;
	$osm->formatOutput = false;
	$osm->loadXML($opdata);

	return $osm;
}

function find_osm_point($cid, $tabcd, $lcd)
{
	return read_overpass("(relation[\"type\"=\"tmc:point\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"$lcd\"];>;);out meta;");
}

function find_osm_area($cid, $tabcd, $lcd)
{
	return read_overpass("(relation[\"type\"=\"tmc:area\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"$lcd\"];>;);out meta;");
}
?>
