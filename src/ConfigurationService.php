<?php

namespace Influx;


class ConfigurationService
{
    public const ROOT_PATH = __DIR__ . '/..';
    public const DEFAULT_TEMPLATE_NAME = 'influx';

    private $filePath;
    private $configuration;

    public function __construct($configurationfilePath)
    {
        $this->filePath = $configurationfilePath;
        $this->loadConfigurationFromFile($this->filePath, true);
    }

    public function loadConfigurationFromFile($file, $default = false)
    {
        if ($file && file_exists($file)) {
            $configuration = parse_ini_file($file);
            $this->configuration = $this->getDefaultConfiguration();
            $this->setConfiguration(array_merge($this->configuration, $configuration));

            return $this->getConfiguration();
        }

        if ($default) {
            $this->setConfiguration($this->getDefaultConfiguration());

            return $this->getDefaultConfiguration();
        }

        throw new \RuntimeException('Could not initialize the configuration');
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultConfiguration()
    {
        $defaultConfiguration['root_path'] = self::ROOT_PATH;
        $defaultConfiguration['template_name'] = self::DEFAULT_TEMPLATE_NAME;
        $defaultConfiguration['templates_folder'] = self::ROOT_PATH . '/templates';
        $defaultConfiguration['template_path'] = self::ROOT_PATH . '/templates/' . self::DEFAULT_TEMPLATE_NAME;
        $defaultConfiguration['template_cache_dir'] = self::ROOT_PATH . '/cache';
        $defaultConfiguration['trans_folder'] = self::ROOT_PATH . '/translations';
        $defaultConfiguration['sql_folder'] = self::ROOT_PATH . '/sql';
        $defaultConfiguration['cookie_dir'] = '';
        $defaultConfiguration['logger_log_path'] = self::ROOT_PATH . '/logs/influx.log';
        $defaultConfiguration['config_file_path'] = $this->filePath;
        $defaultConfiguration[Constants::GENERAL_LANGUAGE] = 'en';

        return $defaultConfiguration;
    }

    public function hasConfigurationFile()
    {
        return file_exists($this->filePath);
    }
}