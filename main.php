<?php
/*
Plugin Name:	Nice Navigation Widget
Plugin URI:		https://github.com/bizzl-greekdog/Nice-Navigation-Widget
Description:	This widget adds a sub navigation to any page, post or whatever.
Version:		1.0.0
Author:			Benjamin Kleiner
Author URI:		https://github.com/bizzl-greekdog
License:		LGPL3
*/
/*
    Copyright (c) 2011 Benjamin Kleiner <bizzl@users.sourceforge.net>
 
    This file is part of Nice Navigation Widget.

    Nice Navigation Widget is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Nice Navigation Widget is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Nice Navigation Widget. If not, see <http://www.gnu.org/licenses/>.
*/

if (!function_exists('join_path')) {

	// This is an implementation of pythons sys.path.join()
	// As the name suggest, join_path takes all arguments and joins
	// them using the directory separator.
	function join_path() {
		$fuck = func_get_args();
		$flat = (object)array('flat' => array());
		array_walk_recursive($fuck, create_function('&$v, $k, &$t', '$t->flat[] = $v;'), $flat);
		$f = implode(DIRECTORY_SEPARATOR, $flat->flat);
		return preg_replace('/(?<!:)\\' . DIRECTORY_SEPARATOR . '+/', DIRECTORY_SEPARATOR, $f);
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
			'show_on_home' => true,
			'reference-menu' => -1,
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
			'width' => 'auto',
			'height' => 350,
			'id_base' => self::$domain
		);

		parent::WP_Widget(self::$domain, __('Nice Navigation Widget', self::$domain), $widget_ops, $control_ops);
	}

	function get_sub_post_tree($root, $me = null) {
		$li = tag('li')->addClass('page_item'); #->addClass('menu-item', 'menu-item-type-' . $root->type, 'menu-item-' . $root->ID, 'menu-item-object-' . $root->object);
		if ($me == $root->object_id)
			$li->addClass('current_page_item');#->addClass('current_page');
		$li->append(tag('a')->attr('href', $root->url)->append(get_post($root->object_id)->post_title));
		if (count($root->children)) {
			$ul = tag('ul')->addClass('sub-menu');
			foreach ($root->children as $child)
				$ul->append($this->get_sub_post_tree($child, $me));
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

		$menu = get_term($instance['reference-menu'], 'nav_menu');
		$menu_items = wp_get_nav_menu_items($menu->term_id);
		$top_most = false;
		$ided_menu_items = array();
		if (!is_array($menu_items))
			return false;
		foreach ($menu_items as $menu_item) {
			$menu_item->children = array();
			$ided_menu_items[$menu_item->ID] = $menu_item;
			if ($post->ID == $menu_item->object_id)
				$top_most = $menu_item;
		}
		//error_log($top_most);
		if ($top_most === false)
			return false;
		foreach ($menu_items as $menu_item) {
			if ($menu_item->menu_item_parent) {
				$ided_menu_items[$menu_item->menu_item_parent]->children[] = $menu_item;
				$menu_item->root = false;
			} else
				$menu_item->root = true;
		}
		while (!$top_most->root) {
			$top_most = $ided_menu_items[$top_most->menu_item_parent];
		}
		$tree = tag('ul');
		foreach ($top_most->children as $child)
			$tree->append($this->get_sub_post_tree($child, $post->ID));

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
		$instance = wp_parse_args((array) $instance, self::$defaults);
		echo tag('p')->append(
				tag('label')->attr('for', $this->get_field_name('title'))->append(__('Title', self::$domain)), tag('br'), tag('input')->attr(array(
					'type' => 'text',
					'name' => $this->get_field_name('title'),
					'id' => $this->get_field_id('title'),
					'value' => $instance['title'],
				))
		);
		$reference_menu_select = tag('select')->attr(array(
					'name' => $this->get_field_name('reference-menu'),
					'id' => $this->get_field_id('reference-menu'),
				));
		foreach (get_terms('nav_menu') as $menu) {
			$option = tag('option')->attr('value', $menu->term_id)->append($menu->name);
			if ($menu->term_id == $instance['reference-menu'])
				$option->attr('selected', 'selected');
			$reference_menu_select->append($option);
		}
		echo tag('p')->append(
				tag('label')->attr('for', $this->get_field_name('reference-menu'))->append(__('Reference Menu', self::$domain)), tag('br'), $reference_menu_select
		);
	}

}

Nice_Navigation_Widget::init();
?>