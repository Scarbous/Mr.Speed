<?php
namespace Scarbous\MrSpeed\Utility;

class GeneralUtility
{
    const TEMP_DIR_NAME = 'mrSpeed';

    /**
     * @param string $url
     * @return bool
     */
    public static function isUrlExternal($url) {
        $components = parse_url($url);
        $currentUrlComponents = parse_url(get_bloginfo('url'));

        if(strtolower($components['host']) == strtolower($currentUrlComponents['host'])) {
            return false;
        } else {
            return true;
        }

    }


    public static function getTempDir($type)
    {
        $uploadDir = wp_upload_dir();
        switch ($type) {
            case 'js':
                return $uploadDir['basedir'] . '/'.self::TEMP_DIR_NAME.'/js';
                break;
            case 'css':
                return $uploadDir['basedir'] . '/'.self::TEMP_DIR_NAME.'/css';
                break;
        }
        return false;
    }


    public static function getTempUrl($type)
    {
        $uploadDir = wp_upload_dir();
        switch ($type) {
            case 'js':
                return $uploadDir['baseurl'] . '/'.self::TEMP_DIR_NAME.'/js';
                break;
            case 'css':
                return $uploadDir['baseurl'] . '/'.self::TEMP_DIR_NAME.'/css';
                break;
        }
        return false;
    }
    public static function cleanTempDir(){
        $uploadDir = wp_upload_dir();
        self::delTree( $uploadDir['basedir'] . '/'.self::TEMP_DIR_NAME.'/', trueh);
    }

    public static function delTree($dir) {
        if(file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
    }
}