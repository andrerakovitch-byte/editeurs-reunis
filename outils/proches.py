"""Les livres proches par le sens : pour chaque livre, ses voisins les plus proches d'après ce qu'il raconte.

Lit les textes exportés par « wp er proches exporter » (une ligne JSON par livre : id, texte), les fait lire par un
modèle multilingue (BAAI/bge-m3 par défaut : français, russe, anglais), compare chaque livre à tous les autres
(cosinus) et écrit, pour chacun, ses k voisins les plus proches. Le site pose le résultat avec
« wp er proches importer » (plugin/er-librairie/inc/proches.php).

    python3 outils/proches.py livres.jsonl proches.json [--modele BAAI/bge-m3] [--k 30] [--rapport rapport.md]

Tourne dans le workflow « Déployer chez Kinsta » (entrée « proches ») : rien n'est écrit dans le dépôt.
"""

import argparse
import json
import statistics
import sys


def lire(chemin):
    """Les livres du fichier : [(id, texte)], les lignes illisibles ou vides écartées."""
    livres = []
    with open(chemin, encoding="utf-8") as f:
        for ligne in f:
            ligne = ligne.strip()
            if not ligne.startswith("{"):
                continue
            try:
                d = json.loads(ligne)
            except json.JSONDecodeError:
                continue
            if d.get("id") and str(d.get("texte", "")).strip():
                livres.append((int(d["id"]), str(d["texte"])))
    return livres


def voisins(vecteurs, ids, k):
    """Pour chaque livre, ses k plus proches voisins : {id: [[voisin, proximité], …]}, les vecteurs étant normés."""
    import numpy as np

    sim = vecteurs @ vecteurs.T
    np.fill_diagonal(sim, -1.0)
    sortie = {}
    for i, ident in enumerate(ids):
        ordre = np.argsort(-sim[i])[:k]
        sortie[str(ident)] = [[int(ids[j]), round(float(sim[i, j]), 3)] for j in ordre]
    return sortie


def centiles(valeurs, quantiles=(10, 50, 90)):
    """Quelques centiles d'une liste, arrondis."""
    if len(valeurs) < 2:
        return {q: round(valeurs[0], 3) if valeurs else None for q in quantiles}
    coupes = statistics.quantiles(valeurs, n=100)
    return {q: round(coupes[q - 1], 3) for q in quantiles}


def rapport(sortie, modele, n, chemin):
    """Ce qu'il faut pour régler le poids de la proximité : la distribution des proximités au 1er, 10e et dernier rang."""
    rangs = {"1er": 0, "10e": 9, "dernier": -1}
    lignes = [f"## Les livres proches ({modele})", "", f"{n} livres, {len(next(iter(sortie.values()), []))} voisins chacun.", ""]
    lignes += ["| rang | 10 % | médiane | 90 % |", "|---|---|---|---|"]
    for nom, r in rangs.items():
        vals = [v[r][1] for v in sortie.values() if len(v) > abs(r)]
        c = centiles(vals)
        lignes.append(f"| {nom} | {c[10]} | {c[50]} | {c[90]} |")
    with open(chemin, "w", encoding="utf-8") as f:
        f.write("\n".join(lignes) + "\n")


def main():
    p = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    p.add_argument("entree")
    p.add_argument("sortie")
    p.add_argument("--modele", default="BAAI/bge-m3")
    p.add_argument("--k", type=int, default=30)
    p.add_argument("--longueur", type=int, default=512, help="jetons lus par texte")
    p.add_argument("--rapport")
    a = p.parse_args()

    livres = lire(a.entree)
    if len(livres) < 2:
        sys.exit(f"Trop peu de livres dans {a.entree} : {len(livres)}")
    ids = [i for i, _ in livres]
    textes = [t for _, t in livres]

    from sentence_transformers import SentenceTransformer

    modele = SentenceTransformer(a.modele, device="cpu")
    modele.max_seq_length = a.longueur
    vecteurs = modele.encode(textes, batch_size=16, normalize_embeddings=True, show_progress_bar=True, convert_to_numpy=True)
    sortie = voisins(vecteurs, ids, min(a.k, len(ids) - 1))
    with open(a.sortie, "w", encoding="utf-8") as f:
        json.dump({"modele": a.modele, "k": a.k, "voisins": sortie}, f, ensure_ascii=False)
    if a.rapport:
        rapport(sortie, a.modele, len(ids), a.rapport)
    print(f"{len(ids)} livres, {a.k} voisins chacun → {a.sortie}")


if __name__ == "__main__":
    main()
