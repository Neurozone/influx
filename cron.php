<?php

require __DIR__ . '/vendor/autoload.php';

require_once 'simplepie/autoloader.php';

$router = new \Bramus\Router\Router();

if (!ini_get('safe_mode')) @set_time_limit(0);
require_once("common.php");

///@TODO: déplacer dans common.php?
$commandLine = 'cli' == php_sapi_name();

if ($commandLine) {
    $action = 'commandLine';
} else {
    $action = @$_['action'];
}
///@TODO: pourquoi ne pas refuser l'accès dès le début ?
Plugin::callHook("action_pre_case", array(&$_, $myUser));

//Execution du code en fonction de l'action
switch ($action) {
    case 'commandLine':
    case 'synchronize':
        //require_once("SimplePie.class.php");
        $syncCode = $configurationManager->get('synchronisationCode');
        $syncGradCount = $configurationManager->get('syncGradCount');
        if (false == $myUser
            && !$commandLine
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
        break;


    case 'removeFeed':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_GET['id'])) {
            $feedManager->delete(array('id' => $_['id']));
            $eventManager->delete(array('feed' => $_['id']));
            Plugin::callHook("action_after_removeFeed", array($_['id']));
        }
        header('location: ./settings.php');
        break;

    case 'addFolder':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_['newFolder'])) {
            $folder = new Folder();
            if ($folder->rowCount(array('name' => $_['newFolder'])) == 0) {
                $folder->setParent(-1);
                $folder->setIsopen(0);
                $folder->setName($_['newFolder']);
                $folder->save();
            }
        }
        header('location: ./settings.php');
        break;


    case 'renameFolder':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_['id'])) {
            $folderManager->change(array('name' => $_['name']), array('id' => $_['id']));
        }
        break;

    case 'renameFeed':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_['id'])) {
            $feedManager->change(array('name' => $_['name'], 'url' => Functions::clean_url($_['url'])), array('id' => $_['id']));
        }
        break;

    case 'removeFolder':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));
        if (isset($_['id']) && is_numeric($_['id']) && $_['id'] > 0) {
            $eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'event` WHERE `' . MYSQL_PREFIX . 'event`.`feed` in (SELECT `' . MYSQL_PREFIX . 'feed`.`id` FROM `' . MYSQL_PREFIX . 'feed` WHERE `' . MYSQL_PREFIX . 'feed`.`folder` =\'' . intval($_['id']) . '\') ;');
            $feedManager->delete(array('folder' => $_['id']));
            $folderManager->delete(array('id' => $_['id']));
        }
        header('location: ./settings.php');
        break;

    case 'readContent':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        if (isset($_['id'])) {
            $event = $eventManager->load(array('id' => $_['id']));
            $eventManager->change(array('unread' => '0'), array('id' => $_['id']));
        }
        break;

    case 'unreadContent':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        if (isset($_['id'])) {
            $event = $eventManager->load(array('id' => $_['id']));
            $eventManager->change(array('unread' => '1'), array('id' => $_['id']));
        }
        break;

    case 'addFavorite':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        $eventManager->change(array('favorite' => '1'), array('id' => $_['id']));
        break;

    case 'removeFavorite':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        $eventManager->change(array('favorite' => '0'), array('id' => $_['id']));
        break;



    case 'changePluginState':
        if ($myUser == false) exit(_t('YOU_MUST_BE_CONNECTED_ACTION'));

        if ($_['state'] == '0') {
            Plugin::enabled($_['plugin']);

        } else {
            Plugin::disabled($_['plugin']);
        }
        header('location: ./settings.php#pluginBloc');
        break;




    case 'displayOnlyUnreadFeedFolder':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        $configurationManager->put('displayOnlyUnreadFeedFolder', $_['displayOnlyUnreadFeedFolder']);
        break;

    case 'displayFeedIsVerbose':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        // changement du statut isverbose du feed
        $feed = new Feed();
        $feed = $feed->getById($_['idFeed']);
        $feed->setIsverbose(($_['displayFeedIsVerbose'] == "0" ? 1 : 0));
        $feed->save();
        break;

    case 'optionFeedIsVerbose':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        // changement du statut de l'option
        $configurationManager = new Configuration();
        $conf = $configurationManager->getAll();
        $configurationManager->put('optionFeedIsVerbose', ($_['optionFeedIsVerbose'] == "0" ? 0 : 1));

        break;

    case 'articleDisplayMode':
        if ($myUser == false) {
            $response_array['status'] = 'noconnect';
            $response_array['texte'] = _t('YOU_MUST_BE_CONNECTED_ACTION');
            header('Content-type: application/json');
            echo json_encode($response_array);
            exit();
        }
        // chargement du content de l'article souhaité
        $newEvent = new Event();
        $event = $newEvent->getById($_['event_id']);

        if ($_['articleDisplayMode'] == 'content') {
            //error_log(print_r($_SESSION['events'],true));
            $content = $event->getContent();
        } else {
            $content = $event->getDescription();
        }
        echo $content;

        break;

    default:
        //require_once("SimplePie.class.php");
        Plugin::callHook("action_post_case", array(&$_, $myUser));
        //exit('0');
        break;

    //Installation d'un nouveau plugin
    case 'installPlugin':
        Plugin::install($_['zip']);
        break;
    case 'getGithubMarket':
        $plugin = new Plugin();
        $plugin->getGithubMarketRepos();
        break;
}


?>
