"""Les livres proches : la lecture des textes exportés et le choix des voisins (outils/proches.py)."""
import json

import pytest

import proches


def test_lire_ecarte_les_lignes_vides_et_le_bruit(tmp_path):
    f = tmp_path / "livres.jsonl"
    f.write_text(
        "Warning: quelque chose\n"
        + json.dumps({"id": 1, "texte": "Vie et Destin"}) + "\n"
        + json.dumps({"id": 2, "texte": "  "}) + "\n"
        + "{pas du json}\n"
        + json.dumps({"id": 3, "texte": "Récits de la Kolyma"}, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )
    assert proches.lire(f) == [(1, "Vie et Destin"), (3, "Récits de la Kolyma")]


def test_voisins_les_plus_proches_d_abord_sans_soi_meme():
    np = pytest.importorskip("numpy")
    v = np.array([[1.0, 0.0], [0.8, 0.6], [0.0, 1.0]])
    sortie = proches.voisins(v, [10, 20, 30], 2)
    assert [x[0] for x in sortie["10"]] == [20, 30]
    assert sortie["10"][0][1] == 0.8
    assert all(x[0] != 20 for x in sortie["20"])


def test_rapport(tmp_path):
    sortie = {"1": [[2, 0.9], [3, 0.5]], "2": [[1, 0.9], [3, 0.6]], "3": [[2, 0.6], [1, 0.5]]}
    f = tmp_path / "r.md"
    proches.rapport(sortie, "modèle", 3, f)
    texte = f.read_text(encoding="utf-8")
    assert "3 livres, 2 voisins chacun" in texte
    assert "| 1er |" in texte
