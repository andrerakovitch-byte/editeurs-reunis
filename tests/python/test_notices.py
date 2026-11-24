"""Les notices des livres (outils/catalogue/notices.py) : les clés, les ISBN, la note d'une notice."""
from notices import cle, isbn13, note, ressemblance, titre_court, translit


def test_les_ecritures_d_un_nom_ont_la_meme_cle():
    assert cle('Dostoïevski') == cle('Dostoevsky') == cle('Достоевский')
    assert cle('Soljénitsyne') == cle('Солженицын') == cle('Solženicyn')
    assert cle('Tsvetaeva') == cle('Цветаева')


def test_translitteration_sans_signes():
    assert translit('Архипелаг ГУЛАГ') == 'Arhipelag GULAG'


def test_titre_court():
    assert titre_court('La Roue rouge, tome 2 : Novembre seize') == 'La Roue rouge'
    assert titre_court('Requiem (édition bilingue)') == 'Requiem'


def test_ressemblance_des_titres():
    assert ressemblance("L'Archipel du Goulag", 'L’archipel du goulag') == 1.0
    assert ressemblance('Les Douze', 'Les douze travaux d’Hercule') < 0.8


def test_isbn13():
    assert isbn13('0-306-40615-2') == '9780306406157'
    assert isbn13('ISBN 978-0-306-40615-7') == '9780306406157'
    assert isbn13('978-0-306-40615-7') == '9780306406157'
    assert isbn13('978-0-306-40615-8') == ''


def test_la_note_d_une_notice():
    livre = {'titre': 'Le Pavillon des cancéreux', 'auteurs': ['Alexandre Soljénitsyne'], 'editeur': 'Julliard',
             'annee': '1968'}
    bonne = {'titre': 'Le pavillon des cancéreux', 'auteurs': ['Soljénitsyne, Alexandre'], 'editeur': 'R. Julliard',
             'annee': '1968'}
    s = note(livre, bonne)
    assert s == {'titre': 1.0, 'auteur': 1.0, 'editeur': True, 'annee': True}
    autre = {'titre': 'Le pavillon des cancéreux', 'auteurs': ['Soljenitsyne'], 'editeur': 'Robert Laffont',
             'annee': '2012'}
    s = note(livre, autre)
    assert s['editeur'] is False and s['annee'] is False and s['auteur'] == 1.0
