<?php

namespace Scarbous\MrSpeed\Optimize;


use Scarbous\MrSpeed\Utility\GeneralUtility;
use Scarbous\MrSpeed\Utility\SettingsUtility;

class Css extends AbstractOptimizer
{

    /**
     * Css constructor.
     */
    function __construct()
    {
        parent::__construct('css', \MatthiasMullie\Minify\CSS::class);
        add_action('wp_default_styles', [$this, 'loadWpDependencies']);
        add_filter('print_styles_array', [$this, 'optimize']);
    }

    /**
     * @return array
     */
    function optimize()
    {
        $queue = $this->getQueue();
        if (count($queue['internal']) > 0) {
            foreach ($queue['internal'] as $media => $styles) {
                $newStyle = $this->optimizeElements($styles);
                $newStyle['media'] = $media;
                echo $this->printTag($newStyle);
            }
        }
        return $this->wpDependencies->to_do;
    }

    /**
     * @param array $data
     * @return string
     */
    function printTag($data)
    {
        $attributes = [];
        $attributes['href'] = $data['url'];
        $attributes['rel'] = "stylesheet";
        $attributes['type'] = "text/css";
        $attributes['media'] = $data['media'];
        if (SettingsUtility::isDebug()) {
            $attributes['data-keys'] = $data['keys'];
        }
        $attributes = apply_filters(MRSPEED_HOOK_PREFIX . '_css_printTag_attributes', $attributes);
        $tag = '<link ' . GeneralUtility::getTagAttributes($attributes) . '/>' . PHP_EOL;
        return apply_filters(MRSPEED_HOOK_PREFIX . '_css_printTag', $tag);
    }
}