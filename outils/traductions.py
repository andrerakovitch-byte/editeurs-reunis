#!/usr/bin/env python3
"""Les traductions du thème et de l'extension : anglais (en_GB) et russe (ru_RU).

Le français est la langue de référence : les chaînes du code sont en français, et ce fichier en donne l'anglais et
le russe. Pour mettre à jour après un changement de texte dans le thème :

    php wp-cli.phar i18n make-pot theme/editeurs-reunis theme/editeurs-reunis/languages/editeurs-reunis.pot \
        --domain=editeurs-reunis --exclude=languages --skip-theme-json
    php wp-cli.phar i18n make-pot plugin/er-librairie plugin/er-librairie/languages/er-librairie.pot \
        --domain=er-librairie --exclude=languages,donnees
    python3 outils/traductions.py

Le script écrit les .po, les .mo et les .l10n.php (plus rapides, depuis WordPress 6.5), nommés comme WordPress les
cherche (ru_RU.mo pour un thème, er-librairie-ru_RU.mo pour une extension), et liste les chaînes qui
n'ont pas encore de traduction. Les titres et descriptions des compositions (vus seulement dans l'éditeur) restent en
français.

L'anglais est prêt à servir. Le russe est un premier jet : il doit être relu par un russophone de la maison avant la
mise en ligne. Les noms suivent l'usage de chaque langue : Soljenitsyne, Solzhenitsyn, Солженицын.

Les textes de l'accueil sont encore dans le thème, donc ici. Les pages, les articles et les fiches des livres se
traduisent dans WordPress, avec Polylang (DeepL pour l'anglais).
"""

import os
import subprocess
import sys

import polib

DEPOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
WP_CLI = os.path.join(os.path.dirname(DEPOT), 'wp-cli.phar')

# (contexte, chaîne française) : (anglais, russe)
T = {
    # Le diaporama
    (None, 'carrousel'): ('carousel', 'карусель'),
    (None, 'diapositive'): ('slide', 'слайд'),
    (None, 'À la une'): ('Featured', 'Главное'),
    (None, '%1$s sur %2$s'): ('%1$s of %2$s', '%1$s из %2$s'),
    (None, 'Diapositive %s'): ('Slide %s', 'Слайд %s'),
    (None, 'Diapositive précédente'): ('Previous slide', 'Предыдущий слайд'),
    (None, 'Diapositive suivante'): ('Next slide', 'Следующий слайд'),

    # Les styles de blocs (l'éditeur)
    (None, 'Pilule blanche'): ('White pill', 'Белая кнопка-капсула'),
    (None, 'Cerné, avec une flèche'): ('Outlined, with an arrow', 'С обводкой и стрелкой'),
    (None, 'Carte voilée'): ('Veiled card', 'Полупрозрачная карточка'),
    (None, 'Ambiance papier (contenu patrimonial)'): ('Paper ambience (heritage content)', 'Бумажная гамма (архивные материалы)'),

    # Les groupes de sous-rayons
    (None, 'Par genre'): ('By genre', 'По жанрам'),
    (None, 'Par époque'): ('By era', 'По эпохам'),
    (None, 'Par période'): ('By period', 'По периодам'),
    (None, 'Par thème'): ('By theme', 'По темам'),
    (None, 'Par âge'): ('By age', 'По возрасту'),
    (None, 'Méthodes'): ('Coursebooks', 'Учебники'),
    (None, 'Outils'): ('Tools', 'Пособия'),
    (None, 'Pensée'): ('Thought', 'Философская мысль'),
    (None, 'Religion'): ('Religion', 'Религия'),
    (None, 'Arts'): ('Arts', 'Искусство'),

    # L'accueil : les anthologies
    (None, 'Nos meilleures ventes YMCA-Press'): ('YMCA-Press bestsellers', 'Бестселлеры YMCA-Press'),
    (None, 'Anthologies de la poésie russe'): ('Anthologies of Russian Poetry', 'Антологии русской поэзии'),
    (None, 'Bilingues russe-français, en quatre volumes'): ('Bilingual Russian–French, in four volumes', 'Двуязычные, русско-французские, в четырёх томах'),
    (None, 'Un panorama saisissant de la poésie russe à travers son histoire, de ses origines jusqu’à son expression la plus contemporaine.'):
        ('A striking panorama of Russian poetry through its history, from its origins to its most contemporary voices.',
         'Яркая панорама русской поэзии на протяжении всей её истории — от истоков до наших дней.'),
    (None, 'Le rayon poésie'): ('The poetry section', 'Раздел поэзии'),
    (None, 'Anthologie de la poésie russe du XVIIIᵉ siècle'): ('Anthology of eighteenth-century Russian poetry', 'Антология русской поэзии XVIII века'),
    (None, 'Anthologie de la poésie russe du XIXᵉ siècle'): ('Anthology of nineteenth-century Russian poetry', 'Антология русской поэзии XIX века'),
    (None, 'Anthologie de la poésie russe : la renaissance du XXᵉ siècle'): ('Anthology of Russian poetry: the twentieth-century renaissance', 'Антология русской поэзии: возрождение XX века'),
    (None, 'Anthologie de la poésie russe du début du XXIᵉ siècle'): ('Anthology of early twenty-first-century Russian poetry', 'Антология русской поэзии начала XXI века'),

    # L'accueil : les trois branches
    (None, 'Librairie des Éditeurs réunis'): ('Les Éditeurs réunis bookshop', 'Книжный магазин «Les Éditeurs réunis»'),
    (None, 'Au cœur du quartier latin depuis un demi-siècle, des livres neufs et d’occasion, en russe et en français.'):
        ('In the heart of the Latin Quarter for half a century: new and second-hand books, in Russian and in French.',
         'Уже полвека в самом сердце Латинского квартала: новые и букинистические книги на русском и французском языках.'),
    (None, 'Portrait de Pouchkine par Vassili Tropinine'): ('Portrait of Pushkin by Vasily Tropinin', 'Портрет Пушкина работы Василия Тропинина'),
    (None, 'La librairie en ligne'): ('The online bookshop', 'Интернет-магазин'),
    (None, 'Les livres en russe'): ('Books in Russian', 'Книги на русском языке'),
    (None, 'Nous trouver'): ('Find us', 'Как нас найти'),
    (None, 'Maison d’édition YMCA-Press'): ('YMCA-Press publishing house', 'Издательство YMCA-Press'),
    (None, 'Un siècle d’édition russe en exil, de Bounine à Soljenitsyne : l’histoire et le catalogue de la maison.'):
        ('A century of Russian publishing in exile, from Bunin to Solzhenitsyn: the history and catalogue of the house.',
         'Век русского книгоиздания в эмиграции, от Бунина до Солженицына: история и каталог издательства.'),
    (None, 'Photographie en noir et blanc : un groupe d’hommes en manteau dans une rue'):
        ('Black-and-white photograph: a group of men in overcoats in a street', 'Чёрно-белая фотография: группа мужчин в пальто на улице'),
    (None, 'Les publications'): ('Publications', 'Издания'),
    (None, 'Un siècle d’histoire'): ('A century of history', 'Век истории'),
    (None, 'Les Amis d’YMCA-Press'): ('Friends of YMCA-Press', 'Друзья YMCA-Press'),
    (None, 'Centre culturel Soljenitsyne'): ('Solzhenitsyn Cultural Centre', 'Культурный центр Солженицына'),
    (None, 'Rencontres, expositions, podcasts et vidéos autour de la littérature et de l’histoire russes.'):
        ('Talks, exhibitions, podcasts and videos on Russian literature and history.', 'Встречи, выставки, подкасты и видео о русской литературе и истории.'),
    (None, 'Portrait d’Anna Akhmatova par Nathan Altman'): ('Portrait of Anna Akhmatova by Nathan Altman', 'Портрет Анны Ахматовой работы Натана Альтмана'),
    (None, 'Nos événements'): ('Events', 'Мероприятия'),
    (None, 'Nos expositions'): ('Exhibitions', 'Выставки'),
    (None, 'Nos podcasts'): ('Podcasts', 'Подкасты'),
    (None, 'Nos vidéos'): ('Videos', 'Видео'),
    (None, 'Le journal du centre'): ('The Centre’s journal', 'Журнал центра'),
    (None, 'Qui nous sommes'): ('Who we are', 'Кто мы'),
    (None, 'Une entité, trois branches'): ('One house, three branches', 'Один дом, три направления'),
    (None, 'La librairie, la maison d’édition YMCA-Press et le Centre culturel Alexandre Soljenitsyne.'):
        ('The bookshop, the YMCA-Press publishing house and the Alexander Solzhenitsyn Cultural Centre.',
         'Книжный магазин, издательство YMCA-Press и Культурный центр Александра Солженицына.'),

    # L'accueil : le communiqué
    (None, 'Communiqué de presse'): ('Press release', 'Пресс-релиз'),
    (None, 'À propos de l’invasion de l’Ukraine par la Fédération de Russie'): ('On the invasion of Ukraine by the Russian Federation', 'О вторжении Российской Федерации в Украину'),
    (None, 'Signé par Mélanie Rakovitch, le 28&nbsp;février 2022.'): ('Signed by Mélanie Rakovitch on 28&nbsp;February 2022.', 'Подписано Мелани Ракович 28&nbsp;февраля 2022&nbsp;года.'),
    (None, 'Lire le communiqué'): ('Read the statement', 'Читать заявление'),

    # L'accueil : notre histoire
    (None, 'YMCA-Press'): ('YMCA-Press', 'YMCA-Press'),
    (None, 'Découvrez notre histoire centenaire'): ('Discover our hundred-year history', 'Наша столетняя история'),
    (None, 'L’histoire centenaire des éditions YMCA-Press, cœur vibrant de la littérature russe du XX<sup>e</sup>&nbsp;siècle, entre Bounine, Akhmatova, Mandelstam, Boulgakov et Soljenitsyne.'):
        ('The hundred-year history of YMCA-Press, a beating heart of twentieth-century Russian literature, from Bunin, Akhmatova and Mandelstam to Bulgakov and Solzhenitsyn.',
         'Столетняя история издательства YMCA-Press — живого сердца русской литературы XX&nbsp;века: Бунин, Ахматова, Мандельштам, Булгаков, Солженицын.'),
    ('Depuis 1921', 'Depuis'): ('Since', 'Год основания'),

    # L'accueil : les nouveautés, le livre du moment, les rayons
    (None, 'La librairie'): ('The bookshop', 'Книжный магазин'),
    (None, 'Nouveautés'): ('New arrivals', 'Новинки'),
    (None, 'Les derniers livres arrivés en rayon, en français et en russe.'): ('The latest books on our shelves, in French and in Russian.', 'Последние поступления на французском и русском языках.'),
    (None, 'Toute la librairie'): ('All books', 'Все книги'),
    (None, 'Le livre du moment'): ('Book of the moment', 'Наш выбор'),
    (None, 'Notes d’un médecin'): ('Notes d’un médecin', 'Записки врача'),
    (None, 'Vikenti Veressaïev, traduit du russe par Julie Bouvard, préface de Dimitri Bortnikov'):
        ('Vikenty Veresaev, translated into French by Julie Bouvard, with a preface by Dimitri Bortnikov',
         'Викентий Вересаев, во французском переводе Жюли Бувар, с предисловием Дмитрия Бортникова'),
    (None, 'Un jeune médecin raconte ses débuts à l’hôpital, ses doutes et ses erreurs. À sa parution, en 1901, le livre fit scandale ; il n’a rien perdu de sa franchise.'):
        ('A young doctor recounts his first years at the hospital, his doubts and his mistakes. The book caused a scandal when it came out in 1901; it has lost none of its candour.',
         'Молодой врач рассказывает о первых годах в больнице, о своих сомнениях и ошибках. В 1901 году книга вызвала скандал — и до сих пор не утратила своей откровенности.'),
    (None, 'Découvrir le livre'): ('Discover the book', 'О книге'),
    (None, 'Couverture de Notes d’un médecin, de Vikenti Veressaïev'): ('Cover of Notes d’un médecin by Vikenty Veresaev', 'Обложка французского издания «Записок врача» Викентия Вересаева'),
    (None, 'Les rayons'): ('Our sections', 'Разделы'),
    (None, 'Littérature classique et contemporaine, poésie, histoire, pensée et théologie orthodoxe, manuels et dictionnaires : en russe et en français, neufs et d’occasion.'):
        ('Classic and contemporary literature, poetry, history, philosophy and Orthodox theology, textbooks and dictionaries: in Russian and in French, new and second-hand.',
         'Классическая и современная литература, поэзия, история, философия и православное богословие, учебники и словари — на русском и французском, новые и букинистические.'),

    # L'accueil : le diaporama
    (None, 'Les secrets de l’Archipel du Goulag'): ('The secrets of The Gulag Archipelago', 'Тайны «Архипелага ГУЛАГ»'),
    (None, 'Un podcast inédit pour comprendre les mystères d’un livre hors du commun.'): ('A new podcast to unravel the mysteries of an extraordinary book.', 'Новый подкаст о загадках необыкновенной книги.'),
    (None, 'Écouter ici'): ('Listen here', 'Слушать'),
    (None, 'Pochette du podcast « Les secrets de L’Archipel du Goulag »'): ('Cover of the podcast “The secrets of The Gulag Archipelago”', 'Обложка подкаста «Тайны „Архипелага ГУЛАГ“»'),
    (None, 'Convoi pour Samarcande'): ('Convoi pour Samarcande', 'Эшелон на Самарканд'),
    (None, 'Le nouveau roman de Gouzel Iakhina, l’auteure de <em>Zouleikha ouvre les yeux</em>, est arrivé à la librairie.'):
        ('The new novel by Guzel Yakhina, author of <em>Zuleikha</em>, has arrived at the bookshop in French translation.',
         'Новый роман Гузель Яхиной, автора <em>«Зулейха открывает глаза»</em>, — теперь в нашем магазине во французском переводе.'),
    (None, 'Voir le livre'): ('See the book', 'О книге'),
    (None, 'Couverture de Convoi pour Samarcande, de Gouzel Iakhina'): ('Cover of Convoi pour Samarcande by Guzel Yakhina', 'Обложка французского издания романа Гузель Яхиной «Эшелон на Самарканд»'),
    (None, 'Nos meilleures ventes YMCA-Press : quatre volumes bilingues russe-français, du XVIII<sup>e</sup>&nbsp;siècle à nos jours.'):
        ('Our YMCA-Press bestsellers: four bilingual Russian–French volumes, from the eighteenth century to the present day.',
         'Бестселлеры YMCA-Press: четыре двуязычных русско-французских тома, от XVIII&nbsp;века до наших дней.'),
    (None, 'Les quatre volumes de l’Anthologie de la poésie russe'): ('The four volumes of the Anthology of Russian Poetry', 'Четыре тома «Антологии русской поэзии»'),

    # Le journal
    (None, 'Article précédent'): ('Previous article', 'Предыдущая статья'),
    (None, 'Article suivant'): ('Next article', 'Следующая статья'),
    ('pagination du journal', 'Plus récents'): ('Newer', 'Более новые'),
    ('pagination du journal', 'Plus anciens'): ('Older', 'Более ранние'),
    (None, 'Rien pour l’instant.'): ('Nothing here yet.', 'Пока ничего нет.'),
    (None, 'Chercher sur le site…'): ('Search the site…', 'Поиск по сайту…'),
    ('titre de la page du journal', 'Le journal du centre'): ('The Centre’s journal', 'Журнал центра'),

    # L'en-tête
    (None, 'Chercher dans la librairie'): ('Search the bookshop', 'Поиск по магазину'),
    (None, 'Rechercher un titre, un auteur…'): ('Search for a title or an author…', 'Название или автор…'),
    (None, 'Chercher'): ('Search', 'Найти'),
    # Le logo sur deux lignes : en anglais, le nom vient d'abord.
    ('logo, première ligne', 'Centre culturel'): ('Alexander Solzhenitsyn', 'Культурный центр'),
    ('logo, seconde ligne', 'Alexandre Soljenitsyne'): ('Cultural Centre', 'Александра Солженицына'),
    ('menu du haut', 'Librairie'): ('Bookshop', 'Магазин'),
    ('menu du haut', 'Maison d’édition YMCA-Press'): ('YMCA-Press publishers', 'Издательство YMCA-Press'),
    ('menu du haut', 'La maison d’édition'): ('The publishing house', 'Об издательстве'),
    ('menu du haut', 'Un siècle d’histoire'): ('A century of history', 'Век истории'),
    ('menu du haut', 'Le catalogue'): ('Catalogue', 'Каталог'),
    ('menu du haut', 'Les Amis d’YMCA-Press'): ('Friends of YMCA-Press', 'Друзья YMCA-Press'),
    ('menu du haut', 'Le centre culturel'): ('Cultural centre', 'Культурный центр'),
    ('menu du haut', 'Le centre'): ('About the centre', 'О центре'),
    ('menu du haut', 'Nos événements'): ('Events', 'Мероприятия'),
    ('menu du haut', 'Nos expositions'): ('Exhibitions', 'Выставки'),
    ('menu du haut', 'Nos podcasts'): ('Podcasts', 'Подкасты'),
    ('menu du haut', 'Nos vidéos'): ('Videos', 'Видео'),
    ('menu du haut', 'Le journal du centre'): ('The Centre’s journal', 'Журнал центра'),
    ('menu du haut', 'Nous trouver'): ('Find us', 'Как нас найти'),

    # Les menus des maisons, sous la barre noire (et dans le menu du téléphone)
    ('menu d’YMCA-Press', 'La maison d’édition'): ('The publishing house', 'Об издательстве'),
    ('menu d’YMCA-Press', 'Un siècle d’histoire'): ('A century of history', 'Век истории'),
    ('menu d’YMCA-Press', 'Le catalogue'): ('Catalogue', 'Каталог'),
    ('menu d’YMCA-Press', 'Les Amis d’YMCA-Press'): ('Friends of YMCA-Press', 'Друзья YMCA-Press'),
    ('menu du centre culturel', 'Le centre'): ('About the centre', 'О центре'),
    ('menu du centre culturel', 'Nos événements'): ('Events', 'Мероприятия'),
    ('menu du centre culturel', 'Nos expositions'): ('Exhibitions', 'Выставки'),
    ('menu du centre culturel', 'Nos podcasts'): ('Podcasts', 'Подкасты'),
    ('menu du centre culturel', 'Nos vidéos'): ('Videos', 'Видео'),
    ('menu du centre culturel', 'Le journal du centre'): ('The Centre’s journal', 'Журнал центра'),
    ('menu du centre culturel', 'Nous trouver'): ('Find us', 'Как нас найти'),
    (None, 'Mon compte'): ('My account', 'Личный кабинет'),

    # La fiche d'un livre
    ('fiche d’un livre', 'Rayon'): ('Section', 'Раздел'),
    ('fiche d’un livre', 'Langue'): ('Language', 'Язык'),
    ('fiche d’un livre', 'Auteur'): ('Author', 'Автор'),
    ('fiche d’un livre', 'Référence'): ('Reference', 'Артикул'),
    (None, 'Vous pouvez aussi le demander à la librairie, 11, rue de la Montagne-Sainte-Geneviève, du mardi au samedi.'):
        ('You can also ask for it at the bookshop, 11 rue de la Montagne-Sainte-Geneviève, Tuesday to Saturday.',
         'Книгу можно также спросить в магазине: 11, rue de la Montagne-Sainte-Geneviève, со вторника по субботу.'),
    (None, 'Du même rayon'): ('From the same section', 'Из того же раздела'),

    # La librairie
    ('pagination des livres', 'Précédents'): ('Previous', 'Назад'),
    ('pagination des livres', 'Suivants'): ('Next', 'Далее'),
    (None, 'Aucun livre ne correspond. <a href="%s">Voir toute la librairie</a>, ou écrivez-nous : nous avons bien plus de livres en rayon qu’en ligne.'):
        ('No books match. <a href="%s">Browse the whole bookshop</a>, or write to us: we have far more books on our shelves than online.',
         'Ничего не найдено. <a href="%s">Посмотрите весь каталог</a> или напишите нам: на полках у нас гораздо больше книг, чем на сайте.'),

    # Le menu de la librairie : les tuiles
    ('tuile du menu, surtitre', 'Classiques'): ('Classics', 'Классика'),
    ('tuile du menu, nom', 'Pouchkine'): ('Pushkin', 'Пушкин'),
    ('tuile du menu, surtitre', 'Les contemporains'): ('Contemporary writers', 'Современная проза'),
    ('tuile du menu, nom', 'Gouzel Iakhina'): ('Guzel Yakhina', 'Гузель Яхина'),
    ('tuile du menu, surtitre', 'Le livre du moment'): ('Book of the moment', 'Наш выбор'),
    ('tuile du menu, nom', 'Notes d’un médecin'): ('Notes d’un médecin', 'Записки врача'),
    ('tuile du menu, surtitre', 'L’émigration'): ('The emigration', 'Эмиграция'),
    ('tuile du menu, nom', 'Un siècle d’YMCA-Press'): ('A century of YMCA-Press', 'Век YMCA-Press'),
    ('tuile du menu, surtitre', 'Le Goulag'): ('The Gulag', 'ГУЛАГ'),
    ('tuile du menu, nom', 'L’Archipel, cinquante ans après'): ('The Archipelago, fifty years on', '«Архипелаг» пятьдесят лет спустя'),
    ('tuile du menu, surtitre', 'Philosophie'): ('Philosophy', 'Философия'),
    ('tuile du menu, nom', 'Berdiaev, Boulgakov, Florensky'): ('Berdyaev, Bulgakov, Florensky', 'Бердяев, Булгаков, Флоренский'),
    ('tuile du menu, surtitre', 'Spiritualité'): ('Spirituality', 'Духовность'),
    ('tuile du menu, nom', 'Théologie orthodoxe'): ('Orthodox theology', 'Православное богословие'),
    ('tuile du menu, surtitre', 'Débuter'): ('Getting started', 'Для начинающих'),
    ('tuile du menu, nom', 'Les manuels pour commencer'): ('Textbooks for beginners', 'Учебники для начинающих'),
    ('tuile du menu, surtitre', 'Les dictionnaires'): ('Dictionaries', 'Словари'),
    ('tuile du menu, nom', 'Français-russe, russe-français'): ('French–Russian, Russian–French', 'Французско-русские и русско-французские'),
    ('tuile du menu, surtitre', 'Cinéma'): ('Cinema', 'Кино'),
    ('tuile du menu, nom', 'Les scénarios de Zviaguintsev'): ('Zvyagintsev’s screenplays', 'Сценарии Звягинцева'),
    ('tuile du menu, surtitre', 'Musique'): ('Music', 'Музыка'),
    ('tuile du menu, nom', 'Moussorgski, par Calvocoressi'): ('Mussorgsky, by Calvocoressi', 'Мусоргский в книге Кальвокоресси'),
    ('tuile du menu, surtitre', 'Contes russes'): ('Russian fairy tales', 'Русские сказки'),
    ('tuile du menu, nom', 'Il était une fois'): ('Once upon a time', 'Жили-были'),
    ('tuile du menu, surtitre', 'Bilingue'): ('Bilingual', 'Двуязычные книги'),
    ('tuile du menu, nom', 'Lire en deux langues'): ('Reading in two languages', 'Читать на двух языках'),
    ('tuile du menu, surtitre', 'Très rare'): ('Very rare', 'Большая редкость'),
    ('tuile du menu, surtitre', 'Occasions'): ('Second-hand', 'Букинистика'),
    ('tuile du menu, nom', 'Des livres d’occasion, en russe et en français'): ('Second-hand books, in Russian and in French', 'Букинистические книги на русском и французском'),

    # Le menu de la librairie : les entrées et les tiroirs
    (None, 'Découvrir'): ('Discover', 'Подробнее'),
    (None, ' : %s'): (': %s', ': %s'),
    (None, 'À découvrir, %s'): ('Discover: %s', 'Рекомендуем: %s'),
    (None, 'Ouvrir %s'): ('Open %s', 'Открыть раздел «%s»'),
    ('menu de la librairie', 'Accueil'): ('Home', 'Главная'),
    (None, 'Tout le rayon %s'): ('All of %s', 'Весь раздел «%s»'),
    ('menu de la librairie', 'Nos meilleures ventes'): ('Bestsellers', 'Бестселлеры'),
    (None, 'Ce que nos lecteurs achètent le plus.'): ('What our readers buy most.', 'То, что наши читатели покупают чаще всего.'),
    (None, 'Toutes nos meilleures ventes'): ('All bestsellers', 'Все бестселлеры'),
    ('menu de la librairie', 'Nouveautés'): ('New arrivals', 'Новинки'),
    (None, 'Toutes les nouveautés'): ('All new arrivals', 'Все новинки'),
    ('menu de la librairie', 'Promos'): ('Offers', 'Скидки'),
    (None, 'Bonnes affaires'): ('Bargains', 'Выгодные предложения'),
    (None, 'Livres d’occasion'): ('Second-hand books', 'Букинистика'),
    (None, 'Fins de série'): ('End of line', 'Последние экземпляры'),
    (None, 'Livres soldés'): ('Books on sale', 'Книги со скидкой'),
    (None, 'Raretés'): ('Rarities', 'Редкости'),
    (None, 'Exemplaires rares'): ('Rare copies', 'Редкие экземпляры'),
    (None, 'Éditions anciennes'): ('Old editions', 'Старые издания'),
    (None, 'Toutes les promos'): ('All offers', 'Все скидки'),
    (None, 'Les Éditeurs réunis, librairie de référence depuis 1925'): ('Les Éditeurs réunis, a Paris bookshop since 1925', 'Книжный магазин «Les Éditeurs réunis» — с 1925 года'),
    (None, 'Les rayons de la librairie'): ('Bookshop sections', 'Разделы магазина'),

    # La page introuvable
    (None, 'Cette page n’existe pas'): ('This page does not exist', 'Такой страницы нет'),
    (None, 'Elle a peut-être changé d’adresse. Cherchez un livre, ou repartez de la librairie.'):
        ('It may have moved. Search for a book, or start again from the bookshop.', 'Возможно, у неё изменился адрес. Найдите книгу или вернитесь в магазин.'),
    (None, 'Chercher un titre, un auteur…'): ('Search for a title or an author…', 'Название или автор…'),

    # Le pied de page
    (None, 'Notre équipe est uniquement composée de bénévoles.'): ('Our team is made up entirely of volunteers.', 'Вся наша команда — волонтёры.'),
    (None, 'Rejoindre l’Association des Amis d’YMCA-Press'): ('Join the Friends of YMCA-Press association', 'Вступить в Общество друзей YMCA-Press'),
    ('titre du pied de page', 'La librairie'): ('The bookshop', 'Магазин'),
    (None, 'Du mardi au samedi, de 10&nbsp;h&nbsp;30 à 18&nbsp;h&nbsp;30'): ('Tuesday to Saturday, 10.30&nbsp;am to 6.30&nbsp;pm', 'Со вторника по субботу, с&nbsp;10:30 до&nbsp;18:30'),
    (None, 'Fermée le dimanche et le lundi'): ('Closed on Sundays and Mondays', 'Воскресенье и понедельник — выходные'),
    ('titre du pied de page', 'Informations'): ('Information', 'Информация'),
    (None, 'Conditions générales de vente'): ('Terms and conditions of sale', 'Условия продажи'),
    (None, 'Livraison et retours'): ('Delivery and returns', 'Доставка и возврат'),
    (None, 'Questions fréquentes'): ('Frequently asked questions', 'Частые вопросы'),
    (None, 'Politique de confidentialité'): ('Privacy policy', 'Политика конфиденциальности'),
    (None, 'Mentions légales'): ('Legal notice', 'Правовая информация'),
    ('titre du pied de page', 'Depuis un demi-siècle'): ('For half a century', 'Уже полвека'),
    (None, 'Au cœur du quartier latin, un vaste choix de livres neufs et d’occasion en russe et en français : la littérature russe classique et moderne, l’histoire et la civilisation russes, la pensée philosophique et la théologie orthodoxe, des manuels, des dictionnaires et des guides pour vos voyages.'):
        ('In the heart of the Latin Quarter, a wide choice of new and second-hand books in Russian and in French: classic and modern Russian literature, Russian history and civilisation, philosophy and Orthodox theology, textbooks, dictionaries and travel guides.',
         'В самом сердце Латинского квартала — большой выбор новых и букинистических книг на русском и французском языках: классическая и современная русская литература, история и культура России, философия и православное богословие, учебники, словари и путеводители.'),
    (None, 'Avec nos partenaires <strong>ACER-MJO</strong>, 91, rue Olivier-de-Serres, Paris&nbsp;15<sup>e</sup>, et la <strong>Fondation Michalski</strong>, Montricher (Suisse).'):
        ('With our partners <strong>ACER-MJO</strong>, 91 rue Olivier-de-Serres, Paris&nbsp;15<sup>e</sup>, and the <strong>Fondation Michalski</strong>, Montricher (Switzerland).',
         'Наши партнёры — <strong>ACER-MJO</strong> (91, rue Olivier-de-Serres, Paris&nbsp;15<sup>e</sup>) и <strong>Fondation Michalski</strong> (Montricher, Швейцария).'),

    # L'extension de la librairie
    (None, 'Langue du site'): ('Site language', 'Язык сайта'),
    (None, 'Auteurs'): ('Authors', 'Авторы'),
    (None, 'Auteur'): ('Author', 'Автор'),
    (None, 'Chercher un auteur'): ('Search authors', 'Найти автора'),
    (None, 'Tous les auteurs'): ('All authors', 'Все авторы'),
    (None, 'Modifier l’auteur'): ('Edit author', 'Изменить автора'),
    (None, 'Ajouter un auteur'): ('Add author', 'Добавить автора'),
    (None, 'Langues'): ('Languages', 'Языки'),
    (None, 'Langue'): ('Language', 'Язык'),
    (None, 'Toutes les langues'): ('All languages', 'Все языки'),
    (None, 'Modifier la langue'): ('Edit language', 'Изменить язык'),
    (None, 'Ajouter une langue'): ('Add language', 'Добавить язык'),
}

LANGUES = {
    'en_GB': {
        'colonne': 0,
        'Language-Team': 'English (UK)',
        'Plural-Forms': 'nplurals=2; plural=(n != 1);',
    },
    'ru_RU': {
        'colonne': 1,
        'Language-Team': 'Русский',
        'Plural-Forms': 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
    },
}

# Ce qui reste en français : vu seulement dans l'administration ou l'éditeur.
GARDE = {'Pattern title', 'Pattern description', 'Theme Name of the theme', 'Description of the theme',
         'Theme URI of the theme', 'Author of the theme', 'Author URI of the theme', 'Plugin Name of the plugin',
         'Description of the plugin'}


def garde(entree):
    return entree.msgctxt in GARDE or any(c.strip() in GARDE for c in entree.comment.splitlines())


def traduire(pot, dossier, domaine, prefixe):
    manquantes = []
    source = polib.pofile(pot)
    for locale, reglages in LANGUES.items():
        po = polib.POFile()
        po.metadata = {
            'Project-Id-Version': domaine,
            'Language': locale,
            'Language-Team': reglages['Language-Team'],
            'MIME-Version': '1.0',
            'Content-Type': 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding': '8bit',
            'Plural-Forms': reglages['Plural-Forms'],
            'X-Domain': domaine,
        }
        if locale == 'ru_RU':
            po.header = 'Premier jet, à faire relire par un russophone de la maison avant la mise en ligne.'
        for e in source:
            nouvelle = polib.POEntry(msgid=e.msgid, msgctxt=e.msgctxt, occurrences=e.occurrences, comment=e.comment)
            cle = (e.msgctxt, e.msgid)
            if cle in T:
                nouvelle.msgstr = T[cle][reglages['colonne']]
            elif not garde(e) and locale == 'en_GB':
                manquantes.append(cle)
            po.append(nouvelle)
        chemin = os.path.join(dossier, f'{prefixe}{locale}')
        po.save(chemin + '.po')
        po.save_as_mofile(chemin + '.mo')
    subprocess.run(['php', WP_CLI, '--allow-root', 'i18n', 'make-php', dossier, '--quiet'], check=True)
    return manquantes


if __name__ == '__main__':
    manquantes = traduire(os.path.join(DEPOT, 'theme/editeurs-reunis/languages/editeurs-reunis.pot'),
                          os.path.join(DEPOT, 'theme/editeurs-reunis/languages'), 'editeurs-reunis', '')
    manquantes += traduire(os.path.join(DEPOT, 'plugin/er-librairie/languages/er-librairie.pot'),
                           os.path.join(DEPOT, 'plugin/er-librairie/languages'), 'er-librairie', 'er-librairie-')
    for contexte, chaine in manquantes:
        print('Sans traduction :', repr(chaine), f'[{contexte}]' if contexte else '')
    print(f'{len(T)} chaînes traduites, {len(manquantes)} sans traduction.')
    sys.exit(1 if manquantes else 0)
