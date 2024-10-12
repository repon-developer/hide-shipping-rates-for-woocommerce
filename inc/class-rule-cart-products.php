<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart products rule class
 */
final class Cart_Products {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_ui_values', array($this, 'rule_ui_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'tags_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'products_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'categories_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_classes_template'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'tags' => [],
			'products' => [],
			'categories' => [],
			'shipping_classes' => [],
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
			'hold_tags' => [],
			'hold_products' => [],
			'hold_categories' => [],
			'hold_shipping_classes' => [],
		));
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		if ('cart_products:products' === $rule['type']) {
			$rule_products = isset($rule['products']) && is_array($rule['products']) ? $rule['products'] : array();

			$cart_products = [];
			foreach (WC()->cart->get_cart() as $item) {
				$cart_products[] = $item['product_id'];
			}

			$cart_products = array_unique(array_filter($cart_products));

			$matched_items = array_intersect($rule_products, $cart_products);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($matched_items) === count($rule_products)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		if ('cart_products:categories' === $rule['type']) {
			$rule_terms = isset($rule['categories']) && is_array($rule['categories']) ? $rule['categories'] : array();

			$cart_terms = [];
			foreach (WC()->cart->get_cart() as $item) {
				$cart_terms = array_merge($cart_terms, wc_get_product_term_ids($item['product_id'], 'product_cat'));
			}

			$matched_items = array_intersect($cart_terms, $rule_terms);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($rule_terms) === count($matched_items)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		if ('cart_products:tags' === $rule['type']) {
			$rule_terms = isset($rule['tags']) && is_array($rule['tags']) ? $rule['tags'] : array();

			$cart_terms = [];
			foreach (WC()->cart->get_cart() as $item) {
				$cart_terms = array_merge($cart_terms, wc_get_product_term_ids($item['product_id'], 'product_tag'));
			}

			$matched_items = array_intersect($cart_terms, $rule_terms);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($rule_terms) === count($matched_items)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		if ('cart_products:shipping_classes' === $rule['type']) {
			$shipping_classes = isset($rule['shipping_classes']) && is_array($rule['shipping_classes']) ? $rule['shipping_classes'] : array();

			$cart_products = WC()->cart->get_cart();
			$product_shipping_classes = [];
			foreach ($cart_products as $item) {
				$product_shipping_classes[] = $item['data']->get_shipping_class_id();
			}

			$product_shipping_classes = array_unique(array_filter($product_shipping_classes));

			$matched_items = array_intersect($shipping_classes, $product_shipping_classes);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($shipping_classes) === count($matched_items)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Products template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function products_template() {
		$model_values = array(
			'model' => 'products',
			'hold_data' => 'hold_products',
			'data_type' => 'post_type:product',
		); ?>
		<template v-if="type == 'cart_products:products'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="products" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Products', 'hide-shipping-rates-for-woocommerce'); ?>" data-model-values="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="product in get_ui_data_items('hold_products')" :value="product.id">{{product.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if any of the above products are available in the cart.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the above product(s) is available in the cart.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart does not contain any product in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Tags template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function tags_template() {
		$model_values = array(
			'model' => 'tags',
			'hold_data' => 'hold_tags',
			'data_type' => 'taxonomy:product_tag',
		); ?>

		<template v-if="type == 'cart_products:tags'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="tags" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Tags', 'hide-shipping-rates-for-woocommerce'); ?>" data-model-values="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="tag in get_ui_data_items('hold_tags')" :value="tag.id">{{tag.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products contain any tag in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the above tags are available in the cart products.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products do not contain any tag in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Categories template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function categories_template() {
		$model_values = array(
			'model' => 'categories',
			'hold_data' => 'hold_categories',
			'data_type' => 'taxonomy:product_cat',
		); ?>

		<template v-if="type == 'cart_products:categories'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="categories" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Categories', 'hide-shipping-rates-for-woocommerce'); ?>" data-model-values="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="category in get_ui_data_items('hold_categories')" :value="category.id">{{category.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products contain any category in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the above categories are available in the cart products.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products do not contain any category in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Shipping classes template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_classes_template() {
		$model_values = array(
			'model' => 'shipping_classes',
			'hold_data' => 'hold_shipping_classes',
			'data_type' => 'taxonomy:product_shipping_class',
		); ?>

		<template v-if="type == 'cart_products:shipping_classes'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="shipping_classes" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Shipping Classes', 'hide-shipping-rates-for-woocommerce'); ?>" data-model-values="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="shipping_class in get_ui_data_items('hold_shipping_classes')" :value="shipping_class.id">{{shipping_class.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the any of above shipping classes are available in the cart products.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products contain all of the above shipping classes.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products do not contain any shipping class in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
<?php
	}
}
