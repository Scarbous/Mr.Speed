<?php
namespace Scarbous\MrSpeed;

use Scarbous\MrSpeed\Optimize\JavaScript;
use Scarbous\MrSpeed\Optimize\Css;

class Core
{

    public function __construct()
    {
        if (is_admin()) {
            $my_settings_page = new Settings();
        } else {
            new Css();
            new JavaScript();
        }
    }
}
