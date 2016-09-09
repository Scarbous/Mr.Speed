<?php
namespace Scarbous\MrSpeed\Optimize;

use Scarbous\MrSpeed\Utility\GeneralUtility;

class Css
{
    /**
     * List of Libs witch should not be optimized
     * @var array
     */
    private $excludeStyles = [
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
            $this->loadExcludeStyles();
            $this->loadWpStyles();
            if (!is_admin()) {
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
     */
    private function loadWpStyles()
    {
        global $wp_styles;
        if (!is_a($wp_styles, 'WP_Styles')) {
            $wp_styles = new \WP_Scripts();
        }
        $this->wpStyles = &$wp_styles;
    }

    /**
     * @param array $css_array
     * @return array
     */
    function optimizeJavaScript($css_array)
    {
        $styles = $this->getScriptQueue();

        if (count($styles['internal']) == 0)
            return ([]);

        $contentArray = [];
        $fileName = md5(implode('', array_keys($styles['internal']))) . '.css';
        $filePath = '/cache/' . $fileName;
        $cacheFilePath = GeneralUtility::getTempDir('css') . $filePath;

        if (!file_exists($cacheFilePath)) {
            foreach ($styles['internal'] as $name => $style) {
                $contentArray[$name] =
                    "/**" . PHP_EOL
                    . " * $name" . PHP_EOL
                    . " */" . PHP_EOL
                    . apply_filters('mrSpeed.CSS.content', file_get_contents($style));
            }
            $style = implode(PHP_EOL, $contentArray);

            if (!is_dir(dirname($cacheFilePath))) {
                mkdir(dirname($cacheFilePath), 0777, true);
            }

            file_put_contents($cacheFilePath, $style);
            $styleZip = gzencode($style);
            file_put_contents($cacheFilePath . '.gzip', $styleZip);
        }

        echo '<link rel="stylesheet" href="' . GeneralUtility::getTempUrl('css') . '" type="text/css" />';

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
        $url = get_bloginfo('url') . '/';
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
                    $theStyles['internal'][$css] = ABSPATH . str_replace($url, '', $src);

                }
            }
        }

        return $theStyles;
    }
}
