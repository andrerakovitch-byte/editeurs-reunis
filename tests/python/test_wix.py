"""La typographie des textes repris de Wix (outils/wix/evenements.py)."""
from evenements import typo


def test_apostrophe_courbe():
    assert typo("L'exil d'Ivan Bounine") == 'L’exil d’Ivan Bounine'


def test_guillemets_francais_avec_espaces_insecables():
    assert typo('Le roman "Ma Russie" paraît') == 'Le roman «\xa0Ma Russie\xa0» paraît'
    assert typo('«Ma Russie »') == '«\xa0Ma Russie\xa0»'


def test_espace_insecable_avant_la_ponctuation_haute():
    assert typo('Venez nombreux  !') == 'Venez nombreux\xa0!'
    assert typo('Entrée libre : réservation') == 'Entrée libre\xa0: réservation'


def test_a_majuscule_accentue_en_debut_de_phrase():
    assert typo("A l'occasion du centenaire.") == 'À l’occasion du centenaire.'
    assert typo('Rendez-vous à 18 h. A partir de demain.') == 'Rendez-vous à 18 h. À partir de demain.'
