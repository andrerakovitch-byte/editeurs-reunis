#!/bin/sh
# Refait les deux archives que WordPress installe (le thème et l'extension), dans apercu/.
# L'aperçu de WordPress Playground (blueprint.json) les télécharge depuis GitHub : relancer ce script et pousser
# après chaque changement du thème ou de l'extension.
set -e
cd "$(dirname "$0")/.."
rm -f apercu/editeurs-reunis.zip apercu/er-librairie.zip
(cd theme && zip -qr -X ../apercu/editeurs-reunis.zip editeurs-reunis)
(cd plugin && zip -qr -X ../apercu/er-librairie.zip er-librairie)
ls -l apercu
