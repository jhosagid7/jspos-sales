<?php
$content = file_get_contents('resources/views/layouts/theme/sidebar.blade.php');
$tokens = token_get_all('<?php ' . $content); // This won't work well for Blade

// Better: find all @directives and @enddirectives
preg_match_all('/(@[a-z]+)/', $content, $matches);
$allDirectives = $matches[1];

$stack = [];
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    // Find all directives in this line
    preg_match_all('/(@(if|canany|can|role|unlessrole|module|unless|auth|guest|isset|empty))\b(?!\w)/', $line, $openMatches);
    preg_match_all('/(@(endif|endcanany|endcan|endrole|endunlessrole|endmodule|endunless|endauth|endguest|endisset|endempty))\b(?!\w)/', $line, $closeMatches);
    
    foreach ($openMatches[1] as $m) {
        $stack[] = ['type' => $m, 'line' => $lineNum];
    }
    
    foreach ($closeMatches[1] as $m) {
        if (empty($stack)) {
            echo "EXTRA CLOSE: $m at line $lineNum\n";
            continue;
        }
        $last = array_pop($stack);
        $expectedClose = str_replace('@', '@end', $last['type']);
        if ($m !== $expectedClose) {
             // Some directives share @endif
             $isIfGroup = in_array($last['type'], ['@if', '@unless', '@isset', '@empty']);
             if ($isIfGroup && $m === '@endif') {
                 // OK
             } else {
                 echo "MISMATCH: $last[type] (line $last[line]) closed by $m (line $lineNum)\n";
             }
        }
    }
}

foreach ($stack as $s) {
    echo "UNCLOSED: $s[type] at line $s[line]\n";
}
