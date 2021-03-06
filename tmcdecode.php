<?php
include_once('rdspdo.php');

function decode_time($time, $now = 0)
{
	if(!$now)
		$now = time();

	$today = 86400 * (int)($now / 86400);
	$tomorrow = $today + 86400;
	if($time < 96)
		return date("l j. F H:i T", $today + 900 * $time);
	else if($time < 201)
		return date("l j. F H:i T", $tomorrow + 3600 * ($time - 96));
	else if($time < 232)
		return ($time - 200) . ". " . date("F", strtotime(($time - 200 > date("j", $now) ? "today UTC" : "first day of next month UTC")));
	else
		return ($time % 2 ? "end of" : "middle of") . " " . date("F", strtotime((int)(($time - 230) / 2) . "/1"));
}

function decode_duration($dur, $type, $nature)
{
	if(strpos($type, 'D') !== false)
	{
		$text = ($nature == 'F' ? "within the next " : "for at least ");
		switch($dur)
		{
		case 1:
			$text .= "15 minutes";
			break;
		case 2:
			$text .= "30 minutes";
			break;
		case 3:
			$text .= "1 hour";
			break;
		case 4:
			$text .= "2 hours";
			break;
		case 5:
			$text .= "3 hours";
			break;
		case 6:
			$text .= "4 hours";
			break;
		case 7:
			$text = ($nature == 'F' ? "later today" : "for the rest of the day");
			break;
		default:
			$text = "no time given";
			break;
		}
	}
	else if(strpos($type, 'L') !== false)
	{
		switch($dur)
		{
		case 1:
			$text = ($nature == 'F' ? "within the next few hours" : "for the next few hours");
			break;
		case 2:
			$text = ($nature == 'F' ? "later today" : "for the rest of the day");
			break;
		case 3:
			$text = ($nature == 'F' ? "tomorrow" : "until tomorrow evening");
			break;
		case 4:
			$text = ($nature == 'F' ? "the day after tomorrow" : "for the rest of the week");
			break;
		case 5:
			$text = ($nature == 'F' ? "this weekend" : "until the end of next week");
			break;
		case 6:
			$text = ($nature == 'F' ? "later this week" : "until the end of the month");
			break;
		case 7:
			$text = ($nature == 'F' ? "next week" : "for a long period");
			break;
		default:
			$text = "no time given";
			break;
		}
	}
	else
		$text = "";

	return $text;
}

function decode_message($ecd, $lcd, $dir, $ext, $dur, $div, $bits)
{
	static $ccnames = array('increase urgency', 'decrease urgency', 'switch directionality', 'switch duration', 'switch verbosity', 'set diversion', 'increase extent by 8', 'increase extent by 16');

	$event = find_event($ecd);
	$last = 0;

	$message = array(); // Holds the whole message.
	$message['ccodes'] = array(); // Holds the control codes.
	$message['iblocks'] = array(); // Holds the information blocks.
	$message['iblocks'][] = array(); // The first information block.
	$message['iblocks'][0]['events'] = array($event);
	$message['supps'] = array(); // Holds supplementary information.
	$message['diversions'] = array(); // Holds detailed diversions.
	$message['lcd'] = $lcd;
	$message['direction'] = $dir;
	$message['extent'] = $ext;
	$message['directions'] = $event['direction'];
	$message['urgency'] = $event['urgency'];
	$message['nature'] = $event['nature'];

	echo "Event: $ecd - " . $event['text'];
	echo "\nLocation: $lcd";
	echo "\nDirection: " . ($dir ? "negative" : "positive");
	echo "\nExtent: $ext";
	echo "\nBits: $bits";
	if($dur)
	{
		echo "\nDuration: $dur";
		$message['duration'] = $dur; // Duration only once per message.
		$message['durtype'] = $event['duration'];
	}
	if($div !== null)
	{
		echo "\nDiversion: " . ($div ? "yes" : "no");
		$message['diversion'] = $div;
	}

	while(strpos($bits, '1') !== false)
	{
		$label = bindec(substr($bits, 0, 4));
		$bits = substr($bits, 4);
		switch($label)
		{
		case 0:
			$duration = bindec(substr($bits, 0, 3));
			$bits = substr($bits, 3);
			echo "\nDuration: $duration";
			$message['duration'] = $duration; // Duration (only once per message).
			$message['durtype'] = $message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['duration'];
			break;
		case 1:
			$control = bindec(substr($bits, 0, 3));
			$bits = substr($bits, 3);
			echo "\nControl code: $control - " . $ccnames[$control];
			$message['ccodes'][] = $control; // Add control code.
			break;
		case 2:
			$lencd = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			if($lencd == 0)
				$length = "&gt; 100km";
			else if($lencd <= 10)
				$length = $lencd . "km";
			else if($lencd <= 15)
				$length = sprintf("%dkm", 2 * $lencd - 10);
			else
				$length = sprintf("%dkm", 5 * $lencd - 55);
			echo "\nAffected route length: $lencd - $length";
			$message['iblocks'][count($message['iblocks']) - 1]['length'] = $length; // Affected route length per information block.
			break;
		case 3:
			$spcd = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			$speed = (5 * $spcd) . "km/h";
			$message['iblocks'][count($message['iblocks']) - 1]['speed'] = $speed; // Speed limit advice per information block.
			echo "\nSpeed limit: $spcd - $speed";
			break;
		case 4:
			$qcd = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quant'] = $qcd;
			echo "\nQuantifier (5 bits): $qcd";
			break;
		case 5:
			$qcd = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quant'] = $qcd;
			echo "\nQuantifier (8 bits): $qcd";
			break;
		case 6:
			$scd = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			$supp = find_supplement($scd);
			echo "\nSupplementary information: $scd - " . $supp['text'];
			$message['supps'][] = $supp; // Supplementary information (may be several per message).
			break;
		case 7:
			$start = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			echo "\nStart time: $start";
			$message['start'] = $start; // Start time (only once per message).
			break;
		case 8:
			$stop = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			echo "\nStop time: $stop";
			$message['stop'] = $stop; // Start time (only once per message).
			break;
		case 9:
			$ecd = bindec(substr($bits, 0, 11));
			$bits = substr($bits, 11);
			$event = find_event($ecd);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][] = $event;
			$message['directions'] = min($message['directions'], $event['direction']);
			$message['urgency'] = max($message['urgency'], $event['urgency']);
			if(strlen($message['nature']) < strlen($event['nature']))
				$message['nature'] = $event['nature'];
			echo "\nAdditional event: $ecd - " . $event['text'];
			break;
		case 10:
			$diversion = bindec(substr($bits, 0, 16));
			$bits = substr($bits, 16);
			echo "\nDiversion: $diversion";
			if($last != $label)
			{
				$message['diversions'][] = array();
				$message['diversions'][count($message['diversions']) - 1]['route'] = array();
				if($last == 11)
					$message['diversions'][count($message['diversions']) - 1]['destinations'] = $dests;
			}
			$message['diversions'][count($message['diversions']) - 1]['route'][] = $diversion;
			break;
		case 11:
			$destination = bindec(substr($bits, 0, 16));
			$bits = substr($bits, 16);
			echo "\nDestination: $destination";
			if($last != $label)
				$dests = array();
			$dests[] = $destination;
			if((bindec(substr($bits, 0, 4)) != 10) && (bindec(substr($bits, 0, 4)) != 11))
			{
				$message['iblocks'][count($message['iblocks']) - 1]['destinations'] = $dests;
				unset($dests);
			}
			break;
		case 12:
			$reserved = bindec(substr($bits, 0, 16));
			$bits = substr($bits, 16);
			echo "\nReserved: $reserved";
			break;
		case 13:
			$cross = bindec(substr($bits, 0, 16));
			$bits = substr($bits, 16);
			echo "\nCross link: $cross";
			$message['cross'] = $cross; // Cross link (only once per message).
			break;
		case 14:
			echo "\nSeparator";
			$message['iblocks'][] = array(); // Create new information block.
			break;
		default:
			break;
		}
		$last = $label;
	}

	foreach($message['ccodes'] as $control)
	{
		switch($control)
		{
		case 0:
			$message['urgency'] = ($message['urgency'] + 1) % 3;
			break;
		case 1:
			$message['urgency'] = ($message['urgency'] + 2) % 3;
			break;
		case 2:
			$message['directions'] = 3 - $message['directions'];
			break;
		case 3:
			$message['durtype'] = preg_replace_callback('/(D|L)/', function($matches) {return chr(144 - ord($matches[0]));}, $message['durtype']);
			break;
		case 4:
			$message['durtype'] = (strlen($message['durtype']) == 3 ? substr($message['durtype'], 1, 1) : "({$message['durtype']})");
			break;
		case 5:
			$message['diversion'] = 1;
			break;
		case 6:
			$message['extent'] += 8;
			break;
		case 7:
			$message['extent'] += 16;
			break;
		default:
			break;
		}
	}

	return $message;
}

function decode_tmc($blocks)
{
	static $message = array();
	static $last = array(0,0,0,0);

	if(array_reduce(array_map(function($a, $b) {return $a == $b;}, $blocks, $last), function($a, $b) {return $a and $b;}, true))
		return;

	$x = $blocks[1] & 0x1f;
	$y = $blocks[2];
	$z = $blocks[3];

	$result = false;
	$multi = ($x >> 3) & 0x3;

	if($multi == 1)
	{
		//echo "Single group message.\n";
		$message['lcd'] = $z;
		$message['ecd'] = $y & 0x7ff;
		$message['ext'] = ($y >> 11) & 0x7;
		$message['dir'] = ($y >> 14) & 0x1;
		$message['div'] = ($y >> 15) & 0x1;
		$message['dur'] = $x & 0x7;

		$result = $message;
		$message = array();
	}
	else if($multi == 0)
	{
		$continuity = $x & 0x7;
		$first = ($y >> 15) & 0x1;
		//echo "Multi group message; CI = $continuity;";
		if($first)
		{
			//echo " first.\n";
			$message['lcd'] = $z;
			$message['ecd'] = $y & 0x7ff;
			$message['ext'] = ($y >> 11) & 0x7;
			$message['dir'] = ($y >> 14) & 0x1;
		}
		else
		{
			$second = ($y >> 14) & 0x1;
			$gsi = ($y >> 12) & 0x3;

			$bit = str_pad(decbin((($y & 0xfff) << 16) + $z), 28, '0', STR_PAD_LEFT);
			if($second)
			{
				//echo " second;";
				$message['bits'] = $bit;
			}
			else
			{
				$message['bits'] .= $bit;
			}
			//echo " gsi = $gsi.\n";

			if(!$gsi)
			{
				if(array_key_exists('ecd', $message))
					$result = $message;
				$message = array();
			}
		}
	}

	$last = $blocks;

	return $result;
}

function system_tmc($blocks)
{
	echo "\nTMC system information:\n";
	echo sprintf("Application ID %04x.\n", $blocks[3]);

	if($blocks[3] != 0xcd46)
		return;

	switch($blocks[2] >> 14)
	{
	case 0:
		$tabcd = ($blocks[2] >> 6) & 0x3f;
		echo "Location table number $tabcd.\n";
		break;
	case 1:
		$sid = ($blocks[2] >> 6) & 0x3f;
		echo "Service identifier $sid.\n";
		break;
	default:
		break;
	}
}
?>
