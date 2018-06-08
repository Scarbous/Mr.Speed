<?php

namespace Scarbous\MrSpeed\Utility;

class SettingsUtility
{

    /**
     * @var array
     */
    static private $settings;

    /**
     * @param string $path
     * @return array|bool|mixed
     */
    static function getSetting($path)
    {
        $path = explode('/', $path);
        $settings = static::getSettings();
        foreach ($path as $el) {
            if (!key_exists($el, $settings)) {
                return false;
            } else {
                $settings = $settings[$el];
            }
        }
        return $settings;
    }

    /**
     * returns all settings, if not loaded it additional loads the settings
     * @return array;
     */
    static private function getSettings()
    {
        if (empty(static::$settings)) {
            static::$settings = get_option('mr_speed');
        }
        return static::$settings;
    }

    /**
     * @return bool
     */
    static function isDebug()
    {
        return (bool)static::getSetting('general/debug');
    }
}