(function ($) {

	const has_pro = wp.hooks.applyFilters('hide_shipping_rates_has_pro', false);

	function get_uid() {
		var d = new Date().getTime();
		var d2 = ((typeof performance !== 'undefined') && performance.now && (performance.now() * 1000)) || 0;//Time in microseconds since page-load or 0 if unsupported
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = Math.random() * 16;
			if (d > 0) {
				r = (d + r) % 16 | 0;
				d = Math.floor(d / 16);
			} else {
				r = (d2 + r) % 16 | 0;
				d2 = Math.floor(d2 / 16);
			}
			return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
		});
	}

	const get_select2_map_data = (element) => {
		return Object.assign({
			model: 'placeholder',
			data_type: 'data_type_placeholder',
			hold_data: 'hold_data_placeholder',
		}, element.data('select2-map'))
	}

	const Rule = {
		template: '#component-hide-shipping-rates-rule',

		props: {
			rule: {
				type: Object,
			},
			number: {
				type: Number,
				default: 0
			},
		},

		data() {
			return Object.assign({
				id: get_uid(),
				...hide_shipping_rates_admin.rule_values,
				...hide_shipping_rates_admin.rule_ui_values,
			}, this.rule)
		},

		computed: {
			get_pro_link() {
				return 'https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/?utm_campaign=hide+shipping+rates+for+woocommerce&utm_source=shipping+methods&utm_medium=field+type&utm_term=' + this.type;
			}
		},

		beforeMount() {
			this.$root.rules[this.number] = this.$data;
		},

		mounted() {
			wp.hooks.doAction('hide_shipping_rates_rule_mounted', this);
			this.preload_layout_data();
		},

		beforeUpdate() {
			$(this.$refs.select2_ajax).select2('destroy')
			$(this.$refs.select2_dropdown).select2('destroy')
		},

		updated() {
			const self = this;

			$(this.$refs.select2_ajax).select2({
				placeholder: $(this).data('placeholder'),
				dropdownCssClass: 'hide-shipping-rates-select2-dropdown',
				ajax: {
					url: hide_shipping_rates_admin.ajax_url,
					dataType: "json",
					type: "POST",
					delay: 500,
					data: function (params) {
						const values_map = get_select2_map_data($(this));

						return {
							country: $(this).attr('data-country'),
							term: params.term,
							type: values_map.data_type,
							security: hide_shipping_rates_admin.nonce_select2_data,
							action: 'hide_shipping_rates/get_select2_data'
						}
					},
					processResults: function (result) {
						return {
							results: $.map(result.data, (data) => ({
								text: data.name,
								id: data.id
							}))
						};
					}
				}
			}).on('change', function () {
				const field_model = get_select2_map_data($(this)).model;
				if (field_model.length) {
					self[field_model] = $(this).val();
				}
			})

			$(this.$refs.select2_dropdown).select2({
				placeholder: $(this).data('placeholder'),
				dropdownCssClass: 'hide-shipping-rates-select2-dropdown',
			}).on('change', function () {
				const selected_model = $(this).data('model');
				if (selected_model && selected_model.length) {
					self[selected_model] = $(this).val();
				}
			})

			wp.hooks.doAction('hide_shipping_rates_rule_updated', self);
		},

		watch: {
			type() {
				this.preload_layout_data();
			},

			cart_value_type: function (value) {
				if ('in_cart' !== value) {
					this.preload_layout_data();
				}
			},

			...wp.hooks.applyFilters('hide_shipping_rates_rule_watch', {}, this)
		},

		methods: {
			get_countries() {
				return hide_shipping_rates_admin.countries;
			},

			get_ui_data_items(key) {
				return Array.isArray(this[key]) ? this[key] : [];
			},

			delete_item() {
				const response = confirm(hide_shipping_rates_admin.i10n.delete_rule_warning)
				if (response) {
					this.$root.rules.splice(this.number, 1)
				}
			},

			preload_layout_data() {
				const self = this;

				this.$nextTick(() => {
					const values_map = get_select2_map_data($(this.$refs.select2_ajax));
					if (!self[values_map.model] || !self[values_map.model].length) {
						return
					}

					self.loading = true;

					const formData = new FormData();
					self[values_map.model].forEach((product) => {
						formData.append('ids[]', product);
					})

					formData.append('type', values_map.data_type)
					formData.append('security', hide_shipping_rates_admin.nonce_select2_data)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					wp.hooks.doAction('hide_shipping_rates_select2_model_form_data', this, formData);

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self[values_map.hold_data] = result.data;
						}
					}).finally(() => {
						self.loading = false;
					})
				});

				wp.hooks.doAction('hide_shipping_rates_rule_preload', this);
			},

			...wp.hooks.applyFilters('hide_shipping_rates_rule_methods', {})
		}
	}

	const Hide_Shipping_Rates = {
		components: {
			'rule': Rule
		},

		data() {
			return {
				rules: [],
				match_type: 'all',
				hide_shipping_rate: false,
				disable_shipping_rules: false,
				alternate_matched_result: false,
			}
		},

		computed: {
			get_rule_data() {
				const rules = JSON.parse(JSON.stringify(this.rules));
				rules.forEach((rule) => {
					delete rule.id
					Object.keys(hide_shipping_rates_admin.rule_ui_values).forEach((remove_key) => {
						delete rule[remove_key];
					})
				})

				return JSON.stringify({ ...this.$data, rules });
			},

			max_free_rule_item() {
				return 3;
			}
		},

		methods: {
			show_get_pro_popup() {
				$('#hide-shipping-rates-modal').trigger('open')
			},

			add_new_rule() {
				if (this.rules.length >= this.max_free_rule_item && has_pro === false) {
					return this.show_get_pro_popup();
				}

				this.rules.push({})
			},

			duplicate_rule(rule_no) {
				if (this.rules.length >= this.max_free_rule_item && has_pro === false) {
					return this.show_get_pro_popup();
				}

				const rule = JSON.parse(JSON.stringify(this.rules[rule_no]));
				this.rules.push({ ...rule, id: get_uid() })
			},

			onOrderChange(event) {
				let item = this.rules.splice(event.oldIndex, 1)[0];
				this.rules.splice(event.newIndex, 0, item);
			},

			has_pro() {
				return has_pro
			},
		}
	}

	function initialize_rule_settings_field() {
		if (!$('#hide-shipping-rates-rules-field').length) {
			return;
		}

		const Main_App = Vue.createApp(Hide_Shipping_Rates).use(sortablejs)
		const main_app_holder = Main_App.mount('#hide-shipping-rates-rules-field')

		const shipping_rate_rule_settings = $('#hide-shipping-rates-rules-field').data('settings');
		if (typeof shipping_rate_rule_settings === 'object') {
			for (const key in shipping_rate_rule_settings) {
				main_app_holder[key] = shipping_rate_rule_settings[key]
			}
		}
	}

	initialize_rule_settings_field();

	$(document.body).on('wc_backbone_modal_loaded', function (event, modal_name) {
		if ('wc-modal-shipping-method-settings' !== modal_name) {
			return;
		}

		initialize_rule_settings_field()
	});

})(jQuery)