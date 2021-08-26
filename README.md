# API-BileMo

## Contexte

[![SymfonyInsight](https://insight.symfony.com/projects/e41230ed-3cc1-40f7-9ab6-5e66fc6bef7f/mini.svg)](https://insight.symfony.com/projects/e41230ed-3cc1-40f7-9ab6-5e66fc6bef7f)

Dans le cadre du projet 7 de la formation OpenClassrooms "Développeur d'application web" en spécialisation symfony, nous devions réaliser une API pour l'entreprise Bilemo

## Prérequis
PHP avec composer est requis pour pouvoir installer le projet. Un utilitaire afin de générer des clés SSH est requis.

## Installation
### Etape 1 :
Cloner le projet sur votre serveur avec cette commande :

git clone https://github.com/MeillatTristan/API-BileMo.git

### Etape 2 :
Ajouter vos informations de Databases dans le fichier .env et lancer la commande ``` php bin/console doctrine:database:create ```

### Etape 3 :
Générer vos clés SSH et ajouter les dans le dossier ``` config/JWT ``` sous le nom de private.pem et public.pem

### Etape 4 :
Installer les différentes dépendances du projet via Composer :

composer install

Le projet est maintenant fonctionnel, vous pouvez connaitre les différentes utilisations de l'API en vous rendant à l'URL /api/doc
