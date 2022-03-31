# [LABO] EmailJSON

*A small library for reading and writing e-mails.*

--------------------------------

## Installation

Installer le module via composer :

`composer require jeremyd/emailjson`

## Utilisation

Utilisez ensuite la classe EmailJSON pour accéder à la boîte mail.

Par exemple :

```php
// Importez le module ici

$mailbox = new EmailJSON('myaddress@email.com', 'm0nM0t2Passe', ['port' => 143]);

$folderslist = $mailbox->getFolders(); // Récupère la liste des dossiers de la boîte mail

// Autres codes
```
