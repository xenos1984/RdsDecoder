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

			echo "<tr><td class=\"ecd\"><a href=\"tmcmsgmap.php?cid=$cid&amp;tabcd=$tabcd&amp;ecd={$message['ecd']}&amp;lcd={$message['lcd']}&amp;ext={$message['ext']}&amp;dir={$message['dir']}";
			if(array_key_exists('div', $message))
				echo "&amp;div={$message['div']}";
			if(array_key_exists('dur', $message))
				echo "&amp;dur={$message['dur']}";
			if(array_key_exists('bits', $message))
				echo "&amp;bits={$message['bits']}";
			echo "&amp;time=$time\">{$message['ecd']}</a></td><td class=\"event\">{$event['text']}</td>";
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
echo "<!--"; print_r($_REQUEST); echo "-->\n";

$function = "decode_{$_REQUEST['format']}_{$_REQUEST['input']}";

switch($function)
{
case "decode_hex_text":
case "decode_bit_text":
case "decode_byte_text":
case "decode_hex_url":
case "decode_bit_url":
case "decode_byte_url":
	if(array_key_exists($_REQUEST['input'], $_REQUEST))
		call_user_func($function, $_REQUEST[$_REQUEST['input']], "show_group");
	else
		echo "<p>No input given.</p>\n";
	break;
case "decode_hex_file":
case "decode_bit_file":
case "decode_byte_file":
	if(array_key_exists('file', $_FILES))
		call_user_func($function, $_FILES['file']['tmp_name'], "show_group");
	else
		echo "<p>No input given.</p>\n";
	break;
default:
	echo "<p>Invalid input type or format.</p>\n";
	break;
}
?>
</table>
</body>
</html>
