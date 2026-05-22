<?php
$html = '<div>
    <div class="row">
        <span>Content</span>
    </div>
    
    <script>
        console.log("test");
    </script>
</div>';

$dom = new \DOMDocument();
@$dom->loadHTML($html);
$body = $dom->getElementsByTagName('body')->item(0);
var_dump($body !== null);
if ($body) {
    var_dump($body->childNodes->length);
}
