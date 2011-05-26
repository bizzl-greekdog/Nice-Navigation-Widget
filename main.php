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
		$li = tag('li');
		$li->append(get_post($root->object_id)->post_title);
		if (count($root->children)) {
			$ul = tag('ul');
			foreach ($root->children as $child)
				$ul->append($this->get_sub_post_tree($child));
			$li->append($ul);
		}
		return $li;
			
	}

	function widget($args, $instance) {
		global $post;
		extract($args);
		$instance = wp_parse_args((array) $instance, self::$defaults);

		if (is_search() || is_404())
			return false;
		if (is_front_page() && !$instance['show_on_home'])
			return false;
		

		if (count($post->ancestors)) {
			$top_most = $post;
			while ($top_most->post_parent)
				$top_most = get_post($top_most->post_parent);
			$tree = wp_list_pages(array('child_of' => $top_most->ID, 'title_li' => '', 'echo' => false));
		} else {
			$nav = array_shift(get_posts(array('meta_key' => '_menu_item_object_id', 'meta_value' => $post->ID, 'post_type' => 'nav_menu_item')));
			$menu = array_shift(wp_get_object_terms(array($nav->ID), 'nav_menu'));
			$menu_items = wp_get_nav_menu_items($menu->term_id);
			$ided_menu_items = array();
			foreach ($menu_items as $menu_item) {
				$menu_item->children = array();
				$ided_menu_items[$menu_item->ID] = $menu_item;
			}
			foreach ($menu_items as $menu_item)
				if ($menu_item->menu_item_parent) {
					$ided_menu_items[$menu_item->menu_item_parent]->children[] = $menu_item;
					$menu_item->root = false;
				} else
					$menu_item->root = true;
			$menu_items = array();
			foreach($ided_menu_items as $id => $item)
				if ($item->root)
					$menu_items[] = $item;
			$tree = tag('ul');
			foreach ($menu_items as $menu_item)
				$tree->append($this->get_sub_post_tree($menu_item));
		}

		echo $before_widget;
		echo $before_title;
		echo apply_filters('widget_title', $instance['title']);
		echo $after_title;
		echo $tree;
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		return array_merge($old_instance, $new_instance);
	}

	function form($instance) {
		$instance = wp_parse_args((array)$instance, self::$defaults);
		echo tag('p')->append(
			tag('label')->attr('for', $this->get_field_name('title'))->append(__('Title', self::$domain)),
			tag('input')->attr(array(
				'type' => 'text',
				'name' => $this->get_field_name('title'),
				'id' => $this->get_field_id('title'),
				'value' => $instance['title'],
			))
		);
	}

}

Nice_Navigation_Widget::init();
?>