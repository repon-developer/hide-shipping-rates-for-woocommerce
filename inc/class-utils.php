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
	 * Check if pro version installed
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function has_pro_installed() {
		return file_exists(WP_PLUGIN_DIR . '/hide-shipping-rates-for-woocommerce-pro/hide-shipping-rates-for-woocommerce-pro.php');
	}

	/**
	 * Get condition operators
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
			'in_list' => __('In list', 'hide-shipping-rates-for-woocommerce'),
			'not_in_list' => __('Not in list', 'hide-shipping-rates-for-woocommerce'),
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
	 * Get condition operators dropdown
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
	 * Group of condition of tier discount
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_condition_groups() {
		return apply_filters('hide_shipping_rates/condition_groups', array(
			'cart' => __('Cart', 'hide-shipping-rates-for-woocommerce'),
			'date' => __('Date', 'hide-shipping-rates-for-woocommerce'),
			'billing' => __('Billing', 'hide-shipping-rates-for-woocommerce'),
			'shipping' => __('Shipping', 'hide-shipping-rates-for-woocommerce'),
			'customer' => __('Customer', 'hide-shipping-rates-for-woocommerce'),
			'custom' => __('Custom', 'hide-shipping-rates-for-woocommerce'),
		));
	}

	/**
	 * Get condition item of groups
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_all_conditions() {
		return apply_filters('hide_shipping_rates/condition_groups', array(
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
			'cart:product_shipping_classes' => array(
				'group' => 'cart',
				'priority' => 20,
				'label' => __('Product Shipping Classes', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:weekly_days' => array(
				'group' => 'date',
				'priority' => 10,
				'label' => __('Weekly Days', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:between_dates' => array(
				'group' => 'date',
				'priority' => 15,
				'is_pro' => true,
				'label' => __('Between Dates', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:between_times' => array(
				'group' => 'date',
				'priority' => 20,
				'label' => __('Between Times', 'hide-shipping-rates-for-woocommerce'),
			),

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

			'customer:users' => array(
				'group' => 'customer',
				'priority' => 10,
				'label' => __('Users', 'hide-shipping-rates-for-woocommerce'),
			),
			'customer:roles' => array(
				'group' => 'customer',
				'priority' => 15,
				'label' => __('Roles', 'hide-shipping-rates-for-woocommerce'),
			),
			'customer:logged_in' => array(
				'group' => 'customer',
				'priority' => 20,
				'label' => __('Logged In', 'hide-shipping-rates-for-woocommerce'),
			),
		));
	}

	/**
	 * Get conditions of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_conditions_by_group($group) {
		$all_conditions = self::get_all_conditions();

		$group_conditions = [];

		foreach ($all_conditions as $key => $condition) {
			if ($group !== $condition['group']) {
				continue;
			}

			$group_conditions[$key] = $condition;
		}

		uasort($group_conditions, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_conditions;
	}

	/**
	 * Free lock message
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function field_lock_message() {
		if (self::has_pro_installed()) {
			if (class_exists('\Hide_Shipping_Rates_Pro\Upgrade')) {
				if (!\Hide_Shipping_Rates_Pro\Upgrade::license_activated()) {
					echo '<div class="locked-message locked-message-activate-license">';
					$message = sprintf(
						/* translators: %1$s: Link open, %2$s: Link close */
						esc_html__('Please activate your license on the %1$sShipping Rules page%2$s for unlock this feature.', 'hide-shipping-rates-for-woocommerce'),
						'<a href="' . esc_url(menu_page_url('hide-shipping-rates-for-woocommerce', false)) . '">',
						'</a>'
					);
					echo wp_kses($message, array('a' => array('href' => true,  'target' => true)));
					echo '</div>';
				}
			} else {
				echo '<div class="locked-message">';
				esc_html_e('Please activate the Advanced Rule Based Shipping Pro plugin.', 'hide-shipping-rates-for-woocommerce');
				echo '</div>';
			}
		} else {
			echo '<div class="locked-message">Get the <a target="_blank" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/">pro version</a> for unlock this feature.</div>';
		}
	}
}
