<?php
namespace Scarbous\MrSpeed\Optimize;

use Scarbous\MrSpeed\Utility\GeneralUtility;
use MatthiasMullie\Minify;

class Css
{
    /**
     * List of Libs witch should not be optimized
     * @var array
     */
    private $excludeStyles = [
        'admin-bar'
    ];

    /**
     * @var \WP_Styles
     */
    private $wpStyles;

    /**
     * JavaScript constructor.
     */
    function __construct()
    {
        $this->settings = get_option('mr_speed');
        $this->settings = $this->settings['css'];

        if ($this->settings['active']) {
            if (!is_admin()) {
                $this->loadExcludeStyles();

                add_action('wp_default_styles', [$this, 'loadWpStyles']);
                add_filter('print_styles_array', [$this, 'optimizeJavaScript']);
                if ($this->settings['shrink']) {
                    #add_Filter('mrSpeed.JS.content', [$this, 'shrinkCss']);
                }
            }
        }
    }

    function loadExcludeStyles()
    {
        $this->excludeStyles = array_merge(
            $this->excludeStyles,
            explode(PHP_EOL, $this->settings['excludeStyles'])
        );
        $this->excludeStyles = array_map('trim', $this->excludeStyles);
    }

    /**
     * Loads the WP_Styles Class
     * @param \WP_Styles $wpStyles
     */
    function loadWpStyles(&$wpStyles)
    {
        $this->wpStyles = &$wpStyles;
    }

    /**
     * @return array
     */
    function optimizeJavaScript()
    {
        $styles = $this->getScriptQueue();

        if (count($styles['internal']) == 0)
            return ([]);

        $fileName = md5(implode('', array_keys($styles['internal']))) . '.css';
        $cacheFilePath = GeneralUtility::getTempDir('css') . $fileName;
        $cacheFileUrl = GeneralUtility::getTempUrl( 'css' ) . $fileName;

        if (!file_exists($cacheFilePath)) {
            $minifier = new Minify\CSS();
            foreach ($styles['internal'] as $style) {
                $minifier->add('/'.$style);
            }

            if (!is_dir(dirname($cacheFilePath))) {
                mkdir(dirname($cacheFilePath), 0777, true);
            }
            $minifier->minify($cacheFilePath);
            $minifier->gzip($cacheFilePath . '.gzip');
        }

        echo '<link rel="stylesheet" data-test="123" href="' . $cacheFileUrl . ($this->settings['gzip']?'.gzip':'') . '" type="text/css" />';

        return ($this->wpStyles->to_do);
    }

    /**
     * Returns the list of JavaScripts which should be minified
     *
     * @return array
     */
    private function getScriptQueue()
    {
        $theStyles = array();

        foreach ($this->wpStyles->to_do as $cssKey => $css) {

            if (in_array($css, $this->excludeStyles)) continue;


            if ($this->wpStyles->query($css, 'queue')) {
                $query = $this->wpStyles->query($css);

                if (filter_var($query->src, FILTER_VALIDATE_URL) && GeneralUtility::isUrlExternal($query->src)) {
                    $theStyles['external'][$css] = $query->src;
                } else {
                    $this->wpStyles->done[] = $this->wpStyles->to_do[$cssKey];
                    unset($this->wpStyles->to_do[$cssKey]);
                    $src = trim($query->src, "/");

                    $theStyles['internal'][$css] = ABSPATH . GeneralUtility::removeDomainFromUrl($src);

                }
            }
        }

        return $theStyles;
    }
}
