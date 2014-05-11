<?php
// Enter database connection details here!
$pdo = new PDO('mysql:host=HOSTNAME;port=3306;dbname=DBMANE;charset=utf8', 'USERNAME', 'PASSWORD', array(PDO::ATTR_PERSISTENT => true));

function find_event($code)
{
	global $pdo;
	static $urgencies = array('' => 0, 'U' => 1, 'X' => 2);

	if(($result = $pdo->query("SELECT * FROM events WHERE code = '$code'")) && ($event = $result->fetch(PDO::FETCH_ASSOC)))
	{
		$event['urgency'] = $urgencies[$event['urgency']];
		return $event;
	}

	return array('code' => $code, 'text' => '', 'nature' => '', 'quantifier' => 0, 'duration' => 0, 'direction' => 1, 'urgency' => 0, 'class' => 0, 'reference' => '');
}

function find_supplement($code)
{
	global $pdo;

	if(($result = $pdo->query("SELECT * FROM supplement WHERE code = '$code'")) && ($supp = $result->fetch(PDO::FETCH_ASSOC)))
		return $supp;

	return '';
}

function find_quantifier($type, $code)
{
	global $pdo;

	if(($result = $pdo->query("SELECT value FROM quantifier WHERE type = '$type' and quantifier = '$code'")) && ($value = $result->fetch(PDO::FETCH_COLUMN)))
		return $value;

	return '';
}

?>
