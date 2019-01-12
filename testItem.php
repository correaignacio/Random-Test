<?php

include("class/Random.php");
include("items.php");
//$items = array("303030");
$cant_items = 300;

$random = new Random();
$random->verbose();
$random->setRunTestFunc('A');
$random->setItems($items);

//$series_mt = $random->generateRandomSerials($cant_items, 'mt_rand');
//$random->setItems($series_mt);

//$series_ri = $random->generateRandomSerials($cant_items, 'random_int');
//$random->setItems($series_ri);

$random->evaluate();