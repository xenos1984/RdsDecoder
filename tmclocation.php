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
	static $tables = array("points", "segments", "roads", "administrativearea", "otherareas");

	foreach($tables as $table)
	{
		if($data = find_location($table, $cid, $tabcd, $lcd))
			return $data;
	}

	return array();
}
?>
