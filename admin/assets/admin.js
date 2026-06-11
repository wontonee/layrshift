(function () {
	var existingToggle = document.getElementById('layrshift-use-existing-toggle');
	var existingPanel = document.getElementById('layrshift-use-existing-field');
	if (existingToggle && existingPanel) {
		existingToggle.addEventListener('click', function () {
			var isHidden = existingPanel.hasAttribute('hidden');
			if (isHidden) {
				existingPanel.removeAttribute('hidden');
				existingToggle.setAttribute('aria-expanded', 'true');
			} else {
				existingPanel.setAttribute('hidden', 'hidden');
				existingToggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	document.querySelectorAll('.layrshift-copy-btn').forEach(function (button) {
		var defaultLabel = button.getAttribute('data-label') || button.textContent;

		button.addEventListener('click', function () {
			var selector = button.getAttribute('data-target');
			var target = document.querySelector(selector);
			if (!target) {
				return;
			}

			var text = target.value || target.textContent || target.innerText || '';

			function onCopied() {
				button.textContent = 'Copied!';
				button.classList.add('is-copied');
				setTimeout(function () {
					button.textContent = defaultLabel;
					button.classList.remove('is-copied');
				}, 1800);
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(onCopied).catch(function () {
					target.select();
					target.setSelectionRange(0, text.length);
					document.execCommand('copy');
					onCopied();
				});
				return;
			}

			target.select();
			target.setSelectionRange(0, text.length);
			try {
				document.execCommand('copy');
				onCopied();
			} catch (e) {
				// No-op.
			}
		});
	});
})();
