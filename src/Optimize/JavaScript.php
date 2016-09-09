<?php
namespace Scarbous\MrSpeed\Optimize;

use Scarbous\MrSpeed\Utility\GeneralUtility;

class JavaScript
{
    /**
     * List of Libs witch should not be optimized
     * @var array
     */
    private $excludeScripts = [
        'admin-bar'
    ];

    /**
     * @var \WP_Scripts
     */
    private $wpScripts;

    /**
     * @var string
     */
    private $includeScriptTag;

    /**
     * JavaScript constructor.
     */
    function __construct()
    {
        $this->settings = get_option('mr_speed');
        $this->settings = $this->settings['js'];

        if ($this->settings['active']) {
            $this->loadExcludeScripts();
            $this->loadWpScripts();
            if (!is_admin()) {
                add_filter('print_scripts_array', [$this, 'optimizeJavaScript']);
                if ($this->settings['JShrink']) {
                    add_Filter('mrSpeed.JS.content', [$this, 'doJShrink']);
                }
            }
        }
    }

    function loadExcludeScripts()
    {
        $this->excludeScripts = array_merge(
            $this->excludeScripts,
            explode(PHP_EOL, $this->settings['excludeScripts'])
        );
        $this->excludeScripts = array_map('trim', $this->excludeScripts);
    }

    /**
     * Shrink JavaScript
     *
     * @param $content
     * @return bool|string
     * @throws \Exception
     */
    function doJShrink($content)
    {
        $newContent = \JShrink\Minifier::minify($content);
        return $newContent ?: $content;
    }

    /**
     * Loads the WP_Scripts Class
     */
    private function loadWpScripts()
    {
        global $wp_scripts;
        if (!is_a($wp_scripts, 'WP_Scripts')) {
            $wp_scripts = new \WP_Scripts();
        }
        $this->wpScripts = &$wp_scripts;
    }

    /**
     * @param array $js_array
     * @return array
     */
    function optimizeJavaScript($js_array)
    {
        $scripts = $this->getScriptQueue();

        if (count($scripts['internal']) == 0)
            return ([]);

        $contentArray = [];
        $fileName = md5(implode('', array_keys($scripts['internal']))) . '.js';
        $filePath = '/cache/' . $fileName;
        $cacheFilePath = GeneralUtility::getTempDir('js') . $filePath;

        if (!file_exists($cacheFilePath)) {
            foreach ($scripts['internal'] as $name => $script) {
                $contentArray[$name] =
                    "/**" . PHP_EOL
                    . " * $name" . PHP_EOL
                    . " */" . PHP_EOL
                    . apply_filters('mrSpeed.JS.content', file_get_contents($script));
            }
            $script = implode(PHP_EOL, $contentArray);

            if (!is_dir(dirname($cacheFilePath))) {
                mkdir(dirname($cacheFilePath), 0777, true);
            }
            file_put_contents($cacheFilePath, $script);
            $scriptZip = gzencode($script);
            file_put_contents($cacheFilePath . '.gzip', $scriptZip);

        }

        $this->includeScriptTag = '<script src="' . GeneralUtility::getTempUrl('js') . '" ></script>';
        if ($this->settings['toFooter']) {
            add_action('wp_footer', function () {
                echo $this->includeScriptTag;
            }, 100000);
        } else {
            echo $this->includeScriptTag;
        }
        return ($this->wpScripts->to_do);


    }

    /**
     * Returns the list of JavaScripts which should be minified
     *
     * @return array
     */
    private function getScriptQueue()
    {
        $url = get_bloginfo('url') . '/';
        foreach ($this->wpScripts->to_do as $jsKey => $js) {

            if (in_array($js, $this->excludeScripts)) continue;


            if ($this->wpScripts->query($js, 'queue')) {
                $query = $this->wpScripts->query($js);

                if (filter_var($query->src, FILTER_VALIDATE_URL) && GeneralUtility::isUrlExternal($query->src)) {
                    $theScripts['external'][$js] = $query->src;
                } else {
                    $this->wpScripts->done[] = $this->wpScripts->to_do[$jsKey];
                    unset($this->wpScripts->to_do[$jsKey]);
                    $src = trim($query->src, "/");
                    $theScripts['internal'][$js] = ABSPATH . str_replace($url, '', $src);

                }
            }
        }

        return $theScripts;
    }
}
