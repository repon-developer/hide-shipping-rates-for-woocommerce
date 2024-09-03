<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Main {

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold admin class
	 * 
	 * @since 1.0.0
	 * @var Admin
	 */
	public $admin = null;

	/**
	 * Conditions template class
	 * 
	 * @since 1.0.0
	 * @var Condition_Template
	 */
	public $rule_templates = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include_files();
		$this->init();
		$this->hooks();
	}

	/**
	 * Load plugin files
	 * 
	 * @version 1.0.0
	 * @return void
	 */
	public function include_files() {
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-utils.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-admin.php';
		//require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-condition-templates.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		$this->admin = new Admin();
		//$this->rule_templates = new Condition_Templates();
	}

	/**
	 * Add hooks of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'cart_condition_type'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'date_condition_type'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'customer_condition_type'), 10, 2);
		add_filter('advanced_rule_based_shipping/condition_matched', array($this, 'billing_shipping_condition_match'), 10, 2);
	}

	/**
	 * Add add coupon link in plugin links
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (HIDE_SHIPPING_RATES_BASENAME == $plugin_file) {
			$new_links[] = sprintf('<a href="%s">%s</a>', menu_page_url('hide-shipping-rates-for-woocommerce', false), __('Shipping Rules', 'hide-shipping-rates-for-woocommerce'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Cart related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function cart_condition_type($matched, $condition) {
		if (!in_array($condition['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight'))) {
			return $matched;
		}

		$operator = $condition['operator'];
		$target_amount = floatval($condition['value']);

		if ('cart:subtotal' === $condition['type']) {
			$subtotal = (float) WC()->cart->get_subtotal();

			if ('equal_to' === $operator && $subtotal == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $subtotal < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $subtotal <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $subtotal >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $subtotal > $target_amount) {
				return true;
			}
		}

		if ('cart:total_quantity' === $condition['type']) {
			$quantity = WC()->cart->get_cart_contents_count();

			if ('equal_to' === $operator && $quantity == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $quantity < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $quantity <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $quantity >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $quantity > $target_amount) {
				return true;
			}
		}

		if ('cart:total_weight' === $condition['type']) {
			$weight = WC()->cart->cart_contents_weight;

			if ('equal_to' === $operator && $weight == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $weight < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $weight <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $weight >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $weight > $target_amount) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Date related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function date_condition_type($matched, $condition) {
		$operator = $condition['operator'];

		if ('date:weekly_days' === $condition['type']) {
			$weekly_days = isset($condition['weekly_days']) && is_array($condition['weekly_days']) ? $condition['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('in_list' == $operator && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $operator && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Customer related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function customer_condition_type($matched, $condition) {
		$operator = $condition['operator'];

		if ('customer:users' === $condition['type']) {
			$customers = isset($condition['customer_users']) && is_array($condition['customer_users']) ? $condition['customer_users'] : array();
			if ('in_list' === $operator && in_array(get_current_user_id(), $customers)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array(get_current_user_id(), $customers)) {
				return true;
			}
		}

		if ('customer:logged_in' === $condition['type'] && 'yes' == $condition['logged_in']) {
			return is_user_logged_in();
		}

		if ('customer:logged_in' === $condition['type'] && 'no' == $condition['logged_in']) {
			return !is_user_logged_in();
		}

		return $matched;
	}

	/**
	 * Billing & Shipping condition filter
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function billing_shipping_condition_match($matched, $condition) {
		$operator = $condition['operator'];

		if ('billing:city' === $condition['type']) {
			$cities = $condition['billing_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_billing_city());
			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('shipping:city' === $condition['type']) {
			$cities = $condition['shipping_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_shipping_city());

			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('billing:country' === $condition['type'] || 'shipping:country' === $condition['type']) {
			$countries = isset($condition['shipping_countries']) && is_array($condition['shipping_countries']) ? $condition['shipping_countries'] : array();

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing:country' === $condition['type']) {
				$countries = isset($condition['billing_countries']) && is_array($condition['billing_countries']) ? $condition['billing_countries'] : array();
				$customer_country = WC()->customer->get_billing_country();
			}

			if ('in_list' === $operator && in_array($customer_country, $countries)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_country, $countries)) {
				return true;
			}
		}

		return $matched;
	}
}

Main::get_instance();
