(function () {
	var root = document.getElementById('layrshift-connection-studio');
	var dataEl = document.getElementById('layrshift-studio-data');
	if (!root || !dataEl) {
		return;
	}

	var data;
	try {
		data = JSON.parse(dataEl.textContent || '{}');
	} catch (e) {
		return;
	}

	var configs = data.configs || {};
	var client = data.client || 'cursor';
	var mcpName = data.defaultName || 'layrshift';
	var defaultName = mcpName;
	var pasteTemplate = data.pasteTemplate || '';
	var namePlaceholder = data.namePlaceholder || '';
	var passwordSentinel = data.passwordSentinel || '';
	var passwordValue = data.passwordValue || '';
	var passwordIsPlaceholder = !!data.passwordIsPlaceholder;
	var copiedLabel = data.copiedLabel || 'Copied';
	var shellBadge = data.shellBadge || 'Shell';

	var agentPromptEl = document.getElementById('layrshift-agent-prompt');
	var snippetCodeEl = document.getElementById('layrshift-snippet-code');
	var clientSelect = document.getElementById('layrshift-client-select');
	var mcpNameInput = document.getElementById('layrshift-mcp-name');
	var pathChipsEl = document.getElementById('layrshift-path-chips');
	var pathsWrap = document.getElementById('layrshift-snippet-paths');
	var hintEl = document.getElementById('layrshift-snippet-hint');
	var formatBadge = document.getElementById('layrshift-format-badge');

	function flashButton(btn, label) {
		if (!btn) {
			return;
		}
		var orig = btn.textContent;
		btn.textContent = label;
		btn.classList.add('is-copied');
		setTimeout(function () {
			btn.textContent = orig;
			btn.classList.remove('is-copied');
		}, 1600);
	}

	function copyText(text, btn) {
		if (!text || !navigator.clipboard) {
			return;
		}
		navigator.clipboard.writeText(text).then(function () {
			flashButton(btn, copiedLabel);
		});
	}

	function renderAgentPrompt() {
		if (!agentPromptEl) {
			return;
		}
		var text = pasteTemplate.split(namePlaceholder).join(mcpName);
		agentPromptEl.textContent = '';
		var idx = text.indexOf(passwordSentinel);
		if (idx === -1) {
			agentPromptEl.appendChild(document.createTextNode(text));
			return;
		}
		agentPromptEl.appendChild(document.createTextNode(text.substring(0, idx)));
		if (passwordIsPlaceholder) {
			var span = document.createElement('span');
			span.className = 'layrshift-token';
			span.textContent = 'YOUR-APP-PASSWORD';
			agentPromptEl.appendChild(span);
		} else {
			agentPromptEl.appendChild(document.createTextNode(passwordValue));
		}
		agentPromptEl.appendChild(document.createTextNode(text.substring(idx + passwordSentinel.length)));
	}

	function detectFormat(cfg) {
		if (!cfg) {
			return 'JSON';
		}
		if (cfg.isShell) {
			return shellBadge;
		}
		var code = cfg.code || '';
		if (code.trim().startsWith('[')) {
			return 'TOML';
		}
		if (code.indexOf('context_servers') !== -1) {
			return 'Zed JSON';
		}
		if (code.indexOf('"servers"') !== -1) {
			return 'VS Code JSON';
		}
		return 'JSON';
	}

	function renderSnippet() {
		var cfg = configs[client];
		if (!cfg || !snippetCodeEl) {
			return;
		}

		var code = cfg.code.split(namePlaceholder).join(mcpName);
		snippetCodeEl.textContent = code;
		if (code.indexOf('YOUR-APP-PASSWORD') !== -1) {
			snippetCodeEl.innerHTML = snippetCodeEl.innerHTML.replace(
				/YOUR-APP-PASSWORD/g,
				'<span class="layrshift-token">YOUR-APP-PASSWORD</span>'
			);
		}

		if (formatBadge) {
			formatBadge.textContent = detectFormat(cfg);
		}

		if (hintEl) {
			hintEl.innerHTML = cfg.hint || '';
		}

		if (pathChipsEl && pathsWrap) {
			pathChipsEl.innerHTML = '';
			var keys = Object.keys(cfg.paths || {});
			if (keys.length === 0) {
				pathsWrap.hidden = true;
			} else {
				pathsWrap.hidden = false;
				keys.forEach(function (label) {
					var chip = document.createElement('span');
					chip.className = 'layrshift-chip';
					chip.title = cfg.paths[label];
					chip.textContent = label + ': ' + cfg.paths[label];
					pathChipsEl.appendChild(chip);
				});
			}
		}
	}

	function render() {
		renderAgentPrompt();
		renderSnippet();
	}

	root.querySelectorAll('[data-studio-mode]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var mode = btn.getAttribute('data-studio-mode');
			root.querySelectorAll('[data-studio-mode]').forEach(function (b) {
				var active = b === btn;
				b.classList.toggle('is-active', active);
				b.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			root.querySelectorAll('[data-studio-panel]').forEach(function (panel) {
				var show = panel.getAttribute('data-studio-panel') === mode;
				panel.classList.toggle('is-active', show);
				panel.hidden = !show;
			});
			if (mode === 'snippet') {
				renderSnippet();
			}
		});
	});

	if (clientSelect) {
		clientSelect.addEventListener('change', function () {
			client = clientSelect.value;
			renderSnippet();
		});
	}

	if (mcpNameInput) {
		mcpNameInput.addEventListener('input', function () {
			mcpName = mcpNameInput.value.trim() || defaultName;
			render();
		});
	}

	var copyAgentBtn = document.getElementById('layrshift-copy-agent-prompt');
	if (copyAgentBtn && agentPromptEl) {
		copyAgentBtn.addEventListener('click', function () {
			copyText(agentPromptEl.textContent, copyAgentBtn);
		});
	}

	var copySnippetBtn = document.getElementById('layrshift-copy-snippet');
	if (copySnippetBtn && snippetCodeEl) {
		copySnippetBtn.addEventListener('click', function () {
			copyText(snippetCodeEl.textContent, copySnippetBtn);
		});
	}

	render();
})();
