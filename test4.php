<?php
$html = file_get_contents('resources/views/livewire/welcome/page.blade.php');
$dom = new \DOMDocument();
@$dom->loadHTML($html);
var_dump($dom->getElementsByTagName('body')->item(0) !== null);
