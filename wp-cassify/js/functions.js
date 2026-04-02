// Admin interactions implemented in vanilla JavaScript.

document.addEventListener('DOMContentLoaded', function () {
	function byId(id) {
		return document.getElementById(id);
	}

	function show(el) {
		if (el) {
			el.style.display = '';
		}
	}

	function hide(el) {
		if (el) {
			el.style.display = 'none';
		}
	}

	function on(id, eventName, handler) {
		var el = byId(id);
		if (el) {
			el.addEventListener(eventName, handler);
		}
	}

	function getSelectedValue(selectEl) {
		if (!selectEl || selectEl.selectedOptions.length === 0) {
			return '';
		}

		return selectEl.selectedOptions[0].value;
	}

	function appendOption(selectEl, value) {
		if (!selectEl) {
			return;
		}

		var option = document.createElement('option');
		option.value = value;
		option.textContent = value;
		selectEl.appendChild(option);
	}

	function removeSelectedOptions(selectEl) {
		if (!selectEl) {
			return;
		}

		Array.from(selectEl.selectedOptions).forEach(function (option) {
			option.remove();
		});
	}

	function splitRule(value, index) {
		if (!value) {
			return '';
		}

		var parts = value.split('|');
		return parts[index] || '';
	}

	// Use native date input instead of jQuery UI datepicker when supported.
	var fixedDatetimeField = byId('wp_cassify_fixed_datetime_limit');
	if (fixedDatetimeField) {
		fixedDatetimeField.setAttribute('type', 'date');
	}
	hide(fixedDatetimeField);
	hide(byId('wp_cassify_custom_user_meta'));

	// for metaboxes
	document.querySelectorAll('.if-js-closed').forEach(function (el) {
		el.classList.remove('if-js-closed');
		el.classList.add('closed');
	});

	if (window.postboxes && typeof window.postboxes.add_postbox_toggles === 'function') {
		window.postboxes.add_postbox_toggles('wp-cassify');
	}

	// scroll to last submit button clicked.
	if (typeof window.wp_cassify_screen_data !== 'undefined' && typeof window.wp_cassify_screen_data.scrollToId !== 'undefined') {
		var wpCassifyIdToScroll;

		if (window.wp_cassify_screen_data.scrollToId === 'wp_cassify_send_notification_test_message') {
			wpCassifyIdToScroll = '#wp_cassify_metabox_notifications_settings';
		} else {
			wpCassifyIdToScroll = '#' + window.wp_cassify_screen_data.scrollToId.replace('save_options', 'metabox');
		}

		var scrollTarget = document.querySelector(wpCassifyIdToScroll);
		if (scrollTarget) {
			window.scrollTo({
				top: scrollTarget.getBoundingClientRect().top + window.pageYOffset,
				behavior: 'smooth'
			});
		}
	}

	// Disabled field if checkbox unchecked
	var smtpAuth = byId('wp_cassify_notifications_smtp_auth');
	var encryptionType = byId('wp_cassify_notifications_encryption_type');
	if (smtpAuth && encryptionType) {
		encryptionType.disabled = !smtpAuth.checked;
	}

	/*
	 *	Authorizations rules functions
	 */
	on('wp_cassify_add_rule', 'click', function (evt) {
		var addRuleOption = byId('wp_cassify_autorization_rule');
		var ruleType = byId('wp_cassify_rule_type');
		var rulesSelect = byId('wp_cassify_autorization_rules');
		var value = getSelectedValue(ruleType) + '|' + (addRuleOption ? addRuleOption.value : '');

		appendOption(rulesSelect, value);

		if (addRuleOption) {
			addRuleOption.value = '';
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_remove_rule', 'click', function (evt) {
		removeSelectedOptions(byId('wp_cassify_autorization_rules'));

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_autorization_rules', 'dblclick', function (evt) {
		var rulesSelect = byId('wp_cassify_autorization_rules');
		var selected = getSelectedValue(rulesSelect);

		var authRule = byId('wp_cassify_autorization_rule');
		var ruleType = byId('wp_cassify_rule_type');
		if (authRule) {
			authRule.value = splitRule(selected, 1);
		}
		if (ruleType) {
			ruleType.value = splitRule(selected, 0);
		}

		removeSelectedOptions(rulesSelect);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	User roles rules functions
	 */
	on('wp_cassify_add_user_role_rule', 'click', function (evt) {
		var userRole = getSelectedValue(byId('wp_cassify_default_user_roles'));
		var userRoleRuleEl = byId('wp_cassify_user_role_rule');
		var userRoleRule = userRoleRuleEl ? userRoleRuleEl.value : '';
		var networkActivatedEl = byId('wp_cassify_network_activated');
		var networkActivated = networkActivatedEl ? networkActivatedEl.value : '';
		var userRoleRules = byId('wp_cassify_user_role_rules');

		if (networkActivated === 'enabled') {
			var blogId = getSelectedValue(byId('wp_cassify_user_role_blog_id'));
			appendOption(userRoleRules, userRole + '|' + blogId + '|' + userRoleRule);
		} else {
			appendOption(userRoleRules, userRole + '|' + userRoleRule);
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_remove_user_role_rule', 'click', function (evt) {
		removeSelectedOptions(byId('wp_cassify_user_role_rules'));

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_user_role_rules', 'dblclick', function (evt) {
		var userRoleRules = byId('wp_cassify_user_role_rules');
		var selected = getSelectedValue(userRoleRules);
		var networkActivatedEl = byId('wp_cassify_network_activated');
		var networkActivated = networkActivatedEl ? networkActivatedEl.value : '';

		if (networkActivated === 'enabled') {
			var userRoleRule = byId('wp_cassify_user_role_rule');
			var userRoleBlogId = byId('wp_cassify_user_role_blog_id');
			var defaultRoles = byId('wp_cassify_default_user_roles');

			if (userRoleRule) {
				userRoleRule.value = splitRule(selected, 2);
			}
			if (userRoleBlogId) {
				userRoleBlogId.value = splitRule(selected, 1);
			}
			if (defaultRoles) {
				defaultRoles.value = splitRule(selected, 0);
			}
		} else {
			var userRoleRuleSimple = byId('wp_cassify_user_role_rule');
			var defaultRolesSimple = byId('wp_cassify_default_user_roles');

			if (userRoleRuleSimple) {
				userRoleRuleSimple.value = splitRule(selected, 1);
			}
			if (defaultRolesSimple) {
				defaultRolesSimple.value = splitRule(selected, 0);
			}
		}

		removeSelectedOptions(userRoleRules);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	Attributes mapping functions
	 */
	on('wp_cassify_add_user_attribute_mapping', 'click', function (evt) {
		var userMetaList = byId('wp_cassify_wordpress_user_meta_list');
		var userMeta = getSelectedValue(userMetaList);
		var casUserAttributeEl = byId('wp_cassify_cas_user_attribute');
		var casUserAttribute = casUserAttributeEl ? casUserAttributeEl.value : '';
		var customMetaEl = byId('wp_cassify_custom_user_meta');

		if (userMetaList && userMetaList.value === 'custom_user_meta' && customMetaEl) {
			userMeta = customMetaEl.value;
		}

		appendOption(byId('wp_cassify_user_attributes_mapping_list'), userMeta + '|' + casUserAttribute);

		if (casUserAttributeEl) {
			casUserAttributeEl.value = '';
		}
		if (customMetaEl) {
			customMetaEl.value = '';
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_remove_user_attribute_mapping', 'click', function (evt) {
		removeSelectedOptions(byId('wp_cassify_user_attributes_mapping_list'));

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_user_attributes_mapping_list', 'dblclick', function (evt) {
		var mappingList = byId('wp_cassify_user_attributes_mapping_list');
		var selected = getSelectedValue(mappingList);
		var mappingUserMeta = splitRule(selected, 0);

		var metaList = byId('wp_cassify_wordpress_user_meta_list');
		var customMeta = byId('wp_cassify_custom_user_meta');
		if (metaList) {
			var exists = Array.from(metaList.options).some(function (option) {
				return option.value === mappingUserMeta;
			});

			if (!exists) {
				metaList.value = 'custom_user_meta';
				show(customMeta);
				if (customMeta) {
					customMeta.value = mappingUserMeta;
				}
			} else {
				metaList.value = mappingUserMeta;
				hide(customMeta);
			}
		}

		var casUserAttribute = byId('wp_cassify_cas_user_attribute');
		if (casUserAttribute) {
			casUserAttribute.value = splitRule(selected, 1);
		}

		removeSelectedOptions(mappingList);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_wordpress_user_meta_list', 'change', function (evt) {
		var metaList = byId('wp_cassify_wordpress_user_meta_list');
		if (metaList && metaList.value === 'custom_user_meta') {
			show(byId('wp_cassify_custom_user_meta'));
		} else {
			hide(byId('wp_cassify_custom_user_meta'));
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	Notifications parameters functions
	 */
	on('wp_cassify_notifications_smtp_auth', 'change', function (evt) {
		var target = evt.currentTarget;
		var encryption = byId('wp_cassify_notifications_encryption_type');
		if (encryption) {
			encryption.disabled = !target.checked;
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	Notifications rules functions
	 */
	on('wp_cassify_add_notification_rule', 'click', function (evt) {
		var notificationAction = getSelectedValue(byId('wp_cassify_notifications_actions'));
		var notificationRuleEl = byId('wp_cassify_notification_rule');
		var notificationRule = notificationRuleEl ? notificationRuleEl.value : '';

		appendOption(byId('wp_cassify_notification_rules'), notificationAction + '|' + notificationRule);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_remove_notification_rule', 'click', function (evt) {
		removeSelectedOptions(byId('wp_cassify_notification_rules'));

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_notification_rules', 'dblclick', function (evt) {
		var notificationRules = byId('wp_cassify_notification_rules');
		var selected = getSelectedValue(notificationRules);
		var notificationRule = byId('wp_cassify_notification_rule');
		var notificationActions = byId('wp_cassify_notifications_actions');

		if (notificationRule) {
			notificationRule.value = splitRule(selected, 1);
		}
		if (notificationActions) {
			notificationActions.value = splitRule(selected, 0);
		}

		removeSelectedOptions(notificationRules);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	Expirations rules functions
	 */
	on('wp_cassify_default_expirations_types', 'change', function (evt) {
		var expirationTypes = byId('wp_cassify_default_expirations_types');
		if (expirationTypes && expirationTypes.value === 'after_user_account_created_time_limit') {
			hide(byId('wp_cassify_fixed_datetime_limit'));
			show(byId('wp_cassify_after_user_account_created_time_limit'));
		} else {
			hide(byId('wp_cassify_after_user_account_created_time_limit'));
			show(byId('wp_cassify_fixed_datetime_limit'));
		}

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_add_expiration_rule', 'click', function (evt) {
		var expirationType = getSelectedValue(byId('wp_cassify_default_expirations_types'));
		var expirationRuleEl = byId('wp_cassify_expiration_rule');
		var expirationRule = expirationRuleEl ? expirationRuleEl.value : '';
		var expirationTypeValue;
		var expirationTypes = byId('wp_cassify_default_expirations_types');

		if (expirationTypes && expirationTypes.value === 'after_user_account_created_time_limit') {
			var afterCreatedLimit = byId('wp_cassify_after_user_account_created_time_limit');
			expirationTypeValue = afterCreatedLimit ? afterCreatedLimit.value : '';
		} else {
			hide(byId('wp_cassify_after_user_account_created_time_limit'));
			var fixedDatetimeLimit = byId('wp_cassify_fixed_datetime_limit');
			expirationTypeValue = fixedDatetimeLimit ? fixedDatetimeLimit.value : '';
		}

		appendOption(byId('wp_cassify_expiration_rules'), expirationType + '|' + expirationTypeValue + '|' + expirationRule);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_remove_expiration_rule', 'click', function (evt) {
		removeSelectedOptions(byId('wp_cassify_expiration_rules'));

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	on('wp_cassify_expiration_rules', 'dblclick', function (evt) {
		var expirationRules = byId('wp_cassify_expiration_rules');
		var selected = getSelectedValue(expirationRules);
		var expirationType = splitRule(selected, 0);
		var expirationTypeValue = splitRule(selected, 1);

		var expirationRule = byId('wp_cassify_expiration_rule');
		var expirationTypes = byId('wp_cassify_default_expirations_types');
		if (expirationRule) {
			expirationRule.value = splitRule(selected, 2);
		}
		if (expirationTypes) {
			expirationTypes.value = expirationType;
		}

		if (expirationType === 'after_user_account_created_time_limit') {
			hide(byId('wp_cassify_fixed_datetime_limit'));
			show(byId('wp_cassify_after_user_account_created_time_limit'));
			var afterCreatedLimit = byId('wp_cassify_after_user_account_created_time_limit');
			if (afterCreatedLimit) {
				afterCreatedLimit.value = expirationTypeValue;
			}
		} else {
			hide(byId('wp_cassify_after_user_account_created_time_limit'));
			show(byId('wp_cassify_fixed_datetime_limit'));
			var fixedDatetimeLimit = byId('wp_cassify_fixed_datetime_limit');
			if (fixedDatetimeLimit) {
				fixedDatetimeLimit.value = expirationTypeValue;
			}
		}

		removeSelectedOptions(expirationRules);

		evt.preventDefault();
		evt.stopPropagation();
		return false;
	});

	/*
	 *	Plugin options saving
	 */
	document.querySelectorAll('[data-style="wp_cassify_save_options"]').forEach(function (el) {
		el.addEventListener('click', function (evt) {
			document.querySelectorAll('#wp_cassify_autorization_rules option').forEach(function (option) {
				option.selected = true;
			});
			document.querySelectorAll('#wp_cassify_user_role_rules option').forEach(function (option) {
				option.selected = true;
			});
			document.querySelectorAll('#wp_cassify_user_attributes_mapping_list option').forEach(function (option) {
				option.selected = true;
			});
			document.querySelectorAll('#wp_cassify_notification_rules option').forEach(function (option) {
				option.selected = true;
			});
			document.querySelectorAll('#wp_cassify_expiration_rules option').forEach(function (option) {
				option.selected = true;
			});

			if (evt.currentTarget.id === 'wp_cassify_save_options_notifications_settings') {
				var salt = byId('wp_cassify_notifications_salt');
				if ([16, 24, 32].indexOf(salt ? salt.value.length : 0) === -1) {
					alert('Salt error : only keys of sizes 16, 24 or 32 supported');

					evt.preventDefault();
					evt.stopPropagation();
					return false;
				}

				var password = byId('wp_cassify_notifications_smtp_password');
				var confirmPassword = byId('wp_cassify_notifications_smtp_confirm_password');
				if ((password ? password.value : '') !== (confirmPassword ? confirmPassword.value : '')) {
					alert('SMTP Password does not macth confirmation !');

					evt.preventDefault();
					evt.stopPropagation();
					return false;
				}
			} else if (evt.currentTarget.id === 'wp_cassify_restore_plugin_options_settings') {
				var restoreFile = byId('wp_cassify_restore_plugin_options_configuration_settings_file');
				if (!restoreFile || restoreFile.value === '') {
					alert('Select configuration file to upload');

					evt.preventDefault();
					evt.stopPropagation();
					return false;
				}
			}

			return true;
		});
	});
});
