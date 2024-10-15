<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utilities class 
 */
class Utils {

	/**
	 * Is shipping screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_shipping_screen() {
		return ('woocommerce_page_wc-settings' === get_current_screen()->id && isset($_GET['tab']) && 'shipping' === $_GET['tab']);
	}

	/**
	 * Is plugin supported screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_plugin_screen() {
		$screen_matched = false;
		if ('woocommerce_page_wc-settings' === get_current_screen()->id && isset($_GET['tab']) && 'shipping' === $_GET['tab']) {
			$screen_matched = true;
		}

		if ('plugins' === get_current_screen()->id) {
			$screen_matched = true;
		}

		return $screen_matched;
	}

	/**
	 * Check if pro version installed
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function has_pro_installed() {
		return file_exists(WP_PLUGIN_DIR . '/hide-shipping-rates-for-woocommerce-pro/hide-shipping-rates-for-woocommerce-pro.php');
	}

	/**
	 * Check if pro plugin activated
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_pro_activated() {
		return class_exists('\Hide_Shipping_Rates_Pro\Main');
	}

	/**
	 * Check if pro plugin activated the license
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function license_activated() {
		if (!class_exists('\Hide_Shipping_Rates_Pro\Upgrade')) {
			return false;
		}

		return \Hide_Shipping_Rates_Pro\Upgrade::license_activated();
	}

	/**
	 * Get rule operators
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators($operators = array()) {
		$supported_operators = array(
			'equal_to' => __('Equal To ( = )', 'hide-shipping-rates-for-woocommerce'),
			'less_than' => __('Less than ( < )', 'hide-shipping-rates-for-woocommerce'),
			'less_than_or_equal' => __('Less than or equal ( <= )', 'hide-shipping-rates-for-woocommerce'),
			'greater_than_or_equal' => __('Greater than or equal ( >= )', 'hide-shipping-rates-for-woocommerce'),
			'greater_than' => __('Greater than ( > )', 'hide-shipping-rates-for-woocommerce'),
			'between' => __('Between', 'hide-shipping-rates-for-woocommerce'),
			'any_in_list' => __('Any in list', 'hide-shipping-rates-for-woocommerce'),
			'all_in_list' => __('All in list', 'hide-shipping-rates-for-woocommerce'),
			'not_in_list' => __('Not in list', 'hide-shipping-rates-for-woocommerce'),

			'before' => __('Before', 'hide-shipping-rates-for-woocommerce'),
			'after' => __('After', 'hide-shipping-rates-for-woocommerce'),
		);

		$return_operators = [];
		while ($key = current($operators)) {
			if (isset($supported_operators[$key])) {
				$return_operators[$key] = $supported_operators[$key];
			}

			next($operators);
		}

		return $return_operators;
	}

	/**
	 * Get rule operators dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators_options($args = array()) {
		$operators = self::get_operators($args);

		$options = array_map(function ($label, $key) {
			return sprintf('<option value="%s">%s</option>', $key, $label);
		}, $operators, array_keys($operators));

		echo wp_kses(implode('', $options), array(
			'option' => array(
				'value' => true
			)
		));
	}

	/**
	 * Group of rule types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_groups() {
		return apply_filters('hide_shipping_rates/rule_type_groups', array(
			'cart' => __('Cart', 'hide-shipping-rates-for-woocommerce'),
			'cart_products' => __('Cart Products', 'hide-shipping-rates-for-woocommerce'),
			'date' => __('Date', 'hide-shipping-rates-for-woocommerce'),
			'billing' => __('Billing', 'hide-shipping-rates-for-woocommerce'),
			'shipping' => __('Shipping', 'hide-shipping-rates-for-woocommerce'),
			'user' => __('Customer', 'hide-shipping-rates-for-woocommerce'),
			'order_history' => __('Order History', 'hide-shipping-rates-for-woocommerce'),
			'others' => __('Others', 'hide-shipping-rates-for-woocommerce'),
		));
	}

	/**
	 * Get types of rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_types() {
		return apply_filters('hide_shipping_rates/rule_types', array(

			/** Cart related field types */
			'cart:subtotal' => array(
				'group' => 'cart',
				'priority' => 10,
				'label' => __('Subtotal', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:total_quantity' => array(
				'group' => 'cart',
				'priority' => 15,
				'label' => __('Total quantity', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:total_weight' => array(
				'group' => 'cart',
				'priority' => 20,
				'label' => __('Total weight', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:coupons' => array(
				'group' => 'cart',
				'priority' => 25,
				'label' => __('Coupons', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Cart products related field types */
			'cart_products:products' => array(
				'group' => 'cart_products',
				'priority' => 5,
				'label' => __('Products', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart_products:categories' => array(
				'group' => 'cart_products',
				'priority' => 10,
				'label' => __('Categories', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart_products:tags' => array(
				'group' => 'cart_products',
				'priority' => 15,
				'label' => __('Tags', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart_products:shipping_classes' => array(
				'group' => 'cart_products',
				'priority' => 20,
				'label' => __('Shipping Classes', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Date related field types */
			'date:time' => array(
				'group' => 'date',
				'priority' => 5,
				'label' => __('Time', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:date' => array(
				'group' => 'date',
				'priority' => 10,
				'label' => __('Date', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:weekly_days' => array(
				'group' => 'date',
				'priority' => 15,
				'label' => __('Weekly Days', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Billing address related field types */
			'billing:city' => array(
				'group' => 'billing',
				'priority' => 10,
				'label' => __('City', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:zipcode' => array(
				'group' => 'billing',
				'priority' => 20,
				'label' => __('Zip code', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:state' => array(
				'group' => 'billing',
				'priority' => 25,
				'label' => __('State', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:country' => array(
				'group' => 'billing',
				'priority' => 30,
				'label' => __('Country', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Shipping address related field types */
			'shipping:city' => array(
				'group' => 'shipping',
				'priority' => 10,
				'label' => __('City', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:zipcode' => array(
				'group' => 'shipping',
				'priority' => 15,
				'label' => __('Zip code', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:state' => array(
				'group' => 'shipping',
				'priority' => 20,
				'label' => __('State', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:country' => array(
				'group' => 'shipping',
				'priority' => 25,
				'label' => __('Country', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Customer related field types */
			'user:users' => array(
				'group' => 'user',
				'priority' => 10,
				'label' => __('Users', 'hide-shipping-rates-for-woocommerce'),
			),
			'user:roles' => array(
				'group' => 'user',
				'priority' => 15,
				'label' => __('Roles', 'hide-shipping-rates-for-woocommerce'),
			),
			'user:logged_in' => array(
				'group' => 'user',
				'priority' => 20,
				'label' => __('Logged In', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Order History related field types */
			'order_history:first_purchase' => array(
				'group' => 'order_history',
				'priority' => 10,
				'label' => __('First Purchase', 'hide-shipping-rates-for-woocommerce'),
			),
		));
	}

	/**
	 * Get rule types of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_types_by_group($group) {
		$group_types = [];
		foreach (self::get_rule_types() as $key => $type) {
			if ($group !== $type['group']) {
				continue;
			}

			$group_types[$key] = $type;
		}

		uasort($group_types, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_types;
	}

	/**
	 * Get rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_values() {
		$rule_values = apply_filters('hide_shipping_rates/rule_values', array());

		return array_merge($rule_values, array(
			'value' => '',
			'type' => 'cart:subtotal',
			'operator' => 'less_than',
		));
	}

	/**
	 * Get rule extra values for UI management
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_ui_values() {
		return apply_filters('hide_shipping_rates/rule_ui_values', array('loading' => false));
	}

	/**
	 * Pro field lock message
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function field_lock_message() {
		if (self::has_pro_installed()) {
			if (self::is_pro_activated()) {
				if (!self::license_activated()) {
					echo '<div class="locked-message locked-message-activate-license">';
					$message = sprintf(
						/* translators: %1$s: Link open, %2%s: Link close */
						esc_html__('Please activate your license for unlock this feature. %1$sClick here%2$s for activate license.', 'hide-shipping-rates-for-woocommerce'),
						'<a href="#" class="btn-open-hide-shipping-rates-license-form">',
						'</a>'
					);
					echo wp_kses($message, array('a' => array('href' => true,  'target' => true, 'class' => true)));
					echo '</div>';
				}
			} else {
				echo '<div class="locked-message locked-message-deactivate">';
				esc_html_e('Please activate the "Hide Shipping Rates for WooCommerce Pro" plugin.', 'hide-shipping-rates-for-woocommerce');
				echo '</div>';
			}
		} else {
			echo '<div class="locked-message locked-message-get-pro">Get the <a target="_blank" :href="get_pro_link">pro version</a> for unlock this feature.</div>';
		}
	}
}
