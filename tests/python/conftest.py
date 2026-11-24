"""Les outils Python ne sont pas un paquet : leurs dossiers vont dans le chemin d'import des tests."""
import pathlib
import sys

RACINE = pathlib.Path(__file__).resolve().parents[2]
for dossier in ('outils/catalogue', 'outils/wix'):
    sys.path.insert(0, str(RACINE / dossier))
