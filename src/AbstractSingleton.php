<?php
namespace Scarbous\MrSpeed;

abstract class AbstractSingleton {

    /**
     * Instance
     *
     * @var AbstractSingleton
     */
    private static $instances = [];

    /**
     * Get instance
     *
     * @return AbstractSingleton
     */
    public static function getInstance() {
        $class = get_called_class();
        if ( ! isset( self::$instances[ $class ] ) ) {
            self::$instances[ $class ] = new $class();
        }

        return self::$instances[ $class ];
    }
}