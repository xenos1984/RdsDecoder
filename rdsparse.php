<?php
include_once('rdsdecode.php');

header("Content-type: text/plain");
decode_hex_text($_REQUEST['text']);
?>
