<?php

namespace Scarbous\MrSpeed;

use Scarbous\MrSpeed\Optimize\JavaScript;
use Scarbous\MrSpeed\Optimize\Css;

class Core extends AbstractSingleton
{

    /**
     * Core constructor.
     */
    public function __construct()
    {
        if (is_admin()) {
            Settings::getInstance();
        } else {
            Css::getInstance();
            JavaScript::getInstance();
        }
    }
}
