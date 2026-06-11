(function (window, document) {
	'use strict';

	var config = window.layrshiftTemplateStudio || {};
	var STORAGE_KEY = 'layrshiftStudioState';

	var state = {
		editor: 'gutenberg',
		title: '',
		content: '',
		editUrl: '',
		configured: !!config.configured,
	};

	function $(id) {
		return document.getElementById(id);
	}

	function loadStoredState() {
		try {
			var raw = window.sessionStorage.getItem(STORAGE_KEY);
			if (!raw) {
				return;
			}
			var stored = JSON.parse(raw);
			if (stored.prompt && $('layrshift-template-prompt')) {
				$('layrshift-template-prompt').value = stored.prompt;
			}
			if (stored.title && $('layrshift-template-title')) {
				$('layrshift-template-title').value = stored.title;
			}
			if (stored.preview && $('layrshift-template-preview')) {
				$('layrshift-template-preview').value = stored.preview;
				state.content = stored.preview;
			}
			if (stored.editor) {
				state.editor = stored.editor;
			}
			if (stored.editUrl) {
				state.editUrl = stored.editUrl;
				var openEditor = $('layrshift-open-editor');
				if (openEditor && state.editUrl) {
					openEditor.href = state.editUrl;
					openEditor.style.display = 'inline-block';
				}
			}
			if ($('layrshift-create-draft')) {
				$('layrshift-create-draft').disabled = !(stored.preview || '').trim();
			}
		} catch (e) {
			// Ignore storage errors.
		}
	}

	function saveStoredState() {
		try {
			var promptEl = $('layrshift-template-prompt');
			var titleEl = $('layrshift-template-title');
			var previewEl = $('layrshift-template-preview');
			window.sessionStorage.setItem(
				STORAGE_KEY,
				JSON.stringify({
					prompt: promptEl ? promptEl.value : '',
					title: titleEl ? titleEl.value : '',
					preview: previewEl ? previewEl.value : state.content,
					editor: state.editor,
					editUrl: state.editUrl,
				})
			);
		} catch (e) {
			// Ignore storage errors.
		}
	}

	function goToTab(tab) {
		window.location.href = (config.appUrl || '') + '&tab=' + tab;
	}

	function setStatus(message, type, targetId) {
		var el = $(targetId || 'layrshift-studio-status');
		if (!el) {
			return;
		}
		el.textContent = message || '';
		el.className = 'layrshift-status' + (type ? ' is-' + type : '');
	}

	function setBusy(isBusy) {
		var generateBtn = $('layrshift-generate-template');
		var createBtn = $('layrshift-create-draft');
		var saveBtn = $('layrshift-save-studio-settings');
		var spinner = document.querySelector('.layrshift-studio-spinner');
		var settingsSpinner = document.querySelector('.layrshift-settings-spinner');

		if (generateBtn) {
			generateBtn.disabled = isBusy || !state.configured;
		}
		if (createBtn) {
			createBtn.disabled = isBusy || !state.content;
		}
		if (saveBtn) {
			saveBtn.disabled = isBusy;
		}
		if (spinner) {
			spinner.classList.toggle('is-active', isBusy);
		}
		if (settingsSpinner) {
			settingsSpinner.classList.toggle('is-active', isBusy);
		}
	}

	function apiRequest(path, method, body) {
		return fetch(config.restUrl + path, {
			method: method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce,
			},
			body: body ? JSON.stringify(body) : undefined,
			credentials: 'same-origin',
		}).then(function (response) {
			return response.json().then(function (data) {
				if (!response.ok) {
					var message = (data && data.message) || config.i18n.errorGeneric;
					throw new Error(message);
				}
				return data;
			});
		});
	}

	function onSaveSettings() {
		var enabledEl = $('layrshift-pro-enabled');
		var apiKeyEl = $('layrshift-studio-api-key');
		var modelEl = $('layrshift-studio-model');
		var editorEl = $('layrshift-studio-default-editor');

		setBusy(true);
		setStatus(config.i18n.savingSettings, 'info', 'layrshift-settings-status');

		apiRequest('/templates/settings', 'PATCH', {
			enabled: enabledEl ? enabledEl.checked : false,
			gemini_api_key: apiKeyEl ? apiKeyEl.value : '',
			gemini_model: modelEl ? modelEl.value : config.model,
			default_editor: editorEl ? editorEl.value : 'auto',
		})
			.then(function (data) {
				state.configured = !!data.configured;
				if (apiKeyEl && apiKeyEl.value) {
					apiKeyEl.value = '';
					apiKeyEl.placeholder = '••••••••••••••••';
				}
				if ($('layrshift-generate-template')) {
					$('layrshift-generate-template').disabled = !state.configured;
				}
				setStatus(config.i18n.settingsSaved, 'success', 'layrshift-settings-status');
				setBusy(false);
			})
			.catch(function (error) {
				setStatus(error.message || config.i18n.errorGeneric, 'error', 'layrshift-settings-status');
				setBusy(false);
			});
	}

	function onGenerate() {
		if (!state.configured) {
			setStatus(config.i18n.apiKeyRequired, 'error');
			return;
		}

		var prompt = ($('layrshift-template-prompt') || {}).value || '';
		var title = ($('layrshift-template-title') || {}).value || '';
		var editor = ($('layrshift-template-editor') || {}).value || 'auto';

		if (!prompt.trim()) {
			setStatus(config.i18n.promptRequired, 'error');
			return;
		}

		setBusy(true);
		setStatus(config.i18n.generating, 'info');

		apiRequest('/templates/generate', 'POST', {
			prompt: prompt,
			title: title,
			editor: editor,
		})
			.then(function (data) {
				state.editor = data.editor;
				state.title = data.title;
				state.content = data.content;
				state.editUrl = '';

				var preview = $('layrshift-template-preview');
				if (preview) {
					preview.value = data.content;
				}

				var titleField = $('layrshift-template-title');
				if (titleField && !titleField.value.trim()) {
					titleField.value = data.title || '';
				}

				var openEditor = $('layrshift-open-editor');
				if (openEditor) {
					openEditor.style.display = 'none';
				}

				saveStoredState();
				goToTab('preview');
			})
			.catch(function (error) {
				setStatus(error.message || config.i18n.errorGeneric, 'error');
				setBusy(false);
			});
	}

	function onCreateDraft() {
		var preview = $('layrshift-template-preview');
		var titleField = $('layrshift-template-title');
		var content = preview ? preview.value : state.content;
		var title = titleField ? titleField.value.trim() : state.title;

		if (!content.trim()) {
			setStatus(config.i18n.previewRequired, 'error', 'layrshift-preview-status');
			return;
		}

		if (!title) {
			title = state.title || config.i18n.defaultTitle;
		}

		setBusy(true);
		setStatus(config.i18n.creating, 'info', 'layrshift-preview-status');

		apiRequest('/templates/create', 'POST', {
			title: title,
			content: content,
			editor: state.editor || 'gutenberg',
		})
			.then(function (data) {
				state.editUrl = data.edit_url || '';
				state.content = content;
				var openEditor = $('layrshift-open-editor');
				if (openEditor && state.editUrl) {
					openEditor.href = state.editUrl;
					openEditor.style.display = 'inline-block';
				}
				saveStoredState();
				setStatus(config.i18n.created, 'success', 'layrshift-preview-status');
				setBusy(false);
			})
			.catch(function (error) {
				setStatus(error.message || config.i18n.errorGeneric, 'error', 'layrshift-preview-status');
				setBusy(false);
			});
	}

	function bindPersistence() {
		['layrshift-template-prompt', 'layrshift-template-title', 'layrshift-template-preview'].forEach(function (id) {
			var el = $(id);
			if (!el) {
				return;
			}
			el.addEventListener('input', function () {
				if (id === 'layrshift-template-preview') {
					state.content = el.value;
					if ($('layrshift-create-draft')) {
						$('layrshift-create-draft').disabled = !state.content.trim();
					}
				}
				saveStoredState();
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		loadStoredState();
		bindPersistence();

		var generateBtn = $('layrshift-generate-template');
		var createBtn = $('layrshift-create-draft');
		var saveBtn = $('layrshift-save-studio-settings');

		if (generateBtn) {
			generateBtn.addEventListener('click', onGenerate);
		}
		if (createBtn) {
			createBtn.addEventListener('click', onCreateDraft);
		}
		if (saveBtn) {
			saveBtn.addEventListener('click', onSaveSettings);
		}
	});
})(window, document);
