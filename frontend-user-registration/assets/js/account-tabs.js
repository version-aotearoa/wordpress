(function () {
	var tabs = document.querySelectorAll('.feur-tab');
	var panels = document.querySelectorAll('.feur-panel');
	if (!tabs.length || !panels.length) {
		return;
	}
	function activate(name) {
		tabs.forEach(function (tab) {
			tab.classList.toggle('feur-tab-active', tab.getAttribute('data-tab') === name);
		});
		panels.forEach(function (panel) {
			panel.classList.toggle('feur-panel-active', panel.getAttribute('data-panel') === name);
		});
	}
	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			activate(tab.getAttribute('data-tab'));
		});
	});
})();
