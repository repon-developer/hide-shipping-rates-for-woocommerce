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
	 * Rule template class
	 * 
	 * @since 1.0.0
	 * @var Rules_Template
	 */
	public $rules_template = null;

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
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rules-template.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		$this->admin = new Admin();
		$this->rules_template = new Rules_Template();
	}

	/**
	 * Add hooks of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_filter('woocommerce_package_rates', array($this, 'manage_shipping_rates'), 100000);
		add_filter('hide_shipping_rates/rule_matched', array($this, 'cart_rule_type'), 10, 2);
		add_filter('hide_shipping_rates/rule_matched', array($this, 'date_rule_type'), 10, 2);
		add_filter('hide_shipping_rates/rule_matched', array($this, 'customer_rule_type'), 10, 2);
		add_filter('hide_shipping_rates/rule_matched', array($this, 'billing_shipping_rule_match'), 10, 2);
	}

	/**
	 * Add get pro link in plugin links
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (Utils::has_pro_installed()) {
			return $actions;
		}

		if (HIDE_SHIPPING_RATES_BASENAME == $plugin_file) {
			$new_links[] = sprintf('<a target="_blank" href="%s">%s</a>', 'https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/', __('Get Pro', 'hide-shipping-rates-for-woocommerce'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Manage shipping rates
	 * 
	 * @since 1.0.0
	 */
	public function manage_shipping_rates($rates) {
		foreach ($rates as $rate_key => $rate) {
			$method   = \WC_Shipping_Zones::get_shipping_method($rate->get_instance_id());
			$rule_settings = json_decode(stripslashes($method->get_option('hide_shipping_rates_rules_settings')), true);
			if (!is_array($rule_settings) || empty($rule_settings)) {
				continue;
			}

			$rules = isset($rule_settings['rules']) && is_array($rule_settings['rules']) ? $rule_settings['rules'] : array();
			if (empty($rules)) {
				continue;
			}

			$matched_rules = array_filter($rules, function ($rule) {
				return apply_filters('hide_shipping_rates/rule_matched', false, $rule);
			});

			//error_log(print_r($matched_rules, true));

			$match_type = 'all';
			if (isset($rule_settings['match_type'])) {
				$match_type = $rule_settings['match_type'];
			}

			if ('all' === $match_type && count($rules) === count($matched_rules)) {
				unset($rates[$rate_key]);
			}

			if ('any' === $match_type && count($matched_rules) > 0) {
				unset($rates[$rate_key]);
			}
		}

		return $rates;
	}

	/**
	 * Cart related rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function cart_rule_type($matched, $rule) {
		if (!in_array($rule['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight', 'cart:product_shipping_classes'))) {
			return $matched;
		}

		if (is_null(WC()->cart)) {
			return $matched;
		}

		$operator = $rule['operator'];
		$target_amount = floatval($rule['value']);

		if ('cart:subtotal' === $rule['type']) {
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

		if ('cart:total_quantity' === $rule['type']) {
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

		if ('cart:total_weight' === $rule['type']) {
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

		if ('cart:product_shipping_classes' === $rule['type']) {
			$shipping_classes = isset($rule['shipping_classes']) && is_array($rule['shipping_classes']) ? $rule['shipping_classes'] : array();

			$cart_products = WC()->cart->get_cart();
			$product_shipping_classes = [];
			foreach ($cart_products as $item) {
				$product_shipping_classes[] = $item['data']->get_shipping_class_id();
			}

			$matched_items = array_intersect($shipping_classes, array_unique($product_shipping_classes));
			if ('in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Date related rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function date_rule_type($matched, $rule) {
		if ('date:weekly_days' === $rule['type']) {
			$weekly_days = isset($rule['weekly_days']) && is_array($rule['weekly_days']) ? $rule['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('in_list' == $rule['operator'] && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Customer related rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function customer_rule_type($matched, $rule) {
		$operator = $rule['operator'];

		if ('customer:users' === $rule['type']) {
			$customers = isset($rule['customer_users']) && is_array($rule['customer_users']) ? $rule['customer_users'] : array();
			if ('in_list' === $operator && in_array(get_current_user_id(), $customers)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array(get_current_user_id(), $customers)) {
				return true;
			}
		}

		if ('customer:logged_in' === $rule['type'] && 'yes' == $rule['logged_in']) {
			return is_user_logged_in();
		}

		if ('customer:logged_in' === $rule['type'] && 'no' == $rule['logged_in']) {
			return !is_user_logged_in();
		}

		return $matched;
	}

	/**
	 * Billing & Shipping rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function billing_shipping_rule_match($matched, $rule) {
		$operator = $rule['operator'];

		if ('billing:city' === $rule['type']) {
			$cities = $rule['billing_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_billing_city());
			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('shipping:city' === $rule['type']) {
			$cities = $rule['shipping_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_shipping_city());

			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('billing:country' === $rule['type'] || 'shipping:country' === $rule['type']) {
			$countries = isset($rule['shipping_countries']) && is_array($rule['shipping_countries']) ? $rule['shipping_countries'] : array();

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing:country' === $rule['type']) {
				$countries = isset($rule['billing_countries']) && is_array($rule['billing_countries']) ? $rule['billing_countries'] : array();
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
