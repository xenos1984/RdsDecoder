<?php
include_once('tmcdecode.php');

function decode_group($blockA, $blockB, $blockC, $blockD)
{
//	echo sprintf("%04x %04x %04x %04x\n", $blockA, $blockB, $blockC, $blockD);
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

function syndrome($vector)
{
	$syndreg = 0;

	for($k = 25; $k >= 0; $k--)
	{
		$bit = $vector & (1 << $k);
		$left = ($syndreg & 0x200);
		$syndreg = ($syndreg << 1) & 0x3ff;
		if($bit)
			$syndreg ^= 0x31B;
		if($left)
			$syndreg ^= 0x1B9;
	}

	return $syndreg;
}

function error_correction()
{
	$error = array();

	for($i = 0; $i <= 15; $i++)
	{
		$err = 1 << $i;
		$error[syndrome(0x4b9 ^ ($err << 10))] = $err;
	}

	for($p = 1; $p <= 32; $p += 2)
	{
		for($i = 0; $i <= 16 - (int)(log($p, 2) + 1); $i++)
		{
			$err = $p << $i;
			$error[syndrome(0x5b9 ^ ($err << 10))] = $err;
		}
	}

	return $error;
}

function decode_bit($bit)
{
	static $group = array();
	static $block = 0, $wideblock = 0, $pi = 0;
	static $bits = 0, $pbits = 0, $ltr = 0;
	static $sync = false;
	static $last = '', $expected = '';

	static $blocks = array('A', 'B', 'C', 'Ci', 'D');
	static $offset = array('A' => 0x0FC, 'B' => 0x198, 'C' => 0x168, 'Ci' => 0x350, 'D' => 0x1B4);
	static $distance = array('A' => 0, 'B' => 1, 'C' => 2, 'Ci' => 2, 'D' => 3);
	static $received = array('A' => false, 'B' => false, 'C' => false, 'Ci' => false, 'D' => false);
	static $next = array('A' => 'B', 'B' => 'C', 'C' => 'D', 'Ci' => 'D', 'D' => 'A');
	static $error = false;
	static $errors = array();
	static $match = array();
/*
	sleep(1);
	echo $bit . "\n";
*/
	if(!$error)
		$error = error_correction();

	$bits++;
	$wideblock = (($wideblock << 1) + $bit) & 0xfffffff;
	$block = ($wideblock >> 1) & 0x3ffffff;

	foreach($offset as $key => $value)
		$match[$key] = (syndrome($block ^ $value) == 0);

	if(!$sync)
	{
		if(array_search(true, $match) !== false)
		{
			foreach($match as $key => $value)
			{
				if($value)
				{
//					echo "Sync $key at $bits.\n";
					$diff = $bits - $pbits;
					if(($last != '') && ($diff % 26 == 0) && ($diff <= 156) && (($distance[$last] + $diff / 26) % 4 == $distance[$key]))
					{
						$sync = true;
						$expected = $key;
					}
					else
					{
						$pbits = $bits;
						$last = $key;
					}
				}
			}
		}
	}

	if($sync)
	{
		$ltr--;
		if($ltr > 0)
			return;

//		echo str_pad(decbin($block), 26, '0', STR_PAD_LEFT) . "\n";
		$data = $block >> 10;
		$ltr = 26;

		if(($expected == 'C') && !$match['C'] && $match['Ci'])
			$expected = 'Ci';

		if(!$match[$expected])
		{
			if(($expected == 'A') && ($pi != 0) && ($data == $pi))
			{
//				echo "Error in check, PI in block A, ignore.\n";
				$match['A'] = true;
			}
			else if(($expected == 'C') && ($pi != 0) && ($data == $pi))
			{
//				echo "Error in check, PI in block C, ignore.\n";
				$match['C'] = true;
			}
			else if(($expected == 'A') && ($pi != 0) && ((($wideblock >> 12) & 0xffff) == $pi))
			{
//				echo "Clock slip, one bit too many.\n";
				$data = $pi;
				$match['A'] = true;
				$ltr--;
			}
			else if(($expected == 'A') && ($pi != 0) && ((($wideblock >> 10) & 0xffff) == $pi))
			{
//				echo "Clock slip, one bit too few.\n";
				$data = $pi;
				$match['A'] = true;
				$ltr++;
			}

			$synd = syndrome($block ^ $offset[$expected]);

			if(array_key_exists($synd, $error))
			{
//				echo "Burst error.\n";
				$data = ($block >> 10) ^ $error[$synd];
				$match[$expected] = true;
			}
		}

		if($match[$expected])
		{
//			echo "Block $expected received.\n";

			$errors[] = 0;

			if($expected == 'A')
				$pi = $data;

			$received[$expected] = true;
			$group[$distance[$expected]] = $data;

			if($received['A'] && $received['B'] && ($received['C'] || $received['Ci']) && $received['D'])
				decode_group($group[0], $group[1], $group[2], $group[3]);
		}
		else
		{
//			echo "Uncorrectable error.\n";
			$errors[] = 1;
		}

		$expected = $next[$expected];
		if($expected == 'A')
			$received = array('A' => false, 'B' => false, 'C' => false, 'Ci' => false, 'D' => false);

		if(count($errors) > 50)
			$errors = array_slice($errors, 1);

		if(array_sum($errors) > 45)
		{
//			echo "Lost sync!";
			$sync = false;
			$errors = array();
			$expected = '';
			$last = '';
			$bits = 0;
			$pbits = 0;
			$ltr = 0;
			$received = array('A' => false, 'B' => false, 'C' => false, 'Ci' => false, 'D' => false);
		}
	}
}

function decode_bit_text($text)
{
	foreach(str_split($text) as $char)
		decode_bit((int)$char);
}

function decode_bit_file($file)
{
	$input = fopen($file, 'r');
	while(!feof($input))
		decode_bit((int)fread($input, 1));
	fclose($input);
}

//decode_bit_file("/data/Maps/TMC/rdsTest.140506-1920");
//decode_hex_file("/data/Maps/TMC/tmc20140204.txt");
?>
