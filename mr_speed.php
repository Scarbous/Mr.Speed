<?php
/*
Plugin Name: Mr Speed
Description:
Version: 0.1
Author: Sascha Heilmeier
*/

require_once('vendor/autoload.php');

define('MRSPEED_HOOK_PREFIX', 'mrSpeed_');

\Scarbous\MrSpeed\Core::getInstance();
