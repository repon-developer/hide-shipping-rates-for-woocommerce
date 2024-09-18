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
	 * Hold of rules classes
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $rules = [];

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
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-cart.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-date.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-customer.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-cart-products.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-order-history.php';
		require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-rule-billing-shipping.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		$this->admin = new Admin();
		$this->rules['cart'] = new Rule\Cart();
		$this->rules['date'] = new Rule\Date();
		$this->rules['customer'] = new Rule\Customer();
		$this->rules['order_history'] = new Rule\Order_History();
		$this->rules['cart_products'] = new Rule\Cart_Products();
		$this->rules['billing_shipping'] = new Rule\Billing_Shipping();
	}

	/**
	 * Add hooks of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_filter('woocommerce_package_rates', array($this, 'manage_shipping_rates'), 100000, 2);		
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
			$new_links[] = sprintf('<a target="_blank" href="%s">%s</a>', 'https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/?utm_campaign=hide+shipping+rates+for+woocommerce&utm_source=plugins+page&utm_medium=get+pro+link', __('Get Pro', 'hide-shipping-rates-for-woocommerce'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Manage shipping rates
	 * 
	 * @since 1.0.0
	 */
	public function manage_shipping_rates($rates, $package) {
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

			$matched_rules = array_filter($rules, function ($rule) use($package) {
				return apply_filters('hide_shipping_rates/rule_matched', false, wp_parse_args($rule, Utils::get_rule_values()), $package);
			});

			//error_log(print_r($package, true));

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
}

Main::get_instance();
