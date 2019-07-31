<?php

namespace Influx;

class Install
{

    private $root = '/';

    public function checkDirectories()
    {

    }

    public function checkPermissions()
    {

    }

    public function createSuperUser()
    {
        /*
         `login` varchar(225) NOT NULL,
  `hash` varchar(225) NOT NULL,
  `email` varchar(225) NOT NULL,
  `otpSecret` varchar(225) DEFAULT NULL,
  `isAdmin` int(1) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `secret` varchar(100) NOT NULL,
         */
        $q = "INSERT INTO user(login, hash, email, isAdmin,secret) 
                VALUES ('" . $title . "', '" . $desc . "', '" . $link . "', '" . $this->getUrl() . "', 0," . $this->getFolder() . ", 1)";

        $this->logger->info($q);

        $return = $this->db->query($q);

        if ($this->db->errno) {
            $this->logger->info("\t\tFailure: \t$this->db->error\n");
            $this->logger->error($q);

            return "\t\tFailure: \t$this->db->error\n";

        }
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param string $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }



}

/*
$router->mount('/install', function () use ($router, $twig, $cookiedir, $logger) {



    $router->get('/', function () use ($twig, $cookiedir) {

        $filelist = glob("locales/*.json");

        foreach ($filelist as $file) {
            $locale = explode(".", basename($file));
            $list_lang[] = $locale[0];
        }

        $templateslist = glob("templates/*");
        foreach ($templateslist as $tpl) {
            $tpl_array = explode(".", basename($tpl));
            $list_templates[] = $tpl_array[0];
        }

        echo $twig->render('install.twig',
            [
                'list_lang' => $list_lang,
                'list_templates' => $list_templates,
            ]);

    });



    $router->post('/', function () use ($twig, $cookiedir) {

        $install = new Install();

        $lang = '';
        if (isset($_GET['lang'])) $lang = $_GET['lang'];
        elseif (isset($_POST['install_changeLngLeed'])) $lang = $_POST['install_changeLngLeed'];
        $installDirectory = dirname(__FILE__) . '/install';
// N'affiche que les langues du navigateur
// @TODO: il faut afficher toutes les langues disponibles
//        avec le choix par défaut de la langue préférée
        $languageList = Functions::getBrowserLanguages();
        if (!empty($lang)) {
            // L'utilisateur a choisi une langue, qu'on incorpore dans la liste
            array_unshift($languageList, $lang);
            $liste = array_unique($languageList);
        }
        unset($i18n); //@TODO: gérer un singleton et le choix de langue / liste de langue
        $currentLanguage = i18n_init($languageList, $installDirectory);
        $languageList = array_unique($i18n->languages);
        if (file_exists('constant.php')) {
            die(_t('ALREADY_INSTALLED'));
        }
        define('DEFAULT_TEMPLATE', 'influx');
        $templates = scandir('templates');
        if (!in_array(DEFAULT_TEMPLATE, $templates)) die('Missing default template : ' . DEFAULT_TEMPLATE);
        $templates = array_diff($templates, array(DEFAULT_TEMPLATE, '.', '..')); // Répertoires non voulus sous Linux
        sort($templates);
        $templates = array_merge(array(DEFAULT_TEMPLATE), $templates); // le thème par défaut en premier
// Cookie de la session
        $cookiedir = '';
        if (dirname($_SERVER['SCRIPT_NAME']) != '/') $cookiedir = dirname($_SERVER["SCRIPT_NAME"]) . '/';
        session_set_cookie_params(0, $cookiedir);
        session_start();
// Protection des variables
        $_ = array_merge($_GET, $_POST);
        $installActionName = 'installButton';
        $install->launch($_, $installActionName);

        $constant = "<?php
//Host de Mysql, le plus souvent localhost ou 127.0.0.1
define('MYSQL_HOST','{$this->options['db']['mysqlHost']}');
//Identifiant MySQL
define('MYSQL_LOGIN','{$this->options['db']['mysqlLogin']}');
//mot de passe MySQL
define('MYSQL_MDP','{$this->options['db']['mysqlMdp']}');
//Nom de la base MySQL ou se trouvera leed
define('MYSQL_BDD','{$this->options['db']['mysqlBase']}');
//Prefix des noms des tables leed pour les bases de données uniques
define('MYSQL_PREFIX','{$this->options['db']['mysqlPrefix']}');
?>";

        file_put_contents(self::CONSTANT_FILE, $constant);
        if (!is_readable(self::CONSTANT_FILE))
            die('"' . self::CONSTANT_FILE . '" not found!');

        header('location: /login');

    });

});

$router->run();

*/
