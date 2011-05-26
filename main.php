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

class Nice_Navigation_Widget extends WP_Widget {

	protected static $domain = 'nice-navigation-widget';
	protected static $base = '';
	protected static $defaults = null;

	protected static function init_defaults() {
		self::$defaults = array(
			'title' => __('Nice Navigation Widget', self::$domain),
			'show_on_home' => true
		);
	}

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
		self::init_defaults();

		add_action('widgets_init', array(__CLASS__, 'register_me'));
	}

	public static function register_me() {
		register_widget(__CLASS__);
	}

	/* Actual Widget Code */

	function Nice_Navigation_Widget() {
		$widget_ops = array(
			'classname' => self::$domain,
			'description' => __('Adds a sub navigation.', self::$domain)
		);

		$control_ops = array(
			'width' => 300,
			'height' => 350,
			'id_base' => self::$domain
		);

		parent::WP_Widget(self::$domain, __('Nice Navigation Widget', self::$domain), $widget_ops, $control_ops);
	}
	
	function get_sub_post_tree($root) {
		
	}

	function widget($args, $instance) {
		global $post;
		extract($args);
		$instance = wp_parse_args((array) $instance, self::$defaults);

		if (is_search() || is_404())
			return false;
		if (is_front_page() && !$instance['show_on_home'])
			return false;
		
		$top_most = $post;
		while ($top_most->post_parent)
			$top_most = get_post($top_most->post_parent);

		echo $before_widget;
		echo $before_title;
		echo apply_filters('widget_title', $instance['title']);
		echo $after_title;
		
		wp_list_pages(array('child_of' => $top_most->ID));
		
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		
	}

	function form($instance) {
		
	}

}

Nice_Navigation_Widget::init();
?>