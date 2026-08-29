<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class Utf8LineFormatter extends LineFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $output = parent::format($record);
        
        // Ensure UTF-8 encoding
        if (!mb_check_encoding($output, 'UTF-8')) {
            $output = @mb_convert_encoding($output, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }
        
        return $output;
    }
}
