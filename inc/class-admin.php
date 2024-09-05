<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin class of the plugin
 */
final class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('woocommerce_init', array($this, 'add_rule_settings_field'));
		add_filter('woocommerce_generate_hide_shipping_rates_rules_settings_html', array($this, 'shipping_rules_settings_field_output'), 10, 4);

		add_action('admin_footer', array($this, 'output_modal'));
		add_action('admin_footer', array($this, 'add_vue_component'));
		add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 1);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_global'), 1);
		add_action('wp_ajax_hide_shipping_rates/get_select2_data', array($this, 'get_select2_data'));
	}

	/**
	 * Add settings field at shipping methods
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rule_settings_field() {
		$methods = WC()->shipping()->load_shipping_methods();
		foreach ($methods as $method) {
			add_filter('woocommerce_shipping_instance_form_fields_' . $method->id, array($this, 'add_hide_shipping_rates_fields'), 10000);
		}
	}

	/**
	 * Add new field below shipping method settings
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function add_hide_shipping_rates_fields($settings) {
		$settings['hide_shipping_rates_rules_settings'] = array(
			'default' => '',
			'title' => esc_html__('Hide this shipping rate if match rule(s).', 'hide-shipping-rates-for-woocommerce'),
			'id' => 'hide_shipping_rates_rules_settings',
			'type' => 'hide_shipping_rates_rules_settings',
		);

		return $settings;
	}

	/**
	 * Output new field below shipping method settings
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function shipping_rules_settings_field_output($html, $field_id, $args, $object) {
		$settings_data = json_decode(stripslashes($object->get_option($field_id)), true);

		ob_start(); ?>
		<tr>
			<th>
				<label class="hide-shipping-rates-rules-settings-label"><?php echo esc_html($args['title']); ?></label>
			</th>
			<td>
				<div id="hide-shipping-rates-rules-field" data-settings="<?php echo esc_attr(wp_json_encode($settings_data)) ?>">
					<input type="hidden" name="<?php echo esc_attr($object->get_field_key($field_id)) ?>" :value="get_rule_data">
					<a v-if="rules.length == 0" @click.prevent="add_new_rule()" class="btn-hide-shipping-rates-add-rule-large" href="#"><?php esc_html_e('Add a rule', 'hide-shipping-rates-for-woocommerce') ?></a>

					<div class="hide-shipping-rates-rules-container" v-sortable="{options: {handle: '.rule-move-handle'}}" @end="onOrderChange">
						<rule v-for="(rule, index) in rules" :key="rule.id" :rule="rule" :number="index"></rule>
					</div>

					<div class="hide-shipping-rates-field-row hide-shipping-rates-match-type" v-if="rules.length > 1">
						<label>
							<input type="radio" v-model="match_type" value="all">
							<?php esc_html_e('Match all', 'hide-shipping-rates-for-woocommerce'); ?>
						</label>

						<label>
							<input type="radio" v-model="match_type" value="any">
							<?php esc_html_e('Match any', 'hide-shipping-rates-for-woocommerce'); ?>
						</label>
					</div>

					<a v-if="rules.length > 0" @click.prevent="add_new_rule()" class="button btn-hide-shipping-rates-add-rule" href="#">
						<span class="dashicons dashicons-lock" v-if="rules.length >= max_free_rule_item && !has_pro()"></span>
						<?php esc_html_e('Add another rule', 'hide-shipping-rates-for-woocommerce') ?>
					</a>
				</div>
			</td>
		</tr>

	<?php
		return ob_get_clean();
	}

	/**
	 * Register styles and scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_scripts() {
		if (defined('HIDE_SHIPPING_RATES_DEV')) {
			wp_register_script('hide-shipping-rates-vue', HIDE_SHIPPING_RATES_URI . 'assets/vue.js', [], '3.5.1', true);
		} else {
			wp_register_script('hide-shipping-rates-vue', HIDE_SHIPPING_RATES_URI . 'assets/vue.min.js', [], '3.5.1', true);
		}
	}

	/**
	 * Enqueue script on dashboard
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts_global() {
		$screen_matched = false;
		if ('woocommerce_page_wc-settings' === get_current_screen()->id && isset($_GET['tab']) && 'shipping' === $_GET['tab']) {
			$screen_matched = true;
		}

		if ('plugins' === get_current_screen()->id) {
			$screen_matched = true;
		}

		if (!$screen_matched) {
			return;
		}

		$wc_countries = new \WC_Countries();

		wp_enqueue_style('hide-shipping-rates-global', HIDE_SHIPPING_RATES_URI . 'assets/global.min.css', [], HIDE_SHIPPING_RATES_VERSION);
		wp_enqueue_script('hide-shipping-rates-global', HIDE_SHIPPING_RATES_URI . 'assets/global.min.js', ['jquery'], HIDE_SHIPPING_RATES_VERSION, true);
		wp_localize_script('hide-shipping-rates-global', 'hide_shipping_rates_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'countries' => $wc_countries->get_countries(),
			'nonce_select2' => wp_create_nonce('_nonce_hide_shipping_rates/get_select2_data'),
			'i10n' => array(
				'delete_rule_warning' => __('Do you want to delete this rule?', 'hide-shipping-rates-for-woocommerce'),
			)
		));

		do_action('hide_shipping_rates/global_enqueue_scripts');
	}

	/**
	 * Enqueue script on shipping page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (get_current_screen()->id !== 'woocommerce_page_wc-settings' || !isset($_GET['tab']) || 'shipping' !== $_GET['tab']) {
			return;
		}

		wp_register_script('sortable', HIDE_SHIPPING_RATES_URI . 'assets/sortable.min.js', [], '1.15.2', true);
		wp_register_script('vue-sortable', HIDE_SHIPPING_RATES_URI . 'assets/vue-sortable.js', ['hide-shipping-rates-vue', 'sortable'], '1.0.7', true);

		wp_register_style('select2', HIDE_SHIPPING_RATES_URI . 'assets/select2.min.css');
		wp_enqueue_style('hide-shipping-rates', HIDE_SHIPPING_RATES_URI . 'assets/admin.min.css', ['select2'], HIDE_SHIPPING_RATES_VERSION);

		do_action('hide_shipping_rates/admin_enqueue_scripts');

		wp_enqueue_script('hide-shipping-rates', HIDE_SHIPPING_RATES_URI . 'assets/admin.min.js', ['jquery', 'hide-shipping-rates-vue', 'select2', 'vue-sortable'], HIDE_SHIPPING_RATES_VERSION, true);
	}

	/**
	 * Output plugin modal
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_modal() {
		if ('woocommerce_page_wc-settings' !== get_current_screen()->id || !isset($_GET['tab']) || 'shipping' !== $_GET['tab']) {
			return;
		} ?>

		<?php if (!Utils::has_pro_installed()) : ?>
			<div id="hide-shipping-rates-modal">
				<div class="modal-body">
					<a href="#" class="btn-modal-close dashicons dashicons-no-alt" data-modal-close></a>
					<span class="modal-icon dashicons dashicons-lock"></span>

					<div class="modal-pro-missing">
						<?php
						$text = sprintf(
							/* translators: %s for link */
							esc_html__('For adding more rule, please get a pro version from %s.', 'hide-shipping-rates-for-woocommerce'),
							'<a target="_blank" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/">' . esc_html__('here', 'hide-shipping-rates-for-woocommerce') . '</a>'
						);

						echo wp_kses($text, array('a' => array('href' => true, 'target' => true)));
						?>
					</div>

					<div class="modal-footer">
						<a class="button" data-modal-close href="#"><?php esc_html_e('Close', 'hide-shipping-rates-for-woocommerce'); ?></a>
						<a class="button button-get-pro" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/" target="_blank"><?php esc_html_e('Get Pro', 'hide-shipping-rates-for-woocommerce'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!Utils::is_pro_activated()) : ?>
			<div id="hide-shipping-rates-modal">
				<div class="modal-body">
					<a href="#" class="btn-modal-close dashicons dashicons-no-alt" data-modal-close></a>
					<div class="modal-pro-deactivated">
						<?php esc_html_e('Please activate the "Hide Shipping Rates for WooCommerce Pro" plugin on the plugins page.', 'hide-shipping-rates-for-woocommerce'); ?>
					</div>

					<div class="modal-footer">
						<a class="button" data-modal-close href="#"><?php esc_html_e('Close', 'hide-shipping-rates-for-woocommerce'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>
<?php
	}

	/**
	 * Add vuejs component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_vue_component() {
		if (get_current_screen()->id !== 'woocommerce_page_wc-settings' || !isset($_GET['tab']) || 'shipping' !== $_GET['tab']) {
			return;
		}

		echo '<template id="component-hide-shipping-rates-rule">';
		include_once HIDE_SHIPPING_RATES_PATH . '/inc/template-shipping-rate-rule.php';
		echo '</template>';
	}

	/**
	 * Get select2 dropdown data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_select2_data() {
		if (!isset($_POST['security'])) {
			wp_send_json_error(array(
				'error' => __('Security Missing.', 'hide-shipping-rates-for-woocommerce')
			));
		}

		check_ajax_referer('_nonce_hide_shipping_rates/get_select2_data', 'security');

		$results = array();
		$search_args = array();

		$query_type = !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : false;
		$search_term = !empty($_POST['term']) ? sanitize_text_field($_POST['term'])  : '';

		if ('shipping_classes' == $query_type) {
			$search_args = array('hide_empty' => false, 'taxonomy' => 'product_shipping_class');

			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['shipping_classes']) && is_array($_POST['shipping_classes'])) {
				$shipping_classes = array_map('absint', $_POST['shipping_classes']);
				$search_args['include'] = $shipping_classes;
			}

			$shipping_classes_terms = get_terms($search_args);

			$results = array_map(function ($shipping_class) {
				return array('id' => $shipping_class->term_id, 'name' => $shipping_class->name);
			}, $shipping_classes_terms);
		}

		if ('users' == $query_type) {
			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {
				$user_ids = array_map('absint', $_POST['user_ids']);
				$search_args['include'] = $user_ids;
			}

			$get_users = get_users($search_args);
			$results = array_map(function ($user) {
				return array('id' => $user->id, 'name' => $user->display_name);
			}, $get_users);
		}

		if ('states' == $query_type) {
			if (empty($_POST['country'])) {
				wp_send_json_error(array(
					'error' => esc_html__('Country Missing', 'advanced-coupon-for-woocommerce')
				));
			}

			$wc_countries = new \WC_Countries();
			$states = $wc_countries->get_states(sanitize_text_field($_POST['country']));

			if (!empty($search_term)) {
				$states = array_filter($states, function ($state) use ($search_term) {
					return stripos($state, $search_term) !== false;
				});
			}

			if (!is_array($states)) {
				$states = [];
			}

			$results = array_map(function ($state, $code) {
				return array('id' => $code, 'name' => html_entity_decode($state));
			}, $states, array_keys($states));
		}

		do_action('hide_shipping_rates/get_select2_data', $query_type, $search_term);

		wp_send_json_success($results);
	}
}
