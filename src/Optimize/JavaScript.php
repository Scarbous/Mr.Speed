<?php
namespace Scarbous\MrSpeed\Optimize;

use Scarbous\MrSpeed\Utility\GeneralUtility;
use MatthiasMullie\Minify;

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
     * @var array
     */
    private $inlineScript;

    /**
     * JavaScript constructor.
     */
    function __construct()
    {
        $this->settings = get_option('mr_speed');
        $this->settings = $this->settings['js'];

        if ($this->settings['active']) {

            if (!is_admin()) {
                $this->loadExcludeScripts();
                add_action('wp_default_scripts', [$this, 'loadWpScripts']);
                add_filter('print_scripts_array', [$this, 'optimizeJavaScript']);
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
     * Loads the \WP_Scripts Class
     */
    function loadWpScripts(&$wpScripts)
    {
        $this->wpScripts = $wpScripts;
    }

    /**
     * @return array
     */
    function optimizeJavaScript()
    {
        $scripts = $this->getScriptQueue();

        if (count($scripts['internal']) == 0)
            return ([]);

        $fileName = md5(implode('', array_keys($scripts['internal']))) . '.js';
        $cacheFilePath = GeneralUtility::getTempDir('js') . $fileName;
        $cacheFileUrl = GeneralUtility::getTempUrl( 'js' ) . $fileName;

        if ( ! file_exists( $cacheFilePath ) ) {
            $minifier = new Minify\JS();
            foreach ( $scripts['internal'] as $script ) {
                if(!empty($script) && file_exists($script)){
                    $minifier->add( '/' . $script );
                }
            }

            if ( ! is_dir( dirname( $cacheFilePath ) ) ) {
                mkdir( dirname( $cacheFilePath ), 0777, true );
            }

            $minifier->minify( $cacheFilePath );
            $minifier->gzip( $cacheFilePath . '.gzip' );
        }

        $this->includeScriptTag[] = '<script src="' . $cacheFileUrl . ($this->settings['gzip']?'.gzip':'') . '" ></script>';
        $this->includeScriptTag[] = '<script>'.implode( '', $this->inlineScript ).'</script>';

        if ($this->settings['toFooter']) {
            add_action('wp_footer', function () {
                echo implode(PHP_EOL,$this->includeScriptTag);

                $this->includeScriptTag =[];
            }, 100000);
        } else {
            echo implode(PHP_EOL,$this->includeScriptTag);
            $this->includeScriptTag =[];
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
      #  print_r($this->wpScripts); die();
        $theScripts = [];
        $topJs=[];
        $footerJs=[];
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

                    if ( key_exists( 'data', $query->extra ) ) {
                        $this->addInlineScript( $query->extra['data'] );
                    }
                    $theScripts['internal'][$js] = $jsPath = ABSPATH . GeneralUtility::removeDomainFromUrl($src);
                    /*if(empty($query->args)){
                        $topJs[$js] = $jsPath;
                    } else {
                        $footerJs[$js] = $jsPath;
                    }*/
                }
            }
        }

        return $theScripts;
    }

    /**
     * @param string $script
     */
    function addInlineScript($script){
        $this->inlineScript[] = $script;
    }
}
