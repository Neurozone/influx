<?php

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Sinergi\BrowserDetector\Language;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Celd\Opml\Importer;
use Celd\Opml\Model\FeedList;
use Celd\Opml\Model\Feeed;

$stream = new StreamHandler(__DIR__ . '/logs/influx.log', Logger::DEBUG);
$logger = new Logger('influxLogger');
$logger->pushHandler($stream);
$logger->info('Influx started');

$templatePath = __DIR__ . '/templates/influx';

$loader = new \Twig\Loader\FilesystemLoader($templatePath);
$twig = new \Twig\Environment($loader, [__DIR__ . '/cache' => 'cache', 'debug' => true,]);
$twig->addExtension(new Umpirsky\Twig\Extension\PhpFunctionExtension());
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Create Router instance
$router = new \Bramus\Router\Router();

session_start();

if (file_exists('conf/config.php')) {
    require_once('conf/config.php');

}

else{
    if(!isset($_SESSION['install']) or $_SESSION['install'] == false)
    {
        $_SESSION['install'] = true;
        header('location: /install');
        exit();
    }

}

function getClientIP(){
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
        return  $_SERVER["HTTP_X_FORWARDED_FOR"];
    }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
        return $_SERVER["REMOTE_ADDR"];
    }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }

    return '';
}

/* ---------------------------------------------------------------- */
// Timezone
/* ---------------------------------------------------------------- */

// Mise en place d'un timezone par default pour utiliser les fonction de date en php
$timezone_default = 'Europe/Paris'; // valeur par défaut :)
date_default_timezone_set($timezone_default);
$timezone_phpini = ini_get('date.timezone');
if (($timezone_phpini != '') && (strcmp($timezone_default, $timezone_phpini))) {
    date_default_timezone_set($timezone_phpini);
}

/* ---------------------------------------------------------------- */
// Database
/* ---------------------------------------------------------------- */

$db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
$db->set_charset('utf8mb4');
$db->query('SET NAMES utf8mb4');

$query_configuration = 'select * from configuration';
$result_configuration = $db->query($query_configuration);

while ($row = $result_configuration->fetch_array()) {
    $config[$row['name']] = $row['value'];
}

/* ---------------------------------------------------------------- */
// Reverse proxy
/* ---------------------------------------------------------------- */

// @todo


// Use X-Forwarded-For HTTP Header to Get Visitor's Real IP Address
/*
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $http_x_headers = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

    $_SERVER['REMOTE_ADDR'] = $http_x_headers[0];
}
*/

/* ---------------------------------------------------------------- */
// i18n
/* ---------------------------------------------------------------- */

$language = new Language();

if ($language->getLanguage() == $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = $language->getLanguage();
    $l_trans = json_decode(file_get_contents('locales/' . $config['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx/locales/' . $config['language'] . '.json'), true);
} elseif ($language->getLanguage() != $config['language'] && is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = $language->getLanguage();
    $l_trans = json_decode(file_get_contents('locales/' . $config['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx/locales/' . $config['language'] . '.json'), true);
} elseif (!is_file('locales/' . $config['language'] . '.json')) {
    $_SESSION['language'] = 'en';
    $l_trans = json_decode(file_get_contents('locales/' . $_SESSION['language'] . '.json'), true);
    $l_trans2 = json_decode(file_get_contents('templates/influx/locales/' . $_SESSION['language'] . '.json'), true);
}

$trans = array_merge($l_trans, $l_trans2);

/* ---------------------------------------------------------------- */
//
/* ---------------------------------------------------------------- */

$cookiedir = '';
if (dirname($_SERVER['SCRIPT_NAME']) != '/')
{
    $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
}


mb_internal_encoding('UTF-8'); // UTF8 pour fonctions mb_*
$start = microtime(true);

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

/* ---------------------------------------------------------------- */
// Get all unread event
/* ---------------------------------------------------------------- */


$results = $db->query('SELECT COUNT(i.guid),f.folder FROM items i INNER JOIN flux f ON i.feed = f.id WHERE i.unread =1 GROUP BY f.folder');
while ($item = $results->fetch_array()) {
    $events[$item[1]] = intval($item[0]);
}

$allEvents = $events;

$results = $db->query("SELECT f.name AS name, f.id AS id, f.url AS url, c.id AS folder 
FROM flux f 
    INNER JOIN categories c ON f.folder = c.id 
ORDER BY f.name");

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

$results = $db->query('SELECT * FROM categories c ORDER BY name ');
while ($rows = $results->fetch_array()) {

    $resultsUnreadByFolder = $db->query('SELECT count(*) as unread
FROM items le 
    inner join flux lfe on le.feed = lfe.id 
    inner join categories lfo on lfe.folder = lfo.id  
where unread = 1 and lfo.id = ' . $rows['id']);

    while ($rowsUnreadByFolder = $resultsUnreadByFolder->fetch_array()) {
        $unreadEventsByFolder = $rowsUnreadByFolder['unread'];
    }

    $resultsFeedsByFolder = $db->query('SELECT fe.id as feed_id, fe.name as feed_name, fe.description as feed_description, fe.website as feed_website, fe.url as feed_url, fe.lastupdate as feed_lastupdate, fe.lastSyncInError as feed_lastSyncInError 
FROM categories f 
    inner join flux fe on fe.folder = f.id 
where f.id = ' . $rows['id'] . " order by fe.name");


    while ($rowsFeedsByFolder = $resultsFeedsByFolder->fetch_array()) {

        $resultsUnreadByFeed = $db->query('SELECT count(*) as unread FROM categories f inner join flux fe on fe.folder = f.id 
    inner join items e on e.feed = fe.id  where e.unread = 1 and fe.id = ' . $rowsFeedsByFolder['feed_id'] );

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

/* ---------------------------------------------------------------- */
// Route: Before for logging
/* ---------------------------------------------------------------- */

$router->before('GET|POST|PUT|DELETE|PATCH|OPTIONS', '^((?!login).)*$', function () use ($logger) {
    $logger->info($_SERVER['REQUEST_URI']);
    $logger->info(getClientIP());

    if (!$_SESSION['user']) {
        header('location: /login');
    }
});

/* ---------------------------------------------------------------- */
// Route: /
/* ---------------------------------------------------------------- */
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


    $action = '';

    //$filter = array('unread' => 1);
    $resultsNbUnread = $db->query('SELECT count(*) as nb_items from items where unread = 1');
    $numberOfItem = 0;

    while ($rows = $resultsNbUnread->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ($articlePerPages > 0 ? ceil($numberOfItem / $articlePerPages) : 1);
    $startArticle = ($page - 1) * $articlePerPages;
    $order = 'pubdate desc';


    $results = $db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where unread = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['guid'],
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


    $html = '';

    //var_dump($events);

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
            //'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'target' => $target,
            //'unread' => $unread,
            'trans' => $trans
        ]
    );


    $logger->info($html);

});

/* ---------------------------------------------------------------- */
// Route: /login
/* ---------------------------------------------------------------- */

$router->get('/login', function () use ($twig) {
    echo $twig->render('login.twig');
});

/* ---------------------------------------------------------------- */
// Route: /login (POST)
/* ---------------------------------------------------------------- */

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

    if ($stmt = $db->prepare("select id,login,password from user where login = ? and password = ?")) {
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
            setcookie('InfluxChocolateCookie', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }
        header('location: /');
    }
    exit();


});

/* ---------------------------------------------------------------- */
// Route: /logout
/* ---------------------------------------------------------------- */

$router->get('/logout', function () {

    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('location: /login');

});

// @TODO: à mettre en place

/* ---------------------------------------------------------------- */
// Route: /update
/* ---------------------------------------------------------------- */

$router->get('/update', function () {

    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('location: /');

});

/* ---------------------------------------------------------------- */
// Route: /favorites
/* ---------------------------------------------------------------- */

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

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items from items where favorite = 1');
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }
    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ceil($numberOfItem / $articlePerPages);
    $startArticle = ($page - 1) * $articlePerPages;
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where favorite = 1 ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['guid'],
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
            //$unreadEventsForFolder,
            //'wrongLogin' => $wrongLogin,
        ]
    );


});

/* ---------------------------------------------------------------- */
// Route: /article
/* ---------------------------------------------------------------- */

// @todo

$router->mount('/article', function () use ($router, $twig, $db,$logger,$trans,$config) {

    /*
     * Plugin::callHook("index_pre_treatment", array(&$_));

$view = "article";
$articleConf = array();
//recuperation de tous les flux
$allFeeds = $feedManager->getFeedsPerFolder();
$tpl->assign('allFeeds',$allFeeds);
$scroll = isset($_['scroll']) ? $_['scroll'] : 0;
$tpl->assign('scrollpage',$scroll);
// récupération des variables pour l'affichage
$articleConf['articlePerPages'] = $configurationManager->get('articlePerPages');
$articleDisplayLink = $configurationManager->get('articleDisplayLink');
$articleDisplayDate = $configurationManager->get('articleDisplayDate');
$articleDisplayAuthor = $configurationManager->get('articleDisplayAuthor');
$articleDisplayHomeSort = $configurationManager->get('articleDisplayHomeSort');
$articleDisplayFolderSort = $configurationManager->get('articleDisplayFolderSort');
$articleDisplayMode = $configurationManager->get('articleDisplayMode');
$optionFeedIsVerbose = $configurationManager->get('optionFeedIsVerbose');

$tpl->assign('articleDisplayAuthor',$articleDisplayAuthor);
$tpl->assign('articleDisplayDate',$articleDisplayDate);
$tpl->assign('articleDisplayLink',$articleDisplayLink);
$tpl->assign('articleDisplayMode',$articleDisplayMode);

if(isset($_['hightlighted'])) {
    $hightlighted = $_['hightlighted'];
    $tpl->assign('hightlighted',$hightlighted);
}

$tpl->assign('time',$_SERVER['REQUEST_TIME']);

$target = '`'.MYSQL_PREFIX.'event`.`title`,`'.MYSQL_PREFIX.'event`.`unread`,`'.MYSQL_PREFIX.'event`.`favorite`,`'.MYSQL_PREFIX.'event`.`feed`,';
if($articleDisplayMode=='summary') $target .= '`'.MYSQL_PREFIX.'event`.`description`,';
if($articleDisplayMode=='content') $target .= '`'.MYSQL_PREFIX.'event`.`content`,';
if($articleDisplayLink) $target .= '`'.MYSQL_PREFIX.'event`.`link`,';
if($articleDisplayDate) $target .= '`'.MYSQL_PREFIX.'event`.`pubdate`,';
if($articleDisplayAuthor) $target .= '`'.MYSQL_PREFIX.'event`.`creator`,';
$target .= '`'.MYSQL_PREFIX.'event`.`id`';

$nblus = isset($_['nblus']) ? $_['nblus'] : 0;
$articleConf['startArticle'] = ($scroll*$articleConf['articlePerPages'])-$nblus;
if ($articleConf['startArticle'] < 0) $articleConf['startArticle']=0;
$action = $_['action'];
$tpl->assign('action',$action);

$filter = array();
Plugin::callHook("article_pre_action", array(&$_,&$filter,&$articleConf));
switch($action){

case 'selectedFeed':
        $currentFeed = $feedManager->getById($_['feed']);
        $allowedOrder = array('date'=>'pubdate DESC','older'=>'pubdate','unread'=>'unread DESC,pubdate DESC');
        $order = (isset($_['order'])?$allowedOrder[$_['order']]:$allowedOrder['unread']);
        $events = $currentFeed->getEvents($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target,$filter);
    break;

    case 'selectedFolder':
        $currentFolder = $folderManager->getById($_['folder']);
        if($articleDisplayFolderSort) {$order = '`'.MYSQL_PREFIX.'event`.`pubdate` desc';} else {$order = '`'.MYSQL_PREFIX.'event`.`pubdate` asc';}
        $events = $currentFolder->getEvents($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target,$filter);
    break;

    case 'favorites':
        $filter['favorite'] = 1;
        //$events = $eventManager->loadAllOnlyColumn($target,$filter,'pubdate DESC',$articleConf['startArticle'].','.$articleConf['articlePerPages']);
    break;

    case 'unreadEvents':
    default:
        $filter['unread'] = 1;
        if($articleDisplayHomeSort) {$order = 'pubdate desc';} else {$order = 'pubdate asc';}
        if($optionFeedIsVerbose) {
            $events = $eventManager->loadAllOnlyColumn($target,$filter,$order,$articleConf['startArticle'].','.$articleConf['articlePerPages']);
        } else {
            $events = $eventManager->getEventsNotVerboseFeed($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target);
        }
        break;
}
$tpl->assign('events',$events);
$tpl->assign('scroll',$scroll);
$view = "article";
Plugin::callHook("index_post_treatment", array(&$events));
$html = $tpl->draw($view);
     */

    /* ---------------------------------------------------------------- */
    // Route: /article
    /* ---------------------------------------------------------------- */

    $router->get('/', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/favorite
    /* ---------------------------------------------------------------- */

    $router->get('/favorites', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/unread
    /* ---------------------------------------------------------------- */

    $router->get('/unread', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/feed/{id}
    /* ---------------------------------------------------------------- */

    $router->get('/feed/{id}', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /article/folder/{id}
    /* ---------------------------------------------------------------- */

    $router->get('/folder/{id}', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

});

/* ---------------------------------------------------------------- */
// Route: /settings
/* ---------------------------------------------------------------- */

$router->mount('/settings', function () use ($router, $twig, $trans, $logger, $config, $db, $cookiedir ) {

    $router->get('/', function () use ($twig, $cookiedir) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/plugin/{name}/state/{state}
    /* ---------------------------------------------------------------- */

    $router->get('/plugin/{name}/state/{state}', function ($name, $state) {

        if ($state == '0') {
            Plugin::enabled($name);
        } else {
            Plugin::disabled($name);
        }
        header('location: /settings/plugins');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/{option}
    /* ---------------------------------------------------------------- */

    $router->get('/{option}', function ($option) use ($twig, $trans, $logger, $config, $cookiedir) {

        //$serviceUrl', rtrim($_SERVER['HTTP_HOST'] . $cookiedir, '/'));

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
        $folders = $folderManager->populate('name');
        $synchronisationType  = $configurationManager->get('synchronisationType');
        $synchronisationEnableCache  = $configurationManager->get('synchronisationEnableCache');
        $synchronisationForceFeed  = $configurationManager->get('synchronisationForceFeed');
        $articleDisplayAnonymous  = $configurationManager->get('articleDisplayAnonymous');
        $articleDisplayLink  = $configurationManager->get('articleDisplayLink');
        $articleDisplayDate  = $configurationManager->get('articleDisplayDate');
        $articleDisplayAuthor  = $configurationManager->get('articleDisplayAuthor');
        $articleDisplayHomeSort  = $configurationManager->get('articleDisplayHomeSort');
        $articleDisplayFolderSort = $configurationManager->get('articleDisplayFolderSort');
        $articleDisplayMode  = $configurationManager->get('articleDisplayMode');
        $optionFeedIsVerbose  = $configurationManager->get('optionFeedIsVerbose');

        $otpEnabled  = false;
        $section  = $option;

        $logger->info('Section: ' . $option);
        $logger->info('User: ' . $myUser->getLogin());

        //Suppression de l'état des plugins inexistants
        Plugin::pruneStates();

        //$plugins', Plugin::getAll());

        //$tpl->draw('settings');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/synchronize
    /* ---------------------------------------------------------------- */

    $router->get('/synchronize', function ($option) use ($twig, $trans, $logger, $config, $cookiedir) {

        if (isset($myUser) && $myUser != false) {
            $syncCode = $conf['synchronisationCode'];
            $syncGradCount = $conf['syncGradCount'];
            if (!(isset($_['code']) && $conf['synchronisationCode'] != null && $_GET['code'] == $conf['synchronisationCode'])) {
                die(_t('YOU_MUST_BE_CONNECTED_ACTION'));
            }
            Functions::triggerDirectOutput();


            echo '<html>
                <head>
                <link rel="stylesheet" href="./templates/influx/css/style.css">
                <meta name="referrer" content="no-referrer" />
                </head>
                <body>
                <div class="sync">';

            $synchronisationType = $conf['synchronisationType'];

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

    /* ---------------------------------------------------------------- */
    // Route: /statistics
    /* ---------------------------------------------------------------- */

    $router->get('/statistics', function () use ($twig, $trans, $logger, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        echo '
	<section id="leedStatslBloc" class="leedStatslBloc" style="display:none;">
		<h2>' . _t('P_LEEDSTATS_TITLE') . '</h2>

		<section class="preferenceBloc">
		<h3>' . _t('P_LEEDSTATS_RESUME') . '</h3>
	';

        //Nombre global d'article lus / non lus / total / favoris
        $requete = 'SELECT
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'flux`) as nbFeed,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE unread = 1) as nbUnread,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE unread = 0) as nbRead,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items`) as nbTotal,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` WHERE favorite = 1) as nbFavorite
                ';
        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_NONLU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_LU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFAV') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFeed'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbTotal'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbUnread'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbRead'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFavorite'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }
        echo '
            <h3>' . _t('P_LEEDSTATS_NBART_BY_FEED_TITLE') . '</h3>

    ';
        //Nombre global d'article lus / non lus / total / favoris
        $requete = 'SELECT name, count(1) as nbTotal,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=1 and le1.feed = le2.feed) as nbUnread,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.unread=0 and le1.feed = le2.feed) as nbRead,
                (SELECT count(1) FROM `' . MYSQL_PREFIX . 'items` le2 WHERE le2.favorite=1 and le1.feed = le2.feed) as nbFavorite
                FROM `' . MYSQL_PREFIX . 'flux` lf1
                INNER JOIN `' . MYSQL_PREFIX . 'items` le1 on le1.feed = lf1.id
                GROUP BY name
                ORDER BY name
                ';
        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_FEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_NONLU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBART_LU') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_NBFAV') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . short_name($data['name'], 32) . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbTotal'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbUnread'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbRead'] . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['nbFavorite'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }

        echo '
            <h3>' . _t('P_LEEDSTATS_LASTPUB_BY_FEED_TITLE') . '</h3>

    ';

        $requete = 'select lf.name, FROM_UNIXTIME(max(le.pubdate)) last_published from flux lf inner join items le on lf.id = le.feed group by lf.name order by 2';

        $query = $mysqli->customQuery($requete);
        if ($query != null) {
            echo '<div id="result_leedStats1" class="result_leedStats1">
                 <table>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_FEED') . '</th>
                        <th class="leedStats_border leedStats_th">' . _t('P_LEEDSTATS_LASTPUB') . '</th>
        ';
            while ($data = $query->fetch_array()) {
                echo '
                <tr>
                    <td class="leedStats_border leedStats_textright">' . short_name($data['name'], 32) . '</td>
                    <td class="leedStats_border leedStats_textright">' . $data['last_published'] . '</td>
                </tr>
            ';
            }
            echo '</table>
            </div>';
        }

        echo '
        </section>
	</section>
	';

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/feed/add
    /* ---------------------------------------------------------------- */

    $router->post('/feed/add', function () use ($twig, $trans, $logger, $config) {



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
            //Plugin::callHook("action_after_addFeed", array(&$newFeed));
        } else {
            //$logger = new Logger('settings');
            $logger->info($trans['FEED_ALREADY_STORED']);
            //$logger->save();
        }
        header('location: /settings/manage');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/feed/remove/{id}
    /* ---------------------------------------------------------------- */

    $router->get('/feed/remove/{id}', function ($id) use ($twig, $trans, $logger, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        if (isset($id)) {
            $feedManager->delete(array('id' => $id));
            $eventManager->delete(array('feed' => $id));
            Plugin::callHook("action_after_removeFeed", array($id));
        }
        header('location: /settings');
    });

    $router->get('/feed/rename/{id}', function ($id) use ($twig, $trans, $logger, $config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        if (isset($id)) {
            $feedManager->change(array('name' => $_['name'], 'url' => Functions::clean_url($_['url'])), array('id' => $_['id']));
        }
        header('location: /settings');
    });

    $router->get('/feed/folder/{id}', function ($id) use ($twig, $trans, $logger, $config, $db) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }



        if (isset($id)) {
            $feedManager->change(array('name' => $_['name'], 'url' => Functions::clean_url($_['url'])), array('id' => $_['id']));
        }
        header('location: /settings');
    });

    $router->post('/folder/add', function () use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

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

    $router->get('/folder/remove/{id}', function ($id) use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        if (isset($id) && is_numeric($id) && $id > 0) {
            $eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'items` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            $feedManager->delete(array('folder' => $id));
            $folderManager->delete(array('id' => $id));
        }
        header('location: /settings');
    });

    $router->get('/folder/rename/{id}', function ($id) use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }



        header('location: /settings');
    });

    $router->get('/feeds/export', function ($id) use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        $feedList = new FeedList();




        $importer = new Importer();
        echo $importer->export($feedList);

        /*
         * if (isset($_POST['exportButton'])) {
            $opml = new Opml();
            $xmlStream = $opml->export();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=leed-' . date('d-m-Y') . '.opml');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($xmlStream));

        ob_clean();
        flush();
        echo $xmlStream;
    }
        break;
         *
         */

        /*
        if (isset($id) && is_numeric($id) && $id > 0) {
            $eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'event` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            $feedManager->delete(array('folder' => $id));
            $folderManager->delete(array('id' => $id));
        }*/

        header('location: /settings');
    });

    $router->get('/feeds/import', function ($id) use ($twig, $db,$logger,$trans,$config) {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

        /*
         * // On ne devrait pas mettre de style ici.
        echo "<html>
            <style>
                a {
                    color:#F16529;
                }

                html,body{
                        font-family:Verdana;
                        font-size: 11px;
                }
                .error{
                        background-color:#C94141;
                        color:#ffffff;
                        padding:5px;
                        border-radius:5px;
                        margin:10px 0px 10px 0px;
                        box-shadow: 0 0 3px 0 #810000;
                    }
                .error a{
                        color:#ffffff;
                }
                </style>
            </style><body>
\n";
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (!isset($_POST['importButton'])) break;
        $opml = new Opml();
        echo "<h3>" . _t('IMPORT') . "</h3><p>" . _t('PENDING') . "</p>\n";
        try {
            $errorOutput = $opml->import($_FILES['newImport']['tmp_name']);
        } catch (Exception $e) {
            $errorOutput = array($e->getMessage());
        }
        if (empty($errorOutput)) {
            echo "<p>" . _t('IMPORT_NO_PROBLEM') . "</p>\n";
        } else {
            echo "<div class='error'>" . _t('IMPORT_ERROR') . "\n";
            foreach ($errorOutput as $line) {
                echo "<p>$line</p>\n";
            }
            echo "</div>";
        }
        if (!empty($opml->alreadyKnowns)) {
            echo "<h3>" . _t('IMPORT_FEED_ALREADY_KNOWN') . " : </h3>\n<ul>\n";
            foreach ($opml->alreadyKnowns as $alreadyKnown) {
                foreach ($alreadyKnown as &$elt) $elt = htmlspecialchars($elt);
                $text = Functions::truncate($alreadyKnown->feedName, 60);
                echo "<li><a target='_parent' href='{$alreadyKnown->xmlUrl}'>"
                    . "{$text}</a></li>\n";
            }
            echo "</ul>\n";
        }
        $syncLink = "action.php?action=synchronize&format=html";
        echo "<p>";
        echo "<a href='$syncLink' style='text-decoration:none;font-size:3em'>"
            . "↺</a>";
        echo "<a href='$syncLink'>" . _t('CLIC_HERE_SYNC_IMPORT') . "</a>";
        echo "<p></body></html>\n";
        break;
         *
         */



        header('location: /settings');
    });

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/readAll
/* ---------------------------------------------------------------- */

$router->get('/action/{readAll}', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /search
/* ---------------------------------------------------------------- */

$router->get('/search', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $search = $this->escape_string($_GET['plugin_search']);
    $requete = 'SELECT title,guid,content,description,link,pubdate,unread, favorite
            FROM `'.MYSQL_PREFIX.'items`
            WHERE title like \'%'.htmlentities($search).'%\'';
    if (isset($_GET['search_option']) && $_GET['search_option']=="1"){
        $requete = $requete.' OR content like \'%'.htmlentities($search).'%\'';
    }
    $requete = $requete.' ORDER BY pubdate desc';

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/readFolder
/* ---------------------------------------------------------------- */

$router->get('/action/{readFolder}', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /action/updateConfiguration
/* ---------------------------------------------------------------- */

$router->get('/action/{updateConfiguration}', function () use ($twig, $db,$logger,$trans,$config) {

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $whereClause = array();
    $whereClause['unread'] = '1';
    if (isset($_['feed'])) $whereClause['feed'] = $_['feed'];
    if (isset($_['last-event-id'])) $whereClause['id'] = '<= ' . $_['last-event-id'];
    $eventManager->change(array('unread' => '0'), $whereClause);
    if (!Functions::isAjaxCall()) {
        header('location: ./index.php');
    }

});

// @todo

/* ---------------------------------------------------------------- */
// Route: /qrcode
/* ---------------------------------------------------------------- */

$router->mount('/qrcode', function () use ($router, $twig, $db,$logger,$trans,$config) {

    $router->get('/qr', function () {

        if (!$_SESSION['user']) {
            header('location: /login');
        }

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

    $router->get('/text', function () use ($twig, $trans, $logger, $config) {

        $qrCode = substr($_SERVER['QUERY_STRING'], 1 + strlen($methode));

    });

});


/* ---------------------------------------------------------------- */
// Route: /feed/{id}
/* ---------------------------------------------------------------- */

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

    if (!$_SESSION['user']) {
        header('location: /login');
    }

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items FROM items le inner join flux lf on lf.id = le.feed where le.feed = ' . $id);
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }

    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ceil($numberOfItem / $articlePerPages);
    $startArticle = ($page - 1) * $articlePerPages;
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where le.feed = ' . $id . ' ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

    while ($rows = $results->fetch_array()) {

        $events[] = array(
            'id' => $rows['guid'],
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

    $resultFlux = $db->query("select * from flux where id = " . $id);
    while ($rows = $resultFlux->fetch_array()) {

        $flux = array(
            'id' => $rows['id'],
            'name' => $rows['name'],
            'description' => $rows['description'],
            'website' => $rows['website'],
            'url' => $rows['url'],
            'lastupdate' => $rows['lastupdate'],
            'folder' => $rows['folder'],
            'isverbose' => $rows['isverbose'],
            'lastSyncInError' => $rows['lastSyncInError'],
        );

    }

    //$order = 'unread';
    //$feed =  $id;

    echo $twig->render('index.twig',
        [
            'action' => 'feed',
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
            'feed' => $flux,
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
            //$unreadEventsForFolder,
            //'wrongLogin' => $wrongLogin,
        ]
    );

});

$router->get('/folder/{id}', function ($id) use (
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

    $resultsNbFavorites = $db->query('SELECT count(*) as nb_items FROM items le inner join flux lf on lf.id = le.feed where le.feed = ' . $id);
    $numberOfItem = 0;

    while ($rows = $resultsNbFavorites->fetch_array()) {
        $numberOfItem = $rows['nb_items'];
    }

    //$numberOfItem = $eventManager->rowCount(array('favorite' => 1));
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $pages = ceil($numberOfItem / $articlePerPages);
    $startArticle = ($page - 1) * $articlePerPages;
    //$events = $eventManager->loadAllOnlyColumn($target, array('favorite' => 1), 'pubdate DESC', $startArticle . ',' . $articlePerPages);

    $results = $db->query('SELECT le.id, le.guid,le.title,le.creator,le.content,le.description,le.link,le.unread,le.feed,le.favorite,le.pubdate,le.syncId, lf.name as feed_name
    FROM items le inner join flux lf on lf.id = le.feed where le.feed = ' . $id . ' ORDER BY pubdate desc,unread desc LIMIT  ' . ($page - 1) * $articlePerPages . ',' . $config['articlePerPages']);

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
            'action' => 'folder',
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
            //$unreadEventsForFolder,
            //'wrongLogin' => $wrongLogin,
        ]
    );

});

$router->run();