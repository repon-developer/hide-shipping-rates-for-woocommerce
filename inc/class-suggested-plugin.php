<?php

namespace Codiepress;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Codiepress Suggested Plugin Class
 */
final class Suggested_Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('admin_footer', array($this, 'add_inline_styles'), 1);
		add_action('woocommerce_init', array($this, 'add_shipqora_notice_field'), 1000);
		add_filter('woocommerce_generate_shipqora_plugin_html', array($this, 'shipqora_output'), 10);
	}

	/**
	 * Add inline styles
	 */
	public function add_inline_styles() { ?>
		<style>
			#shipqora-suggest-notice {
				font-size: 14px;
				border-radius: 2px;
				padding: 15px;
				padding-bottom: 15px;
				margin-top: 10px;
				max-width: 700px;
				background-color: #f6f4ff;
				border: 1px solid #bca9ff;
			}

			#shipqora-suggest-notice h3 {
				font-size: 15px;
				font-weight: 600;
				margin-block: 0 5px;
			}

			#shipqora-suggest-notice h3 a {
				color: inherit
			}

			#shipqora-suggest-notice p {margin-block: 5px 10px}

			#shipqora-suggest-notice .action-tools {
				margin-top:10px;
			}
		</style>

	<?php
	}

	/**
	 * Add settings field at shipping methods
	 * 
	 * @return void
	 */
	public function add_shipqora_notice_field() {
		if (class_exists('\ShipQora\Main')) {
			return;
		}

		$methods = WC()->shipping->get_shipping_methods();
		foreach ($methods as $method) {
			add_filter('woocommerce_shipping_instance_form_fields_' . $method->id, array($this, 'suggest_shipqora'), 100000);
		}
	}

	/**
	 * Add new field below shipping method settings
	 * 
	 * @return array
	 */
	public function suggest_shipqora($settings) {
		$settings['suggest_shipqora'] = array(
			'title' => '',
			'default' => '', //Don't remove this one. Otherwise system will show error
			'type' => 'shipqora_plugin',
		);

		return $settings;
	}

	/**
	 * Output new field below the shipping method settings
	 * 
	 * @return string
	 */
	public function shipqora_output() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$shipqora_plugin = 'shipqora/shipqora.php';

		ob_start(); ?>
		<tr>
			<th></th>
			<td>
				<?php
				if (!is_plugin_active($shipqora_plugin)) : ?>
					<div id="shipqora-suggest-notice">
						<h3><a href="https://wordpress.org/plugins/shipqora" target="_blank">ShipQora</a> — All-in-One Shipping Solution for WooCommerce</h3>

						<p class="adjust-line">Unlock complete control over your checkout experience with ShipQora, the ultimate all-in-one shipping engine designed to build advanced dynamic pricing, custom rate adjustments, and powerful restrictions in real time.</p>
						<p><strong>Key Features:</strong> <br><strong>Hide Shipping Methods</strong>, Hide Other Shipping Methods, Hide Payment Methods, Shipping Cost Adjustment, Product-Based Shipping Cost and Cart-Based Shipping Cost.</p>

						<div class="action-tools">
							<?php
							if (isset(get_plugins()[$shipqora_plugin])) {
								$activated_url = wp_nonce_url('plugins.php?action=activate&plugin=shipqora/shipqora.php&plugin_status=all&paged=1', 'activate-plugin_shipqora/shipqora.php');
								echo '<a class="button" href="' . esc_url($activated_url) . '">Activate ShipQora Plugin</a>';
							} else {
								echo '<a class="button" href="' . esc_url(wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=shipqora'), 'install-plugin_shipqora')) . '">Install ShipQora Plugin</a>';
							} ?>
						</div>
					</div>
				<?php endif; ?>
			</td>
		</tr>
<?php
		return ob_get_clean();
	}
}

new Suggested_Plugin();
