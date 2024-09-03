<?php

if (!defined('ABSPATH')) {
	exit;
}

use Hide_Shipping_Rates\Utils;

$condition_groups = Utils::get_condition_groups(); ?>

<fieldset class="hide-shipping-rates-rule-item">
	<select class="rule-type" v-model="type">
		<?php
		foreach ($condition_groups as $group_key => $group_label) {
			$conditions = Utils::get_conditions_by_group($group_key);
			if (count($conditions) == 0) {
				continue;
			}

			echo '<optgroup label="' . esc_attr($group_label) . '">';
			foreach ($conditions as $key => $condition) {
				echo '<option value="' . esc_attr($key) . '">' . esc_html($condition['label']) . ' </option>';
			}
			echo '</optgroup>';
		}
		?>
	</select>


	<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight'].includes(type)">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than_or_equal', 'greater_than')); ?>
		</select>

		<input type="text" v-model="value" placeholder="<?php echo '0.00'; ?>">
	</template>

	<template v-if="type == 'customer:users'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<div class="input-field-loading" v-if="loading_customers"></div>
		<select ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Select users', 'advanced-coupon-for-woocommerce'); ?>" data-model="customer_users" data-type="users">
			<option v-for="user in get_ui_data_items('hold_customers')" :value="user.id" :selected="customer_users.includes(user.id.toString())">{{user.name}}</option>
		</select>
	</template>

	<template v-if="type == 'customer:logged_in'">
		<select v-model="logged_in">
			<option value="yes"><?php esc_html_e('Yes', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="no"><?php esc_html_e('No', 'advanced-coupon-for-woocommerce'); ?></option>
		</select>
	</template>

	<template v-if="type == 'date:weekly_days'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<select v-model="weekly_days" data-model="weekly_days" ref="select2_dropdown" data-placeholder="<?php esc_attr_e('Select days', 'advanced-coupon-for-woocommerce'); ?>" multiple>
			<option value="sunday"><?php esc_html_e('Sunday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="monday"><?php esc_html_e('Monday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="tuesday"><?php esc_html_e('Tuesday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="wednesday"><?php esc_html_e('Wednesday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="thursday"><?php esc_html_e('Thursday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="friday"><?php esc_html_e('Friday', 'advanced-coupon-for-woocommerce'); ?></option>
			<option value="saturday"><?php esc_html_e('Saturday', 'advanced-coupon-for-woocommerce'); ?></option>
		</select>
	</template>

	<template v-if="type == 'billing:city'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<?php $placeholder = __('Example: Chicago, New York', 'advanced-coupon-for-woocommerce'); ?>
		<input style="flex: 1;" type="text" v-model="billing_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
	</template>

	<template v-if="type == 'shipping:city'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<?php $placeholder = __('Example: Chicago, New York', 'advanced-coupon-for-woocommerce'); ?>
		<input style="width: 400px;" type="text" v-model="shipping_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
	</template>

	<template v-if="type == 'billing:country'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<select v-model="billing_countries" ref="select2_dropdown" multiple data-model="billing_countries" data-placeholder="<?php esc_attr_e('Select country', 'advanced-coupon-for-woocommerce'); ?>">
			<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
		</select>
	</template>

	<template v-if="type == 'shipping:country'">
		<select v-model="operator">
			<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
		</select>

		<select v-model="shipping_countries" ref="select2_dropdown" multiple data-model="shipping_countries" data-placeholder="<?php esc_attr_e('Select country', 'advanced-coupon-for-woocommerce'); ?>">
			<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
		</select>
	</template>

	<?php do_action('hide_shipping_rates/rule_templates'); ?>

	<div class="rule-action-tools">
		<span class="rule-move-handle dashicons dashicons-menu-alt"></span>
		<a href="#" class="btn-condition-delete dashicons dashicons-no-alt" @click.prevent="delete_item()"></a>
	</div>


</fieldset>