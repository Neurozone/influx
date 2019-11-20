<?php

namespace Influx;

use Influx\Controllers\InstallController;
use Influx\Services\INIFileConfigurationDumper;
use Influx\Services\InstallerService;
use Influx\Services\PHPFileConfigurationDumper;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Sinergi\BrowserDetector\Language;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class DependencyInjection
 */
class DependencyInjection
{
    private const LOGS_DAYS_TO_KEEP = 7;

    private $container;
    private $configurationService;

    public function __construct(Container $container, ConfigurationService $configurationService = null)
    {
        $this->container = $container;
        $this->configurationService = $configurationService;
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->initializeParameters();
        $this->initializeServices();
    }

    private function initializeParameters()
    {
        if ($this->configurationService->hasConfigurationFile()) {
            $configuration = $this->configurationService->getConfiguration();

            $this->container[Constants::DB_DATABASE] = $configuration[Constants::DB_DATABASE];
            $this->container[Constants::DB_HOST] = $configuration[Constants::DB_HOST];
            $this->container[Constants::DB_LOGIN] = $configuration[Constants::DB_LOGIN];
            $this->container[Constants::DB_PASSWORD] = $configuration[Constants::DB_PASSWORD];
            $this->container[Constants::DB_PREFIX] = $configuration[Constants::DB_PREFIX];
            $this->container[Constants::DB_CHARSET] = $configuration[Constants::DB_CHARSET];
            $this->container[Constants::GENERAL_LANGUAGE] = $configuration[Constants::GENERAL_LANGUAGE];

        } else {
            $configuration = $this->configurationService->getDefaultConfiguration();
        }

        $this->container['root_path'] = $configuration['root_path'];
        $this->container['template_name'] = $configuration['template_name'];
        $this->container['templates_folder'] = $configuration['templates_folder'];
        $this->container['template_path'] = $configuration['template_path'];
        $this->container['template_cache_dir'] = $configuration['template_cache_dir'];
        $this->container['trans_folder'] = $configuration['trans_folder'];
        $this->container['cookie_dir'] = $configuration['cookie_dir'];
        $this->container['logger_log_path'] = $configuration['logger_log_path'];
        $this->container['config_file_path'] = $configuration['config_file_path'];
        $this->container['config_file_name'] = $configuration['config_file_name'];
        $this->container[Constants::GENERAL_LANGUAGE] = 'en';
    }

    private function initializeServices()
    {
        $this->container['logger'] = function ($c) {
            $logger = new Logger('influxLogger');
            $logger->pushHandler(new RotatingFileHandler($c['logger_log_path'], self::LOGS_DAYS_TO_KEEP ?? 7));
            $logger->pushHandler(new StreamHandler($c['logger_log_path'], Logger::DEBUG));

            return $logger;
        };

        $this->container['template_engine'] = function ($c) {
            $loader = new FilesystemLoader($c['template_path']);
            $twig = new Environment($loader, ['cache' => $c['template_cache_dir'], 'debug' => true,]);
            $twig->addExtension(new \Twig\Extension\DebugExtension());

            $twig->addGlobal('template_name', $c['template_name'] ?? self::TEMPLATE_NAME);

            return $twig;
        };

        $this->container['router'] = function ($c) {
            return new \Bramus\Router\Router();
        };

        $this->container['install_controller'] = function ($c): InstallController {
            return new InstallController($c['template_engine'], $c['configuration_service'], $c['installer_service'], $c['logger']);
        };

        $this->container['langage'] = function ($c) {
            return new Language();
        };

        $this->container['configuration_service'] = function ($c) {
            return new ConfigurationService($c['config_file_path']);
        };

        $this->container['installer_service'] = function ($c) {
            return new InstallerService(new INIFileConfigurationDumper());
        };

        $this->container['database_service'] = function ($c) {
            if (
                (null !== $c[Constants::DB_HOST])
                && (null !== $c[Constants::DB_LOGIN])
                && (null !== $c[Constants::DB_PASSWORD])
                && (null !== $c[Constants::DB_DATABASE])
            ) {
                $database = new \mysqli($c[Constants::DB_HOST], $c[Constants::DB_LOGIN], $c[Constants::DB_PASSWORD], $c[Constants::DB_DATABASE]);
                $database->set_charset(Constants::DB_CHARSET);
                $database->query('SET NAMES ' . Constants::DB_CHARSET);

                return $database;
            }

            return null;
        };
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}