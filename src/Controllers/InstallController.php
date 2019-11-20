<?php

namespace Influx\Controllers;

use Influx\ConfigurationService;
use Influx\Constants;
use Influx\Routing\InstallRouting;
use Influx\Services\InstallerService;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Class InstallController
 * @package Influx\Controllers
 */
class InstallController
{
    private const STEP_GENERAL = 'step.general';
    private const STEP_DATABASE = 'step.database';
    private const STEP_USER = 'step.user';

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var InstallerService
     */
    private $installerService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $trans;

    private $installationData = ['general' => [], 'database' => [], 'user' => []];

    /**
     * InstallController constructor.
     * @param Environment $twig
     * @param ConfigurationService $configurationService
     * @param InstallerService $installerService
     * @param LoggerInterface $logger
     */
    public function __construct(Environment $twig, ConfigurationService $configurationService, InstallerService $installerService, LoggerInterface $logger = null)
    {
        $this->twig = $twig;
        $this->configurationService = $configurationService;
        $this->installerService = $installerService;
        $this->logger = $logger;
    }

    public function addTrans($trans)
    {
        $this->trans = $trans;
    }

    /* ---------------------------------------------------------------- */
    // Route: /install (GET)
    /* ---------------------------------------------------------------- */
    public function homepage()
    {
        $_SESSION['installation_data'] = json_encode($this->installationData);
        echo $this->twig->render('installation/homepage.html.twig',
            [
                'trans' => $this->trans,
            ]);
    }

    /* ---------------------------------------------------------------- */
    // Route: /install/general (GET)
    /* ---------------------------------------------------------------- */
    public function general()
    {
        $this->preController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRequest(self::STEP_GENERAL, $_POST);
            $_SESSION['installation_data'] = json_encode($this->installationData);
            \Safe\session_write_close();

            return header('Location: /install' . InstallRouting::DATABASE_PAGE);
        }

        echo $this->twig->render('installation/general.html.twig',
            [
                'action' => 'general',
                'list_lang' => $this->getLocales(),
                'list_templates' => $this->getTemplatesList(),
                'trans' => $this->trans,
                'general_informations' => $this->installationData['general'],
            ]);
    }

    /* ---------------------------------------------------------------- */
    // Route: /install/database (GET)
    /* ---------------------------------------------------------------- */
    public function database()
    {
        $this->preController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRequest(self::STEP_DATABASE, $_POST);
            $_SESSION['installation_data'] = json_encode($this->installationData);
            \Safe\session_write_close();

            return header('Location: /install' . InstallRouting::USER_PAGE);
        }

        echo $this->twig->render('installation/database.html.twig',
            [
                'trans' => $this->trans,
                'database_informations' => $this->installationData['database'],
            ]);
    }

    /* ---------------------------------------------------------------- */
    // Route: /install/user (GET)
    /* ---------------------------------------------------------------- */
    public function user()
    {
        $this->preController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRequest(self::STEP_USER, $_POST);
            $_SESSION['installation_data'] = json_encode($this->installationData);
            \Safe\session_write_close();

            return header('Location: /install' . InstallRouting::INSTALLATION_PAGE);
        }

        echo $this->twig->render('installation/user.html.twig',
            [
                'trans' => $this->trans,
                'user_informations' => $this->installationData['user']
            ]);
    }

    public function install()
    {
        $this->preController();
        $this->installerService->proceedToInstallation($this->configurationService, $this->installationData);
    }

    public function handleRequest($step, $request)
    {
        switch ($step) {
            case self::STEP_GENERAL:
                $this->registerGeneralInformations($request);
                break;
            case self::STEP_DATABASE:
                $this->registerDatabaseInformations($request);
                break;
            case self::STEP_USER:
                $this->registerUserInformations($request);
                break;
            default:
                throw new \RuntimeException('No valid installation step');
                break;
        }
    }

    /**
     * PRIVATE METHODS
     */
    protected function preController()
    {
        $this->installationData = array_key_exists('installation_data', $_SESSION) ?
            json_decode($_SESSION['installation_data'], true) : $this->installationData;
    }

    private function registerGeneralInformations($request)
    {
        $this->installationData['general'] = array_merge($this->installationData['general'], [
            Constants::GENERAL_LANGUAGE => $request[Constants::GENERAL_LANGUAGE],
            Constants::GENERAL_TEMPLATE => $request[Constants::GENERAL_TEMPLATE],
        ]);
    }

    private function registerDatabaseInformations($request)
    {
        $this->installationData['database'] = array_merge($this->installationData['database'], [
            Constants::DB_HOST => $request[Constants::DB_HOST],
            Constants::DB_LOGIN => $request[Constants::DB_LOGIN],
            Constants::DB_PASSWORD => $request[Constants::DB_PASSWORD],
            Constants::DB_DATABASE => $request[Constants::DB_DATABASE],
            Constants::DB_PREFIX => $request[Constants::DB_PREFIX],
        ]);
    }

    private function registerUserInformations($request)
    {
        $this->installationData['user'] = array_merge($this->installationData['user'], [
            Constants::ADMIN_USER => $request[Constants::ADMIN_USER],
            Constants::ADMIN_PASSWORD => $request[Constants::ADMIN_PASSWORD],
        ]);
    }

    private function getTemplatesList()
    {
        $templatesList = glob($this->configurationService->getConfiguration()['templates_folder'] . '/*');
        $listTemplates = [];

        foreach ($templatesList as $tpl) {
            $tpl_array = explode('.', basename($tpl));
            $listTemplates[] = $tpl_array[0];
        }

        return $listTemplates;
    }

    private function getLocales()
    {
        $fileList = glob($this->configurationService->getConfiguration()['trans_folder'] . '/*');
        $list_lang = [];

        foreach ($fileList as $file) {
            $locale = explode('.', basename($file));
            $list_lang[] = $locale[0];
        }

        return $list_lang;
    }
}