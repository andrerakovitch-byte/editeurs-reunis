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

    # La recherche : le panneau, les pistes, les résultats
    (None, 'Soljenitsyne'): ('Solzhenitsyn', 'Солженицын'),
    (None, 'L’Archipel du Goulag'): ('The Gulag Archipelago', 'Архипелаг ГУЛАГ'),
    (None, 'Akhmatova'): ('Akhmatova', 'Ахматова'),
    (None, 'Gouzel Iakhina'): ('Guzel Yakhina', 'Гузель Яхина'),
    (None, 'Poésie'): ('Poetry', 'Поэзия'),
    (None, 'Dictionnaire'): ('Dictionary', 'Словарь'),
    (None, 'Neuf'): ('New', 'Новые'),
    (None, 'Occasion'): ('Second-hand', 'Букинистика'),
    (None, 'Édition ancienne'): ('Old edition', 'Старое издание'),
    (None, 'Très rare'): ('Very rare', 'Большая редкость'),
    (None, 'de'): ('from', 'от'),
    (None, 'à'): ('to', 'до'),
    (None, 'Rechercher'): ('Search', 'Поиск'),
    (None, 'Chercher un titre, un auteur, un sujet'): ('Search for a title, an author, a subject', 'Найти название, автора, тему'),
    (None, 'Un titre, un auteur, un sujet…'): ('A title, an author, a subject…', 'Название, автор, тема…'),
    (None, 'Effacer'): ('Clear', 'Стереть'),
    (None, 'Compte'): ('Account', 'Кабинет'),
    (None, 'Panier'): ('Basket', 'Корзина'),
    (None, 'Partout'): ('Everywhere', 'Везде'),
    (None, 'Le centre culturel'): ('The cultural centre', 'Культурный центр'),
    (None, 'Filtrer'): ('Filter', 'Фильтры'),
    (None, 'Où chercher'): ('Where to look', 'Где искать'),
    (None, 'Aucun livre ne répond à ces filtres. Retirez-en un, ou essayez la recherche détaillée.'): ('No book matches these filters. Remove one, or try the detailed search.', 'Нет книг, отвечающих этим фильтрам. Снимите один из них или воспользуйтесь расширенным поиском.'),
    (None, 'Rien pour « %s » avec ces filtres. Retirez-en un, ou essayez un autre mot.'): ('Nothing for “%s” with these filters. Remove one, or try another word.', 'По запросу «%s» с этими фильтрами ничего нет. Снимите один из них или попробуйте другое слово.'),
    (None, 'Un titre, un auteur, un rayon…'): ('A title, an author, a section…', 'Название, автор, раздел…'),
    (None, 'Dans le catalogue d’YMCA-Press…'): ('In the YMCA-Press catalogue…', 'В каталоге YMCA-Press…'),
    (None, 'Dans les événements et le journal du centre…'): ('In the centre’s events and journal…', 'В событиях и журнале центра…'),
    (None, 'Recherche détaillée'): ('Advanced search', 'Расширенный поиск'),
    (None, 'Fermer'): ('Close', 'Закрыть'),
    (None, 'Vos dernières recherches'): ('Your recent searches', 'Вы недавно искали'),
    (None, 'On cherche souvent'): ('Often searched', 'Часто ищут'),
    (None, 'Par rayon'): ('By section', 'По разделам'),
    (None, 'Par langue'): ('By language', 'По языку'),
    (None, 'Ailleurs sur le site'): ('Elsewhere on the site', 'Ещё на сайте'),
    (None, 'Les nouveautés de la librairie'): ('New arrivals at the bookshop', 'Новинки магазина'),
    (None, 'Le catalogue d’YMCA-Press, depuis 1921'): ('The YMCA-Press catalogue, since 1921', 'Каталог YMCA-Press с 1921 года'),
    (None, 'Les événements du centre, les podcasts, les vidéos'): ('The Centre’s events, podcasts and videos', 'Мероприятия центра, подкасты, видео'),
    (None, 'Les derniers arrivés'): ('Just in', 'Последние поступления'),
    (None, 'Titre'): ('Title', 'Название'),
    (None, 'Un mot du titre'): ('A word from the title', 'Слово из названия'),
    (None, 'Dans n’importe quelle écriture'): ('In any spelling or script', 'В любом написании'),
    (None, 'Traducteur, préfacier'): ('Translator, preface writer', 'Переводчик, автор предисловия'),
    (None, 'Par exemple Georges Nivat'): ('For example Georges Nivat', 'Например, Жорж Нива'),
    (None, 'ISBN'): ('ISBN', 'ISBN'),
    (None, 'Rayon'): ('Section', 'Раздел'),
    (None, 'Tous les rayons'): ('All sections', 'Все разделы'),
    (None, 'Éditeur'): ('Publisher', 'Издательство'),
    (None, 'Par exemple YMCA-Press'): ('For example YMCA-Press', 'Например, YMCA-Press'),
    (None, 'Collection'): ('Series', 'Серия'),
    (None, 'Toutes'): ('All', 'Все'),
    (None, 'Année d’édition'): ('Year of publication', 'Год издания'),
    (None, 'Nombre de pages'): ('Number of pages', 'Число страниц'),
    (None, 'Mentions'): ('Labels', 'Пометки'),
    # Les couvertures (Produits → Couvertures)
    (None, 'Trop petites'): ('Too small', 'Слишком маленькие'),
    (None, 'Absentes'): ('Missing', 'Отсутствуют'),
    (None, 'Passables'): ('Acceptable', 'Сносные'),
    (None, 'Bonnes'): ('Good', 'Хорошие'),
    (None, 'Les couvertures'): ('Covers', 'Обложки'),
    (None, 'Couvertures'): ('Covers', 'Обложки'),
    (None, 'Une couverture est bonne à partir de 800 pixels de haut : nette même en grand sur la fiche. Passable, de 450 à 800 pixels : nette dans les listes, montrée à sa taille sur la fiche. En dessous de 450 pixels, le site la remplace par une couverture typographique (le titre et l’auteur), jusqu’à ce qu’une meilleure image arrive. Les livres en stock viennent en tête.'):
        ('A cover is good from 800 pixels high: sharp even large on the book page. Acceptable, from 450 to 800 pixels: sharp in lists, shown at its own size on the book page. Below 450 pixels, the site replaces it with a typographic cover (title and author) until a better image arrives. Books in stock come first.',
         'Обложка хорошая от 800 пикселей в высоту: чёткая даже крупно на странице книги. Сносная — от 450 до 800 пикселей: чёткая в списках, на странице книги показана в своём размере. Меньше 450 пикселей сайт заменяет типографской обложкой (название и автор), пока не появится изображение лучше. Книги в наличии идут первыми.'),
    (None, 'Livre'): ('Book', 'Книга'),
    (None, 'Taille de l’image'): ('Image size', 'Размер изображения'),
    (None, 'Stock'): ('Stock', 'Наличие'),
    (None, 'En stock'): ('In stock', 'В наличии'),
    (None, 'Épuisé'): ('Out of stock', 'Нет в наличии'),
    (None, 'Changer la couverture'): ('Change the cover', 'Сменить обложку'),
    (None, 'Aucun livre.'): ('No books.', 'Книг нет.'),
    (None, 'Slug'): ('Slug', 'Слаг'),
    (None, 'Format (poche, broché, relié…)'): ('Format (paperback, hardback…)', 'Формат (карманный, в обложке, в переплёте…)'),
    (None, 'Prix'): ('Price', 'Цена'),
    (None, 'État'): ('Condition', 'Состояние'),
    (None, 'Disponibilité'): ('Availability', 'Наличие'),
    (None, 'En stock seulement'): ('In stock only', 'Только в наличии'),
    (None, 'Tout effacer'): ('Clear all', 'Сбросить всё'),
    (None, 'Voir les livres'): ('See the books', 'Показать книги'),
    (None, 'Les champs se combinent, on peut n’en remplir qu’un ; le nombre de livres change à chaque choix.'):
        ('The fields combine, and you can fill in just one; the number of books updates with every choice.',
         'Поля можно сочетать, а можно заполнить только одно; число книг меняется при каждом выборе.'),
    (None, 'On cherche dans les trois langues : <strong>Dostoïevski</strong>, <strong lang="ru">Достоевский</strong> ou <strong>Dostoevsky</strong> mènent au même auteur.'):
        ('Search in any of the three languages: <strong>Dostoevsky</strong>, <strong lang="ru">Достоевский</strong> or <strong lang="fr">Dostoïevski</strong> lead to the same author.',
         'Искать можно на трёх языках: <strong>Достоевский</strong>, <strong lang="fr">Dostoïevski</strong> или <strong lang="en">Dostoevsky</strong> приведут к одному автору.'),
    (None, 'choisir'): ('choose', 'выбрать'),
    (None, 'Entrée'): ('Enter', 'Enter'),
    (None, 'ouvrir'): ('open', 'открыть'),
    (None, 'Échap'): ('Esc', 'Esc'),
    (None, 'fermer'): ('close', 'закрыть'),
    (None, 'Tout'): ('All', 'Всё'),
    (None, 'Livres'): ('Books', 'Книги'),
    (None, 'Rayons'): ('Sections', 'Разделы'),
    (None, 'Encyclopédie'): ('Encyclopaedia', 'Энциклопедия'),
    (None, 'Centre culturel'): ('Cultural centre', 'Культурный центр'),
    (None, 'Journal'): ('Journal', 'Журнал'),
    (None, 'Pages'): ('Pages', 'Страницы'),
    (None, 'Dans l’encyclopédie'): ('In the encyclopaedia', 'В энциклопедии'),
    (None, 'Chez YMCA-Press'): ('At YMCA-Press', 'В YMCA-Press'),
    (None, 'Au centre culturel'): ('At the cultural centre', 'В культурном центре'),
    (None, 'Dans le journal'): ('In the journal', 'В журнале'),
    (None, 'Résultats pour %s.'): ('Results for %s.', 'Результаты для: %s.'),
    (None, 'Cherché aussi : %s.'): ('Also searched: %s.', 'Также искали: %s.'),
    (None, 'Rien pour « %s ». Essayez un autre mot, une autre écriture, ou la recherche détaillée.'):
        ('Nothing for “%s”. Try another word, another spelling, or the advanced search.', 'По запросу «%s» ничего не найдено. Попробуйте другое слово, другое написание или расширенный поиск.'),
    (None, 'Voir les %s livres'): ('See the %s books', 'Показать книги: %s'),
    (None, 'Aucun livre'): ('No books', 'Нет книг'),
    (None, 'Les %s livres'): ('All %s books', 'Все книги: %s'),
    (None, 'La page de l’auteur'): ('The author’s page', 'Страница автора'),
    (None, '%s livres en rayon'): ('%s books on our shelves', 'Книг на полках: %s'),
    (None, 'Un livre en rayon'): ('One book on our shelves', 'Одна книга на полках'),
    (None, 'en stock'): ('in stock', 'в наличии'),
    (None, 'épuisé pour l’instant'): ('out of stock for now', 'пока нет в наличии'),
    (None, 'Les résultats changent à chaque lettre ; Entrée ouvre la page des livres trouvés.'):
        ('Results update with every letter; Enter opens the page of books found.', 'Результаты обновляются с каждой буквой; Enter открывает страницу найденных книг.'),
    (None, 'Vos critères :'): ('Your criteria:', 'Ваши условия:'),
    (None, 'Retirer : %s'): ('Remove: %s', 'Убрать: %s'),
    (None, 'Autres graphies'): ('Other spellings', 'Другие написания'),
    (None, 'Une par ligne : le nom en russe, en anglais, une transcription ancienne. La recherche les trouve toutes.'):
        ('One per line: the name in Russian, in English, an older transliteration. The search finds them all.', 'По одному в строке: имя по-русски, по-английски, старая транскрипция. Поиск найдёт любое.'),

    # La fiche d'un livre
    ('fiche d’un livre', 'Rayon'): ('Section', 'Раздел'),
    ('fiche d’un livre', 'Langue'): ('Language', 'Язык'),
    ('fiche d’un livre', 'Auteur'): ('Author', 'Автор'),
    ('fiche d’un livre', 'Référence'): ('Reference', 'Артикул'),
    ('fiche d’un livre', 'Traduction'): ('Translation', 'Перевод'),
    ('fiche d’un livre', 'Éditeur'): ('Publisher', 'Издательство'),
    ('fiche d’un livre', 'Collection'): ('Series', 'Серия'),
    ('fiche d’un livre', 'Parution'): ('Published', 'Год издания'),
    ('fiche d’un livre', 'Pages'): ('Pages', 'Страниц'),
    ('fiche d’un livre', 'Format'): ('Format', 'Формат'),
    ('fiche d’un livre', 'ISBN'): ('ISBN', 'ISBN'),
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
    # La recherche chez Algolia (Réglages → Recherche, inc/algolia.php) : la boutique.
    (None, 'Algolia n’est pas réglé.'): ('Algolia is not set up.', 'Algolia не настроена.'),
    (None, 'La recherche'): ('Search', 'Поиск'),
    (None, 'Recherche'): ('Search', 'Поиск'),
    (None, 'L’envoi a échoué : %s'): ('Sending failed: %s', 'Отправка не удалась: %s'),
    (None, '%d fiche envoyée à Algolia.'): (('%d record sent to Algolia.', '%d records sent to Algolia.'), ('%d запись отправлена в Algolia.', '%d записи отправлены в Algolia.', '%d записей отправлено в Algolia.')),
    (None, 'La loupe cherche dans l’index du site, qui réunit les trois écritures des noms. Avec Algolia, la même recherche tolère les fautes de frappe, et le tableau de bord d’Algolia montre ce que les visiteurs cherchent. Si Algolia ne répond pas, la recherche repasse sur l’index du site.'): (
        'The search looks in the site’s index, which brings together the three spellings of names. With Algolia, the same search tolerates typos, and the Algolia dashboard shows what visitors look for. If Algolia does not answer, the search falls back on the site’s index.',
        'Поиск идёт по указателю сайта, который объединяет три написания имён. С Algolia тот же поиск прощает опечатки, а панель Algolia показывает, что ищут посетители. Если Algolia не отвечает, поиск возвращается к указателю сайта.'),
    (None, 'Algolia :'): ('Algolia:', 'Algolia:'),
    (None, 'pas de réponse (%s).'): ('no answer (%s).', 'нет ответа (%s).'),
    (None, '%d fiche dans l’index.'): (('%d record in the index.', '%d records in the index.'), ('%d запись в индексе.', '%d записи в индексе.', '%d записей в индексе.')),
    (None, 'La loupe cherche chez Algolia.'): ('The search uses Algolia.', 'Поиск идёт через Algolia.'),
    (None, 'La loupe cherche encore dans l’index du site.'): ('The search still uses the site’s index.', 'Поиск пока идёт по указателю сайта.'),
    (None, 'Application ID'): ('Application ID', 'Application ID'),
    (None, 'Search API Key (la clé de recherche, publique)'): ('Search API Key (public)', 'Search API Key (публичный ключ поиска)'),
    (None, 'La clé d’écriture (Write API Key, côté serveur)'): ('Write API Key (server side)', 'Ключ записи (Write API Key, на сервере)'),
    (None, 'L’index'): ('Index', 'Индекс'),
    (None, 'Posée. Laisser vide pour la garder.'): ('Set. Leave empty to keep it.', 'Задан. Оставьте пустым, чтобы сохранить.'),
    (None, 'Pas encore posée.'): ('Not set yet.', 'Пока не задан.'),
    (None, 'Chercher chez Algolia'): ('Search with Algolia', 'Искать через Algolia'),
    (None, 'La loupe et la recherche des livres interrogent Algolia (à cocher une fois l’index envoyé).'): ('The search panel and the book search use Algolia (tick once the index has been sent).', 'Панель поиска и поиск книг обращаются к Algolia (отметьте, когда индекс отправлен).'),
    (None, 'Enregistrer'): ('Save', 'Сохранить'),
    (None, 'Envoyer tout l’index'): ('Send the whole index', 'Отправить весь индекс'),
    (None, 'Refait l’index du site, règle celui d’Algolia, le vide, puis y envoie tout ce qu’on peut chercher sur le site. Ensuite, chaque contenu enregistré s’y recopie de lui-même.'): (
        'Rebuilds the site’s index, sets up Algolia’s, empties it, then sends everything that can be searched on the site. After that, every saved item is copied there automatically.',
        'Перестраивает указатель сайта, настраивает индекс Algolia, очищает его и отправляет туда всё, что можно искать на сайте. Затем каждый сохранённый материал копируется туда сам.'),
    (None, 'Envoyer tout l’index à Algolia'): ('Send the whole index to Algolia', 'Отправить весь индекс в Algolia'),

    # Les rencontres du centre (l'extension : inc/evenements.php). Un pluriel : ((anglais, …), (russe, …)).
    (None, 'Événements'): ('Events', 'События'),
    (None, 'Événement'): ('Event', 'Событие'),
    (None, 'Ajouter'): ('Add', 'Добавить'),
    (None, 'Ajouter un événement'): ('Add an event', 'Добавить событие'),
    (None, 'Modifier l’événement'): ('Edit event', 'Редактировать событие'),
    (None, 'Voir l’événement'): ('View event', 'Посмотреть событие'),
    (None, 'Tous les événements'): ('All events', 'Все события'),
    (None, 'Chercher un événement'): ('Search events', 'Искать событие'),
    (None, 'Aucun événement'): ('No events', 'Событий нет'),
    (None, 'Aucun événement dans la corbeille'): ('No events in the bin', 'В корзине нет событий'),
    (None, 'Cycles'): ('Series', 'Циклы'),
    (None, 'Cycle'): ('Series', 'Цикл'),
    (None, 'Tous les cycles'): ('All series', 'Все циклы'),
    (None, 'Modifier le cycle'): ('Edit series', 'Редактировать цикл'),
    (None, 'Ajouter un cycle'): ('Add a series', 'Добавить цикл'),
    (None, 'Intervenants'): ('Speakers', 'Участники'),
    (None, 'Intervenant'): ('Speaker', 'Участник'),
    (None, 'Tous les intervenants'): ('All speakers', 'Все участники'),
    (None, 'Modifier l’intervenant'): ('Edit speaker', 'Редактировать участника'),
    (None, 'Ajouter un intervenant'): ('Add a speaker', 'Добавить участника'),
    (None, 'Séparer les noms par des virgules'): ('Separate names with commas', 'Разделяйте имена запятыми'),
    (None, 'Début'): ('Starts', 'Начало'),
    (None, 'Facultatif.'): ('Optional.', 'Необязательно.'),
    (None, 'Lieu'): ('Venue', 'Место'),
    (None, 'Vide : au centre, 11, rue de la Montagne-Sainte-Geneviève.'): ('Empty: at the centre, 11 rue de la Montagne-Sainte-Geneviève.', 'Пусто: в центре, 11, rue de la Montagne-Sainte-Geneviève.'),
    (None, 'Forme'): ('Format', 'Формат'),
    (None, 'Rencontre, conférence, présentation, soirée, exposition…'): ('Talk, lecture, book launch, evening, exhibition…', 'Встреча, лекция, презентация, вечер, выставка…'),
    (None, 'Prix de l’entrée (€)'): ('Admission price (€)', 'Цена входа (€)'),
    (None, 'Pour la billetterie.'): ('For ticketing.', 'Для продажи билетов.'),
    (None, 'Places'): ('Seats', 'Места'),
    (None, 'Vide : sans limite.'): ('Empty: no limit.', 'Пусто: без ограничений.'),
    (None, 'Vidéos et enregistrements'): ('Videos and recordings', 'Видео и записи'),
    (None, 'Un lien par ligne : YouTube, Vimeo, SoundCloud, Dailymotion, Spotify, Facebook, ou un fichier de la médiathèque. Pour un titre : « Titre | lien ».'): ('One link per line: YouTube, Vimeo, SoundCloud, Dailymotion, Spotify, Facebook, or a file from the media library. For a title: “Title | link”.', 'По одной ссылке на строку: YouTube, Vimeo, SoundCloud, Dailymotion, Spotify, Facebook или файл из медиатеки. С названием: «Название | ссылка».'),
    (None, 'Livres liés'): ('Related books', 'Связанные книги'),
    (None, 'Leurs références (ou identifiants), séparées par des virgules. Les livres des intervenants qui sont aussi des auteurs s’y ajoutent.'): ('Their SKUs (or IDs), separated by commas. Books by speakers who are also authors are added automatically.', 'Их артикулы (или идентификаторы) через запятую. Книги участников, которые есть среди авторов, добавляются сами.'),
    (None, 'Billet'): ('Ticket', 'Билет'),
    (None, 'Ancien site'): ('Former site', 'Старый сайт'),
    (None, 'Date à venir'): ('Date to be announced', 'Дата будет объявлена'),
    (None, 'du %1$s au %2$s'): ('from %1$s to %2$s', 'с %1$s по %2$s'),
    (None, 'j F'): ('j F', 'j F'),
    (None, 'j F Y'): ('j F Y', 'j F Y'),
    (None, 'l j F Y'): ('l j F Y', 'l, j F Y'),
    (None, 'En français'): ('In French', 'На французском'),
    (None, 'En russe'): ('In Russian', 'На русском'),
    (None, 'En français et en russe'): ('In French and Russian', 'На французском и русском'),
    (None, 'En anglais'): ('In English', 'На английском'),
    (None, 'Entrée — %s'): ('Admission — %s', 'Вход — %s'),
    (None, 'La rencontre'): ('The event', 'Событие'),
    (None, 'Entrée libre, sans inscription'): ('Free, no registration', 'Вход свободный, без записи'),
    (None, 'Gratuite, sur inscription'): ('Free, registration required', 'Бесплатно, по записи'),
    (None, 'Payante : billetterie'): ('Paid: ticketing', 'Платно: продажа билетов'),
    (None, 'Prévue'): ('Scheduled', 'Запланировано'),
    (None, 'Complète'): ('Full', 'Мест нет'),
    (None, 'Annulée'): ('Cancelled', 'Отменено'),
    (None, '%d place prise'): (('%d seat taken', '%d seats taken'), ('%d место занято', '%d места занято', '%d мест занято')),
    (None, 'Le'): ('Date', 'Дата'),
    (None, 'annulée'): ('cancelled', 'отменено'),
    (None, 'vidéo'): ('video', 'видео'),
    (None, 'Quand'): ('When', 'Когда'),
    (None, 'Où'): ('Where', 'Где'),
    (None, 'Au centre, 11, rue de la Montagne-Sainte-Geneviève, Paris 5e'): ('At the centre, 11 rue de la Montagne-Sainte-Geneviève, Paris 5th', 'В центре: 11, rue de la Montagne-Sainte-Geneviève, Париж, 5-й округ'),
    (None, 'Payante'): ('Paid', 'Платно'),
    (None, 'Libre, sur inscription'): ('Free, registration required', 'Бесплатно, по записи'),
    (None, 'Libre, sans inscription'): ('Free, no registration', 'Свободный вход'),
    (None, 'il reste %d place'): (('%d seat left', '%d seats left'), ('осталось %d место', 'осталось %d места', 'осталось %d мест')),
    (None, 'Avec'): ('With', 'С участием'),
    (None, 'Cette rencontre est annulée.'): ('This event has been cancelled.', 'Это событие отменено.'),
    (None, 'C’était %s.'): ('It ran %s.', 'Проходила %s.'),
    (None, 'Cette rencontre a eu lieu le %s.'): ('This event took place on %s.', 'Это событие прошло %s.'),
    (None, 'Revoir la rencontre'): ('Watch the event', 'Смотреть запись'),
    (None, 'Complet.'): ('Sold out.', 'Мест нет.'),
    (None, 'S’inscrire'): ('Register', 'Записаться'),
    (None, 'Réserver'): ('Book', 'Забронировать'),
    (None, 'Les réservations ouvrent bientôt.'): ('Booking opens soon.', 'Бронирование скоро откроется.'),
    (None, 'Voir sur %s'): ('Watch on %s', 'Смотреть на %s'),
    (None, 'Voir la vidéo'): ('Watch the video', 'Смотреть видео'),
    (None, 'Revoir, réécouter'): ('Watch and listen again', 'Смотреть и слушать'),
    (None, 'Toutes les rencontres'): ('All events', 'Все события'),
    (None, 'Complet'): ('Sold out', 'Мест нет'),
    (None, 'Entrée libre'): ('Free', 'Вход свободный'),
    (None, 'Voir'): ('View', 'Подробнее'),
    (None, 'Vidéo'): ('Video', 'Видео'),
    (None, 'À venir'): ('Upcoming', 'Скоро'),
    (None, 'Cette semaine'): ('This week', 'На этой неделе'),
    (None, 'La semaine prochaine'): ('Next week', 'На следующей неделе'),
    (None, 'Plus tard'): ('Later', 'Позже'),
    (None, 'Pas de rencontre annoncée pour l’instant : les prochaines paraîtront ici.'): ('No events announced yet: upcoming ones will appear here.', 'Пока ничего не объявлено: новые события появятся здесь.'),
    (None, 'Les rencontres passées'): ('Past events', 'Прошедшие события'),
    (None, 'Avec une vidéo'): ('With a video', 'С видео'),
    (None, 'Les cycles'): ('Series', 'Циклы'),
    (None, '%d rencontre'): (('%d event', '%d events'), ('%d событие', '%d события', '%d событий')),
    (None, 'Les rencontres, les conférences, les lectures et les expositions du centre, au 11, rue de la Montagne-Sainte-Geneviève.'): ('Talks, lectures, readings and exhibitions at the centre, 11 rue de la Montagne-Sainte-Geneviève.', 'Встречи, лекции, чтения и выставки центра: 11, rue de la Montagne-Sainte-Geneviève.'),

    # Les étiquettes (auteurs, sujets) et les vidéos et podcasts (l'extension : inc/sujets.php, inc/medias.php).
    (None, 'Sujet'): ('Subject', 'Тема'),
    (None, 'Sujets'): ('Subjects', 'Темы'),
    ('fiche d’un livre', 'Autour de'): ('About', 'О ком'),
    ('fiche d’un livre', 'Sujets'): ('Subjects', 'Темы'),
    (None, 'Autour de'): ('About', 'О ком'),
    (None, 'Chercher un sujet'): ('Search subjects', 'Искать тему'),
    (None, 'Tous les sujets'): ('All subjects', 'Все темы'),
    (None, 'Modifier le sujet'): ('Edit subject', 'Редактировать тему'),
    (None, 'Ajouter un sujet'): ('Add a subject', 'Добавить тему'),
    (None, 'Séparer les sujets par des virgules'): ('Separate subjects with commas', 'Разделяйте темы запятыми'),
    (None, 'Choisir parmi les plus utilisés'): ('Choose from the most used', 'Выбрать из самых частых'),
    (None, 'Aucun sujet'): ('No subjects', 'Тем нет'),
    (None, '%s est un auteur : choisissez-le dans « Auteurs » plutôt qu’en sujet.'): ('%s is an author: choose them under “Authors” rather than as a subject.', '%s — автор: выберите его в разделе «Авторы», а не как тему.'),
    (None, 'Les personnes dont parle le livre, séparées par des virgules : des auteurs (un nom nouveau devient un auteur). Le livre paraît sur leur page, dans « Autour de ».'): ('The people the book is about, separated by commas: authors (a new name becomes an author). The book appears on their page, under “About”.', 'Люди, о которых книга, через запятую: авторы (новое имя становится автором). Книга появится на их странице, в разделе «О ком».'),
    (None, 'Rencontre'): ('Event', 'Встреча'),
    (None, 'À lire, à revoir'): ('Read and watch more', 'Читать и смотреть ещё'),
    (None, 'Prochainement au centre'): ('Coming up at the centre', 'Скоро в центре'),
    (None, 'Autour de %s'): ('About %s', 'О нём: %s'),
    (None, 'Les rencontres'): ('Events', 'Встречи'),
    (None, 'Au centre'): ('At the centre', 'В центре'),
    (None, 'Écouter, regarder'): ('Listen and watch', 'Слушать и смотреть'),
    (None, 'À lire sur le site'): ('Read on the site', 'Читать на сайте'),
    (None, 'Rien encore sur ce sujet.'): ('Nothing on this subject yet.', 'По этой теме пока ничего нет.'),
    (None, 'Sujet · %d rencontre ou article'): (('Subject · %d item', 'Subject · %d items'), ('Тема · %d материал', 'Тема · %d материала', 'Тема · %d материалов')),
    (None, 'Vidéos et podcasts'): ('Videos and podcasts', 'Видео и подкасты'),
    (None, 'Vidéo ou podcast'): ('Video or podcast', 'Видео или подкаст'),
    (None, 'Ajouter une vidéo ou un podcast'): ('Add a video or podcast', 'Добавить видео или подкаст'),
    (None, 'Modifier'): ('Edit', 'Редактировать'),
    (None, 'Toutes les vidéos et les podcasts'): ('All videos and podcasts', 'Все видео и подкасты'),
    (None, 'Rien pour l’instant'): ('Nothing yet', 'Пока ничего'),
    (None, 'Séries'): ('Series', 'Серии'),
    (None, 'Série'): ('Series', 'Серия'),
    (None, 'Toutes les séries'): ('All series', 'Все серии'),
    (None, 'Modifier la série'): ('Edit series', 'Редактировать серию'),
    (None, 'Ajouter une série'): ('Add a series', 'Добавить серию'),
    (None, 'La vidéo ou le podcast'): ('The video or podcast', 'Видео или подкаст'),
    (None, 'Lien'): ('Link', 'Ссылка'),
    (None, 'YouTube, Vimeo, SoundCloud, Spotify, un fichier audio ou vidéo ; un lien par ligne, le premier est le principal. Pour un titre : « Titre | lien ».'): ('YouTube, Vimeo, SoundCloud, Spotify, an audio or video file; one link per line, the first is the main one. For a title: “Title | link”.', 'YouTube, Vimeo, SoundCloud, Spotify, аудио- или видеофайл; по одной ссылке на строку, первая — основная. С названием: «Название | ссылка».'),
    (None, 'Genre'): ('Type', 'Тип'),
    (None, 'Podcast'): ('Podcast', 'Подкаст'),
    (None, 'Durée (minutes)'): ('Length (minutes)', 'Длительность (минуты)'),
    (None, '%d min'): ('%d min', '%d мин'),
    (None, 'Et aussi'): ('And also', 'А также'),

    # La page d'un auteur (inc/auteurs.php) et la signature d'un article (inc/journal.php).
    (None, 'Ses livres'): ('Books', 'Книги'),
    (None, 'Portrait'): ('Portrait', 'Портрет'),
    (None, 'Naissance'): ('Born', 'Рождение'),
    (None, 'Le lieu et la date : « Kislovodsk, 11 décembre 1918 ».'): ('Place and date: “Kislovodsk, 11 December 1918”.', 'Место и дата: «Кисловодск, 11 декабря 1918».'),
    (None, 'Mort'): ('Died', 'Смерть'),
    (None, '« Moscou, 3 août 2008 » ; vide s’il est vivant.'): ('“Moscow, 3 August 2008”; empty if still living.', '«Москва, 3 августа 2008»; пусто, если жив.'),
    (None, 'Biographie'): ('Biography', 'Биография'),
    (None, 'Le texte de la page, avec ses intertitres et ses images. La présentation courte est le champ « Description ».'): ('The text of the page, with its subheadings and images. The short introduction is the “Description” field.', 'Текст страницы с подзаголовками и иллюстрациями. Краткое представление — поле «Описание».'),
    (None, 'Photos'): ('Photographs', 'Фотографии'),
    (None, 'Leur légende est celle de la médiathèque.'): ('Their caption is the one in the media library.', 'Подпись берётся из медиатеки.'),
    (None, 'Un lien par ligne : « Titre | lien ».'): ('One link per line: “Title | link”.', 'По одной ссылке на строку: «Название | ссылка».'),
    (None, 'Page de l’encyclopédie'): ('Encyclopedia page', 'Статья энциклопедии'),
    (None, 'La page qui lui est consacrée, s’il y en a une : elle mène à celle-ci, et celle-ci à elle.'): ('The page devoted to them, if any: each links to the other.', 'Посвящённая ему страница, если есть: они ссылаются друг на друга.'),
    (None, 'Choisir les photos'): ('Choose photographs', 'Выбрать фотографии'),
    (None, 'Choisir le portrait'): ('Choose portrait', 'Выбрать портрет'),
    (None, 'Ôter'): ('Remove', 'Убрать'),
    (None, 'Lire toute la biographie'): ('Read the full biography', 'Читать всю биографию'),
    (None, 'L’article de l’encyclopédie'): ('The encyclopedia article', 'Статья в энциклопедии'),
    (None, 'Tout %s : ses livres, les rencontres, les vidéos, les articles'): ('All about %s: books, events, videos, articles', '%s: книги, встречи, видео, статьи'),
    (None, 'Par %s'): ('By %s', 'Автор: %s'),

    # Le journal comme un journal en ligne : les genres, l'en-tête, la une (inc/journal-une.php)
    ('genre d’un article', 'Chronique'): ('Column', 'Колонка'),
    ('genre d’un article', 'Chroniques'): ('Columns', 'Колонки'),
    ('genre d’un article', 'Critique'): ('Review', 'Рецензия'),
    ('genre d’un article', 'Critiques'): ('Reviews', 'Рецензии'),
    ('genre d’un article', 'Essai'): ('Essay', 'Эссе'),
    ('genre d’un article', 'Essais'): ('Essays', 'Эссе'),
    ('genre d’un article', 'Récit'): ('Memoir', 'Воспоминания'),
    ('genre d’un article', 'Récits'): ('Memoirs', 'Воспоминания'),
    ('genre d’un article', 'Entretien'): ('Interview', 'Интервью'),
    ('genre d’un article', 'Entretiens'): ('Interviews', 'Интервью'),
    ('genre d’un article', 'Hommage'): ('Tribute', 'Памяти'),
    ('genre d’un article', 'Hommages'): ('Tributes', 'Памяти'),
    ('genre d’un article', 'Archive'): ('From the archive', 'Из архива'),
    ('genre d’un article', 'Archives'): ('From the archive', 'Из архива'),
    ('genre d’un article', 'Document'): ('Document', 'Документ'),
    ('genre d’un article', 'Documents'): ('Documents', 'Документы'),
    (None, 'Genres'): ('Genres', 'Жанры'),
    (None, 'Tous les genres'): ('All genres', 'Все жанры'),
    (None, 'Modifier le genre'): ('Edit genre', 'Изменить жанр'),
    (None, 'Ajouter un genre'): ('Add genre', 'Добавить жанр'),
    (None, 'Aucun genre'): ('No genres', 'Жанров нет'),
    (None, '%d min de lecture'): (('%d min read', '%d min read'), ('%d мин чтения', '%d мин чтения', '%d мин чтения')),
    (None, 'Chroniques, critiques, essais et archives : la littérature russe et son histoire, par ceux qui la lisent et la traduisent.'):
        ('Columns, reviews, essays and archives: Russian literature and its history, by those who read and translate it.',
         'Колонки, рецензии, эссе и архивы: русская литература и её история глазами тех, кто её читает и переводит.'),
    (None, 'Les genres du journal'): ('Journal sections', 'Рубрики журнала'),
    (None, 'Les sujets du journal'): ('Journal topics', 'Темы журнала'),
    (None, 'Le fil'): ('Latest', 'Лента'),
    (None, 'Du plus récent au plus ancien'): ('Newest first', 'Сначала новые'),
    ('date courte du fil', 'j M Y'): ('j M Y', 'j M Y'),
    (None, 'Tout le fil'): ('All pieces', 'Вся лента'),
    (None, '%1$d chronique depuis %2$s'): (('%1$d column since %2$s', '%1$d columns since %2$s'), ('%1$d колонка с %2$s года', '%1$d колонки с %2$s года', '%1$d колонок с %2$s года')),
    (None, 'Les chroniques'): ('Columns', 'Колонки'),
    (None, 'Toutes les chroniques'): ('All columns', 'Все колонки'),
    (None, 'Grand format'): ('Long read', 'Большой материал'),
    (None, 'Depuis %s'): ('Since %s', 'С %s года'),
    (None, 'Les sujets'): ('Topics', 'Темы'),
    (None, '%d sujet'): (('%d topic', '%d topics'), ('%d тема', '%d темы', '%d тем')),
    (None, '%d texte'): (('%d piece', '%d pieces'), ('%d текст', '%d текста', '%d текстов')),
    (None, 'Tous les textes'): ('All pieces', 'Все тексты'),
    (None, 'Les pages du journal'): ('Journal pages', 'Страницы журнала'),

    # L'accueil, à la une (les compositions patterns/une-*.php)
    (None, 'Voir plus'): ('Show more', 'Показать ещё'),
    (None, 'Voir moins'): ('Show less', 'Свернуть'),
    (None, 'À la une · YMCA-Press, la librairie, le centre'):
        ('Featured · YMCA-Press, the bookshop, the centre',
         'Главное · YMCA-Press, книжный магазин, культурный центр'),
    (None, 'Couverture de L’Archipel du Goulag, cinquante ans après'):
        ('Cover of The Gulag Archipelago, Fifty Years On',
         'Обложка книги «Архипелаг ГУЛАГ пятьдесят лет спустя»'),
    (None, 'Paru'):
        ('Published',
         'Вышла'),
    (None, '28 décembre 1973, en russe'):
        ('28 December 1973, in Russian',
         '28 декабря 1973 года, по-русски'),
    (None, 'YMCA-Press, Paris'):
        ('YMCA-Press, Paris',
         'YMCA-Press, Париж'),
    (None, 'Alexandre Soljenitsyne'):
        ('Alexander Solzhenitsyn',
         'Александр Солженицын'),
    (None, '[nombre]'):
        ('[number]',
         '[число]'),
    (None, 'À la librairie'):
        ('At the bookshop',
         'В магазине'),
    (None, 'le recueil de 2023'):
        ('the 2023 collection',
         'сборник 2023 года'),
    (None, 'Me prévenir de son retour'):
        ('Tell me when it’s back',
         'Сообщить о поступлении'),
    (None, 'Écouter le podcast'):
        ('Listen to the podcast',
         'Слушать подкаст'),
    (None, 'Les archives de 1973'):
        ('The 1973 archives',
         'Архивы 1973 года'),
    (None, 'Pour aller plus loin'):
        ('Further reading',
         'Чтобы узнать больше'),
    (None, '<strong>En librairie</strong> <a href="%1$s"><em>Voix et visages du Goulag</em></a>'):
        ('<strong>At the bookshop</strong> <a href="%1$s"><em>Voix et visages du Goulag</em></a>',
         '<strong>В магазине</strong> <a href="%1$s"><em>Голоса и лица ГУЛАГа</em></a>'),
    (None, 'Un autre livre du rayon, pour entendre d’autres voix des camps que celle de Soljenitsyne.'):
        ('Another book from the same shelf, to hear voices from the camps other than Solzhenitsyn’s.',
         'Ещё одна книга с той же полки: другие голоса из лагерей, кроме голоса Солженицына.'),
    (None, '<strong>L’encyclopédie</strong> <a href="%1$s">Le Goulag</a>'):
        ('<strong>The encyclopedia</strong> <a href="%1$s">The Gulag</a>',
         '<strong>Энциклопедия</strong> <a href="%1$s">ГУЛАГ</a>'),
    (None, 'Les camps et leurs témoins&nbsp;: Chalamov, Guinzbourg, Soljenitsyne, et pour chacun les livres qu’on trouve ici.'):
        ('The camps and their witnesses: Shalamov, Ginzburg, Solzhenitsyn, and for each one the books you can find here.',
         'Лагеря и их свидетели: Шаламов, Гинзбург, Солженицын, и для каждого — книги, которые у нас есть.'),
    (None, '<strong>Podcast</strong> <a href="%1$s"><em>Les secrets de L’Archipel du Goulag</em></a>'):
        ('<strong>Podcast</strong> <a href="%1$s"><em>The Secrets of The Gulag Archipelago</em></a>',
         '<strong>Подкаст</strong> <a href="%1$s"><em>Тайны «Архипелага ГУЛАГ»</em></a>'),
    (None, 'L’histoire d’un manuscrit caché, recopié, sorti d’URSS, épisode par épisode.'):
        ('The story of a manuscript hidden, copied and smuggled out of the USSR, episode by episode.',
         'История рукописи, которую прятали, переписывали и вывезли из СССР, — серия за серией.'),
    (None, 'Le livre de Soljenitsyne sur les camps soviétiques paraît d’abord en russe, à Paris, chez YMCA-Press, avant de faire le tour du monde.'):
        ('Solzhenitsyn’s book on the Soviet camps first came out in Russian, in Paris, from YMCA-Press, before going round the world.',
         'Книга Солженицына о советских лагерях сначала вышла по-русски в Париже, в YMCA-Press, а потом обошла весь мир.'),
    (None, 'Soljenitsyne et Nikita Struve à l’imprimerie'):
        ('Solzhenitsyn and Nikita Struve at the printing works',
         'Солженицын и Никита Струве в типографии'),
    (None, 'Soljenitsyne, Nikita Struve, Léonide Lifar et Serge Béresniak. Photo Hélène Lifar.'):
        ('Solzhenitsyn, Nikita Struve, Léonide Lifar and Serge Béresniak. Photo: Hélène Lifar.',
         'Солженицын, Никита Струве, Леонид Лифарь и Сергей Бересняк. Фото Элен Лифарь.'),
    (None, 'Soljenitsyne l’écrit en secret, entre 1958 et 1968, à partir de ses années de camp et de plus de deux cents témoignages d’anciens détenus. Il le sous-titre «&nbsp;essai d’investigation littéraire&nbsp;»&nbsp;: ni roman ni livre d’histoire, mais les deux à la fois.'):
        ('Solzhenitsyn wrote it in secret between 1958 and 1968, drawing on his years in the camps and on more than two hundred accounts by former prisoners. He subtitled it “an experiment in literary investigation”: neither a novel nor a history book, but both at once.',
         'Солженицын писал его тайно, с 1958 по 1968 год, по собственному лагерному опыту и более чем двумстам свидетельствам бывших заключённых. Подзаголовок — «опыт художественного исследования»: не роман и не историческая книга, а то и другое сразу.'),
    (None, 'En 1973, le KGB saisit l’un des exemplaires du manuscrit&nbsp;; l’auteur décide de le faire publier sans attendre. Le premier tome paraît à Paris le 28 décembre. En février 1974, Soljenitsyne est arrêté, déchu de sa citoyenneté et expulsé d’URSS.'):
        ('In 1973 the KGB seized one of the copies of the manuscript; the author decided to have it published at once. The first volume came out in Paris on 28 December. In February 1974 Solzhenitsyn was arrested, stripped of his citizenship and expelled from the USSR.',
         'В 1973 году КГБ изъял один из экземпляров рукописи, и автор решил печатать книгу немедленно. Первый том вышел в Париже 28 декабря. В феврале 1974 года Солженицына арестовали, лишили гражданства и выслали из СССР.'),
    (None, 'Cinquante ans après, le recueil dirigé par Georges Nivat, <em>L’Archipel du Goulag, cinquante ans après</em>, en reprend les pages essentielles, avec les lectures qu’on en a faites depuis. Au centre culturel, un podcast en plusieurs épisodes, <em>Les secrets de L’Archipel du Goulag</em>, raconte les mystères d’un livre hors du commun.'):
        ('Fifty years on, the collection edited by Georges Nivat, <em>L’Archipel du Goulag, cinquante ans après</em>, brings together its essential pages and the readings it has had since. At the cultural centre, a podcast in several episodes, <em>The Secrets of The Gulag Archipelago</em>, tells the mysteries of an extraordinary book.',
         'Пятьдесят лет спустя сборник под редакцией Жоржа Нива, <em>«Архипелаг ГУЛАГ» пятьдесят лет спустя</em>, собирает главные страницы книги и то, как её читали с тех пор. В культурном центре подкаст в нескольких выпусках, <em>Тайны «Архипелага ГУЛАГ»</em>, рассказывает о загадках этой необыкновенной книги.'),
    (None, 'Le journal du centre · L’article du mois'):
        ('The centre’s journal · Article of the month',
         'Журнал центра · Статья месяца'),
    (None, 'Le Paris de l’émigration russe'):
        ('The Paris of the Russian emigration',
         'Париж русской эмиграции'),
    (None, 'Une cathédrale, un institut de théologie, des cafés, une cité d’artistes, des usines, un cimetière&nbsp;: dix lieux pour raconter la ville où s’est installée, après 1917, la Russie en exil.'):
        ('A cathedral, a theological institute, cafés, an artists’ colony, factories, a cemetery: ten places that tell the story of the city where Russia in exile settled after 1917.',
         'Собор, богословский институт, кафе, городок художников, заводы, кладбище: десять мест, по которым можно рассказать о городе, где после 1917 года обосновалась Россия в изгнании.'),
    (None, 'Un groupe d’étudiants russes devant une maison, à la fin des années 1920'):
        ('A group of Russian students in front of a house, in the late 1920s',
         'Группа русских студентов перед домом, конец 1920-х годов'),
    (None, 'Congrès de l’Action chrétienne des étudiants russes, à la fin des années 1920&nbsp;: Berdiaev au centre.'):
        ('A congress of the Russian Student Christian Movement, in the late 1920s: Berdyaev in the centre.',
         'Съезд Русского студенческого христианского движения, конец 1920-х годов. В центре — Бердяев.'),
    (None, 'Après la révolution et la guerre civile, des dizaines de milliers de Russes s’installent à Paris, qui devient, avec Berlin et Prague, l’une des capitales de l’émigration.'):
        ('After the revolution and the civil war, tens of thousands of Russians settled in Paris, which became, with Berlin and Prague, one of the capitals of the emigration.',
         'После революции и Гражданской войны в Париже поселились десятки тысяч русских, и он стал, вместе с Берлином и Прагой, одной из столиц эмиграции.'),
    (None, 'La cathédrale Saint-Alexandre-Nevski, rue Daru, en est le cœur&nbsp;; l’institut de théologie Saint-Serge ouvre en 1925, rue de Crimée. Beaucoup travaillent aux usines Renault de Billancourt&nbsp;; beaucoup reposent au cimetière de Sainte-Geneviève-des-Bois.'):
        ('Saint Alexander Nevsky Cathedral, on the rue Daru, is its heart; the Saint Sergius Theological Institute opened in 1925, on the rue de Crimée. Many worked at the Renault factories in Billancourt; many lie in the cemetery of Sainte-Geneviève-des-Bois.',
         'Её сердце — собор Александра Невского на улице Дарю; в 1925 году на улице Крымэ открывается Свято-Сергиевский богословский институт. Многие работают на заводах «Рено» в Бийанкуре; многие похоронены на кладбище Сент-Женевьев-де-Буа.'),
    (None, 'L’article suit ces lieux un à un, sur une carte, avec trois promenades à faire à pied. La librairie, rue de la Montagne-Sainte-Geneviève, en est l’une des adresses.'):
        ('The article follows these places one by one, on a map, with three walks to do on foot. The bookshop, on the rue de la Montagne-Sainte-Geneviève, is one of the addresses.',
         'Статья проходит по этим местам одно за другим, по карте, с тремя пешими прогулками. Книжный магазин на улице Монтань-Сент-Женевьев — один из этих адресов.'),
    (None, 'Par [l’auteur] · [date] · dix lieux, trois promenades, [4] vidéos'):
        ('By [author] · [date] · ten places, three walks, [4] videos',
         'Автор: [имя] · [дата] · десять мест, три прогулки, [4] видео'),
    (None, 'Lire l’article'):
        ('Read the article',
         'Читать статью'),
    (None, 'Les billets'):
        ('Columns',
         'Заметки'),
    (None, 'Les billets d’Yves Hamant'):
        ('Yves Hamant’s columns',
         'Заметки Ива Амана'),
    (None, 'Anne-Marie Pelletier, Résister avec Vassily Grossman'):
        ('Anne-Marie Pelletier, Résister avec Vassily Grossman',
         'Анн-Мари Пеллетье, «Сопротивляться вместе с Василием Гроссманом»'),
    (None, 'Yves Hamant lit le livre qu’Anne-Marie Pelletier consacre à Vassily Grossman, l’auteur de <em>Vie et destin</em>, et à ce que résister veut dire chez lui.'):
        ('Yves Hamant reads Anne-Marie Pelletier’s book on Vasily Grossman, the author of <em>Life and Fate</em>, and on what resistance means in his work.',
         'Ив Аман читает книгу Анн-Мари Пеллетье о Василии Гроссмане, авторе романа <em>«Жизнь и судьба»</em>, и о том, что значит у него сопротивляться.'),
    (None, 'Yves Hamant · 27 janvier · 10 min'):
        ('Yves Hamant · 27 January · 10 min',
         'Ив Аман · 27 января · 10 мин'),
    (None, 'Les billets de Dominique Samson'):
        ('Dominique Samson’s columns',
         'Заметки Доминик Самсон'),
    (None, 'Du jardin secret des Lykov, vieux-croyants de Sibérie'):
        ('The secret garden of the Lykovs, Old Believers in Siberia',
         'Тайный сад Лыковых, сибирских старообрядцев'),
    (None, 'En 1978, des géologues découvrent dans la taïga une famille de vieux-croyants, les Lykov, coupée du monde depuis plus de quarante ans. Dominique Samson raconte leur jardin.'):
        ('In 1978, geologists found in the taiga a family of Old Believers, the Lykovs, cut off from the world for more than forty years. Dominique Samson tells the story of their garden.',
         'В 1978 году геологи нашли в тайге семью старообрядцев Лыковых, больше сорока лет жившую вдали от мира. Доминик Самсон рассказывает об их саде.'),
    (None, 'Dominique Samson · 13 avril 2023 · 28 min'):
        ('Dominique Samson · 13 April 2023 · 28 min',
         'Доминик Самсон · 13 апреля 2023 · 28 мин'),
    (None, 'Le journal, en russe'):
        ('The journal, in Russian',
         'Журнал, по-русски'),
    (None, 'Irina Roskina revient sur <em lang="ru">Четыре главы</em>, le livre de souvenirs littéraires de Natalia Roskina, paru chez YMCA-Press en 1980.'):
        ('Irina Roskina looks back at <em lang="ru">Четыре главы</em> (Four Chapters), Natalia Roskina’s literary memoir, published by YMCA-Press in 1980.',
         'Ирина Роскина вспоминает книгу <em lang="ru">«Четыре главы»</em> — литературные воспоминания Наталии Роскиной, вышедшие в YMCA-Press в 1980 году.'),
    (None, '28 septembre · 6 min'):
        ('28 September · 6 min',
         '28 сентября · 6 мин'),
    (None, 'Les billets de Georges Nivat'):
        ('Georges Nivat’s columns',
         'Заметки Жоржа Нива'),
    (None, 'Quand un chardonneret s’échappe dans le métro'):
        ('When a goldfinch gets loose in the metro',
         'Когда щегол вырывается на волю в метро'),
    (None, '[Deux lignes&nbsp;: de quoi parle le billet.]'):
        ('[Two lines: what the column is about.]',
         '[Две строки: о чём заметка.]'),
    (None, 'Georges Nivat · 28 décembre 2024 · 3 min'):
        ('Georges Nivat · 28 December 2024 · 3 min',
         'Жорж Нива · 28 декабря 2024 · 3 мин'),
    (None, '<strong>Ils écrivent dans le journal&nbsp;:</strong> Georges Nivat, Yves Hamant, Anne Hogenhuis, Françoise Lesourd, Cécile Vaissié, Anita Davidenkoff, Anne Misslin, Nikolaï Tikhonchuk, Véronique Jobert, François de Saint-Chéron, Marguerite Léna, Nikita Struve, Dominique Samson et Margarita Sokolova.'):
        ('<strong>They write for the journal:</strong> Georges Nivat, Yves Hamant, Anne Hogenhuis, Françoise Lesourd, Cécile Vaissié, Anita Davidenkoff, Anne Misslin, Nikolai Tikhonchuk, Véronique Jobert, François de Saint-Chéron, Marguerite Léna, Nikita Struve, Dominique Samson and Margarita Sokolova.',
         '<strong>В журнале пишут:</strong> Жорж Нива, Ив Аман, Анн Огенюи, Франсуаза Лесур, Сесиль Вессье, Анита Давиденкофф, Анн Мисслен, Николай Тихончук, Вероника Жобер, Франсуа де Сен-Шерон, Маргерит Лена, Никита Струве, Доминик Самсон и Маргарита Соколова.'),
    (None, 'Tout le journal'):
        ('The whole journal',
         'Весь журнал'),
    (None, 'Retraduire · la librairie'):
        ('Retranslating · the bookshop',
         'Новый перевод · книжный магазин'),
    (None, 'Le Docteur Jivago'):
        ('Doctor Zhivago',
         'Доктор Живаго'),
    (None, 'Couverture du Docteur Jivago'):
        ('Cover of Doctor Zhivago',
         'Обложка «Доктора Живаго»'),
    (None, 'Première édition'):
        ('First edition',
         'Первое издание'),
    (None, 'Milan, 1957, en italien'):
        ('Milan, 1957, in Italian',
         'Милан, 1957, по-итальянски'),
    (None, '[éditeur de la nouvelle traduction]'):
        ('[publisher of the new translation]',
         '[издатель нового перевода]'),
    (None, 'Boris Pasternak'):
        ('Boris Pasternak',
         'Борис Пастернак'),
    (None, 'nouvelle traduction'):
        ('new translation',
         'новый перевод'),
    (None, 'Ajouter au panier'):
        ('Add to basket',
         'В корзину'),
    (None, 'Feuilleter'):
        ('Look inside',
         'Полистать'),
    (None, 'Pasternak, dans l’encyclopédie'):
        ('Pasternak, in the encyclopedia',
         'Пастернак в энциклопедии'),
    (None, '<strong>En librairie</strong> <a href="%1$s">Platonov, <em>Tchevengour</em></a>'):
        ('<strong>At the bookshop</strong> <a href="%1$s">Platonov, <em>Chevengur</em></a>',
         '<strong>В магазине</strong> <a href="%1$s">Платонов, <em>«Чевенгур»</em></a>'),
    (None, 'Lui aussi écrit sous Staline et refusé de son vivant&nbsp;: paru en entier en URSS en 1988, la même année que Jivago.'):
        ('Also written under Stalin and turned down in its author’s lifetime: published in full in the USSR in 1988, the same year as Zhivago.',
         'Тоже написан при Сталине и отвергнут при жизни автора: целиком вышел в СССР в 1988 году, в один год с «Живаго».'),
    (None, '<strong>Journal du centre</strong> <a href="%1$s"><em>Le Docteur Jivago</em>, par Georges Nivat</a>'):
        ('<strong>The centre’s journal</strong> <a href="%1$s"><em>Doctor Zhivago</em>, by Georges Nivat</a>',
         '<strong>Журнал центра</strong> <a href="%1$s"><em>«Доктор Живаго»</em>: статья Жоржа Нива</a>'),
    (None, '[Deux lignes&nbsp;: de quoi parle l’article.]'):
        ('[Two lines: what the article is about.]',
         '[Две строки: о чём статья.]'),
    (None, '<strong>Le fonds d’archives</strong> <a href="%1$s">Interdits là-bas, publiés ailleurs</a>'):
        ('<strong>The archives</strong> <a href="%1$s">Banned there, published elsewhere</a>',
         '<strong>Архив</strong> <a href="%1$s">Запрещённые там, изданные здесь</a>'),
    (None, 'Les livres qui ont paru d’abord à l’étranger, ou des décennies après avoir été écrits.'):
        ('Books that first came out abroad, or decades after they were written.',
         'Книги, которые сначала вышли за границей или через десятилетия после того, как были написаны.'),
    (None, 'Le roman de Pasternak a paru d’abord en italien, à Milan, en 1957, avant toute édition en russe&nbsp;; une nouvelle traduction le fait relire.'):
        ('Pasternak’s novel first came out in Italian, in Milan, in 1957, before any edition in Russian; a new translation sends us back to it.',
         'Роман Пастернака сначала вышел по-итальянски, в Милане, в 1957 году, раньше любого русского издания; новый перевод заставляет его перечитать.'),
    (None, 'Une bougie derrière une vitre givrée'):
        ('A candle behind a frosted window',
         'Свеча за замёрзшим окном'),
    (None, 'Une bougie derrière une vitre givrée&nbsp;: l’image que le roman a rendue célèbre.'):
        ('A candle behind a frosted window: the image the novel made famous.',
         'Свеча за замёрзшим окном — образ, который роман сделал знаменитым.'),
    (None, 'Pasternak l’écrit pendant dix ans, de la fin de la guerre au milieu des années 1950&nbsp;: la vie d’un médecin et poète, Iouri Jivago, dans la Russie de la révolution et de la guerre civile, et son amour pour Lara.'):
        ('Pasternak wrote it over ten years, from the end of the war to the mid-1950s: the life of a doctor and poet, Yuri Zhivago, in the Russia of the revolution and the civil war, and his love for Lara.',
         'Пастернак писал его десять лет, с конца войны до середины 1950-х: жизнь врача и поэта Юрия Живаго в России революции и Гражданской войны и его любовь к Ларе.'),
    (None, 'Refusé par les revues soviétiques, le manuscrit passe à l’Ouest&nbsp;; l’éditeur Giangiacomo Feltrinelli le publie à Milan en novembre 1957. L’année suivante, Pasternak reçoit le prix Nobel de littérature&nbsp;; sous la pression du pouvoir, il le refuse.'):
        ('Turned down by Soviet journals, the manuscript reached the West; the publisher Giangiacomo Feltrinelli brought it out in Milan in November 1957. The following year Pasternak was awarded the Nobel Prize in Literature; under pressure from the authorities, he declined it.',
         'Отвергнутая советскими журналами рукопись попала на Запад; издатель Джанджакомо Фельтринелли выпустил роман в Милане в ноябре 1957 года. На следующий год Пастернак получил Нобелевскую премию по литературе и под давлением власти от неё отказался.'),
    (None, 'En URSS, le roman ne paraît qu’en 1988, dans la revue <em>Novy Mir</em>, trente ans après le Nobel.'):
        ('In the USSR the novel did not appear until 1988, in the journal <em>Novy Mir</em>, thirty years after the Nobel.',
         'В СССР роман вышел только в 1988 году, в журнале <em>«Новый мир»</em>, через тридцать лет после Нобелевской премии.'),
    (None, '[Une phrase du libraire&nbsp;: ce que la nouvelle traduction change à la lecture.]'):
        ('[A sentence from the bookseller: what the new translation changes for the reader.]',
         '[Фраза книготорговца: что меняет новый перевод.]'),
    (None, 'Une page · Alexandre Blok, 1912'):
        ('A page · Alexander Blok, 1912',
         'Страница · Александр Блок, 1912'),
    (None, 'Une rue la nuit&nbsp;: un réverbère, une pharmacie, le canal'):
        ('A street at night: a street lamp, a chemist’s, the canal',
         'Улица ночью: фонарь, аптека, канал'),
    (None, 'La nuit, la rue, le réverbère, la pharmacie&nbsp;; et dans l’eau du canal, les mêmes, à l’envers.'):
        ('The night, the street, the lamp, the chemist’s; and in the water of the canal, the same again, upside down.',
         'Ночь, улица, фонарь, аптека — и в воде канала то же самое, вверх ногами.'),
    (None, 'Nuit, rue, réverbère, pharmacie&nbsp;: quatre mots, et la ville de Blok est là. Le poème est écrit le 10 octobre 1912&nbsp;; il fait partie du cycle «&nbsp;Danses macabres&nbsp;».'):
        ('Night, street, lamp, chemist’s: four words, and Blok’s city is there. The poem was written on 10 October 1912; it belongs to the cycle “Dances of Death”.',
         'Ночь, улица, фонарь, аптека: четыре слова — и вот он, город Блока. Стихотворение написано 10 октября 1912 года и входит в цикл «Пляски смерти».'),
    (None, 'Les deux strophes se répondent&nbsp;: la seconde reprend la première à rebours, de la nuit au réverbère, comme un reflet dans l’eau glacée du canal. Vivre encore un quart de siècle n’y changera rien&nbsp;: tout recommencera.'):
        ('The two stanzas answer each other: the second takes up the first in reverse, from the night to the lamp, like a reflection in the icy water of the canal. Living another quarter of a century will change nothing: it will all begin again.',
         'Две строфы отвечают друг другу: вторая повторяет первую в обратном порядке, от ночи к фонарю, как отражение в ледяной воде канала. Проживи ещё хоть четверть века — ничего не изменится: всё повторится.'),
    (None, 'Blok est le grand poète du symbolisme russe. Six ans plus tard, en janvier 1918, il écrit <em>Les Douze</em>, le poème de la révolution&nbsp;: douze gardes rouges marchent dans Petrograd, sous la neige.'):
        ('Blok is the great poet of Russian Symbolism. Six years later, in January 1918, he wrote <em>The Twelve</em>, the poem of the revolution: twelve Red Guards march through Petrograd in the snow.',
         'Блок — главный поэт русского символизма. Через шесть лет, в январе 1918 года, он пишет <em>«Двенадцать»</em>, поэму о революции: двенадцать красногвардейцев идут по Петрограду под снегом.'),
    (None, 'En librairie'):
        ('At the bookshop',
         'В магазине'),
    (None, 'Couverture des Douze, d’Alexandre Blok'):
        ('Cover of The Twelve, by Alexander Blok',
         'Обложка поэмы Александра Блока «Двенадцать»'),
    (None, '<em>Les Douze</em>'):
        ('<em>The Twelve</em>',
         '<em>«Двенадцать»</em>'),
    (None, 'Alexandre Blok · le poème de janvier 1918'):
        ('Alexander Blok · the poem of January 1918',
         'Александр Блок · поэма января 1918 года'),
    (None, 'Et la traduction du libraire, vers à vers, dans <a href="%1$s">l’encyclopédie</a>.'):
        ('And the bookseller’s line-by-line translation, in <a href="%1$s">the encyclopedia</a>.',
         'И подстрочный перевод книготорговца — в <a href="%1$s">энциклопедии</a>.'),
    (None, 'Les vidéos du centre · Le Paris de l’émigration'):
        ('The centre’s videos · The Paris of the emigration',
         'Видео центра · Париж эмиграции'),
    (None, 'Mère Marie et ses compagnons au foyer de la rue de Lourmel&nbsp;; voir la vidéo'):
        ('Mother Maria and her companions at the rue de Lourmel hostel; watch the video',
         'Мать Мария и её товарищи в приюте на улице Лурмель; смотреть видео'),
    (None, 'Sainte Marie de Paris, deuxième à gauche, au foyer de la rue de Lourmel.'):
        ('Saint Maria of Paris, second from left, at the rue de Lourmel hostel.',
         'Святая Мария Парижская, вторая слева, в приюте на улице Лурмель.'),
    (None, 'La rue de Lourmel et mère Marie'):
        ('The rue de Lourmel and Mother Maria',
         'Улица Лурмель и мать Мария'),
    (None, 'Au 77, rue de Lourmel, dans le quinzième arrondissement, mère Marie Skobtsova ouvre en 1935 un foyer pour les plus pauvres de l’émigration&nbsp;: on y loge, on y mange, on y prie.'):
        ('At 77 rue de Lourmel, in the fifteenth arrondissement, Mother Maria Skobtsova opened a hostel in 1935 for the poorest of the emigration: people slept, ate and prayed there.',
         'В доме 77 на улице Лурмель, в пятнадцатом округе, мать Мария (Скобцова) открывает в 1935 году приют для самых бедных эмигрантов: здесь живут, едят и молятся.'),
    (None, 'Sous l’Occupation, la maison cache des Juifs et leur fournit de faux papiers. Arrêtée en 1943, mère Marie meurt à Ravensbrück en mars 1945. L’Église orthodoxe l’a canonisée en 2004.'):
        ('Under the Occupation the house hid Jews and gave them false papers. Arrested in 1943, Mother Maria died at Ravensbrück in March 1945. The Orthodox Church canonised her in 2004.',
         'Во время оккупации в доме прячут евреев и делают им фальшивые документы. Арестованная в 1943 году, мать Мария погибла в Равенсбрюке в марте 1945 года. В 2004 году Православная церковь причислила её к лику святых.'),
    (None, '[Une phrase&nbsp;: ce que la vidéo montre de la rue aujourd’hui.]'):
        ('[A sentence: what the video shows of the street today.]',
         '[Фраза: что видео показывает об улице сегодня.]'),
    (None, '[14 min] · Réalisée par [Prénom Nom], bénévole · [date]'):
        ('[14 min] · Made by [First name Surname], volunteer · [date]',
         '[14 мин] · Автор: [имя и фамилия], волонтёр · [дата]'),
    (None, 'Six séries, des rendez-vous'):
        ('Six series, regular slots',
         'Шесть циклов, регулярные выпуски'),
    (None, 'Plutôt que des vidéos isolées, des séries&nbsp;: on y revient, comme à une émission.'):
        ('Series rather than one-off videos: you come back to them, as you would to a programme.',
         'Не отдельные видео, а циклы: к ним возвращаешься, как к передаче.'),
    (None, 'Un livre, cinq minutes'):
        ('One book, five minutes',
         'Одна книга, пять минут'),
    (None, '[8] vidéos'):
        ('[8] videos',
         '[8] видео'),
    (None, 'Un lecteur, un livre de la librairie, cinq minutes pour donner envie.'):
        ('One reader, one book from the bookshop, five minutes to make you want it.',
         'Один читатель, одна книга из магазина и пять минут, чтобы её захотелось прочесть.'),
    (None, 'Le Paris de l’émigration'):
        ('The Paris of the emigration',
         'Париж эмиграции'),
    (None, '[4] vidéos'):
        ('[4] videos',
         '[4] видео'),
    (None, 'Un lieu par épisode&nbsp;: la rue Daru, la rue de Lourmel, Montparnasse, Sainte-Geneviève-des-Bois.'):
        ('One place per episode: the rue Daru, the rue de Lourmel, Montparnasse, Sainte-Geneviève-des-Bois.',
         'Одно место в каждом выпуске: улица Дарю, улица Лурмель, Монпарнас, Сент-Женевьев-де-Буа.'),
    (None, 'À voix haute'):
        ('Out loud',
         'Вслух'),
    (None, '[12] vidéos'):
        ('[12] videos',
         '[12] видео'),
    (None, 'Des poèmes et des pages lus en russe et en français, de Pouchkine à Blok.'):
        ('Poems and pages read in Russian and in French, from Pushkin to Blok.',
         'Стихи и страницы прозы, прочитанные по-русски и по-французски, от Пушкина до Блока.'),
    (None, 'Dans les archives'):
        ('In the archives',
         'В архиве'),
    (None, '[3] vidéos'):
        ('[3] videos',
         '[3] видео'),
    (None, 'On ouvre une boîte des archives d’YMCA-Press, et on raconte ce qu’on y trouve.'):
        ('We open a box from the YMCA-Press archives and tell what we find inside.',
         'Открываем коробку из архива YMCA-Press и рассказываем, что в ней нашлось.'),
    (None, 'Les entretiens'):
        ('Conversations',
         'Беседы'),
    (None, '[6] vidéos'):
        ('[6] videos',
         '[6] видео'),
    (None, 'Des traducteurs, des auteurs, des chercheurs, invités au centre.'):
        ('Translators, authors and researchers, invited to the centre.',
         'Переводчики, авторы, исследователи — гости центра.'),
    (None, 'Premiers pas en russe'):
        ('First steps in Russian',
         'Первые шаги в русском'),
    (None, '[10] vidéos'):
        ('[10] videos',
         '[10] видео'),
    (None, 'Des leçons courtes, pour accompagner les manuels de la librairie.'):
        ('Short lessons to go with the coursebooks sold at the bookshop.',
         'Короткие уроки в помощь учебникам из нашего магазина.'),
    (None, 'Toutes les vidéos'):
        ('All the videos',
         'Все видео'),
    (None, 'L’article du jour'):
        ('Article of the day',
         'Статья дня'),
    (None, 'L’Âge d’argent'):
        ('The Silver Age',
         'Серебряный век'),
    (None, 'On appelle <strong>Âge d’argent</strong> (<em lang="ru">Серебряный век</em>) la période de la poésie russe qui va des années 1890 à 1921, par opposition à l’«&nbsp;<em>Âge d’or</em>&nbsp;» de Pouchkine<sup>1</sup>. Trois courants s’y succèdent et s’y opposent&nbsp;: le <a href="%1$s">symbolisme</a> d’Alexandre Blok et d’Andreï Biély, l’<a href="%1$s">acméisme</a> de Nikolaï Goumilev, d’Anna Akhmatova et d’Ossip Mandelstam, qui veulent des mots clairs et des choses précises, et le <a href="%1$s">futurisme</a> de Khlebnikov et de Maïakovski.'):
        ('The <strong>Silver Age</strong> (<em lang="ru">Серебряный век</em>) is the name given to the period of Russian poetry from the 1890s to 1921, as opposed to Pushkin’s “<em>Golden Age</em>”<sup>1</sup>. Three movements followed and opposed one another: the <a href="%1$s">Symbolism</a> of Alexander Blok and Andrei Bely; the <a href="%1$s">Acmeism</a> of Nikolai Gumilev, Anna Akhmatova and Osip Mandelstam, who wanted clear words and precise things; and the <a href="%1$s">Futurism</a> of Khlebnikov and Mayakovsky.',
         '<strong>Серебряным веком</strong> (<em lang="ru">Серебряный век</em>) называют период русской поэзии с 1890-х годов до 1921 года, в отличие от пушкинского «<em>золотого века</em>»<sup>1</sup>. В нём сменяют друг друга и спорят три течения: <a href="%1$s">символизм</a> Александра Блока и Андрея Белого, <a href="%1$s">акмеизм</a> Николая Гумилёва, Анны Ахматовой и Осипа Мандельштама, которым нужны ясные слова и точные вещи, и <a href="%1$s">футуризм</a> Хлебникова и Маяковского.'),
    (None, 'La révolution en fait éclater la communauté. Blok salue Octobre dans <a href="%1$s"><em>Les Douze</em></a>, en janvier 1918&nbsp;; en août 1921, il meurt à Petrograd, et Goumilev est fusillé, accusé de complot<sup>2</sup>. Beaucoup de ceux qui restent sont réduits au silence&nbsp;; d’autres partent, et l’Âge d’argent se prolonge dans l’<a href="%2$s">émigration</a>, à Berlin, à Prague et à Paris.'):
        ('The revolution broke the community apart. Blok hailed October in <a href="%1$s"><em>The Twelve</em></a>, in January 1918; in August 1921 he died in Petrograd, and Gumilev was shot, accused of conspiracy<sup>2</sup>. Many of those who stayed were silenced; others left, and the Silver Age lived on in the <a href="%2$s">emigration</a>, in Berlin, Prague and Paris.',
         'Революция разрушила это сообщество. Блок приветствует Октябрь в поэме <a href="%1$s"><em>«Двенадцать»</em></a>, в январе 1918 года; в августе 1921 года он умирает в Петрограде, а Гумилёва расстреливают по обвинению в заговоре<sup>2</sup>. Многих из оставшихся заставили замолчать; другие уехали, и Серебряный век продолжился в <a href="%2$s">эмиграции</a> — в Берлине, Праге и Париже.'),
    (None, 'Portrait d’Anna Akhmatova par Natan Altman'):
        ('Portrait of Anna Akhmatova by Natan Altman',
         'Портрет Анны Ахматовой работы Натана Альтмана'),
    (None, 'Anna Akhmatova par Natan Altman, 1914&nbsp;: l’Âge d’argent, en un portrait.'):
        ('Anna Akhmatova by Natan Altman, 1914: the Silver Age in a single portrait.',
         'Анна Ахматова работы Натана Альтмана, 1914: Серебряный век в одном портрете.'),
    (None, 'Article détaillé&nbsp;: <a href="%1$s">L’Âge d’argent</a> · Voir aussi&nbsp;: <a href="%2$s">Alexandre Soljenitsyne</a>, <a href="%3$s">Andreï Platonov</a>'):
        ('Main article: <a href="%1$s">The Silver Age</a> · See also: <a href="%2$s">Alexander Solzhenitsyn</a>, <a href="%3$s">Andrei Platonov</a>',
         'Подробнее: <a href="%1$s">Серебряный век</a> · См. также: <a href="%2$s">Александр Солженицын</a>, <a href="%3$s">Андрей Платонов</a>'),
    (None, 'Le terme s’impose après coup&nbsp;; ses limites, surtout la date de départ, varient selon les historiens.'):
        ('The term took hold after the fact; its limits, especially its starting date, vary from one historian to another.',
         'Термин утвердился задним числом; его границы, особенно начальная дата, у разных историков разные.'),
    (None, 'L’«&nbsp;<em>affaire Tagantsev</em>&nbsp;»&nbsp;: Goumilev est réhabilité en 1992.'):
        ('The “<em>Tagantsev affair</em>”: Gumilev was rehabilitated in 1992.',
         '«<em>Дело Таганцева</em>»: Гумилёв реабилитирован в 1992 году.'),
    (None, 'Âge d’argent'):
        ('Silver Age',
         'Серебряный век'),
    (None, 'Période'):
        ('Period',
         'Период'),
    (None, 'vers 1890 – 1921'):
        ('c. 1890 – 1921',
         'около 1890 – 1921'),
    (None, 'Lieux'):
        ('Places',
         'Места'),
    (None, 'Saint-Pétersbourg, Moscou'):
        ('Saint Petersburg, Moscow',
         'Санкт-Петербург, Москва'),
    (None, 'Courants'):
        ('Movements',
         'Течения'),
    (None, 'symbolisme, acméisme, futurisme'):
        ('Symbolism, Acmeism, Futurism',
         'символизм, акмеизм, футуризм'),
    (None, 'Figures'):
        ('Key figures',
         'Имена'),
    (None, 'Blok, Biély, Akhmatova, Goumilev, Mandelstam, Tsvetaïeva, Maïakovski'):
        ('Blok, Bely, Akhmatova, Gumilev, Mandelstam, Tsvetaeva, Mayakovsky',
         'Блок, Белый, Ахматова, Гумилёв, Мандельштам, Цветаева, Маяковский'),
    (None, 'Fin'):
        ('End',
         'Конец'),
    (None, '1921&nbsp;: mort de Blok, exécution de Goumilev'):
        ('1921: death of Blok, execution of Gumilev',
         '1921: смерть Блока, расстрел Гумилёва'),
    (None, '[34] livres · [12] auteurs'):
        ('[34] books · [12] authors',
         '[34] книги · [12] авторов'),
    (None, 'Les livres cités, en librairie'):
        ('Books mentioned, at the bookshop',
         'Упомянутые книги в магазине'),
    (None, 'Les Douze'):
        ('The Twelve',
         'Двенадцать'),
    (None, 'Alexandre Blok'):
        ('Alexander Blok',
         'Александр Блок'),
    (None, 'Poésies choisies'):
        ('Selected Poems',
         'Избранные стихотворения'),
    (None, 'Nikolaï Goumilev'):
        ('Nikolai Gumilev',
         'Николай Гумилёв'),
    (None, 'Ekatérina Ivanovna, suivi de Requiem'):
        ('Ekaterina Ivanovna, followed by Requiem',
         'Екатерина Ивановна. Реквием'),
    (None, 'Léonid Andréïev'):
        ('Leonid Andreyev',
         'Леонид Андреев'),
    (None, '[Un livre d’Anna Akhmatova]'):
        ('[A book by Anna Akhmatova]',
         '[Книга Анны Ахматовой]'),
    (None, 'à choisir dans le catalogue'):
        ('to be chosen from the catalogue',
         'выбрать в каталоге'),
    (None, '[Un livre d’Ossip Mandelstam]'):
        ('[A book by Osip Mandelstam]',
         '[Книга Осипа Мандельштама]'),
    (None, '[Un livre d’Andreï Biély]'):
        ('[A book by Andrei Bely]',
         '[Книга Андрея Белого]'),
    (None, 'Le saviez-vous&nbsp;?'):
        ('Did you know?',
         'А вы знали?'),
    (None, '… que <a href="%1$s"><em>Le Docteur Jivago</em></a> de Pasternak a paru pour la première fois en italien, à Milan, en 1957, avant toute édition en russe&nbsp;?'):
        ('… that Pasternak’s <a href="%1$s"><em>Doctor Zhivago</em></a> was first published in Italian, in Milan, in 1957, before any edition in Russian?',
         '…что <a href="%1$s"><em>«Доктор Живаго»</em></a> Пастернака впервые вышел по-итальянски, в Милане, в 1957 году, раньше любого русского издания?'),
    (None, '… que <a href="%1$s"><em>Tchevengour</em></a>, écrit par Platonov à la fin des années 1920, n’a paru en entier en URSS qu’en 1988&nbsp;?'):
        ('… that <a href="%1$s"><em>Chevengur</em></a>, written by Platonov in the late 1920s, was not published in full in the USSR until 1988?',
         '…что <a href="%1$s"><em>«Чевенгур»</em></a>, написанный Платоновым в конце 1920-х, целиком вышел в СССР только в 1988 году?'),
    (None, '… que <a href="%1$s"><em>L’Archipel du Goulag</em></a> a paru d’abord en russe, à Paris, chez YMCA-Press, le 28 décembre 1973&nbsp;?'):
        ('… that <a href="%1$s"><em>The Gulag Archipelago</em></a> first came out in Russian, in Paris, from YMCA-Press, on 28 December 1973?',
         '…что <a href="%1$s"><em>«Архипелаг ГУЛАГ»</em></a> впервые вышел по-русски в Париже, в YMCA-Press, 28 декабря 1973 года?'),
    (None, '… que les <a href="%1$s"><em>Notes d’un médecin</em></a> de Veressaïev, sur les débuts d’un jeune médecin, firent scandale à leur parution, en 1901&nbsp;?'):
        ('… that Veresaev’s <a href="%1$s"><em>Memoirs of a Physician</em></a>, about a young doctor’s first years, caused a scandal when they came out in 1901?',
         '…что <a href="%1$s"><em>«Записки врача»</em></a> Вересаева, о первых шагах молодого врача, вызвали скандал, когда вышли в 1901 году?'),
    (None, 'En novembre'):
        ('In November',
         'В ноябре'),
    (None, '7 novembre 1917'):
        ('7 November 1917',
         '7 ноября 1917'),
    (None, 'La révolution d’Octobre (25 octobre dans le calendrier julien).'):
        ('The October Revolution (25 October in the Julian calendar).',
         'Октябрьская революция (25 октября по юлианскому календарю).'),
    (None, '9 novembre 1818'):
        ('9 November 1818',
         '9 ноября 1818'),
    (None, 'Naissance d’Ivan Tourguéniev, à Orel.'):
        ('Ivan Turgenev is born in Oryol.',
         'Родился Иван Тургенев, в Орле.'),
    (None, '9 novembre 1933'):
        ('9 November 1933',
         '9 ноября 1933'),
    (None, 'Ivan Bounine reçoit le prix Nobel de littérature, le premier écrivain russe à l’obtenir.'):
        ('Ivan Bunin is awarded the Nobel Prize in Literature, the first Russian writer to receive it.',
         'Иван Бунин получает Нобелевскую премию по литературе — первым из русских писателей.'),
    (None, '11 novembre 1821'):
        ('11 November 1821',
         '11 ноября 1821'),
    (None, 'Naissance de Fiodor Dostoïevski, à Moscou.'):
        ('Fyodor Dostoevsky is born in Moscow.',
         'Родился Фёдор Достоевский, в Москве.'),
    (None, '20 novembre 1910'):
        ('20 November 1910',
         '20 ноября 1910'),
    (None, 'Mort de Léon Tolstoï, à la gare d’Astapovo.'):
        ('Leo Tolstoy dies at Astapovo station.',
         'Умер Лев Толстой, на станции Астапово.'),
    (None, '28 novembre 1880'):
        ('28 November 1880',
         '28 ноября 1880'),
    (None, 'Naissance d’Alexandre Blok, à Saint-Pétersbourg.'):
        ('Alexander Blok is born in Saint Petersburg.',
         'Родился Александр Блок, в Санкт-Петербурге.'),
    (None, 'Dates du calendrier grégorien.'):
        ('Dates in the Gregorian calendar.',
         'Даты по григорианскому календарю.'),
    (None, 'Un livre, une vie · la librairie'):
        ('A book, a life · the bookshop',
         'Книга и жизнь · книжный магазин'),
    (None, 'Tchevengour'):
        ('Chevengur',
         'Чевенгур'),
    (None, 'Couverture de Tchevengour, d’Andreï Platonov'):
        ('Cover of Chevengur, by Andrei Platonov',
         'Обложка романа Андрея Платонова «Чевенгур»'),
    (None, 'Paru en URSS'):
        ('Published in the USSR',
         'Вышел в СССР'),
    (None, 'en entier, en 1988'):
        ('in full, in 1988',
         'целиком, в 1988 году'),
    (None, '[éditeur de la nouvelle édition]'):
        ('[publisher of the new edition]',
         '[издатель нового издания]'),
    (None, 'Andreï Platonov'):
        ('Andrei Platonov',
         'Андрей Платонов'),
    (None, 'nouvelle édition'):
        ('new edition',
         'новое издание'),
    (None, 'Platonov, dans l’encyclopédie'):
        ('Platonov, in the encyclopedia',
         'Платонов в энциклопедии'),
    (None, '<strong>En librairie</strong> <a href="%1$s">Boulgakov, <em>Le Maître et Marguerite</em></a>'):
        ('<strong>At the bookshop</strong> <a href="%1$s">Bulgakov, <em>The Master and Margarita</em></a>',
         '<strong>В магазине</strong> <a href="%1$s">Булгаков, <em>«Мастер и Маргарита»</em></a>'),
    (None, 'Écrit lui aussi dans les années 1930, sans espoir de publication&nbsp;; paru en URSS, censuré, en 1966-1967.'):
        ('Also written in the 1930s with no hope of publication; it came out in the USSR, censored, in 1966–1967.',
         'Тоже написан в 1930-е годы без надежды на публикацию; вышел в СССР с цензурными сокращениями в 1966–1967 годах.'),
    (None, '<strong>Vidéo</strong> <a href="%1$s">Zamiatine, <em>Nous</em>, vu par André Markowicz</a>'):
        ('<strong>Video</strong> <a href="%1$s">Zamyatin’s <em>We</em>, seen by André Markowicz</a>',
         '<strong>Видео</strong> <a href="%1$s">Замятин, <em>«Мы»</em>, глазами Андре Марковича</a>'),
    (None, 'Une autre utopie, écrite en 1920&nbsp;: refusée en URSS, elle paraît d’abord en anglais, à New York, en 1924.'):
        ('Another utopia, written in 1920: turned down in the USSR, it first came out in English, in New York, in 1924.',
         'Ещё одна утопия, написанная в 1920 году: отвергнутая в СССР, она сначала вышла по-английски, в Нью-Йорке, в 1924 году.'),
    (None, '<strong>Le fonds d’archives</strong> <a href="%1$s">1917, et après</a>'):
        ('<strong>The archives</strong> <a href="%1$s">1917 and after</a>',
         '<strong>Архив</strong> <a href="%1$s">1917-й и после</a>'),
    (None, 'La révolution vue par ceux qui l’ont vécue, de Blok à Platonov.'):
        ('The revolution seen by those who lived through it, from Blok to Platonov.',
         'Революция глазами тех, кто её пережил, от Блока до Платонова.'),
    (None, 'Le roman d’une utopie communiste dans un bourg de la steppe, que son auteur n’a jamais vu paraître en entier.'):
        ('The novel of a communist utopia in a steppe village, which its author never saw published in full.',
         'Роман о коммунистической утопии в степном городке, который автор так и не увидел изданным целиком.'),
    (None, 'Le soleil sur la steppe, un petit bourg'):
        ('The sun over the steppe, a small village',
         'Солнце над степью, маленький городок'),
    (None, 'Le bourg de Tchevengour&nbsp;: on a cessé de travailler, le soleil travaille pour tous.'):
        ('The village of Chevengur: people have stopped working, and the sun works for everyone.',
         'Чевенгур: люди перестали работать, солнце трудится за всех.'),
    (None, 'Platonov écrit Tchevengour à la fin des années 1920. Dans un bourg perdu de la steppe, une poignée de révolutionnaires décide que le communisme est arrivé&nbsp;: ils chassent les «&nbsp;bourgeois&nbsp;», cessent de travailler, et laissent le soleil travailler pour eux.'):
        ('Platonov wrote Chevengur in the late 1920s. In a remote steppe village, a handful of revolutionaries decide that communism has arrived: they drive out the “bourgeois”, stop working, and let the sun work for them.',
         'Платонов пишет «Чевенгур» в конце 1920-х годов. В затерянном степном городке горстка революционеров решает, что коммунизм наступил: они изгоняют «буржуев», перестают работать и предоставляют солнцу трудиться за них.'),
    (None, 'Sa langue, faite de mots de la propagande pris au pied de la lettre, est l’une des plus singulières de la littérature russe&nbsp;: on y entend à la fois l’espoir de la révolution et son absurdité.'):
        ('His language, made of propaganda words taken literally, is one of the most singular in Russian literature: in it you hear both the hope of the revolution and its absurdity.',
         'Его язык, сложенный из слов пропаганды, понятых буквально, — один из самых необычных в русской литературе: в нём слышны и надежда революции, и её абсурд.'),
    (None, 'Refusé de son vivant, le livre ne paraît en entier en URSS qu’en 1988, presque quarante ans après la mort de son auteur, en 1951.'):
        ('Rejected in his lifetime, the book did not appear in full in the USSR until 1988, almost forty years after its author’s death in 1951.',
         'Отвергнутая при жизни автора, книга целиком вышла в СССР только в 1988 году, почти через сорок лет после его смерти в 1951 году.'),
    # Une sélection, sous un titre (les compositions patterns/selection-*.php)
    (None, 'Une sélection · la librairie'):
        ('A selection · the bookshop',
         'Подборка · книжный магазин'),
    (None, 'Avant l’orage, 1901&nbsp;–&nbsp;1918'):
        ('Before the storm, 1901&nbsp;–&nbsp;1918',
         'Перед грозой, 1901&nbsp;–&nbsp;1918'),
    (None, 'Entre un médecin qui dit ses erreurs et douze gardes rouges dans la neige, les dernières années d’un monde, et l’une des époques les plus inventives de la littérature russe.'):
        ('Between a doctor admitting his mistakes and twelve Red Guards in the snow: the last years of a world, and one of the most inventive periods in Russian literature.',
         'Между врачом, который признаётся в своих ошибках, и двенадцатью красногвардейцами в снегу — последние годы целого мира и одна из самых изобретательных эпох русской литературы.'),
    (None, 'En 1901, un jeune médecin, Vikenti Veressaïev, raconte sans fard ses débuts à l’hôpital, ses doutes et ses erreurs&nbsp;: ses <em>Notes d’un médecin</em> font scandale. C’est le début d’un temps qu’on appellera après coup l’Âge d’argent, où la poésie, le théâtre et la prose russes se cherchent et se répondent.'):
        ('In 1901 a young doctor, Vikenty Veresaev, gave a frank account of his first years at the hospital, his doubts and his mistakes: his <em>Memoirs of a Physician</em> caused a scandal. It was the start of what would later be called the Silver Age, when Russian poetry, theatre and prose searched for new forms and answered one another.',
         'В 1901 году молодой врач Викентий Вересаев без прикрас рассказывает о своих первых шагах в больнице, о сомнениях и ошибках: его <em>«Записки врача»</em> вызывают скандал. Так начинается время, которое потом назовут Серебряным веком: русская поэзия, театр и проза ищут себя и откликаются друг другу.'),
    (None, 'Au théâtre, Léonid Andréïev met en scène des âmes troublées (<em>Ekatérina Ivanovna</em>, 1912). En poésie, Nikolaï Goumilev, avec Anna Akhmatova et Ossip Mandelstam, veut des mots clairs et des choses précises, contre le flou du symbolisme&nbsp;: c’est l’acméisme.'):
        ('In the theatre, Leonid Andreyev staged troubled souls (<em>Ekaterina Ivanovna</em>, 1912). In poetry, Nikolai Gumilev, with Anna Akhmatova and Osip Mandelstam, wanted clear words and precise things, against the vagueness of Symbolism: this was Acmeism.',
         'В театре Леонид Андреев выводит на сцену смятённые души (<em>«Екатерина Ивановна»</em>, 1912). В поэзии Николай Гумилёв вместе с Анной Ахматовой и Осипом Мандельштамом хочет ясных слов и точных вещей, против символистской размытости: это акмеизм.'),
    (None, 'En janvier 1918, Alexandre Blok salue la révolution dans <em>Les Douze</em>&nbsp;: douze gardes rouges marchent dans Petrograd, sous la neige, et le Christ marche devant eux. Trois ans plus tard, en août 1921, Blok meurt, et Goumilev est fusillé.'):
        ('In January 1918 Alexander Blok hailed the revolution in <em>The Twelve</em>: twelve Red Guards march through Petrograd in the snow, and Christ walks ahead of them. Three years later, in August 1921, Blok died and Gumilev was shot.',
         'В январе 1918 года Александр Блок приветствует революцию в поэме <em>«Двенадцать»</em>: двенадцать красногвардейцев идут по Петрограду под снегом, а впереди — Иисус Христос. Через три года, в августе 1921-го, Блок умирает, а Гумилёва расстреливают.'),
    (None, 'Ces cinq livres, tous à la librairie, en racontent les années, du cabinet du médecin aux rues de Petrograd. Le plus rare est une anthologie en russe, dont nous n’avons qu’un exemplaire&nbsp;: on peut venir la feuilleter.'):
        ('These five books, all at the bookshop, tell the story of those years, from the doctor’s consulting room to the streets of Petrograd. The rarest is an anthology in Russian, of which we have only one copy: come and browse it.',
         'Эти пять книг, все — в нашем магазине, рассказывают о тех годах, от кабинета врача до улиц Петрограда. Самая редкая — антология на русском языке, у нас всего один экземпляр: её можно прийти полистать.'),
    (None, 'Couverture&nbsp;: Notes d’un médecin'):
        ('Cover: Memoirs of a Physician',
         'Обложка: «Записки врача»'),
    (None, 'Vikenti Veressaïev · 1901'):
        ('Vikenty Veresaev · 1901',
         'Викентий Вересаев · 1901'),
    (None, 'Le livre qui fit scandale&nbsp;: un médecin dit ses doutes et ses erreurs.'):
        ('The book that caused a scandal: a doctor speaks of his doubts and mistakes.',
         'Книга, вызвавшая скандал: врач рассказывает о сомнениях и ошибках.'),
    (None, 'Couverture&nbsp;: Ekatérina Ivanovna, suivi de Requiem'):
        ('Cover: Ekaterina Ivanovna, followed by Requiem',
         'Обложка: «Екатерина Ивановна. Реквием»'),
    (None, 'Léonid Andréïev · 1912'):
        ('Leonid Andreyev · 1912',
         'Леонид Андреев · 1912'),
    (None, 'Le théâtre d’Andréïev&nbsp;: deux pièces, des âmes troublées.'):
        ('Andreyev’s theatre: two plays, troubled souls.',
         'Театр Андреева: две пьесы, смятённые души.'),
    (None, 'Couverture&nbsp;: Poésies choisies'):
        ('Cover: Selected Poems',
         'Обложка: «Избранные стихотворения»'),
    (None, 'L’acméisme&nbsp;: le mot exact, contre le flou du symbolisme.'):
        ('Acmeism: the exact word, against the vagueness of Symbolism.',
         'Акмеизм: точное слово против символистской размытости.'),
    (None, 'Couverture&nbsp;: Les Douze'):
        ('Cover: The Twelve',
         'Обложка: «Двенадцать»'),
    (None, 'Alexandre Blok · 1918'):
        ('Alexander Blok · 1918',
         'Александр Блок · 1918'),
    (None, 'La révolution en un poème&nbsp;: douze gardes rouges dans la neige.'):
        ('The revolution in one poem: twelve Red Guards in the snow.',
         'Революция в одной поэме: двенадцать красногвардейцев в снегу.'),
    (None, 'Anthologie · très rare'):
        ('Anthology · very rare',
         'Антология · большая редкость'),
    (None, 'Un seul exemplaire, à venir feuilleter à la librairie.'):
        ('A single copy, to come and browse at the bookshop.',
         'Единственный экземпляр — приходите полистать в магазин.'),
    (None, 'À voir, à écouter'):
        ('To watch, to listen to',
         'Смотреть и слушать'),
    (None, 'Voir la vidéo&nbsp;: Blok, lu en russe et en français'):
        ('Watch the video: Blok, read in Russian and in French',
         'Смотреть видео: Блок по-русски и по-французски'),
    (None, 'Vidéo · À voix haute'):
        ('Video · Out loud',
         'Видео · Вслух'),
    (None, 'Blok, lu en russe et en français'):
        ('Blok, read in Russian and in French',
         'Блок по-русски и по-французски'),
    (None, '[6 min] · [Prénom Nom], bénévole'):
        ('[6 min] · [First name Surname], volunteer',
         '[6 мин] · [имя и фамилия], волонтёр'),
    (None, '<strong>L’encyclopédie</strong> <a href="%1$s">L’Âge d’argent, l’article du jour</a>'):
        ('<strong>The encyclopedia</strong> <a href="%1$s">The Silver Age, article of the day</a>',
         '<strong>Энциклопедия</strong> <a href="%1$s">Серебряный век, статья дня</a>'),
    (None, '<strong>Podcast</strong> <a href="%1$s">[Un épisode sur Blok et Les Douze]</a>'):
        ('<strong>Podcast</strong> <a href="%1$s">[An episode on Blok and The Twelve]</a>',
         '<strong>Подкаст</strong> <a href="%1$s">[Выпуск о Блоке и «Двенадцати»]</a>'),
    (None, 'Les cinq livres'):
        ('The five books',
         'Пять книг'),
    (None, 'Toute la sélection, en librairie'):
        ('The whole selection, at the bookshop',
         'Вся подборка в магазине'),
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
            if e.msgid_plural:
                nouvelle.msgid_plural = e.msgid_plural
            cle = (e.msgctxt, e.msgid)
            if cle in T:
                traduction = T[cle][reglages['colonne']]
                if isinstance(traduction, tuple):  # un pluriel : une forme par cas de la langue
                    nouvelle.msgstr_plural = dict(enumerate(traduction))
                else:
                    nouvelle.msgstr = traduction
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
