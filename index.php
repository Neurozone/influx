<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL & ~E_NOTICE);

const CONF_FILE = __DIR__ . '/conf/conf.ini';

require __DIR__ . '/vendor/autoload.php';

use Influx\Classes\Category;
use Influx\Classes\Configuration;
use Influx\Classes\Flux;
use Influx\Classes\Items;
use Influx\Classes\Opml;
use Influx\Classes\User;
use Influx\ConfigurationService;
use Influx\Constants;
use Influx\Controllers\InstallController;
use Influx\DependencyInjection;
use Influx\Routing\InstallRouting;
use Influx\Utils\Utils;
use Pimple\Container;
use Sinergi\BrowserDetector\Language;

$configurationService = new ConfigurationService(CONF_FILE);
$container = (new DependencyInjection(new Container(), $configurationService))->getContainer();

if ($container['configuration_service']->hasConfigurationFile()) {
    $userConfiguration = new Configuration($container['database_service']);
    $fluxObject = new Flux($container['database_service'], $container['logger']);
    $itemsObject = new Items($container['database_service'], $container['logger']);
    $userObject = new User($container['database_service'], $container[Constants::DB_PREFIX], $container['logger']);
    $categoryObject = new Category($container['database_service'], $container['logger']);
    $opmlObject = new Opml($container['database_service'], $container['logger']);

    // @TODO Demander à Flo ce qu'est ce code de synchronisation
    $synchronisationCode = $userConfiguration->get('synchronisationCode');

    mb_internal_encoding('UTF-8');
    $start = microtime(true);

    /* ---------------------------------------------------------------- */
    // Timezone
    /* ---------------------------------------------------------------- */

    $timezone_default = 'Europe/Paris';
    date_default_timezone_set($timezone_default);

    $scroll = false;
    $unreadEventsForCategory = 0;
    $highlighted = 0;

    $page = 1;

} else if (!$container['configuration_service']->hasConfigurationFile() && !in_array($container['router']->getCurrentUri(), InstallRouting::getRoutes('install'), true)) {
    return header('location: /install');
}

/* ---------------------------------------------------------------- */
// i18n
/* ---------------------------------------------------------------- */
$language = new Language();

if ($container[Constants::GENERAL_LANGUAGE]) {
    if ($language->getLanguage() == $container[Constants::GENERAL_LANGUAGE] && is_file('locales/' . $container[Constants::GENERAL_LANGUAGE] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/' . $container['template_name'] . '/locales/' . $container[Constants::GENERAL_LANGUAGE] . '.json'), true);
    } elseif (($language->getLanguage() != $container[Constants::GENERAL_LANGUAGE]) && is_file('locales/' . $container[Constants::GENERAL_LANGUAGE] . '.json')) {
        $_SESSION['language'] = $language->getLanguage();
        $l_trans = json_decode(file_get_contents('templates/' . $container['template_name'] . '/locales/' . $container[Constants::GENERAL_LANGUAGE] . '.json'), true);
    } elseif (!is_file('locales/' . $container[Constants::GENERAL_LANGUAGE] . '.json')) {
        $_SESSION['language'] = 'en';
        $l_trans = json_decode(file_get_contents('templates/' . $container['template_name'] . '/locales/' . $_SESSION['language'] . '.json'), true);
    }
} else {
    $_SESSION['language'] = 'en';
    $l_trans = json_decode(file_get_contents('templates/' . $container['template_name'] . '/locales/' . $_SESSION['language'] . '.json'), true);
}

$trans = $l_trans;

/* ---------------------------------------------------------------- */
// Cookie
/* ---------------------------------------------------------------- */
$cookiedir = '';
if (dirname($_SERVER['SCRIPT_NAME']) !== '/') {
    $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
}

/* ---------------------------------------------------------------- */
// Route: Before for logging
/* ---------------------------------------------------------------- */
$container['router']->before('GET|POST|PUT|DELETE|PATCH|OPTIONS', '/.*', function () use ($container) {
    session_start();

    $container['logger']->info("before");
    $container['logger']->info($_SERVER['REQUEST_URI']);
    $container['logger']->info(Utils::getClientIP());
    $container['logger']->info($_SESSION['install']);
    $container['logger']->info($_SESSION['user']);

    //L'existance du fichier de configuration conditionne le transfert vers l'installation ou non
    if (!$container['configuration_service']->hasConfigurationFile()) {
        if (!in_array($container['router']->getCurrentUri(), InstallRouting::getRoutes('install'), true)) {
            $container['logger']->info('Aucun fichier de configuration trouvé on redirige vers l\'installation');

            return header('Location: /install');
        }

        return true;
    }

    if (!isset($_SESSION['user']) && $_SERVER['REQUEST_URI'] == '/password/recover') {
        return header('Location: /password/recover');
    } elseif (!isset($_SESSION['user']) && $_SERVER['REQUEST_URI'] !== '/login') {
        return header('Location: /login');
    } else {
        $container['logger']->info("Aucune des conditions du before n'a été exécutée !");
    }

});

/* ---------------------------------------------------------------- */
// Route: / (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/', function () use (
    $container,
    $scroll,
    $config,
    $trans,
    $itemsObject,
    $categoryObject
) {
    $action = 'all';
    $numberOfItem = $itemsObject->countAllUnreadItem();
    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * 25; //$config['articlePerPages'];
    $row_count = 25; //$config['articlePerPages'];

    echo $container['template_engine']->render('index.twig',
        [
            'action' => $action,
            'config' => $config,
            'events' => $itemsObject->loadAllUnreadItem($offset, $row_count),
            'categories' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'trans' => $trans
        ]
    );
});

/* ---------------------------------------------------------------- */
// Route: /login
/* ---------------------------------------------------------------- */

$container['router']->get('/login', function () use ($container) {
    echo $container['template_engine']->render('login.twig');
});

/* ---------------------------------------------------------------- */
// Route: /login (POST)
/* ---------------------------------------------------------------- */

$container['router']->post('/login', function () use ($container, $config, $userObject) {
    $userObject->setLogin($_POST['login']);

    if ($userObject->checkPassword($_POST['password'])) {
        $_SESSION['user'] = $_POST['login'];
        $_SESSION['userId'] = $userObject->getId();
        $_SESSION['userEmail'] = $userObject->getEmail();
        if (isset($_POST['rememberMe'])) {
            setcookie('InfluxChocolateCookie', sha1($_POST['password'] . $_POST['login']), time() + 31536000);
        }

        return header('location: /');
    } else {
        return header('location: /login');
    }
});

/* ---------------------------------------------------------------- */
// Route: /password/recover (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/password/recover', function () use ($container, $config, $trans) {
    echo $container['template_engine']->render('recover.twig', []);
});

// Route: /password/new/{id} (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/password/new/{token}', function ($token) use ($container, $config, $trans, $userObject) {
    $userObject->setToken($token);
    $userInfos = $userObject->getUserInfosByToken();

    echo $container['template_engine']->render('password.twig', ['token' => $token]);
});

$container['router']->post('/password/new', function () use ($container, $config, $trans, $userObject) {
    $userObject->setToken($_POST['token']);
    $userInfos = $userObject->createHash($_POST['password']);

    return header('location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /password/recover (POST)
/* ---------------------------------------------------------------- */

$container['router']->post('/password/recover', function () use ($container, $db, $config) {
    $token = bin2hex(random_bytes(50));

    if ($stmt = $container['database']->prepare("select id,login,email from user where email = ?")) {
        $stmt->bind_param('s', $_POST['email']);
        /* execute query */
        $stmt->execute();

        /* instead of bind_result: */
        $result = $stmt->get_result();

        while ($row = $result->fetch_array()) {
            $login = $row['login'];
            $email = $row['email'];

        }
    }

    if (!empty($login)) {
        $db->query("UPDATE user SET token = '" . $token . "' where email = '" . $email . "'");
    }

    $container['logger']->error('Message could not be sent to: ' . $email);
    $container['logger']->error('Message could not be sent to: ' . $_POST['email']);

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host = SMTP_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = SMTP_AUTH;                                   // Enable SMTP authentication
        $mail->Username = SMTP_LOGIN;                     // SMTP username
        $mail->Password = SMTP_PASSWORD;                               // SMTP password
        $mail->SMTPSecure = SMTP_SECURE;                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port = SMTP_PORT;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('rss@neurozone.fr', 'no-reply@neurozone.fr');
        $mail->addAddress($email, $login);

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Reset your password on InFlux';
        $mail->Body = 'Hi there, click on this <a href="https://influx.neurozone.fr/password/new/' . $token . '">link</a> to reset your password on our site';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $container['logger']->error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }

});

/* ---------------------------------------------------------------- */
// Route: /logout (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/logout', function () {
    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();

    return header('location: /login');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /update (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/update', function () {
    setcookie('InfluxChocolateCookie', '', -1);
    $_SESSION = array();
    session_unset();
    session_destroy();

    return header('location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /favorites (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/favorites', function () use (
    $container,
    $scroll,
    $config,
    $db,
    $categoryObject,
    $itemsObject,
    $fluxObject
) {
    $numberOfItem = $itemsObject->getNumberOfFavorites();
    $flux = $fluxObject->getFluxById();

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * $config['articlePerPages'];
    $row_count = $config['articlePerPages'];

    echo $container['template_engine']->render('index.twig',
        [

            'events' => $itemsObject->getAllFavorites($offset, $row_count),
            'category' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll

        ]
    );
});

/* ---------------------------------------------------------------- */
// Route: /article (GET)
/* ---------------------------------------------------------------- */

$container['router']->mount('/article', function () use ($container, $trans, $config, $itemsObject) {
    /* ---------------------------------------------------------------- */
    // Route: /article (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/', function () use ($container, $trans, $config) {
        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /article/favorite (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/favorites', function () use ($container, $trans, $config) {
        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /article/unread (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/unread', function () use ($container, $trans, $config) {
        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /article/flux/ (POST)
    /* ---------------------------------------------------------------- */
    $container['router']->post('/flux', function () use ($container, $config, $itemsObject) {
        $scroll = $_POST['scroll'];
        $hightlighted = $_POST['hightlighted'];
        $action = $_POST['action'];
        $category = $_POST['category'];
        $flux = (int)$_POST['flux'];

        $nblus = isset($_POST['nblus']) ? $_POST['nblus'] : 0;

        $articleConf['startArticle'] = ($scroll * 50) - $nblus;

        $container['logger']->info($articleConf['startArticle']);
        $container['logger']->info($config['articlePerPages']);

        $offset = $articleConf['startArticle'];
        $rowcount = $articleConf['startArticle'] + $config['articlePerPages'];

        if ($articleConf['startArticle'] < 0) {
            $articleConf['startArticle'] = 0;
        }

        $itemsObject->setFlux($flux);
        $items = $itemsObject->loadUnreadItemPerFlux($offset, $rowcount);

        echo $container['template_engine']->render('article.twig',
            [
                'events' => $items,
                'scroll' => $scroll,
            ]
        );
    });

    /* ---------------------------------------------------------------- */
    // Route: /article/category/{id} (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/category/{id}', function () use ($container, $trans, $config) {
        if (!$_SESSION['user']) {
            return header('location: /login');
            exit();
        }

        return header('location: /settings/manage');
        exit();
    });
});

/* ---------------------------------------------------------------- */
// Route: /settings (GET)
/* ---------------------------------------------------------------- */

$container['router']->mount('/settings', function () use ($container, $trans, $config, $cookiedir, $categoryObject, $fluxObject, $opmlObject) {
    $container['router']->get('/', function () use ($container, $cookiedir) {
        return header('location: /settings/manage');
        exit();
    });

    $container['router']->get('/settings/user', function () use ($container, $cookiedir) {
        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/synchronize/all (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/synchronize', function ($option) use ($container, $trans, $config, $cookiedir) {
        $container['logger']->info('On entre dans /settings/synchronize/all');
    });

    /* ---------------------------------------------------------------- */
    // Route: /statistics (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/statistics', function () use ($container, $trans, $config) {
        $container['logger']->info('On entre dans /statistics');
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/add (POST)
    /* ---------------------------------------------------------------- */
    $container['router']->post('/flux/add', function () use ($container, $trans, $config, $fluxObject) {
        $cat = isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1;
        $sp = new SimplePie();

        $fluxObject->setUrl($_POST['newUrl']);

        if ($fluxObject->notRegistered()) {

            //$fluxObject->getInfos();
            $fluxObject->setcategory((isset($_POST['newUrlCategory']) ? $_POST['newUrlCategory'] : 1));
            $fluxObject->add($sp);

        } else {

            $container['logger']->info($trans['FEED_ALREADY_STORED']);
        }

        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/remove/{id} (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/flux/remove/{id}', function ($id) use ($container, $trans, $config, $fluxObject) {
        $fluxObject->setId($id);
        $container['logger']->info($fluxObject->getId($id));
        $fluxObject->remove();

        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/rename (POST)
    // Action: Rename flux
    /* ---------------------------------------------------------------- */
    $container['router']->post('/flux/rename', function () use ($container, $fluxObject) {
        // data:{id:flux,name:fluxNameValue,url:fluxUrlValue}

        $fluxObject->setId($_POST['id']);
        $fluxObject->setName($_POST['name']);
        $fluxObject->setUrl($_POST['url']);

        return $fluxObject->rename();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/category/{id} (GET)
    // Action: Rename flux
    /* ---------------------------------------------------------------- */
    $container['router']->get('/flux/category/{id}', function ($id) use ($container, $trans, $config, $fluxObject) {
        $fluxObject->setCategory($_GET['id']);
        $fluxObject->setName($_GET['name']);
        $fluxObject->setUrl($_GET['url']);
        $fluxObject->changeCategory();

        return header('location: /settings');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/add (POST)
    /* ---------------------------------------------------------------- */
    $container['router']->post('/category/add', function () use ($container, $trans, $config, $categoryObject) {

        $name = $_POST['categoryName'];
        $categoryObject->setName($name);
        if (isset($_POST['categoryName']) && !$categoryObject->exist()) {

            $categoryObject->add();
        }

        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/remove/{id} (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/category/remove/{id}', function ($id) use ($container, $trans, $config) {
        if (isset($id) && is_numeric($id) && $id > 0) {
            //$eventManager->customQuery('DELETE FROM `' . MYSQL_PREFIX . 'items` WHERE `' . MYSQL_PREFIX . 'event`.`flux` in (SELECT `' . MYSQL_PREFIX . 'flux`.`id` FROM `' . MYSQL_PREFIX . 'flux` WHERE `' . MYSQL_PREFIX . 'flux`.`category` =\'' . intval($_['id']) . '\') ;');
            //$fluxManager->delete(array('category' => $id));
            //$categoryManager->delete(array('id' => $id));
        }

        return header('location: /settings/manage');
        exit();
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/category/rename (POST)
    /* ---------------------------------------------------------------- */
    $container['router']->post('/category/rename', function () use ($container, $trans, $config, $categoryObject) {

        $id = $_POST['id'];
        $name = $_POST['name'];
        $categoryObject->setId($id);
        $categoryObject->setName($name);

        $container['logger']->info(" avant le if rename");

        if (isset($_POST['id']) && $categoryObject->exist()) {

            $container['logger']->info(" on rentre dans le if rename");
            $categoryObject->rename();
        }
        header('location: /settings/manage');

    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/flux/export (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/flux/export', function () use ($container, $trans, $config, $opmlObject) {
        header('Content-Disposition: attachment;filename=export.opml');
        header('Content-Type: text/xml');

        echo $opmlObject->export();
    });

    $container['router']->get('/flux/import', function () use ($container, $trans, $config) {
        echo $container['template_engine']->render('settings.twig',
            [
                'action' => 'category',
                'section' => 'fluxs/import',
                'trans' => $trans,
                'otpEnabled' => false,
                'currentTheme' => $config['theme'],
                'config' => $config
            ]
        );
    });

    /* ---------------------------------------------------------------- */
    // Route: /settings/{option} (GET)
    /* ---------------------------------------------------------------- */
    $container['router']->get('/{option}', function ($option) use ($container, $trans, $config, $cookiedir, $categoryObject) {
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

        // @todo
        $resultsFlux = $container['database']->query('SELECT id FROM flux f ORDER BY name ');
        while ($rows = $resultsFlux->fetch_array()) {
            $flux['id'] = $rows['id'];
        }

        $container['logger']->info('Section: ' . $option);

        echo $container['template_engine']->render('settings.twig',
            [
                'action' => 'category',
                'section' => $option,
                'trans' => $trans,
                'themeList' => $themeList,
                'otpEnabled' => false,
                'currentTheme' => $config['theme'],
                'categories' => $categoryObject->getFluxByCategories(),
                'flux' => $flux,
                'config' => $config,
                'user' => $_SESSION['user']
            ]
        );

    });
});

/* ---------------------------------------------------------------- */
// Route: /action/read/all (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/action/read/all', function () use ($container, $trans, $config, $fluxObject) {
    $fluxObject->markAllRead();

    return header('location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /action/read/flux/{id} (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/action/read/flux/{id}', function ($id) use ($container, $trans, $config, $fluxObject) {
    $fluxObject->setId($id);
    $fluxObject->markAllRead();

    return header('location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /action/unread/flux/{id} (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/action/unread/flux/{id}', function ($id) use ($container, $trans, $config) {
    $result = $container['database']->query("update items set unread = 1 where guid = '" . $id . "'");
});

/* ---------------------------------------------------------------- */
// Route: /action/read/item/{id} (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/action/read/item/{id}', function ($id) use ($container, $trans, $config, $itemsObject) {
    $itemsObject->setGuid($id);
    $itemsObject->markItemAsReadByGuid();

    return header('location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /action/unread/item/{id} (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/action/unread/item/{id}', function ($id) use ($container, $trans, $config) {
    $result = $container['database']->query("update items set unread = 1 where guid = '" . $id . "'");
});

/* ---------------------------------------------------------------- */
// Route: /search (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/search', function () use ($container, $trans, $config) {
    $search = $this->escape_string($_GET['plugin_search']);
    $requete = "SELECT title,guid,content,description,link,pubdate,unread, favorite FROM items 
            WHERE title like '%" . htmlentities($search) . '%\'  OR content like \'%' . htmlentities($search) . '%\' ORDER BY pubdate desc';
});

/* ---------------------------------------------------------------- */
// Route: /action/read/category/{id} (GET)
/* ---------------------------------------------------------------- */
$container['router']->get('/action/read/category/{id}', function () use ($container, $trans, $config) {
    return header('Location: /');
    exit();
});

/* ---------------------------------------------------------------- */
// Route: /action/updateConfiguration (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/action/updateConfiguration', function () use ($container, $trans, $config) {
    if (!$_SESSION['user']) {
        return header('location: /login');
        exit();
    }
});

// @todo

/* ---------------------------------------------------------------- */
// Route: /qrcode
// @TODO
/* ---------------------------------------------------------------- */
$container['router']->mount('/qrcode', function () use ($container, $trans, $config) {
    $container['router']->get('/qr', function () {

        if (!$_SESSION['user']) {
            header('location: /login');
        }
    });
});

/* ---------------------------------------------------------------- */
// Route: /flux/{id} (GET)
/* ---------------------------------------------------------------- */

$container['router']->get('/flux/{id}', function ($id) use (
    $container,
    $trans,
    $scroll,
    $config,
    $itemsObject,
    $fluxObject,
    $categoryObject
) {
    $fluxObject->setId($id);
    $flux = $fluxObject->getFluxById();
    $itemsObject->setFlux($id);
    $numberOfItem = $itemsObject->countUnreadItemPerFlux();

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $startArticle = ($page - 1) * $config['articlePerPages'];

    $offset = ($page - 1) * $config['articlePerPages'];
    $row_count = $config['articlePerPages'];

    echo $container['template_engine']->render('index.twig',
        [
            'action' => 'items',
            'events' => $itemsObject->loadUnreadItemPerFlux($offset, $row_count),
            'flux' => $flux,
            'fluxId' => $id,
            'categories' => $categoryObject->getFluxByCategories(),
            'numberOfItem' => $numberOfItem,
            'page' => $page,
            'startArticle' => $startArticle,
            'user' => $_SESSION['user'],
            'scroll' => $scroll,
            'trans' => $trans,
            'config' => $config

        ]
    );
});

/* ---------------------------------------------------------------- */
// Route: /install
/* ---------------------------------------------------------------- */
$container['router']->mount('/install', function () use ($container, $trans) {
    /** @var InstallController $InstallController */
    $InstallController = $container['install_controller'];
    $InstallController->addTrans($trans);

    $container['router']->get(InstallRouting::HOME_PAGE, [$InstallController, 'homepage']);
    $container['router']->match('GET|POST', InstallRouting::GENERAL_PAGE, [$InstallController, 'general']);
    $container['router']->match('GET|POST', InstallRouting::DATABASE_PAGE, [$InstallController, 'database']);
    $container['router']->match('GET|POST', InstallRouting::USER_PAGE, [$InstallController, 'user']);
    $container['router']->get(InstallRouting::INSTALLATION_PAGE, [$InstallController, 'install']);
});

$container['router']->run();