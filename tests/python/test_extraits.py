"""Les pages à feuilleter (outils/extraits.py) : la langue d'une page, sa légende, les doubles pages à moitié vides."""

import pytest

import extraits


def test_langue_d_une_page():
    assert extraits.langue("Тогда я понял, что всё это было сном. " * 5) == "ru"
    assert extraits.langue("Quand la poésie prend-elle naissance en Russie ? " * 5) == "fr"
    assert extraits.langue("12") == ""


def test_numero_imprime():
    assert extraits.numero_imprime("34\nNam krzyż Chrystusów maszt jest") == 34
    assert extraits.numero_imprime("Sans numéro") == 0


def test_legende_tiree_de_l_intertitre():
    assert extraits.titre_de("7\nPROLOGUE\nSaint-Pétersbourg, 5 octobre 2011. Je dispose…") == "Prologue"
    assert extraits.titre_de("15\nPréface\n« Je revois ma lampe verte… »") == "Préface"
    # L'apostrophe de certaines polices sort en caractère de contrôle.
    assert extraits.titre_de("Note de l\x01éditeur\nQu’adviendra-t-il ?") == "Note de l’éditeur"


def test_double_page_a_moitie_vide():
    image = pytest.importorskip("PIL.Image")
    double = image.new("L", (200, 140), 255)
    double.paste(0, (120, 20, 180, 120))  # du texte sur la page de droite seulement
    seule = extraits.sans_moitie_blanche(double)
    assert seule.size == (100, 140)
    pleine = image.new("L", (200, 140), 255)
    pleine.paste(0, (20, 20, 80, 120))
    pleine.paste(0, (120, 20, 180, 120))
    assert extraits.sans_moitie_blanche(pleine).size == (200, 140)
