<?php
include_once('tmcdecode.php');

function decode_group($blockA, $blockB, $blockC, $blockD)
{
	if(($blockB & 0xf800) == 0x8000)
		decode_tmc($blockA, $blockB, $blockC, $blockD);
}

function decode_hex($line)
{
	if(!preg_match('/([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])/i', $line, $result))
		return;

	$blockA = (hexdec($result[1]) << 8) + hexdec($result[2]);
	$blockB = (hexdec($result[3]) << 8) + hexdec($result[4]);
	$blockC = (hexdec($result[5]) << 8) + hexdec($result[6]);
	$blockD = (hexdec($result[7]) << 8) + hexdec($result[8]);

	decode_group($blockA, $blockB, $blockC, $blockD);
}

function decode_hex_text($text)
{
	$lines = explode("\n", $text);
	foreach($lines as $line)
		decode_hex($line);
}

function decode_hex_file($file)
{
	$input = fopen($file, 'r');
	while(!feof($input))
		decode_hex(trim(fgets($input)));
	fclose($input);
}
?>
