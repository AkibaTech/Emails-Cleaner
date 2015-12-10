# Emails-cleaner

Script plug'n'play "à-la-con" qui permet de lancer un vérification d'adresses emails en masse.

Nettoie : doublons, syntaxe invalide, domaines inexistants...

# Installation

Glissez-déposez le dossier où vous voulez.
Puis lancer un :

    composer install

# Congifuration

Les constantes sont en début de script.

### LOGIN_PASSWORD (string)
Hash MD5 du password pour l'accès à l'outil. Laissez vide pour désactiver l'authentification.
*Par défaut : Vide*

### MAX_EMAILS (int)
Nombre maximum d'emails à vérifier. Mettez 0 pour désactiver la limite.
Dangereux sur les gros volumes avec la vérification DNS.
*Par défaut : 1000*

### SPAM_TIME (int)
Temps en secondes entre chaque traitement. Utile si l'outil est publiquement accessible.
*Par défaut : 5*

### CHECK_DNS (bool)
Active ou non la vérification DNS de l'email fournie. C'est un peu l'intérêt de l'outil ! Mais c'est coûteux en performance.
*Par défaut : true*

# Licence
Le script est sous licence WTFPL "licence publique foutez-en ce que vous voulez".
En revanches les dépendances ne le sont pas.

# Remerciements
Merci à [egulias](https://github.com/egulias) pour sa librairie [EmailValidator](https://github.com/egulias/EmailValidator).