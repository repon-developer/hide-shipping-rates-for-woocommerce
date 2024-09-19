<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Date rule class
 */
final class Date {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'weekly_days'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'between_times'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'before_time'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'after_time'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'between_dates'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'before_datetime'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'after_datetime'));
	}

	/**
	 * Date rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'weekly_days' => [],
			'after_datetime' => '',
			'after_time' => '',
		));
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		//error_log(print_r($rule, true));
		if ('date:weekly_days' === $rule['type']) {
			$weekly_days = isset($rule['weekly_days']) && is_array($rule['weekly_days']) ? $rule['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('any_in_list' == $rule['operator'] && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		if ('date:before_datetime' === $rule['type']) {
			if (empty($rule['before_datetime'])) {
				return $matched;
			}

			$before_datetime = strtotime($rule['before_datetime']);
			if (false === $before_datetime) {
				return $matched;
			}

			return current_time('timestamp') < $before_datetime;
		}

		if ('date:after_datetime' === $rule['type']) {
			if (empty($rule['after_datetime'])) {
				return $matched;
			}

			$after_datetime = strtotime($rule['after_datetime']);
			if (false === $after_datetime) {
				return $matched;
			}

			return current_time('timestamp') > $after_datetime;
		}

		if ('date:before_time' === $rule['type']) {
			if (empty($rule['before_time'])) {
				return $matched;
			}

			$before_time = strtotime($rule['before_time']);
			if (false === $before_time) {
				return $matched;
			}

			return current_time('timestamp') < $before_time;
		}

		if ('date:after_time' === $rule['type']) {
			if (empty($rule['after_time'])) {
				return $matched;
			}

			$after_time = strtotime($rule['after_time']);
			if (false === $after_time) {
				return $matched;
			}

			return current_time('timestamp') > $after_time;
		}

		return $matched;
	}

	/**
	 * Weekly days type template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function weekly_days() { ?>
		<template v-if="type == 'date:weekly_days'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select class="select2-flex1" v-model="weekly_days" data-model="weekly_days" ref="select2_dropdown" data-placeholder="<?php esc_attr_e('Select days', 'hide-shipping-rates-for-woocommerce'); ?>" multiple>
				<option value="sunday"><?php esc_html_e('Sunday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="monday"><?php esc_html_e('Monday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="tuesday"><?php esc_html_e('Tuesday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="wednesday"><?php esc_html_e('Wednesday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="thursday"><?php esc_html_e('Thursday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="friday"><?php esc_html_e('Friday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="saturday"><?php esc_html_e('Saturday', 'hide-shipping-rates-for-woocommerce'); ?></option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add between times template
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
	 * Add before time template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function before_time() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'date:before_time'">
			<input type="time">
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add after time template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function after_time() { ?>
		<template v-if="type == 'date:after_time'">
			<input type="time" v-model="after_time">
		</template>
	<?php
	}

	/**
	 * Add between dates template of rule
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
	 * Add before datetime rule template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function before_datetime() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'date:before_datetime'">
			<input type="datetime-local">
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add after datetime rule template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function after_datetime() { ?>
		<template v-if="type == 'date:after_datetime'">
			<input type="datetime-local" v-model="after_datetime">
		</template>
<?php
	}
}
