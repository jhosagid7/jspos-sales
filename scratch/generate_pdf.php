<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$markdown = file_get_contents('C:/Users/User/.gemini/antigravity/brain/72cb4acc-0574-4ce6-b720-974ea726d392/soplados_app_plan.md');

// Remove the image from the PDF or convert it to a format dompdf supports.
// Local images might fail if not absolute path correctly.
$markdown = preg_replace('/!\[.*?\]\(.*?\)/', '', $markdown);

$html = \Illuminate\Support\Str::markdown($markdown);
$html = '<html><head><meta charset="UTF-8"><style>body{font-family:sans-serif;} table {border-collapse: collapse; width: 100%;} th, td {border: 1px solid #ddd; padding: 8px;} th {background-color: #f2f2f2;}</style></head><body>' . $html . '</body></html>';

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
$pdf->save('public/Plan_Accion_Soplados.pdf');
echo "PDF generated successfully.";
