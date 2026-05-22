<?php
$html = "   ";
$dom = new \DOMDocument();
@$dom->loadHTML($html);
$body = $dom->getElementsByTagName('body')->item(0);
var_dump($body !== null);
