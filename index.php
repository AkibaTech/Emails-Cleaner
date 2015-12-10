<?php

/**
 * Emails-Cleaner
 *
 * Script plug'n'play "à-la-con" qui permet de lancer un vérification d'adresses emails en masse.
 * Ca prend 10 minutes à coder et ça fait son effet dans une boîte de comm' :p (Et pis ça réduit les erreurs dans les mailings...)
 *
 * @package  Emails-Cleaner
 * @author   Marceau Casals <marceau@casals.fr>
 * @support	 Le script est pas si compliqué, non?
 * @licence  WTFPL
 */

session_start();
require 'vendor/autoload.php';

/**
 * Définit le mot de passe pour le login. Hash md5.
 * Laisser vide pour désactiver la protection
 *
 * @const	string
 */
define('LOGIN_PASSWORD', '');

/**
 * Définit le nombre max d'emails à tester.
 * Mettre 0 pour désactiver la protection.
 *
 * @const	int
 */
define('MAX_EMAILS', 1000);

/**
 * Définit le temps souhaité (en secondes) pour la protection SPAM.
 * Mettre 0 pour désactiver la protection.
 *
 * @const	int
 */
define('SPAM_TIME', 5);

/**
 * Active ou non la vérification DNS des emails.
 * Beaucoup plus performant quand désactivé.
 *
 * @const	bool
 */
define('CHECK_DNS', true);

/** @var bool $logged */
$logged  = false;
/** @var bool $sent */
$sent    = false;
/** @var array $raw */
$raw     = [];
/** @var array $cleaned */
$cleaned = [];
/** @var array $errors */
$errors  = [];
/** @var int $count */
$count   = 0;
/** @var int $countc */
$countc  = 0;
/** @var bool $process */
$process = false;
/** @var bool $last */
$can     = false;
/** @var bool|string $message */
$message = false;

/*
 * L'utilisateur est-il connecté ?
 */
if ( $_SESSION['logged'] !== LOGIN_PASSWORD AND strlen(LOGIN_PASSWORD) > 0 ) {
    $logged = false;
} else {
    $logged = true;
}

/*
 * Vérif du loggin
 */
if ( !empty($_POST['password']) )
{
    if ( md5($_POST['password']) === LOGIN_PASSWORD )
    {
        $_SESSION['logged'] = LOGIN_PASSWORD;
        $logged = true;
    }
    else
    {
        $message = "Passe invalide !";
    }
}

/*
 * Le formulaire a t'il été envoyé ?
 */
if ( !empty($_POST['emails']) AND $logged )
{
    $sent  = true;
    $raw   = explode("\n", $_POST['emails']);
    $count = count($raw);

    // Protection...
    if ( isset($_SESSION['last']) )
    {
        $last = $_SESSION['last'];

        if ( $last > time() - SPAM_TIME ) {
            $can = false;
        } else {
            $can = true;
        }
    }
    else
    {
        $can = true;
    }

    // Quelques vérifications...
    if ( $count == 0 ) {
        $message = "Veuillez saisir un email par ligne.";
    } elseif ( $count > MAX_EMAILS && MAX_EMAILS > 0 ) {
        $message = "La liste peut contenir au maximum ".MAX_EMAILS." emails.";
    } elseif ( $can === false ) {
        $message = "Veuillez attendre ".SPAM_TIME." secondes entre chaque essai.";
    } else {
        $process = true;
    }

    // Nettoie sommairement le tableau... mais vraiment sommairement
    $raw  = array_map(function($e){
        return trim($e);
    }, $raw);

    // Zou !
    if ($process === true)
    {
        // Le vrai gars ici c'est lui :)
        $validator = new Egulias\EmailValidator\EmailValidator;

        // Brrbrbrbrrrrbrr (bruit de moteur pour les incultes !)
        foreach ($raw as $email)
        {
            // La ligne est vide :(
            if ( empty($email) ) {
                continue;
            }

            // L'email est doublon... Ca peut devenir vilain sur les grosses listes.
            if ( in_array($email, $cleaned, true) ) {
                continue;
            }

            if ( $validator->isValid($email, CHECK_DNS, true) ) {
                $cleaned[] = $email;
            } else {
                // On stocke les erreurs mais elles ne servent nulle part...
                $errors[$email] = ['error' => $validator->getError(), 'warning' => $validator->getWarnings()];
            }
        }

        // Petit enregistrement pour la protection SPAM (limitée au touristes qui ne savent pas effacer leurs cookies)
        if ( !empty(LOGIN_PASSWORD) ) {
            $_SESSION['last'] = time();
        }

        $countc = count($cleaned);
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emails Cleaner</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
    body {
        padding-top: 70px;
    }
    .container {
        max-width: 960px;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
              <a class="navbar-brand" href="/">Emails Cleaner</a>
            </div>
            <div id="navbar" class="collapse navbar-collapse"></div>
        </div>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col-xs-12">

                <?php if ( $logged === true ): ?>

                    <h1>
                        Nettoyer les emails en masse<br>
                        <small>Doublons, syntaxe invalide, comptes inexistants...</small>
                    </h1>
                    <hr>
                    <?php if ($sent === true && $message === false): ?>

                        <p>
                            Vous avez fourni <strong><?= $count; ?></strong> email(s), une fois nettoyée, la liste contient
                            <strong><?= $countc; ?></strong> email(s).<br>
                            <a href="/">Recommencer ?</a>
                        </p>

                        <?php if ( count($cleaned) > 0 ): ?>

                            <pre class="pre-scrollable"><?= implode(PHP_EOL, $cleaned); ?></pre>

                            <a class="btn btn-primary export" id="export" href="#export">Export CSV</a>

                        <?php else: ?>

                            <div class="alert alert-info">La liste fournie ne contient aucun email valide.</div>

                        <?php endif; ?>



                    <?php elseif ($sent === true && $message != false): ?>

                        <div class="alert alert-danger">
                            <?= $message; ?><br>
                            <a href="/">Recommencer ?</a>
                        </div>

                    <?php else: ?>

                        <form method="POST">
                            <div class="form-group">
                                <label for="emails">Emails</label>
                                <textarea id="emails" class="form-control" name="emails"
                                          placeholder="Liste des emails (un par ligne)"></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Nettoyer</button>
                            </div>
                        </form>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-sm-offset-3">
                            <div class="well well-sm">
                                <?php if ($message !== false): ?>

                                    <div class="alert alert-danger"><?= $message; ?></div>

                                <?php endif; ?>

                                <form method="POST">
                                    <div class="form-group">
                                        <label for="password">Mot de passe</label>
                                        <input class="form-control" type="password" name="password" id="password" />
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Connexion</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
        <hr>
        <footer>
            <p>&copy; <?= date('Y'); ?> <a href="https://marceau.casals.fr/">Marceau Casals</a></p>
        </footer>
    </div>
    <script type="text/javascript" src="assets/jquery.min.js"></script>
    <script type="text/javascript" src="assets/bootstrap.min.js"></script>
    <script type="text/javascript" src="assets/autogrow.min.js"></script>
    <script>
        $(function(){
            <?php /* Beurk ! Du PHP dans le JS ! */ ?>

            <?php if ( $sent === false ) : ?>
            if ( $('textarea').length > 0 ) {
                $('textarea').autogrow({
                    onInitialize: true,
                    animate: false
                });
            }
            <?php endif; ?>

            <?php if ( $sent === true && $countc > 0 ): ?>
            var csv = '<?= implode(',\n', $cleaned); ?>';

            $(".export").on('click', function (event) {
                csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

                $(this)
                    .attr({
                        'download': 'export-<?= $countc; ?>.csv',
                        'href': csvData,
                        'target': '_blank'
                    });
            });
            <?php endif; ?>
        });
    </script>
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</body>
</html>