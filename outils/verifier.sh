#!/usr/bin/env bash
# Vérifie le code avant qu'il parte en ligne : le même contrôle sur un poste et dans GitHub (.github/workflows/verifier.yml),
# qui l'exige avant chaque déploiement chez Kinsta.
#
#   bash outils/verifier.sh
#
# 1. La syntaxe : chaque fichier PHP (php -l) et JavaScript (node --check).
# 2. Les tests : tests/php/lancer.php, puis pytest sur tests/python.
# 3. Le style et les règles de sécurité de WordPress : PHPCS (phpcs.xml.dist), s'il est installé (composer install) ;
#    ruff sur les outils Python (ruff.toml).
# S'arrête à la première étape qui échoue.
set -euo pipefail
cd "$(dirname "$0")/.."

echo '— La syntaxe PHP'
if ! sortie=$(find theme plugin tests -name '*.php' -not -path '*/languages/*' -print0 | xargs -0 -n1 -P4 php -l 2>&1); then
	echo "$sortie" | grep -v '^No syntax errors'
	exit 1
fi
echo '  ok'

echo '— La syntaxe JavaScript'
for f in theme/editeurs-reunis/assets/js/*.js plugin/er-librairie/assets/js/*.js; do
	node --check "$f"
done
echo '  ok'

echo '— Les tests PHP'
php tests/php/lancer.php

echo '— Les outils Python'
ruff check outils tests/python
python3 -m pytest -q tests/python

if [ -x vendor/bin/phpcs ]; then
	echo '— Le style et la sécurité (PHPCS)'
	vendor/bin/phpcs
else
	echo '— PHPCS absent (composer install pour l’avoir) : passé'
fi
