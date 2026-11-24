"""Les ISBN du catalogue (outils/catalogue/isbn.py)."""
import pytest

from isbn import cle_isbn10, cle_isbn13, normaliser


def test_cles_de_controle():
    assert cle_isbn13('978207036822') == '8'
    assert cle_isbn13('978030640615') == '7'
    assert cle_isbn10('030640615') == '2'
    assert cle_isbn10('080442957') == 'X'


@pytest.mark.parametrize('brut, attendu', [
    ('978-2-07-036822-8', '9782070368228'),
    ('ISBN 978 2 07 036822 8', '9782070368228'),
    ('ISBN-13 : 9782070368228', '9782070368228'),
    (9782070368228.0, '9782070368228'),  # un nombre lu dans un tableur
    ('0-306-40615-2', '9780306406157'),  # un ISBN-10 passe en ISBN-13
    ('0-8044-2957-X', '9780804429573'),  # sans buter sur son X
])
def test_isbn_valables(brut, attendu):
    assert normaliser(brut)[0] == attendu


@pytest.mark.parametrize('brut, raison', [
    ('978-2-07-036822-9', 'clé de contrôle fausse'),
    ('2-07-036822-1', 'ISBN-10 dont la clé est fausse'),
    ('1234-5678', 'ISSN'),
    ('épuisé', 'pas de numéro'),
    ('12345', 'ni 10 ni 13 chiffres'),
])
def test_isbn_refuses_avec_leur_raison(brut, raison):
    isbn, remarque = normaliser(brut)
    assert isbn is None
    assert raison in remarque


def test_cases_vides():
    assert normaliser(None) == (None, '')
    assert normaliser(' — ') == (None, '')


def test_plusieurs_numeros_le_premier_retenu():
    isbn, remarque = normaliser('9782070368228 / 9782070368235')
    assert isbn == '9782070368228'
    assert 'plusieurs numéros' in remarque


def test_ean_qui_n_est_pas_un_isbn_garde():
    isbn, remarque = normaliser('4006381333931')
    assert isbn == '4006381333931'
    assert 'EAN' in remarque
