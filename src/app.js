import MicroModal from 'micromodal';

window.addEventListener('DOMContentLoaded', (ev) => {

	$ = jQuery.noConflict();

	for (const key in SitehubBlocks) {
		if (SitehubBlocks.hasOwnProperty(key)) {
			const block = SitehubBlocks[key];
			block.init();
		}
	}

	for (const key in SitehubModules) {
		if (SitehubModules.hasOwnProperty(key)) {
			const block = SitehubModules[key];
			block.init();
		}
	}

	if (undefined !== window.SHCustomBlocks && window.acf) {
		window.SHCustomBlocks.forEach((b) => {
			let camelCasedName =
				'PX' +
				b.name
					.split('-')
					.map((el) => el.charAt(0).toUpperCase() + el.slice(1))
					.join('');

			window.acf.addAction(`render_block_preview/type=${b.name}`, SitehubBlocks[camelCasedName].init);
		});
	}
});