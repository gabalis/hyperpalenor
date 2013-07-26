
v0.15
=====
* does not redirect to same address as original, to prevent loops, if ever it wanted to. 



v0.14 - 2011-02-23
=====
* Maintained from now on by Matthieu Baudoux 

* Bugfix so the plugin works under PHP 5.3 and MODx 1.0.5


v0.13
=====
déplacement de la fonction "debugLog()" en début de document


v0.12
=====
vérification de l'existence de fonctions (plugin appelé plusieurs fois de suite ?)


v0.11
=====
nouvelles chaines traduites


v0.10
=====
gestion du déplacement avec le menu "déplacer" ("new_parent" et pas "parent" à récupérer en POST)


v0.9
=====
correction pour compatibilité avec PHP 5


v0.8
=====
amélioration de la recherche d'url avec "/" et avec préfixe et suffixe


v0.7
=====
changement du traitement des redirections manuelles pour permettre les parametres


v0.6
=====
enregistrement des urls qui ne mènent à rien dans la base de données


V0.5
=====
on n'utilise plus la redirection MODx qui renvoit le header 302, on renvoit le 301


v0.4
=====
ajout du créateur de la redirection dans la table des redirections


v0.3
=====
gestion de la suppression et de l'ajout de redirections (OnBeforeDocFormSave)


v0.2
=====
affichage des redirections dans le formulaire d'édition (OnDocFormRender)
 

v0.1
====
Original idea by Matthieu Baudoux, created and maintained from June 20, 2007 by Benjamin Toussaint
