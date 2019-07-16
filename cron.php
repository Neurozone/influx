<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Bramus\Router\Router();

function truncate($msg,$limit){
    $str = html_entity_decode($msg, ENT_QUOTES, 'UTF-8');
    $count = preg_match_all('/\X/u', $str);
    if($count<=$limit){
        return $msg;
    }
    $fin='…' ;
    $nb=$limit-1;
    return htmlentities(mb_substr($str, 0, $nb, 'UTF-8').$fin);
}

if (!ini_get('safe_mode')) @set_time_limit(0);

/*
 @nom: common
 @auteur: Idleman (http://blog.idleman.fr)
 @description: Page incluse dans tous (ou presque) les fichiers du projet, inclus les entitées SQL et récupère/traite les variables de requetes
 */
define('LEED_VERSION_NUMBER','1.8.3');
define('LEED_VERSION_NAME','stable');
/* Assure la compatibilité des greffons utilisant ces anciennes constantes.
 * Cela doit rester en place jusque Leed v2.0.
 */
if (!defined('VERSION_NUMBER')) define('VERSION_NUMBER', LEED_VERSION_NUMBER);
if (!defined('VERSION_NAME')) define('VERSION_NAME', LEED_VERSION_NAME);
/* ---------------------------------------------------------------- */
// Mise en place d'un timezone par default pour utiliser les fonction de date en php
$timezone_default = 'Europe/Paris'; // valeur par défaut :)
date_default_timezone_set($timezone_default);
$timezone_phpini = ini_get('date.timezone');
if (($timezone_phpini!='') && (strcmp($timezone_default, $timezone_phpini))) {
    date_default_timezone_set($timezone_phpini);
}
/* ---------------------------------------------------------------- */
$cookiedir = '';
if(dirname($_SERVER['SCRIPT_NAME'])!='/') $cookiedir=dirname($_SERVER["SCRIPT_NAME"]).'/';
session_set_cookie_params(0, $cookiedir);
session_start();
mb_internal_encoding('UTF-8'); // UTF8 pour fonctions mb_*
$start=microtime(true);
require_once('constant.php');
require_once('RainTPL.php');
require_once('i18n.php');
require_once('otphp/lib/otphp.php');
class_exists('Functions') or require_once('Functions.class.php');
class_exists('Plugin') or require_once('Plugin.class.php');
class_exists('MysqlEntity') or require_once('MysqlEntity.class.php');
class_exists('Update') or require_once('Update.class.php');
class_exists('Feed') or require_once('Feed.class.php');
class_exists('Event') or require_once('Event.class.php');
class_exists('User') or require_once('User.class.php');
class_exists('Folder') or require_once('Folder.class.php');
class_exists('Configuration') or require_once('Configuration.class.php');
class_exists('Opml') or require_once('Opml.class.php');
class_exists('Logger') or require_once('Logger.class.php');
//error_reporting(E_ALL);
//Calage de la date
date_default_timezone_set('Europe/Paris');
$configurationManager = new Configuration();
$conf = $configurationManager->getAll();
$theme = $configurationManager->get('theme');
//Instanciation du template
$tpl = new RainTPL();
//Definition des dossiers de template
raintpl::configure("base_url", null );
raintpl::configure("tpl_dir", './templates/'.$theme.'/' );
raintpl::configure("cache_dir", "./cache/tmp/" );
$resultUpdate = Update::ExecutePatch();
$userManager = new User();
$myUser = (isset($_SESSION['currentUser'])?unserialize($_SESSION['currentUser']):false);
if (empty($myUser)) {
    /* Pas d'utilisateur dans la session ?
     * On tente de récupérer une nouvelle session avec un jeton. */
    $myUser = User::existAuthToken();
    $_SESSION['currentUser'] = serialize($myUser);
}
$feedManager = new Feed();
$eventManager = new Event();
$folderManager = new Folder();
// Sélection de la langue de l'interface utilisateur
if (!$myUser) {
    $languages = Translation::getHttpAcceptLanguages();
} else {
    $languages = array($configurationManager->get('language'));
}
i18n_init($languages, dirname(__FILE__).'/templates/'.$theme.'/');
if ($resultUpdate) die (_t('LEED_UPDATE_MESSAGE'));
$view = '';
$tpl->assign('myUser',$myUser);
$tpl->assign('feedManager',$feedManager);
$tpl->assign('eventManager',$eventManager);
$tpl->assign('userManager',$userManager);
$tpl->assign('folderManager',$folderManager);
$tpl->assign('configurationManager',$configurationManager);
$tpl->assign('synchronisationCode',$configurationManager->get('synchronisationCode'));
//Récuperation et sécurisation de toutes les variables POST et GET
$_ = array();
foreach($_POST as $key=>$val){
    $_[$key]=Functions::secure($val, 2); // on ne veut pas d'addslashes
}
foreach($_GET as $key=>$val){
    $_[$key]=Functions::secure($val, 2); // on ne veut pas d'addslashes
}
$tpl->assign('_',$_);
$tpl->assign('action','');
//Inclusion des plugins
Plugin::includeAll();
// pour inclure aussi les traductions des plugins dans les js
$tpl->assign('i18n_js',$i18n_js);

$commandLine = 'cli' == php_sapi_name();

if ($commandLine) {
    $action = 'commandLine';
}

$syncCode = $configurationManager->get('synchronisationCode');
$syncGradCount = $configurationManager->get('syncGradCount');
if (!$commandLine
    && !(isset($_['code'])
        && $configurationManager->get('synchronisationCode') != null
        && $_['code'] == $configurationManager->get('synchronisationCode')
    )
) {
    die(_t('YOU_MUST_BE_CONNECTED_ACTION'));
}
Functions::triggerDirectOutput();

if (!$commandLine) {
    echo '<html>
                <head>
                <link rel="stylesheet" href="./templates/' . $theme . '/css/style.css">
                <meta name="referrer" content="no-referrer" />
                </head>
                <body>
                <div class="sync">';
}
$synchronisationType = $configurationManager->get('synchronisationType');

$synchronisationCustom = array();

if (isset($synchronisationCustom['type'])) {
    $feeds = $synchronisationCustom['feeds'];
    $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t($synchronisationCustom['type']);
} elseif ('graduate' == $synchronisationType) {
    // sélectionne les 10 plus vieux flux
    $feeds = $feedManager->loadAll(null, 'lastupdate', $syncGradCount);
    $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t('GRADUATE_SYNCHRONISATION');
} else {
    // sélectionne tous les flux, triés par le nom
    $feeds = $feedManager->populate('name');
    $syncTypeStr = _t('SYNCHRONISATION_TYPE') . ' : ' . _t('FULL_SYNCHRONISATION');
}

if (!isset($synchronisationCustom['no_normal_synchronize'])) {
    $feedManager->synchronize($feeds, $syncTypeStr, $commandLine, $configurationManager, $start);

    $currentDate = date('d/m/Y H:i:s');
    if (!$commandLine) {
        echo "<p>{$syncTypeStr} {$currentDate}</p>\n";
        echo "<dl>\n";
    } else {
        echo "{$syncTypeStr}\t{$currentDate}\n";
    }
    $maxEvents = $configurationManager->get('feedMaxEvents');
    $nbErrors = 0;
    $nbOk = 0;
    $nbTotal = 0;
    $localTotal = 0; // somme de tous les temps locaux, pour chaque flux
    $nbTotalEvents = 0;
    $syncId = time();
    $enableCache = ($configurationManager->get('synchronisationEnableCache')=='')?0:$configurationManager->get('synchronisationEnableCache');
    $forceFeed = ($configurationManager->get('synchronisationForceFeed')=='')?0:$configurationManager->get('synchronisationForceFeed');
    foreach ($feeds as $feed) {
        $nbEvents = 0;
        $nbTotal++;
        $startLocal = microtime(true);
        $parseOk = $feed->parse($syncId,$nbEvents, $enableCache, $forceFeed);
        $parseTime = microtime(true)-$startLocal;
        $localTotal += $parseTime;
        $parseTimeStr = number_format($parseTime, 3);
        if ($parseOk) { // It's ok
            $errors = array();
            $nbTotalEvents += $nbEvents;
            $nbOk++;
        } else {
            // tableau au cas où il arrive plusieurs erreurs
            $errors = array($feed->getError());
            $nbErrors++;
        }
        $feedName = truncate($feed->getName(),30);



        $feedUrl = $feed->getUrl();
        $feedUrlTxt = truncate($feedUrl, 30);

            echo date('d/m/Y H:i:s')."\t".$parseTimeStr."\t";
            echo "{$feedName}\t{$feedUrlTxt}\n";

        foreach($errors as $error) {
            if ($commandLine)
                echo "$error\n";
            else
                echo "<dd>$error</dd>\n";
        }

    }
    assert('$nbTotal==$nbOk+$nbErrors');
    $totalTime = microtime(true)-$start;
    assert('$totalTime>=$localTotal');
    $totalTimeStr = number_format($totalTime, 3);
    $currentDate = date('d/m/Y H:i:s');

        echo "\t{$nbErrors}\t"._t('ERRORS')."\n";
        echo "\t{$nbOk}\t"._t('GOOD')."\n";
        echo "\t{$nbTotal}\t"._t('AT_TOTAL')."\n";
        echo "\t$currentDate\n";
        echo "\t$nbTotalEvents\n";
        echo "\t{$totalTimeStr}\t"._t('SECONDS')."\n";


}



