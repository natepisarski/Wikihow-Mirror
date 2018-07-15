<?php

require_once __DIR__ . '/../../commandLine.inc';

$aid = 2053;
$name = "Initiating a Kiss";
$answer = 2;
$data = [
	"options" => ["this is the first", "this is the second", "this is the third"],
	"explanations" => ["something one", "something two", "something"]
];

//Quiz::insertRow($aid, $name, $answer, $data);

ArticleQuizzes::importSpreadsheet();