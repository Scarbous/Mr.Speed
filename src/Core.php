<?php
namespace Scarbous\MrSpeed;

use Scarbous\MrSpeed\Optimize\JavaScript;

class Core
{

    public function __construct()
    {
        wp_enqueue_script('BootstrapJs', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery-core'),'3.3.7');
        wp_enqueue_script('testJs', '/wp-content/themes/test/test.js', array('jquery-core'),'0.0.1');

        if (is_admin()) {
            $my_settings_page = new Settings();
        } else {
           new JavaScript();
        }
    }
}
