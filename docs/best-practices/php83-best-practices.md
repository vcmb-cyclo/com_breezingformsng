# PHP 8.3 — Pratiques de code

- MUST écrire du PHP syntaxiquement compatible avec la version configurée (par défaut 8.3) et ne pas utiliser de fonctionnalités 8.4+.
- SHOULD utiliser typage des paramètres, retours, propriétés et `strict_types=1` dans les nouveaux modules lorsque les contrats Joomla/legacy le permettent.
- MUST ne pas changer la signature de méthode redéfinie sans vérifier le parent et les plugins appelants.
- SHOULD respecter PSR-4 et le style défini par le projet (PSR-12/PER si choisi), sans reformatter arbitrairement le legacy.
- MUST remplacer les suppressions d'erreur par une gestion explicite des exceptions et logs sûrs lorsque cela est demandé.
- SHOULD employer valeurs/objets immuables là où le domaine s'y prête; ne pas introduire d'abstraction non nécessaire.
- MUST vérifier les dépréciations et changements de types PHP affectant la base historique.
- SHOULD lancer `php -l`, analyse statique et tests sur chaque zone modifiée.

Références : https://www.php.net/manual/ ; https://www.php-fig.org/psr/psr-4/ ; https://www.php-fig.org/psr/psr-12/ ; https://www.php.net/supported-versions.php
