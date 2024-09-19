<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart rule class
 */
final class Cart {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_ui_values', array($this, 'rule_ui_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'coupon_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'common_templates'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'coupons' => [],
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
			'hold_coupons' => [],
			'loading_coupon' => true,
		));
	}

	/**
	 * Cart rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
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

		return $matched;
	}

	/**
	 * Common templates
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function common_templates() { ?>
		<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight'].includes(type)">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than_or_equal', 'greater_than')); ?>
			</select>

			<input type="text" v-model="value" placeholder="<?php echo '0.00'; ?>">
		</template>
	<?php
	}

	/**
	 * Coupon template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function coupon_template() { ?>
		<template v-if="type == 'cart:coupons'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading_coupon"></div>
			<select class="select2-flex1" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Coupons', 'hide-shipping-rates-for-woocommerce'); ?>" data-model="coupons" data-type="post_type:shop_coupon">
				<option v-for="coupon in get_ui_data_items('hold_coupons')" :value="coupon.id" :selected="coupons.includes(coupon.id.toString())">{{coupon.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart contains any coupon in the selected list.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart does not contain any coupon in the selected list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}
}
