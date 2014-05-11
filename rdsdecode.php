<?php
include_once('tmcdecode.php');

function decode_group($blockA, $blockB, $blockC, $blockD)
{
	if($blockB & 0xf800 == 0x8000)
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

decode_hex("d3 68 85 46 e0 cb 2f ca");
decode_hex("d3 68 85 46 6e 95 31 45");
decode_hex("d3 68 85 46 1f a7 45 ac");
decode_hex("d3 68 85 46 04 00 00 00");

?>
