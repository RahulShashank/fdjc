<?php
date_default_timezone_set("GMT");

//$m = new MongoClient("mongodb://10.76.106.125:27017");
$m = new MongoClient();
$db = $m->selectDB('connectivityLogs');
$collection = new MongoCollection($db, 'Ka_connectivityEvents');
$collectionActivity = new MongoCollection($db, 'Ka_connectivityActivity');
//echo var_dump($m).'<br>';
//echo var_dump($db);
?>
