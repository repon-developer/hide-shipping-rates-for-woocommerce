(function ($) {

	const has_pro = wp.hooks.applyFilters('hide_shipping_rates_has_pro', false);

	function get_uid(random_number = 66) {
		var d = new Date().getTime() + random_number;
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

	const rule_params = wp.hooks.applyFilters('hide_shipping_rates_rule_params', {
		value: '',
		weekly_days: [],
		logged_in: 'yes',
		customer_users: [],
		billing_cities: '',
		shipping_cities: '',
		type: 'cart:subtotal',
		operator: 'less_than',
		billing_countries: [],
		shipping_countries: [],
		shipping_classes: []
	});

	const rule_extra_params = wp.hooks.applyFilters('hide_shipping_rates_rule_extra_params', {
		hold_customers: [],
		loading_customers: true,
		hold_shipping_classes: [],
		loading_shipping_classes: true,
	});

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
				...rule_params,
				...rule_extra_params
			}, this.rule)
		},

		beforeMount() {
			this.$root.rules[this.number] = this.$data;
		},

		mounted() {
			this.pre_load_layout_data();
			wp.hooks.doAction('hide_shipping_rates_rule_mounted', this);
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
						let country = self.billing_state_country;
						if ($(this).data('model') == 'shipping_states') {
							country = self.shipping_state_country;
						}

						return {
							country,
							term: params.term,
							type: $(this).data('type'),
							security: hide_shipping_rates_admin.nonce_select2,
							action: 'hide_shipping_rates/get_select2_data'
						}
					},
					processResults: function (result) {
						return {
							results: $.map(result.data, function (user) {
								return {
									text: user.name,
									id: user.id
								}
							})
						};
					}
				}
			}).on('change', function () {
				const selected_model = $(this).data('model');
				if (selected_model && selected_model.length) {
					self[selected_model] = $(this).val();
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
				this.pre_load_layout_data();
			},

			...wp.hooks.applyFilters('hide_shipping_rates_rule_watch', {})
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

			pre_load_layout_data() {
				const self = this;

				(function () {
					if (self.type !== 'cart:product_shipping_classes') {
						return
					}

					if (self.shipping_classes.length == 0 || !Array.isArray(self.shipping_classes)) {
						return self.loading_shipping_classes = false;
					}

					self.loading_shipping_classes = true;

					const formData = new FormData();
					self.shipping_classes.forEach((shipping_class) => {
						formData.append('shipping_classes[]', shipping_class);
					})

					formData.append('type', 'shipping_classes')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						self.loading_shipping_classes = false;
						if (result.success == true) {
							self.hold_shipping_classes = result.data;
						}
					}).catch((e) => { })
				})();

				(function () {
					if (self.type !== 'customer:users') {
						return
					}

					if (self.customer_users.length == 0 || !Array.isArray(self.customer_users)) {
						return self.loading_customers = false;
					}

					self.loading_customers = true;

					const formData = new FormData();
					self.customer_users.forEach((user_id) => {
						formData.append('user_ids[]', user_id);
					})

					formData.append('type', 'users')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						self.loading_customers = false;
						if (result.success == true) {
							self.hold_customers = result.data;
						}
					}).catch((e) => { })
				})();


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
			}
		},

		computed: {
			get_rule_data() {
				const rules = JSON.parse(JSON.stringify(this.rules));
				rules.forEach((rule) => {
					delete rule.id
					Object.keys(rule_extra_params).forEach((remove_key) => {
						delete rule[remove_key];
					})
				})

				return JSON.stringify({
					rules,
					match_type: this.match_type
				});
			},

			max_free_rule_item() {
				return 2;
			}
		},

		methods: {
			show_get_pro_popup() {
				$('#hide-shipping-rates-pro-modal').trigger('open')
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
				this.rules.push({ ...rule, collapse: false, id: get_uid(), })
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


	$('#hide-shipping-rates-pro-modal').on('open', function () {
		$(this).addClass('modal-opened')
	})

	$('#hide-shipping-rates-pro-modal').on('close', function () {
		$(this).removeClass('modal-opened')
	})

	$('#hide-shipping-rates-pro-modal [data-modal-close]').on('click', function (e) {
		e.preventDefault();
		$('#hide-shipping-rates-pro-modal').trigger('close')
	})

	$(document).keyup(function (e) {
		if (e.key === "Escape") {
			$('#hide-shipping-rates-pro-modal').trigger('close')
		}
	});

})(jQuery)