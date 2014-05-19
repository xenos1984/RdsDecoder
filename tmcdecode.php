<?php
include_once('rdspdo.php');

function decode_time($time)
{
	$now = time();
	$today = 86400 * (int)($now / 86400);
	$tomorrow = $today + 86400;
	if($time < 96)
		return date("l j. F H:i T", $today + 900 * $time);
	else if($time < 201)
		return date("l j. F H:i T", $tomorrow + 3600 * ($time - 96));
	else if($time < 232)
		return ($time - 200) . ". " . date("F", strtotime(($time - 200 > date("j") ? "today UTC" : "first day of next month UTC")));
	else
		return ($time % 2 ? "end of" : "middle of") . " " . date("F", strtotime((int)(($time - 230) / 2) . "/1"));
}

function decode_message($ecd, $lcd, $dir, $ext, $dur, $div, $bits)
{
	static $units = array('', '', 'm', '%', 'km/h', 'min', '°C', 'min', 't', 'm', 'mm', 'MHz', 'kHz');
	static $ccnames = array('increase urgency', 'decrease urgency', 'switch directionality', 'switch duration', 'switch verbosity', 'set diversion', 'increase extent by 8', 'increase extent by 16');
	static $urgnames = array('normal', 'urgent', 'extremely urgent');

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

	echo "\nEvent: $ecd - " . $event['text'];
	echo "\nLocation: $lcd";
	echo "\nDirection: " . ($dir ? "negative" : "positive");
	echo "\nExtent: $ext";
	if($dur !== null)
	{
		echo "\nDuration: $dur";
		$message['duration'] = $dur; // Duration only once per message.
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
			break;
		case 1:
			$control = bindec(substr($bits, 0, 3));
			$bits = substr($bits, 3);
			echo "\nControl code: $control - " . $ccnames[$control];
			$message['ccodes'][] = $control; // Add control code.
			break;
		case 2:
			$length = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			echo "\nAffected route length: $length - ";
			if($length == 0)
				echo "&gt; 100km";
			else if($length <= 10)
				echo "${length}km";
			else if($length <= 15)
				echo sprintf("%dkm", 2 * $length - 10);
			else
				echo sprintf("%dkm", 5 * $length - 55);
			$message['iblocks'][count($message['iblocks']) - 1]['length'] = $length; // Affected route length per information block.
			break;
		case 3:
			$speed = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			echo "\nSpeed limit: " . (5 * $speed) . "km/h";
			$message['iblocks'][count($message['iblocks']) - 1]['speed'] = $speed; // Speed limit advice per information block.
			break;
		case 4:
			$qcd = bindec(substr($bits, 0, 5));
			$bits = substr($bits, 5);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quant'] = $qcd;
			$value = find_quantifier($message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quantifier'], $qcd) . $units[$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quantifier']];
			echo "\nQuantifier (5 bits): $qcd, Q = $value";
			break;
		case 5:
			$qcd = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quant'] = $qcd;
			$value = find_quantifier($message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quantifier'], $qcd) . $units[$message['iblocks'][count($message['iblocks']) - 1]['events'][count($message['iblocks'][count($message['iblocks']) - 1]['events']) - 1]['quantifier']];
			echo "\nQuantifier (8 bits): $qcd, Q = $value";
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
			echo "\nStart time: $start - " . decode_time($start);
			$message['start'] = $start; // Start time (only once per message).
			break;
		case 8:
			$stop = bindec(substr($bits, 0, 8));
			$bits = substr($bits, 8);
			echo "\nStop time: $stop - " . decode_time($stop);
			$message['stop'] = $stop; // Start time (only once per message).
			break;
		case 9:
			$ecd = bindec(substr($bits, 0, 11));
			$bits = substr($bits, 11);
			$event = find_event($ecd);
			$message['iblocks'][count($message['iblocks']) - 1]['events'][] = $event;
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
				$message['iblocks'][count($message['iblocks']) - 1]['destinations'] = $dest;
				unset($dest);
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

	echo "\n";
	print_r($message);
	echo "\n";
}

function decode_tmc($blockA, $blockB, $blockC, $blockD)
{
	static $ecd = 0, $lcd, $dir, $ext, $bits;
	static $lastA = 0, $lastB = 0, $lastC = 0, $lastD = 0;

	if(($lastA == $blockA) && ($lastB == $blockB) && ($lastC == $blockC) && ($lastD == $blockD))
		return;

	$x = $blockB & 0x1f;
	$y = $blockC;
	$z = $blockD;

	$multi = ($x >> 3) & 0x3;

	if($multi == 1)
	{
		$lcd = $z;
		$ecd = $y & 0x7ff;
		$ext = ($y >> 11) & 0x7;
		$dir = ($y >> 14) & 0x1;
		$div = ($y >> 15) & 0x1;
		$dur = $x & 0x7;

		decode_message($ecd, $lcd, $dir, $ext, $dur, $div, "");
	}
	else if($multi == 0)
	{
		$continuity = $x & 0x7;
		$first = ($y >> 15) & 0x1;
		if($first)
		{
			$lcd = $z;
			$ecd = $y & 0x7ff;
			$ext = ($y >> 11) & 0x7;
			$dir = ($y >> 14) & 0x1;
		}
		else
		{
			$second = ($y >> 14) & 0x1;
			$gsi = ($y >> 12) & 0x3;

			$bit = str_pad(decbin((($y & 0xfff) << 16) + $z), 28, '0', STR_PAD_LEFT);
			if($second)
				$bits = $bit;
			else
				$bits .= $bit;

			if((!$gsi) && $ecd)
			{
				decode_message($ecd, $lcd, $dir, $ext, null, null, $bits);
			}
		}
	}

	$lastA = $blockA;
	$lastB = $blockB;
	$lastC = $blockC;
	$lastD = $blockD;
}

?>
