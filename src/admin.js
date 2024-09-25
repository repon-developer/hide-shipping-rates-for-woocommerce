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
			this.pre_load_layout_data();
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
						return {
							country: $(this).attr('data-country'),
							term: params.term,
							type: $(this).data('type'),
							security: hide_shipping_rates_admin.nonce_select2,
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

			pre_load_layout_data() {
				const self = this;

				(function () {
					if (self.type !== 'cart_products:products' || self.cart_products.length == 0) {
						return
					}

					self.loading_products = true;

					const formData = new FormData();
					self.cart_products.forEach((product) => {
						formData.append('ids[]', product);
					})

					formData.append('type', 'post_type:product')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_products = result.data;
						}
					}).finally(() => {
						self.loading_products = false;
					})
				})();

				(function () {
					if (self.type !== 'cart_products:categories' || self.categories.length == 0) {
						return
					}

					self.loading_categories = true;

					const formData = new FormData();
					self.categories.forEach((category_id) => {
						formData.append('term_ids[]', category_id);
					})

					formData.append('type', 'taxonomy:product_cat')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_categories = result.data;
						}
					}).finally(() => {
						self.loading_categories = false;
					})
				})();

				(function () {
					if (self.type !== 'cart_products:tags' || self.tags.length == 0) {
						return
					}

					self.loading_tags = true;

					const formData = new FormData();
					self.tags.forEach((tag_id) => {
						formData.append('term_ids[]', tag_id);
					})

					formData.append('type', 'taxonomy:product_tag')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_tags = result.data;
						}
					}).finally(() => {
						self.loading_tags = false;
					})
				})();

				(function () {
					if (self.type !== 'cart_products:shipping_classes' || self.shipping_classes.length == 0) {
						return
					}

					self.loading_shipping_classes = true;

					const formData = new FormData();
					self.shipping_classes.forEach((shipping_class) => {
						formData.append('term_ids[]', shipping_class);
					})

					formData.append('type', 'taxonomy:product_shipping_class')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_shipping_classes = result.data;
						}
					}).finally(() => {
						self.loading_shipping_classes = false;
					})
				})();

				(function () {
					if (self.type !== 'user:users' || self.users.length == 0) {
						return;
					}

					self.loading_users = true;

					const formData = new FormData();
					self.users.forEach((user_id) => {
						formData.append('user_ids[]', user_id);
					})

					formData.append('type', 'users')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_users = result.data;
						}
					}).finally(() => {
						self.loading_users = false;
					})
				})();

				(function () {
					if (self.type !== 'cart:coupons' || self.coupons.length == 0) {
						return
					}

					self.loading_coupon = true;

					const formData = new FormData();
					self.coupons.forEach((coupon_id) => {
						formData.append('ids[]', coupon_id);
					})

					formData.append('type', 'post_type:shop_coupon')
					formData.append('security', hide_shipping_rates_admin.nonce_select2)
					formData.append('action', 'hide_shipping_rates/get_select2_data')

					fetch(hide_shipping_rates_admin.ajax_url, {
						method: 'POST',
						body: formData
					}).then((response) => response.json()).then((result) => {
						if (result.success == true) {
							self.hold_coupons = result.data;
						}
					}).finally(() => {
						self.loading_coupon = false;
					})
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