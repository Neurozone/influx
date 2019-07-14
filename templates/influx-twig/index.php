<?php

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Sinergi\BrowserDetector\Language;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$stream = new StreamHandler(__DIR__ . '/logs/influx.log', Logger::DEBUG);
$logger = new Logger('influxLogger');
$logger->pushHandler($stream);
$logger->info('Influx started');

$templatePath = __DIR__ . '/templates/influx-twig';

$loader = new \Twig\Loader\FilesystemLoader($templatePath);
$twig = new \Twig\Environment($loader, [__DIR__ . '/cache' => 'cache', 'debug' => true,]);
$twig->addExtension(new Umpirsky\Twig\Extension\PhpFunctionExtension());
$twig->addExtension(new \Twig\Extension\DebugExtension());

/*
spl_autoload_register(function ($className) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/' . $className . '.php';
});
*/
// Create Router instance
$router = new \Bramus\Router\Router();

if (!file_exists('conf/config.php')) {
    header('location: install.php');
    exit();
}

require_once('conf/config.php');

if (!defined('VERSION_NUMBER')) define('VERSION_NUMBER', LEED_VERSION_NUMBER);
if (!defined('VERSION_NAME')) define('VERSION_NAME', LEED_VERSION_NAME);

/* ---------------------------------------------------------------- */
// Mise en place d'un timezone par default pour utiliser les fonction de date en php
$timezone_default = 'Europe/Paris'; // valeur par défaut :)
date_default_timezone_set($timezone_default);
$timezone_phpini = ini_get('date.timezone');
if (($timezone_phpini != '') && (strcmp($timezone_default, $timezone_phpini))) {
    date_default_timezone_set($timezone_phpini);
}
/* ---------------------------------------------------------------- */


/* ---------------------------------------------------------------- */
$db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
$db->set_charset('utf8mb4');
$db->query('SET NAMES utf8mb4');

/* ---------------------------------------------------------------- */
$query_configuration = 'select * from leed_configuration';
$result_configuration = $db->query($query_configuration);

while ($row = $result_configuration->fetch_array()) {
    $config[$row['key']] = $row['value'];
}

/* ---------------------------------------------------------------- */
$language = new Language();

if ($language->getLanguage() == $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = $language->getLanguage();
    $l_trans = json_decode(file_get_contents('locales/' . $config['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx-twig/locales/' . $config['language'] . '.json'), true);
} elseif ($language->getLanguage() != $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = $language->getLanguage();
    $l_trans = json_decode(file_get_contents('locales/' . $config['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx-twig/locales/' . $config['language'] . '.json'), true);
} elseif (!is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = 'en';
    $l_trans = json_decode(file_get_contents('locales/' . $_SESSION['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx-twig/locales/' . $_SESSION['language'] . '.json'), true);
}

$trans = array_merge($l_trans, $l_trans2);


/* ---------------------------------------------------------------- */

$cookiedir = '';
if (dirname($_SERVER['SCRIPT_NAME']) != '/') $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
session_set_cookie_params(0, $cookiedir);
session_start();
mb_internal_encoding('UTF-8'); // UTF8 pour fonctions mb_*
$start = microtime(true);
date_default_timezone_set('Europe/Paris');
/* ---------------------------------------------------------------- */

//$configurationManager = new Configuration();
//$conf = $configurationManager->getAll();
//$theme = $configurationManager->get('theme');

//$logger->info('templates/' . $theme . '/');

//$userManager = new User();
//$myUser = (isset($_SESSION['currentUser']) ? unserialize($_SESSION['currentUser']) : false);


//$feedManager = new Feed();
//$eventManager = new Event();
//$folderManager = new Folder();

//recuperation de tous les flux

//utilisé pour récupérer le statut d'un feed dans le template (en erreur ou ok)
//$feedState = new Feed();

$articleDisplayAuthor = $config['articleDisplayAuthor'];
$articleDisplayDate = $config['articleDisplayDate'];
$articleDisplayFolderSort = $config['articleDisplayFolderSort'];
$articleDisplayHomeSort = $config['articleDisplayHomeSort'];
$articleDisplayLink = $config['articleDisplayLink'];
$articleDisplayMode = $config['articleDisplayMode'];
$articlePerPages = $config['articlePerPages'];
$displayOnlyUnreadFeedFolder = $config['displayOnlyUnreadFeedFolder'];
if (!isset($displayOnlyUnreadFeedFolder)) $displayOnlyUnreadFeedFolder = false;
($displayOnlyUnreadFeedFolder == 'true') ? $displayOnlyUnreadFeedFolder_reverse = 'false' : $displayOnlyUnreadFeedFolder_reverse = 'true';
$optionFeedIsVerbose = $config['optionFeedIsVerbose'];

$target = '`' . MYSQL_PREFIX . 'event`.`title`,`' . MYSQL_PREFIX . 'event`.`unread`,`' . MYSQL_PREFIX . 'event`.`favorite`,`' . MYSQL_PREFIX . 'event`.`feed`,';
if ($articleDisplayMode == 'summary') $target .= '`' . MYSQL_PREFIX . 'event`.`description`,';
if ($articleDisplayMode == 'content') $target .= '`' . MYSQL_PREFIX . 'event`.`content`,';
if ($articleDisplayLink) $target .= '`' . MYSQL_PREFIX . 'event`.`link`,';
if ($articleDisplayDate) $target .= '`' . MYSQL_PREFIX . 'event`.`pubdate`,';
if ($articleDisplayAuthor) $target .= '`' . MYSQL_PREFIX . 'event`.`creator`,';
$target .= '`' . MYSQL_PREFIX . 'event`.`id`';

$pagesArray = array();

$wrongLogin = !empty($wrongLogin);
$filter = array('unread' => 1);
/*
 * if ($optionFeedIsVerbose) {
    $numberOfItem = $eventManager->rowCount($filter);
} else {
    $numberOfItem = $eventManager->getEventCountNotVerboseFeed();
}

$page = (isset($_['page']) ? $_['page'] : 1);
$pages = ($articlePerPages > 0 ? ceil($numberOfItem / $articlePerPages) : 1);
$startArticle = ($page - 1) * $articlePerPages;


if ($articleDisplayHomeSort) {
    $order = 'pubdate desc';
} else {
    $order = 'pubdate asc';
}
if ($optionFeedIsVerbose) {
    $events = $eventManager->loadAllOnlyColumn($target, $filter, $order, $startArticle . ',' . $articlePerPages);
} else {
    $events = $eventManager->getEventsNotVerboseFeed($startArticle, $articlePerPages, $order, $target);
}


*/
$paginationScale = $config['paginationScale'];
if (empty($paginationScale)) {

    $paginationScale = 5;
}
/*
for ($i = ($page - $paginationScale <= 0 ? 1 : $page - $paginationScale); $i < ($page + $paginationScale > $pages + 1 ? $pages + 1 : $page + $paginationScale); $i++) {
    $pagesArray[] = $i;
}
*/

/*
$previousPages = $page - $paginationScale < 0 ? -1 : $page - $paginationScale - 1;
$nextPages = $page + $paginationScale > $pages + 1 ? -1 : $page + $paginationScale;
*/

$scroll = false;
$unreadEventsForFolder = 0;
$hightlighted = 0;
//recuperation de tous les flux par dossier

//Recuperation de tous les non Lu
//$unread = $feedManager->countUnreadEvents();
$synchronisationCode = $config['synchronisationCode'];

//$allEvents = $eventManager->getEventCountPerFolder();

$results = $db->query('SELECT COUNT(leed_event.id),leed_feed.folder FROM leed_event INNER JOIN leed_feed ON leed_event.feed = leed_feed.id WHERE leed_event.unread =1 GROUP BY leed_feed.folder');
while ($item = $results->fetch_array()) {
    $events[$item[1]] = intval($item[0]);
}

$allEvents = $events;

$results = $db->query("SELECT leed_feed.name AS name, leed_feed.id AS id, leed_feed.url AS url, leed_folder.id AS folder 
FROM leed_feed 
    INNER JOIN leed_folder ON leed_feed.folder = leed_folder.id 
ORDER BY leed_feed.name");

if ($results != false) {
    while ($item = $results->fetch_array()) {
        $name = $item['name'];
        $feedsIdMap[$item['id']]['name'] = $name;


        $feedsFolderMap[$item['folder']][$item['id']]['id'] = $item['id'];
        $feedsFolderMap[$item['folder']][$item['id']]['name'] = $name;
        $feedsFolderMap[$item['folder']][$item['id']]['url'] = $item['url'];

    }
}
$feeds['folderMap'] = $feedsFolderMap;
$feeds['idMap'] = $feedsIdMap;

$allFeeds = $feeds;
$allFeedsPerFolder = $allFeeds['folderMap'];

$results = $db->query('SELECT * FROM leed_folder ORDER BY name ');
while ($rows = $results->fetch_array()) {

    $resultsUnreadByFolder = $db->query('SELECT count(*) as unread
FROM leed_event le 
    inner join leed_feed lfe on le.feed = lfe.id 
    inner join leed_folder lfo on lfe.folder = lfo.id  
where unread = 1 and lfo.id = ' . $rows['id']);

    while ($rowsUnreadByFolder = $resultsUnreadByFolder->fetch_array()) {
        $unreadEventsByFolder = $rowsUnreadByFolder['unread'];
    }

    $resultsFeedsByFolder = $db->query('SELECT fe.id as feed_id, fe.name as feed_name, fe.description as feed_description, fe.website as feed_website, fe.url as feed_url, fe.lastupdate as feed_lastupdate, fe.lastSyncInError as feed_lastSyncInError 
FROM leed_folder f 
    inner join leed_feed fe on fe.folder = f.id 
where f.id = ' . $rows['id']);


    while ($rowsFeedsByFolder = $resultsFeedsByFolder->fetch_array()) {

        $resultsUnreadByFeed = $db->query('SELECT count(*) as unread FROM leed_folder f inner join leed_feed fe on fe.folder = f.id inner join leed_event e on e.feed = fe.id  where e.unread = 1 and fe.id = ' . $rowsFeedsByFolder['feed_id']);

        $unreadEventsByFeed = 0;

        while ($rowsUnreadByFeed = $resultsUnreadByFeed->fetch_array()) {
            $unreadEventsByFeed = $rowsUnreadByFeed['unread'];
        }

        $feedsByFolder[] = array('id' => $rowsFeedsByFolder['feed_id'],
            'name' => $rowsFeedsByFolder['feed_name'],
            'description' => $rowsFeedsByFolder['feed_description'],
            'website' => $rowsFeedsByFolder['feed_website'],
            'url' => $rowsFeedsByFolder['feed_url'],
            'lastupdate' => $rowsFeedsByFolder['feed_lastupdate'],
            'lastSyncInError' => $rowsFeedsByFolder['feed_lastSyncInError'],
            'unread' => $unreadEventsByFeed
        );
    }

    $folders[] = array(
        'id' => $rows['id'],
        'name' => $rows['name'],
        'parent' => $rows['parent'],
        'isopen' => $rows['isopen'],
        'unread' => $unreadEventsByFolder,
        'feeds' => $feedsByFolder
    );

    $feedsByFolder = null;
}


$page = (isset($_GET['page']) ? $_['page'] : 1);

$events = '';

$articleDisplayMode = $config['articleDisplayMode'];


$router->before('GET|POST|PUT|DELETE|PATCH|OPTIONS', '.*', function () use ($logger) {
    $logger->info($_SERVER['REQUEST_URI']);
});

$router->get('/', function () use (
    $twig, $logger,
    //$feedState,
    //$nextPages,
    //$previousPages,
    $scroll,
    //$eventManager,
    $unreadEventsForFolder,
    $optionFeedIsVerbose,
    $articlePerPages,
    $articleDisplayHomeSort,
    $articleDisplayFolderSort,
    $articleDisplayAuthor,
    $articleDisplayLink,
    $articleDisplayDate,
    $articleDisplayMode,
    $target,
    $displayOnlyUnreadFeedFolder_reverse,
    $displayOnlyUnreadFeedFolder,
    $folders,
    $allEvents,
    //$unread,
    $allFeedsPerFolder,
    $config,
    $db,
    $trans
) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }
    $action = '';

    $wrongLogin = !empty($wrongLogin);

    $filter = array('unread' => 1);
    $resultsNbUnread = $db->query('SELECT count(*) as nb_items from leed_event where unread = 1');
    $numberOfItem = 0;

    while ($rows = $resultsNbUnread->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ($articlePerPages > 0 ? ceil($numberOfItem / $articlePerPages) : 1);
    $startArticle = ($page - 1) * $articlePerPages;
    $order = 'pubdate desc';


    $results = $db->query('SELECT le.id, le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM leed_event le inner join leed_feed lf on lf.id = le.feed where unread = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['id'],
            'guid' => $rows['guid'],
            'title' => $rows['title'],
            'creator' => $rows['creator'],
            'content' => $rows['content'],
            'description' => $rows['description'],
            'link' => $rows['link'],
            'unread' => $rows['unread'],
            'feed' => $rows['feed'],
            'favorite' => $rows['favorite'],
            'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
            'syncId' => $rows['syncId'],
            'feed_name' => $rows['feed_name'],
        );

    }

    if (!isset($_SESSION['user'])) {
        header('location: /login');
    }

    echo $twig->render('index.twig',
        [
            'action' => $action,
            'allEvents' => $allEvents,
            'allFeedsPerFolder' => $allFeedsPerFolder,
            'articleDisplayFolderSort' => $articleDisplayFolderSort,
            'articleDisplayHomeSort' => $articleDisplayHomeSort,
            'articleDisplayAuthor' => $articleDisplayAuthor,
            'articleDisplayLink' => $articleDisplayLink,
            'articleDisplayDate' => $articleDisplayDate,
            'articleDisplayMode' => $articleDisplayMode,
            'articlePerPages' => $articlePerPages,
            'displayOnlyUnreadFeedFolder_reverse' => $displayOnlyUnreadFeedFolder_reverse,
            'displayOnlyUnreadFeedFolder' => $displayOnlyUnreadFeedFolder,
            //'eventManager' => $eventManager,
            'events' => $events,
            //'feedState' => $feedState,
            'folders' => $folders,
            //'functions' => New Functions(),
            //'nextPages' => $nextPages,
            'numberOfItem' => $numberOfItem,
            'optionFeedIsVerbose' => $optionFeedIsVerbose,
            //'previousPages' => $previousPages,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'target' => $target,
            //'unread' => $unread,
            'wrongLogin' => $wrongLogin,
            'trans' => $trans
        ]
    );

});

$router->get('/login', function () use ($twig) {
    echo $twig->render('login.html');
});

$router->post('/login', function () use ($db, $config, $logger) {

    define('RESET_PASSWORD_FILE', 'resetPassword');
    if (file_exists(RESET_PASSWORD_FILE)) {

        @unlink(RESET_PASSWORD_FILE);
        if (file_exists(RESET_PASSWORD_FILE)) {
            $message = 'Unable to remove "' . RESET_PASSWORD_FILE . '"!';

        } else {
            $resetPassword = $_POST['password'];
            assert('!empty($resetPassword)');
            $tmpUser = User::get($_POST['login']);
            if (false === $tmpUser) {
                $message = "Unknown user '{$_POST['login']}'! No password reset.";
            } else {
                $tmpUser->resetPassword($resetPassword, $config['cryptographicSalt']);
                $message = "User '{$_POST['login']}' (id={$tmpUser->getId()}) Password reset to '$resetPassword'.";
            }
        }
        error_log($message);
    }

    $salt = $config['cryptographicSalt'];

    if ($stmt = $db->prepare("select id,login,password from leed_user where login = ? and password = ?")) {
        $stmt->bind_param("ss", $_POST['login'], sha1($_POST['password'] . $salt));
        /* execute query */
        $stmt->execute();

        /* instead of bind_result: */
        $result = $stmt->get_result();

        while ($row = $result->fetch_array()) {
            $_SESSION['user'] = $row['login'];
            $_SESSION['userid'] = $row['id'];
            $user = $row['login'];
        }
    }


    if ($user == false) {
        $logger->info("wrong login for '" . $_POST['login'] . "'");
        header('location: /login');
    } else {
        $_SESSION['currentUser'] = $user;
        if (isset($_POST['rememberMe'])) {
            setcookie('StaySignedIn', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }
        header('location: /');
    }
    exit();


});

$router->get('/logout', function () {

    //User::delStayConnected();
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('location: /');

});


$router->get('/favorites', function () use (
    $twig, $logger,
    //$feedState,
    //$nextPages,
    //$previousPages,
    $scroll,
    //$eventManager,
    $unreadEventsForFolder,
    $optionFeedIsVerbose,
    $articlePerPages,
    $articleDisplayHomeSort,
    $articleDisplayFolderSort,
    $articleDisplayAuthor,
    $articleDisplayLink,
    $articleDisplayDate,
    $articleDisplayMode,
    $target,
    $displayOnlyUnreadFeedFolder_reverse,
    $displayOnlyUnreadFeedFolder,
    $folders,
    $allEvents,
    //$unread,
    $allFeedsPerFolder,
    $config,
    $db
) {

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items from leed_event where favorite = 1');
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }
    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_['page']) ? $_['page'] : 1);
    $pages = ceil($numberOfItem / $articlePerPages);
    $startArticle = ($page - 1) * $articlePerPages;
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.id, le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM leed_event le inner join leed_feed lf on lf.id = le.feed where favorite = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['id'],
            'guid' => $rows['guid'],
            'title' => $rows['title'],
            'creator' => $rows['creator'],
            'content' => $rows['content'],
            'description' => $rows['description'],
            'link' => $rows['link'],
            'unread' => $rows['unread'],
            'feed' => $rows['feed'],
            'favorite' => $rows['favorite'],
            'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
            'syncId' => $rows['syncId'],
            'feed_name' => $rows['feed_name'],
        );

    }

    echo $twig->render('index.twig',
        [
            //'action' => $action,
            'allEvents' => $allEvents,
            'allFeedsPerFolder' => $allFeedsPerFolder,
            'articleDisplayFolderSort' => $articleDisplayFolderSort,
            'articleDisplayHomeSort' => $articleDisplayHomeSort,
            'articleDisplayAuthor' => $articleDisplayAuthor,
            'articleDisplayLink' => $articleDisplayLink,
            'articleDisplayDate' => $articleDisplayDate,
            'articleDisplayMode' => $articleDisplayMode,
            'articlePerPages' => $articlePerPages,
            'displayOnlyUnreadFeedFolder_reverse' => $displayOnlyUnreadFeedFolder_reverse,
            'displayOnlyUnreadFeedFolder' => $displayOnlyUnreadFeedFolder,
            //'eventManager' => $eventManager,
            'events' => $events,
            //'feedState' => $feedState,
            'folders' => $folders,
            'functions' => New Functions(),
            'nextPages' => $nextPages,
            'numberOfItem' => $numberOfItem,
            'optionFeedIsVerbose' => $optionFeedIsVerbose,
            'previousPages' => $previousPages,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'target' => $target,
            'unread' => $unread,
            //$unreadEventsForFolder,
            //'wrongLogin' => $wrongLogin,
        ]
    );


});




$router->mount('/settings', function () use ($router, $twig, $myUser, $configurationManager, $feedManager, $folderManager, $cookiedir, $eventManager,$logger) {

    $router->get('/', function () use ($twig , $myUser, $configurationManager, $feedManager, $folderManager, $cookiedir) {

        header('location: /settings/manage');

    });

    $router->get('/plugin/changeState/{name}/state/{state}', function ($name,$state) {

        if ($state == '0')
        {
            Plugin::enabled($name);
        }
        else
        {
            Plugin::disabled($name);
        }
        header('location: /settings/plugins');

    });

    $router->get('/{option}', function ($option) use ($twig, $myUser, $configurationManager, $feedManager, $folderManager, $logger,$cookiedir) {

        //$serviceUrl', rtrim($_SERVER['HTTP_HOST'] . $cookiedir, '/'));

        //$logger = new Logger('settings');



        $wrongLogin = !empty($wrongLogin);


        // gestion des thèmes
        $themesDir = 'templates/';
        $dirs = scandir($themesDir);
        foreach ($dirs as $dir) {
            if (is_dir($themesDir . $dir) && !in_array($dir, array(".", ".."))) {
                $themeList[] = $dir;
            }
        }
        sort($themeList);
        //$themeList', $themeList);
        //$currentTheme', $configurationManager->get('theme'));
        //$feeds', $feedManager->populate('name'));
        $folders', $folderManager->populate('name'));
        $synchronisationType', $configurationManager->get('synchronisationType'));
        $synchronisationEnableCache', $configurationManager->get('synchronisationEnableCache'));
        $synchronisationForceFeed', $configurationManager->get('synchronisationForceFeed'));
        $articleDisplayAnonymous', $configurationManager->get('articleDisplayAnonymous'));
        $articleDisplayLink', $configurationManager->get('articleDisplayLink'));
        $articleDisplayDate', $configurationManager->get('articleDisplayDate'));
        $articleDisplayAuthor', $configurationManager->get('articleDisplayAuthor'));
        $articleDisplayHomeSort', $configurationManager->get('articleDisplayHomeSort'));
        $articleDisplayFolderSort', $configurationManager->get('articleDisplayFolderSort'));
        $articleDisplayMode', $configurationManager->get('articleDisplayMode'));
        $optionFeedIsVerbose', $configurationManager->get('optionFeedIsVerbose'));

        $otpEnabled', false);
        $section', $option);

        $logger->info('Section: ' . $option);
        $logger->info('User: ' . $myUser->getLogin());

        //Suppression de l'état des plugins inexistants
        Plugin::pruneStates();

        $plugins', Plugin::getAll());

        $tpl->draw('settings');

    });

    $router->get('/synchronize', function ($option) use ($tpl, $i18n, $myUser, $configurationManager, $feedManager, $folderManager, $cookiedir) {

        if (isset($myUser) && $myUser != false) {
            $syncCode = $configurationManager->get('synchronisationCode');
            $syncGradCount = $configurationManager->get('syncGradCount');
            if (false == $myUser && !(isset($_['code']) && $configurationManager->get('synchronisationCode') != null && $_['code'] == $configurationManager->get('synchronisationCode')
                )
            ) {
                die(_t('YOU_MUST_BE_CONNECTED_ACTION'));
            }
            Functions::triggerDirectOutput();


            echo '<html>
                <head>
                <link rel="stylesheet" href="./templates/' . $theme . '/css/style.css">
                <meta name="referrer" content="no-referrer" />
                </head>
                <body>
                <div class="sync">';

            $synchronisationType = $configurationManager->get('synchronisationType');

            $synchronisationCustom = array();
            Plugin::callHook("action_before_synchronisationtype", array(&$synchronisationCustom, &$synchronisationType, &$commandLine, $configurationManager, $start));
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
            }
        } else {
            echo _t('YOU_MUST_BE_CONNECTED_ACTION');
        }

    });


    $router->post('/feed/add', function () use ($tpl, $configurationManager, $myUser,$logger) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));


        $newFeed = new Feed();
        $newFeed->setUrl(Functions::clean_url($_POST['newUrl']));

        if ($newFeed->notRegistered()) {
            $newFeed->getInfos();
            $newFeed->setFolder(
                (isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1)
            );
            $newFeed->save();
            $enableCache = ($configurationManager->get('synchronisationEnableCache') == '') ? 0 : $configurationManager->get('synchronisationEnableCache');
            $forceFeed = ($configurationManager->get('synchronisationForceFeed') == '') ? 0 : $configurationManager->get('synchronisationForceFeed');
            $newFeed->parse(time(), $_, $enableCache, $forceFeed);
            Plugin::callHook("action_after_addFeed", array(&$newFeed));
        } else {
            //$logger = new Logger('settings');
            $logger->info(_t("FEED_ALREADY_STORED"));
            //$logger->save();
        }
        header('location: /settings#manageBloc');
    });

    $router->get('/feed/remove/{id}', function ($id) use ($tpl, $configurationManager, $myUser, $feedManager, $eventManager) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($id)) {
            $feedManager->delete(array('id' => $id));
            $eventManager->delete(array('feed' => $id));
            Plugin::callHook("action_after_removeFeed", array($id));
        }
        header('location: /settings');
    });

    $router->get('/feed/rename/{id}', function ($id) use ($tpl, $configurationManager, $myUser, $feedManager, $folderManager, $eventManager) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($id)) {
            $feedManager->change(array('name' => $_['name'], 'url' => Functions::clean_url($_['url'])), array('id' => $_['id']));
        }
        header('location: /settings');
    });

    $router->post('/folder/add', function () use ($tpl, $configurationManager, $myUser, $eventManager, $feedManager, $folderManager) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_POST['newFolder'])) {
            $folder = new Folder();
            if ($folder->rowCount(array('name' => $_POST['newFolder'])) == 0) {
                $folder->setParent(-1);
                $folder->setIsopen(0);
                $folder->setName($_POST['newFolder']);
                $folder->save();
            }
        }
        header('location: /settings');
    });

    $router->get('/folder/remove/{id}', function ($id) use ($tpl, $configurationManager, $myUser, $feedManager, $folderManager, $eventManager) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($id) && is_numeric($id) && $id > 0) {
            $eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'event` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            $feedManager->delete(array('folder' => $id));
            $folderManager->delete(array('id' => $id));
        }
        header('location: /settings');
    });

    $router->get('/folder/rename/{id}', function ($id) use ($tpl, $configurationManager, $myUser, $feedManager, $folderManager, $eventManager) {
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($id) && is_numeric($id) && $id > 0) {
            $eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'event` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            $feedManager->delete(array('folder' => $id));
            $folderManager->delete(array('id' => $id));
        }

        header('location: /settings');
    });

});

$router->get('/action/{readAll}', function () use ($myUser, $eventManager) {

    if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }

});

$router->mount('/qrcode', function () use ($router, $tpl, $myUser, $configurationManager, $feedManager, $folderManager, $cookiedir, $eventManager) {

    $router->get('/qr', function () use ($myUser, $eventManager) {

        if (empty($myUser)) exit();

        require_once('phpqrcode.php');


        Functions::chargeVarRequest('label', 'user', 'key', 'issuer', 'algorithm', 'digits', 'period');
        if (empty($key)) {
            $key = "**********";
        }
        $qrCode = "otpauth://totp/{$label}:{$user}?secret={$key}";
        foreach (array('issuer', 'algorithm', 'digits', 'period') as $champ)
            if (!empty(${$champ}))
                $qrCode .= "&{$champ}={${$champ}}";



    Functions::chargeVarRequest('_qrSize', '_qrMargin');
    if (empty($_qrSize)) $_qrSize = 3;
    if (empty($_qrMargin)) $_qrMargin = 4;

    QRcode::png($qrCode, false, 'QR_LEVEL_H', $_qrSize, $_qrMargin);
    });

$router->get('/text', function () use ($myUser, $eventManager) {

    $qrCode = substr($_SERVER['QUERY_STRING'], 1 + strlen($methode));

});

});

*/

$router->get('/feed/{id}', function ($id) use (
    $twig, $logger,
    //$feedState,
    //$nextPages,
    //$previousPages,
    $scroll,
    //$eventManager,
    $unreadEventsForFolder,
    $optionFeedIsVerbose,
    $articlePerPages,
    $articleDisplayHomeSort,
    $articleDisplayFolderSort,
    $articleDisplayAuthor,
    $articleDisplayLink,
    $articleDisplayDate,
    $articleDisplayMode,
    $target,
    $displayOnlyUnreadFeedFolder_reverse,
    $displayOnlyUnreadFeedFolder,
    $folders,
    $allEvents,
    //$unread,
    $allFeedsPerFolder,
    $config,
    $db
) {


    //$currentFeed = $feedManager->getById($id);
    //var_dump($currentFeed);

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items FROM leed_event le inner join leed_feed lf on lf.id = le.feed where le.feed = ' . $id);
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }

    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_['page']) ? $_['page'] : 1);
    $pages = ceil($numberOfItem / $articlePerPages);
    $startArticle = ($page - 1) * $articlePerPages;
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.id, le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM leed_event le inner join leed_feed lf on lf.id = le.feed where le.feed = ' . $id . ' ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['id'],
            'guid' => $rows['guid'],
            'title' => $rows['title'],
            'creator' => $rows['creator'],
            'content' => $rows['content'],
            'description' => $rows['description'],
            'link' => $rows['link'],
            'unread' => $rows['unread'],
            'feed' => $rows['feed'],
            'favorite' => $rows['favorite'],
            'pubdate' => date('Y-m-d H:i:s', $rows['pubdate']),
            'syncId' => $rows['syncId'],
            'feed_name' => $rows['feed_name'],
        );

    }


    $events = $events;

    //$order = 'unread';
    //$feed =  $id;

    echo $twig->render('index.twig',
        [
            //'action' => $action,
            'allEvents' => $allEvents,
            'allFeedsPerFolder' => $allFeedsPerFolder,
            'articleDisplayFolderSort' => $articleDisplayFolderSort,
            'articleDisplayHomeSort' => $articleDisplayHomeSort,
            'articleDisplayAuthor' => $articleDisplayAuthor,
            'articleDisplayLink' => $articleDisplayLink,
            'articleDisplayDate' => $articleDisplayDate,
            'articleDisplayMode' => $articleDisplayMode,
            'articlePerPages' => $articlePerPages,
            'displayOnlyUnreadFeedFolder_reverse' => $displayOnlyUnreadFeedFolder_reverse,
            'displayOnlyUnreadFeedFolder' => $displayOnlyUnreadFeedFolder,
            //'eventManager' => $eventManager,
            'events' => $events,
            //'feedState' => $feedState,
            'folders' => $folders,
            'functions' => New Functions(),
            'nextPages' => $nextPages,
            'numberOfItem' => $numberOfItem,
            'optionFeedIsVerbose' => $optionFeedIsVerbose,
            'previousPages' => $previousPages,
            'page' => $page,
            'pages' => $pages,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'target' => $target,
            'unread' => $unread,
            //$unreadEventsForFolder,
            //'wrongLogin' => $wrongLogin,
        ]
    );

});


$router->run();