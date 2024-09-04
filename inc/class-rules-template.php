<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Condition class for template of options
 */
final class Rules_Template {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('hide_shipping_rates/rule_templates', array($this, 'between_dates'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'between_times'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_zipcode_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_state_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_zipcode_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_state_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'customer_roles'));
	}

	/**
	 * Add between dates template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function between_dates() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'date:between_dates'">
			<input type="datetime-local">
			<input type="datetime-local">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add between times template of condition template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function between_times() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'date:between_times'">
			<input type="time">
			<input type="time">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add zipcode template of billing conditin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_zipcode_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'billing:zipcode'">
			<select>
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<input style="flex: 1;" type="text" placeholder="<?php esc_html_e('Example: 38632, 21710, 38686', 'hide-shipping-rates-for-woocommerce'); ?>">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add state of billing template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_state_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'billing:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select ref="select2_dropdown">
				<option value=""><?php esc_html_e('Select a country', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>

			<select ref="select2_ajax" multiple data-placeholder="<?php esc_html_e('Select states', 'hide-shipping-rates-for-woocommerce'); ?>">
				<option value="state1">State one</option>
				<option value="state1">State two</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add state template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_zipcode_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'shipping:zipcode'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<input style="flex: 1" type="text" v-model="zipcodes" placeholder="<?php esc_html_e('Example: 38632, 21710, 38686', 'hide-shipping-rates-for-woocommerce'); ?>">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add state of shipping template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_state_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'shipping:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select ref="select2_dropdown">
				<option value=""><?php esc_html_e('Select a country', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>

			<select ref="select2_ajax" multiple data-placeholder="<?php esc_html_e('Select states', 'hide-shipping-rates-for-woocommerce'); ?>">
				<option value="state1">State one</option>
				<option value="state1">State two</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}


	/**
	 * Add customer roles template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function customer_roles() { ?>
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
}
