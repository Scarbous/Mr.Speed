<?php
namespace Scarbous\MrSpeed\Utility;

class GeneralUtility
{
    const TEMP_DIR_NAME = 'mrSpeed';

    static public $RdfuPattern;

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

    /**
     * @param string $type
     *
     * @return bool|string
     */
    public static function getTempDir( $type ) {
        $uploadDir = wp_upload_dir();
        $dir       = $uploadDir['basedir'] . '/' . self::TEMP_DIR_NAME . '/';
        switch ( $type ) {
            case 'js':
                return $dir . 'js/';
                break;
            case 'css':
                return $dir . 'css/';
                break;
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return bool|string
     */
    public static function getTempUrl( $type ) {
        $uploadDir = wp_upload_dir();
        $url       = $uploadDir['baseurl'] . '/' . self::TEMP_DIR_NAME . '/';
        switch ( $type ) {
            case 'js':
                return $url . 'js/';
                break;
            case 'css':
                return $url . 'css/';
                break;
        }

        return false;
    }

    /**
     * Clean Temp dir
     */
    public static function cleanTempDir(){
        $uploadDir = wp_upload_dir();
        self::delTree( $uploadDir['basedir'] . '/'.self::TEMP_DIR_NAME.'/');
    }

    /**
     * Remove Files recursive
     *
     * @param string $dir
     *
     * @return bool
     */
    public static function delTree($dir) {
        if(file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
        return true;
    }

    /**
     * @return string
     */
    private static function getRdfuPattern() {
        if ( empty( self::$RdfuPattern ) ) {
            $url               = parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
            self::$RdfuPattern = "'^.*?" . preg_quote( $url ) . "/'i";
        }

        return self::$RdfuPattern;
    }

    /**
     * @param string $url
     *
     * @return mixed
     */
    public static function removeDomainFromUrl( $url ) {
        return preg_replace( self::getRdfuPattern(), '', $url );
    }
}