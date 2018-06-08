<?php

namespace Scarbous\MrSpeed\Optimize;

use Scarbous\MrSpeed\AbstractSingleton;
use Scarbous\MrSpeed\Utility\GeneralUtility;
use Scarbous\MrSpeed\Utility\SettingsUtility;

class AbstractOptimizer extends AbstractSingleton
{
    /**
     * @var string
     */
    private $type = '';

    /**
     * @var string
     */
    private $minifyClass = \MatthiasMullie\Minify\Minify::class;

    /**
     * List of Libs witch should not be optimized
     * @var array
     */
    private $excludes = [];

    /**
     * @var \WP_Dependencies
     */
    public $wpDependencies;

    /**
     * AbstractOptimize constructor.
     */
    function __construct($type,$minifyClass)
    {
        $this->type = $type;
        $this->minifyClass = $minifyClass;
    }

    function getType()
    {
        return $this->type;
    }

    /**
     * Point to the Style/Script Dependencies object
     *
     * @param \WP_Dependencies $wpDependencies
     */
    function loadWpDependencies(&$wpDependencies)
    {
        $this->wpDependencies = &$wpDependencies;
    }

    /**
     * @param array $elements
     * @return array
     */
    function optimizeElements($elements)
    {
        $keys = [];
        foreach ($elements as $handel => $element) {
            $keys[] = $handel . $element['ver'] ? '_' . $element['ver'] : '';
        }
        $keys = implode(',', $keys);
        $fileName = md5($keys) . '.' . $this->type;
        $cacheFilePath = GeneralUtility::getTempDir($this->type) . $fileName;
        $cacheFileUrl = GeneralUtility::getTempUrl($this->type) . $fileName;

        if (!file_exists($cacheFilePath)) {
            /** @var \MatthiasMullie\Minify\Minify $minifier */
            $minifier = new $this->minifyClass();
            foreach ($elements as $element) {
                $minifier->add($element['path']);
            }

            if (!is_dir(dirname($cacheFilePath))) {
                mkdir(dirname($cacheFilePath), 0777, true);
            }
            $minifier->minify($cacheFilePath);
            $minifier->gzip($cacheFilePath . '.gzip');
        }
        $url = $cacheFileUrl . (SettingsUtility::getSetting($this->type . '/gzip') ? '.gzip' : '');
        return [
            'url' =>$url,
            'keys' => $keys
        ];
    }

    /**
     * checks all elements that are to be merged
     */
    function getQueue()
    {
        $excludes = $this->mergeExcludes();
        $elements = [];
        foreach ($this->wpDependencies->to_do as $key => $handel) {
            if (in_array($handel, $excludes)) {
                continue;
            }
            if ($this->wpDependencies->query($handel, 'queue')) {
                /** @var \_WP_Dependency $query */
                $query = $this->wpDependencies->query($handel);
                if (preg_match('#^\/{2}#', $query->src)) {
                    $query->src = 'https:' . $query->src;
                }
                if (filter_var($query->src, FILTER_VALIDATE_URL) && GeneralUtility::isUrlExternal($query->src)) {
                    $elements['external'][$handel] = $query->src;
                } else {
                    $this->wpDependencies->done[] = $handel;
                    $src = trim($query->src, "/");

                    $ownHandel = $handel;# . ($query->ver ? '_' . $query->ver : '');
                    $path = ABSPATH . GeneralUtility::removeDomainFromUrl($src);

                    if ($this->getType() == 'css') {
                        $media = isset($query->args) ? esc_attr($query->args) : 'all';
                        $elements['internal'][$media][$ownHandel] = [
                            'path' => $path,
                            'ver' => $query->ver
                        ];
                    } else {
                        $elements['internal'][$ownHandel] = [
                            'path' => $path,
                            'ver' => $query->ver
                        ];
                    }
                }
            }
        }
        return $elements;
    }

    /**
     * merges default excludes
     */
    function mergeExcludes()
    {
        $excludes = trim(SettingsUtility::getSetting($this->type . '/excludes'));
        $excludes = explode(PHP_EOL, $excludes);
        $excludes = array_merge($this->excludes,$excludes);
        $excludes = array_map('trim', $excludes);
        $excludes = apply_filters(MRSPEED_HOOK_PREFIX . $this->getType() . '_excludes', $excludes);
        return $excludes;
    }

}
