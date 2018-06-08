<?php

namespace Scarbous\MrSpeed\Optimize;


use Scarbous\MrSpeed\Utility\GeneralUtility;
use Scarbous\MrSpeed\Utility\SettingsUtility;

class JavaScript extends AbstractOptimizer
{

    static protected $count = 0;

    static protected $scripts = [];

    /**
     * @var  \WP_Scripts
     */
    public $wpDependencies;

    /**
     * Css constructor.
     */
    function __construct()
    {
        parent::__construct('js', \MatthiasMullie\Minify\JS::class);
        add_action('wp_default_scripts', [$this, 'loadWpDependencies']);
        add_filter('print_scripts_array', [$this, 'optimize']);
        add_filter('script_loader_tag', [$this, 'output'], 100, 3);
    }

    function output($tag, $handle, $src)
    {
        if (strpos($handle, 'mr_speed_') === 0) {
            $tag = '';
            $extraScripts = [];
            foreach (static::$scripts[$handle] as $item) {
                $extraScripts[] = $this->wpDependencies->print_extra_script($item, false);
            }
            $tag .= "<script type='text/javascript'>\n" .
                "/* <![CDATA[ */\n" .
                implode("", $extraScripts) .
                "/* ]]> */\n" .
                "</script>\n";

            $tag .= $this->printTag([
                'src' => $src,
                'keys' =>  implode(',', static::$scripts[$handle])
            ]);
        }
        return $tag;
    }


    /**
     * @return array
     */
    function optimize()
    {
        static::$count++;
        $script = 'mr_speed_' . static::$count;
        static::$scripts[$script] ='';
        $queue = $this->getQueue();
        if (count($queue['internal']) > 0) {
            $newScript = $this->optimizeElements($queue['internal']);
            static::$scripts[$script] = array_keys($queue['internal']);
            $this->wpDependencies->add($script, $newScript['url'], $this->wpDependencies->to_do);
            $this->wpDependencies->to_do[] = $script;
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
        $attributes['src'] = $data['src'];
        $attributes['type'] = "text/javascript";
        if (SettingsUtility::isDebug()) {
            $attributes['data-keys'] = $data['keys'];
        }
        $attributes = apply_filters(MRSPEED_HOOK_PREFIX . '_js_printTag_attributes', $attributes);
        $tag = '<script ' . GeneralUtility::getTagAttributes($attributes) . '></script>' . PHP_EOL;
        return apply_filters(MRSPEED_HOOK_PREFIX . '_js_printTag', $tag);
    }
}