<?php
include_once('rdsdecode.php');
include_once('tmcdecode.php');
include_once('tmclocation.php');

function show_group($group)
{
	global $cid, $tabcd, $time;

	//echo sprintf("<li><pre>%04x %04x %04x %04x</pre></li>\n", $group[0], $group[1], $group[2], $group[3]);

	switch($group[1] >> 11)
	{
	case 0x08: // 4A = Clock-time and date
		$offset = ($group[3] & 0x1f) * ($group[3] & 0x20 ? -1 : 1);
		$minute = ($group[3] >> 6) & 0x3f;
		$hour = ($group[3] >> 12) + (($group[2] & 0x01) << 4);
		$date = ($group[2] >> 1) + (($group[1] & 0x03) << 15);
		$time = ($date - 40587) * 86400 + $hour * 3600 + $minute * 60;
		$loctime = $time + $offset * 1800;
		echo "<tr><td class=\"timestamp\" colspan=\"4\">Timestamp: " . gmdate("d. m. Y H:i T", $time) . " / " . ($group[3] & 0x20 ? "-" : "+") . sprintf("%02d:%02d", ($group[3] >> 1) & 0x0f, ($group[3] & 0x01) * 30) . "</td></tr>\n";
		break;
	case 0x10: // 8A = TMC data
		if($message = decode_tmc($group))
		{
			$event = find_event($message['ecd']);
			$location = find_place($cid, $tabcd, $message['lcd']);

			echo "<tr><td class=\"ecd\"><a href=\"tmcmsgmap.php?ecd={$message['ecd']}&lcd={$message['lcd']}&ext={$message['ext']}&dir={$message['dir']}";
			if(array_key_exists('div', $message))
				echo "&div={$message['div']}";
			if(array_key_exists('dur', $message))
				echo "&dur={$message['dur']}";
			if(array_key_exists('bits', $message))
				echo "&bits={$message['bits']}";
			echo "&time=$time\">{$message['ecd']}</a></td><td class=\"event\">{$event['text']}</td>";
			echo "<td class=\"lcd\"><a href=\"/tmc/tmcview.php?cid=$cid&amp;tabcd=$tabcd&amp;lcd={$message['lcd']}\">$cid:$tabcd:{$message['lcd']}</a></td><td class=\"location\">";
			switch($location['class'])
			{
			case 'P':
				echo trim($location['junctionnumber'] . " " . $location['n1id']);
				break;
			case 'L':
				echo trim($location['roadnumber'] . " " . $location['rnid']);
				break;
			case 'A':
				echo $location['nid'];
				break;
			}
			echo "</td></tr>\n";
		}
		break;
	default:
		//echo sprintf("<!-- %d%s -->\n", $group[1] >> 12, ($group[1] & (1 << 11) ? 'B' : 'A'));
		break;
	}

	flush();
}

$cid = (array_key_exists('cid', $_REQUEST) ? (int)$_REQUEST['cid'] : 58);
$tabcd = (array_key_exists('tabcd', $_REQUEST) ? (int)$_REQUEST['tabcd'] : 1);

$time = (array_key_exists('time', $_REQUEST) ? (int)$_REQUEST['time'] : time());

header("Content-type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="rds.css"/>
<title>RDS decoder</title>
</head>
<body>
<h1>RDS decoder</h1>
<table>
<?php
decode_hex_text($_REQUEST['text'], "show_group");
//decode_bit_file("/data/Maps/TMC/rdsTest.140506-1920", "show_group");
//decode_hex_file("/data/Maps/TMC/tmc20140204.txt", "show_group");
?>
</table>
</body>
</html>
