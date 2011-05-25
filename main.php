<?php
/*
Plugin Name: Nice Navigation Widget
Plugin URI: 
Description: This widgets adds a sub navigation to any page, post or whatever.
Version: 1.0.0
Author: Benjamin Kleiner <bizzl@users.sourceforge.net>
Author URI: 
License: LGPL3
*/

if (!function_exists('join_path')) {
	function join_path() {
		$fuck = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $fuck);
	}
}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tag.php');

class Nice_Navigation_Widget {

	protected static $domain = 'nice-navigation-widget';
	protected static $base = '';
	
	protected static function init_base() {
		self::$base = basename(dirname(__FILE__));
	}

	protected static function init_l10n() {
		$j = join_path(self::$base, 'locale');
		load_plugin_textdomain(self::$domain, false, $j);
	}
	
	public static function init() {
		self::init_base();
		self::init_l10n();
	}
	
}

Nice_Navigation_Widget::init();
?>