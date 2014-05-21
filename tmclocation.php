<?php
include_once('rdspdo.php');

function find_names($data)
{
	global $pdo;
	static $names = array('nid', 'rnid', 'n1id', 'n2id');

	foreach($names as $name)
	{
		if(array_key_exists($name, $data))
		{
			if($data[$name])
			{
				$result = $pdo->query("SELECT name FROM names WHERE cid = '" . $data['cid'] . "' AND nid = '" . $data[$name] . "'");
				if($result && ($value = $result->fetch(PDO::FETCH_COLUMN)))
					$data[$name] = $value;
				else
					$data[$name] = "";
			}
			else
				$data[$name] = "";
		}
	}

	return $data;
}

function find_location($table, $cid, $tabcd, $lcd)
{
	global $pdo;

	$result = $pdo->query("SELECT * FROM $table WHERE cid = '$cid' AND tabcd = '$tabcd' AND lcd = '$lcd'");
	if(!$result)
		return false;
	$data = $result->fetch(PDO::FETCH_ASSOC);
	if(!$data)
		return false;
	return find_names($data);
}

function find_place($cid, $tabcd, $lcd)
{
	static $tables = array('points', 'segments', 'roads', 'administrativearea', 'otherareas');

	foreach($tables as $table)
	{
		if($data = find_location($table, $cid, $tabcd, $lcd))
			return $data;
	}

	return array();
}

function find_offsets($cid, $tabcd, $lcd, $ext, $dir)
{
	global $pdo;

	$off = $lcd;
	$field = ($dir ? 'neg_off_lcd' : 'pos_off_lcd');
	$locations = array();

	for($i = 0; $i <= $ext; $i++)
	{
		if(!($data = find_location('points', $cid, $tabcd, $off)))
			break;
		if($data2 = find_location('poffsets', $cid, $tabcd, $off))
			$data = array_merge($data, $data2);

		$locations[$off] = $data;

		if($i == $ext)
			break;

		$result = $pdo->query("SELECT $field FROM poffsets WHERE cid = '$cid' AND tabcd = '$tabcd' AND lcd = '$off'");
		if(!$result)
			break;
		$off = $result->fetch(PDO::FETCH_COLUMN);
		if(!$off)
			break;
	}

	return $locations;
}

function find_links($data)
{
	$links = array();

	if(array_key_exists('pol_lcd', $data) && $data['pol_lcd'] && ($link = find_location('administrativearea', $data['cid'], $data['tabcd'], $data['pol_lcd'])))
		$links['pol_lcd'] = $link;
	if(array_key_exists('oth_lcd', $data) && $data['oth_lcd'] && ($link = find_location('otherareas', $data['cid'], $data['tabcd'], $data['oth_lcd'])))
		$links['oth_lcd'] = $link;
	if(array_key_exists('seg_lcd', $data) && $data['seg_lcd'] && ($link = find_location('segments', $data['cid'], $data['tabcd'], $data['seg_lcd'])))
		$links['seg_lcd'] = $link;
	if(array_key_exists('roa_lcd', $data) && $data['roa_lcd'] && ($link = find_location('roads', $data['cid'], $data['tabcd'], $data['roa_lcd'])))
		$links['roa_lcd'] = $link;
	if(array_key_exists('neg_off_lcd', $data) && $data['neg_off_lcd'] && ($link = find_location(($data['class'] == 'P' ? 'points' : 'segments'), $data['cid'], $data['tabcd'], $data['neg_off_lcd'])))
		$links['neg_off_lcd'] = $link;
	if(array_key_exists('pos_off_lcd', $data) && $data['pos_off_lcd'] && ($link = find_location(($data['class'] == 'P' ? 'points' : 'segments'), $data['cid'], $data['tabcd'], $data['pos_off_lcd'])))
		$links['pos_off_lcd'] = $link;
	if(array_key_exists('interruptsroad', $data) && $data['interruptsroad'] && ($link = find_location('points', $data['cid'], $data['tabcd'], $data['interruptsroad'])))
		$links['interruptsroad'] = $link;

	return $links;
}
?>
