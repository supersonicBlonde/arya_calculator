# Arya NSK Calculator

Plugin WordPress autonome fournissant le shortcode `[arya_calculator]`.

## Installation

1. Copier le dossier `arya-nsk-calculator` dans `wp-content/plugins/`.
2. Activer **Arya NSK Calculator** dans l’administration WordPress.
3. Ouvrir **Réglages > Calculateur Arya** et vérifier l’adresse qui recevra les notifications.
4. Créer ou modifier une page puis y insérer `[arya_calculator]`.

## Utilisation

Le shortcode affiche un formulaire avec prénom, e-mail, date de naissance et consentement obligatoire. Après validation, le résultat adulte et enfant est affiché sur la même page.

Une notification est envoyée seulement à l’adresse définie dans les réglages. Aucun e-mail n’est envoyé au visiteur. Les données de formulaire ne sont pas enregistrées par le plugin.

Si le champ anti-spam caché est renseigné, le résultat reste visible mais aucune notification n’est envoyée. En cas d’échec de `wp_mail()`, le résultat reste également visible sans message destiné au visiteur.

## Règles métier

La logique est centralisée dans `includes/class-arya-nsk-calculator.php` :

- `adult_number_for_year()` calcule le chiffre adulte ;
- `child_number_for()` contient les tranches du tableau enfant ;
- `element_names()` contient la table chiffre → élément.

Modifier uniquement ces méthodes si les règles ou correspondances changent. La formule utilise `10 - chiffre` jusqu’en 2000 inclus, `11 - chiffre` à partir de 2001, puis réduit le résultat final à un chiffre (`10` devient `1`).

## Tests

Les tests de la logique métier ne nécessitent ni WordPress ni dépendance externe :

```bash
php wp-content/plugins/arya-nsk-calculator/tests/test-calculator.php
```

Ils couvrent les exemples fournis, la correction de l’élément `6 → Métal`, le passage à 2001, les trois colonnes du tableau et chaque borne de tranche de date.

## Vérifications manuelles

1. Vérifier que le formulaire est utilisable sur mobile et au clavier.
2. Soumettre une date invalide, future, un e-mail invalide et un consentement non coché : chaque erreur doit être claire.
3. Soumettre `03/03/1994` : résultat adulte `6 Métal`, enfant `5 Terre`.
4. Vérifier la réception de l’e-mail administratif et son contenu.
5. Vérifier qu’aucun e-mail n’est reçu par le visiteur.
6. Remplir le champ honeypot avec les outils du navigateur : le résultat doit apparaître mais aucune notification ne doit être expédiée.
