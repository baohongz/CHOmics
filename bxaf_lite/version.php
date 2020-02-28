<?php

$version['primary'] 	= '3';
$version['secondary'] 	= '17';
$version['date']        = '2018-09-07';
$version['time']        = '16:00:00';

$version['version'] 	= $version['primary'] . $version['secondary'];
$version['datetime'] 	= $version['date'] . ' ' . $version['time'];

if ($_GET['output']) echo json_encode($version);
//else echo $version['datetime'];

?>