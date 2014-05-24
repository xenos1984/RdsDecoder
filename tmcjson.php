<?php
function json_combine($k, $v)
{
	return "\"$k\": $v";
}

function json_string($array)
{
	if(is_numeric($array))
		return $array;

	if(is_string($array))
		return "\"$array\"";

	if(count(array_filter(array_keys($array), 'is_string')))
		return '{' . implode(", ", array_map("json_combine", array_keys($array), array_map("json_string", $array))) . '}';
	else
		return '[' . implode(", ", array_map("json_string", $array)) . ']';
}
?>
