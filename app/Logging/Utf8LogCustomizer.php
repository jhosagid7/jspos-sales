<?php

namespace App\Logging;

use Monolog\Logger;

class Utf8LogCustomizer
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new Utf8LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s",
                true,
                true
            ));
        }
    }
}
