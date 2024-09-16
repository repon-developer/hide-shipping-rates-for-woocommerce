<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Customer rule class
 */
final class Customer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_ui_values', array($this, 'rule_ui_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'users_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'logged_in_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'customer_roles_template'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'logged_in' => 'no',
			'customer_users' => [],
		));
	}

	/**
	 * Rule UI values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_ui_values($values) {
		return array_merge($values, array(
			'hold_customers' => [],
			'loading_customers' => true,
		));
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
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
	 * Add users template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function users_template() { ?>
		<template v-if="type == 'customer:users'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading_customers"></div>
			<select class="select2-flex1" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Select users', 'hide-shipping-rates-for-woocommerce'); ?>" data-model="customer_users" data-type="users">
				<option v-for="user in get_ui_data_items('hold_customers')" :value="user.id" :selected="customer_users.includes(user.id.toString())">{{user.name}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add user roles template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function customer_roles_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'customer:roles'">
			<select>
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select>
				<option value="test">Administrator</option>
			</select>

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add logged in user template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function logged_in_template() { ?>
		<template v-if="type == 'customer:logged_in'">
			<select v-model="logged_in">
				<option value="yes"><?php esc_html_e('Yes', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="no"><?php esc_html_e('No', 'hide-shipping-rates-for-woocommerce'); ?></option>
			</select>
		</template>
<?php
	}
}
