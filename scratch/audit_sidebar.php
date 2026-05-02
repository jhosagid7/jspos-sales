<?php
$content = file_get_contents('resources/views/layouts/theme/sidebar.blade.php');
$lines = explode("\n", $content);
$stack = [];
foreach ($lines as $i => $line) {
    $lineNum = $i + 1;
    if (preg_match('/(@if|@canany|@can|@role|@unlessrole|@module)\b/', $line, $matches)) {
        $type = $matches[1];
        $stack[] = ['type' => $type, 'line' => $lineNum];
    }
    if (preg_match('/(@endif|@endcanany|@endcan|@endrole|@endunlessrole|@endmodule)\b/', $line, $matches)) {
        $type = $matches[1];
        if (empty($stack)) {
            echo "EXTRA CLOSE: $type at line $lineNum\n";
            continue;
        }
        $last = array_pop($stack);
        $expected = str_replace('@end', '@', $type);
        if ($expected === '@if') $expected = '@if'; // wait
        
        $matchMap = [
            '@endif' => '@if',
            '@endcanany' => '@canany',
            '@endcan' => '@can',
            '@endrole' => '@role',
            '@endunlessrole' => '@unlessrole',
            '@endmodule' => '@module'
        ];
        
        if ($matchMap[$type] !== $last['type']) {
            echo "MISMATCH: Expected " . $matchMap[$type] . " to close with $type, but found " . $last['type'] . " at line " . $last['line'] . " (closed at $lineNum)\n";
        }
    }
}
foreach ($stack as $item) {
    echo "UNCLOSED: " . $item['type'] . " at line " . $item['line'] . "\n";
}
