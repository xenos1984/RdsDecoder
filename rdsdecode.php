<?php
function basic_tuning($group)
{
	static $pname = "\x00\x00\x00\x00\x00\x00\x00\x00";

	$offset = 2 * ($group[1] & 0x3);
	$pname[$offset] = chr($group[3] >> 8);
	$pname[$offset + 1] = chr($group[3] & 0xff);
	echo $pname . "\n";
}

function radio_text($group)
{
	static $msg32 = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
	static $msg64 = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
	static $oldflag = 2;

	$flag = ($group[1] >> 4) & 1;
	$addr = $group[1] & 0xf;
	$type = ($group[1] >> 11) & 1;
//	echo "Type: $type, Flag: $flag, Address: $addr\n";

	if($type)
	{
		if($flag != $oldflag)
			$msg32 = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
		$offset = 2 * $addr;
		$msg32[$offset] = chr($group[3] >> 8);
		$msg32[$offset + 1] = chr($group[3] & 0xff);
		echo $msg32 . "\n";
	}
	else
	{
		if($flag != $oldflag)
			$msg64 = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
		$offset = 4 * $addr;
		$msg64[$offset] = chr($group[2] >> 8);
		$msg64[$offset + 1] = chr($group[2] & 0xff);
		$msg64[$offset + 2] = chr($group[3] >> 8);
		$msg64[$offset + 3] = chr($group[3] & 0xff);
		echo $msg64 . "\n";
	}
	$oldflag = $flag;
}

function decode_hex($line)
{
	if(!preg_match('/([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])[^[:xdigit:]]*([[:xdigit:]][[:xdigit:]])/i', $line, $result))
		return false;

	return array(
		(hexdec($result[1]) << 8) + hexdec($result[2]),
		(hexdec($result[3]) << 8) + hexdec($result[4]),
		(hexdec($result[5]) << 8) + hexdec($result[6]),
		(hexdec($result[7]) << 8) + hexdec($result[8])
	);
}

function decode_hex_text($text, $callback = null)
{
	$lines = explode("\n", $text);
	foreach($lines as $line)
	{
		if(($result = decode_hex($line)) && ($callback !== null))
			$callback($result);
	}
}

function decode_hex_url($url, $callback = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$data = curl_exec($ch);
	curl_close($ch);

	if($data !== false)
		decode_hex_text($data, $callback);
}

function decode_hex_file($file, $callback = null)
{
	$input = fopen($file, 'r');
	while(!feof($input))
	{
		if(($result = decode_hex(trim(fgets($input)))) && ($callback !== null))
			$callback($result);
	}
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

	$bit = ord($bit) & 0x01;
	$result = false;
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
			return false;

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
				$result = $group;
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

		return $result;
	}
}

function decode_bit_text($text, $callback = null)
{
	foreach(str_split($text) as $char)
	{
		if(($result = decode_bit($char)) && ($callback !== null))
			$callback($result);
	}
}

function decode_bit_url($url, $callback = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$data = curl_exec($ch);
	curl_close($ch);

	if($data !== false)
		decode_bit_text($data, $callback);
}

function decode_bit_file($file, $callback = null)
{
	$input = fopen($file, 'r');
	while(!feof($input))
	{
		if(($result = decode_bit(fread($input, 1))) && ($callback !== null))
			$callback($result);
	}
	fclose($input);
}

function decode_byte_text($text, $callback = null)
{
	foreach(str_split($text) as $char)
	{
		$byte = ord($char);
		for($i = 7; $i >= 0; $i--)
		{
			if(($result = decode_bit(($byte >> $i) & 1)) && ($callback !== null))
				$callback($result);
		}
	}
}

function decode_byte_url($url, $callback = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$data = curl_exec($ch);
	curl_close($ch);

	if($data !== false)
		decode_byte_text($data, $callback);
}

function decode_byte_file($file, $callback = null)
{
	$input = fopen($file, 'r');
	while(!feof($input))
	{
		$byte = ord(fread($input, 1));
		for($i = 7; $i >= 0; $i--)
		{
			if(($result = decode_bit(($byte >> $i) & 1)) && ($callback !== null))
				$callback($result);
		}
	}
	fclose($input);
}
?>
