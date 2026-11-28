<?php
/**
 * L'essai des blancs du fond (28 nov. 2026, à l'essai) : quatre blancs très légèrement plus chauds que le blanc pur, à
 * comparer sur le site lui-même, avec les photos, les cadres des livres et les aplats.
 *
 * « ?blanc=vélin » (ou neige, craie, lait, pur) ouvre l'essai : le navigateur retient le blanc choisi d'une page à
 * l'autre, et un petit sélecteur, en bas à gauche, passe de l'un à l'autre. « × » ou « ?blanc=non » referme l'essai.
 * Rien ne change pour les autres visiteurs : sans essai, le fond reste le blanc pur de theme.json.
 *
 * Le blanc retenu s'applique à --wp--preset--color--base, sur la racine, avant le premier affichage (pas d'éclair
 * blanc). Les voiles translucides (la barre accrochée, le rond du menu, la recherche, les menus) suivent la même
 * variable par color-mix().
 *
 * @package editeurs-reunis
 */

/**
 * Les blancs essayés, du plus froid au plus chaud.
 *
 * @return array<string, array{0: string, 1: string}> La clé, puis le nom et la couleur.
 */
function er_essai_blancs() {
	return array(
		'pur'   => array( 'Pur', '#FFFFFF' ),
		'neige' => array( 'Neige', '#FFFEFB' ),
		'craie' => array( 'Craie', '#FCFBF8' ),
		'lait'  => array( 'Lait', '#FEFCF8' ),
		'velin' => array( 'Vélin', '#FFFCF4' ),
	);
}

add_action(
	'wp_head',
	function () {
		$blancs = array();
		foreach ( er_essai_blancs() as $cle => $b ) {
			$blancs[ $cle ] = array(
				'nom'     => $b[0],
				'couleur' => $b[1],
			);
		}
		?>
<script id="er-essai-blancs">
(function () {
	var B = <?php echo wp_json_encode( $blancs ); ?>, K = 'er-blanc', r = document.documentElement;
	function poser(c) {
		if (c && B[c] && 'pur' !== c) { r.style.setProperty('--wp--preset--color--base', B[c].couleur); } else { r.style.removeProperty('--wp--preset--color--base'); }
	}
	try {
		var q = new URLSearchParams(location.search).get('blanc');
		if (null !== q) {
			q = q.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
			if (B[q]) { localStorage.setItem(K, q); } else { localStorage.removeItem(K); }
		}
		var c = localStorage.getItem(K);
		if (!c || !B[c]) { return; }
		poser(c);
		document.addEventListener('DOMContentLoaded', function () {
			var s = document.createElement('div'), h = '';
			s.className = 'er-essai-blancs';
			s.setAttribute('role', 'group');
			s.setAttribute('aria-label', 'Essai des blancs du fond');
			s.style.cssText = 'position:fixed;left:16px;bottom:16px;z-index:99999;display:flex;align-items:center;gap:4px;padding:6px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.08),0 6px 18px rgba(0,0,0,.14);font:500 12px/1 Montserrat,system-ui,sans-serif;color:#1C1C1C';
			Object.keys(B).forEach(function (k) {
				h += '<button type="button" data-blanc="' + k + '" style="display:flex;align-items:center;gap:6px;height:30px;padding:0 10px 0 6px;border:0;border-radius:999px;background:transparent;font:inherit;color:inherit;cursor:pointer">'
					+ '<span style="width:18px;height:18px;border-radius:50%;background:' + B[k].couleur + ';box-shadow:inset 0 0 0 1px rgba(0,0,0,.18)"></span>' + B[k].nom + '</button>';
			});
			h += '<button type="button" data-fermer aria-label="Refermer l’essai" style="width:30px;height:30px;border:0;border-radius:50%;background:transparent;font:inherit;font-size:16px;color:#6B6B66;cursor:pointer">×</button>';
			s.innerHTML = h;
			function marquer(k) {
				s.querySelectorAll('[data-blanc]').forEach(function (b) {
					var oui = b.getAttribute('data-blanc') === k;
					b.style.background = oui ? '#EEECE6' : 'transparent';
					b.style.fontWeight = oui ? '700' : '500';
					b.setAttribute('aria-pressed', oui ? 'true' : 'false');
				});
			}
			s.addEventListener('click', function (e) {
				var b = e.target.closest('button');
				if (!b) { return; }
				if (b.hasAttribute('data-fermer')) { localStorage.removeItem(K); poser(null); s.remove(); return; }
				var k = b.getAttribute('data-blanc');
				localStorage.setItem(K, k);
				poser(k);
				marquer(k);
			});
			marquer(c);
			document.body.appendChild(s);
		});
	} catch (e) {}
})();
</script>
		<?php
	},
	1
);
