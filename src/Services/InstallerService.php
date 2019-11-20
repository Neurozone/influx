<?php

namespace Influx\Services;

use Influx\ConfigurationService;
use Influx\Constants;
use Influx\Services\Interfaces\FileConfigurationDumperInterface;

/**
 * Class InstallerService
 */
class InstallerService
{
    private $fileConfigurationDumper;

    /**
     * Install constructor.
     * @param $fileConfigurationDumper
     */
    public function __construct(FileConfigurationDumperInterface $fileConfigurationDumper)
    {
        $this->fileConfigurationDumper = $fileConfigurationDumper;
    }

    /**
     * @param ConfigurationService $configurationService
     * @param $configuration
     */
    public function proceedToInstallation(ConfigurationService $configurationService, $configuration)
    {
        //We write the configuration file
        $this->fileConfigurationDumper->dumpFile($configurationService->getConfiguration()['config_file_path'], $configuration);
        $configurationService->loadConfigurationFromFile($configurationService->getConfiguration()['config_file_path']);
        //We create the database if it doesn't exist
        $this->createDatabase($configurationService, $configuration);
        //We create the super user
        $this->createAdmin($configurationService, $configuration);

        //We redirect to the login page
        return header('Location: /login');
    }

    /**
     * PRIVATE METHODS
     * @param ConfigurationService $configurationService
     * @param $configuration
     * @return string|null
     */
    private function createDatabase(ConfigurationService $configurationService, $configuration)
    {
        $db = new \mysqli($configuration['database'][Constants::DB_HOST], $configuration['database'][Constants::DB_LOGIN], $configuration['database'][Constants::DB_PASSWORD], $configuration['database'][Constants::DB_DATABASE]);

        if (mysqli_connect_errno()) {
            throw new \RuntimeException('Could not connect to database');
        }

        foreach (self::getDatabaseCreationQueries() as $table => $sql) {
            $tableName = $configuration['database'][Constants::DB_PREFIX] !== null ? $configuration['database'][Constants::DB_PREFIX] . $table : $table;
            $query = str_replace('{{table_name}}', $tableName, $sql);
            $db->query($query);
        }

        $db->close();
    }

    private function createAdmin(ConfigurationService $configurationService, $configuration)
    {
        $db = new \mysqli($configuration['database'][Constants::DB_HOST], $configuration['database'][Constants::DB_LOGIN], $configuration['database'][Constants::DB_PASSWORD], $configuration['database'][Constants::DB_DATABASE]);

        if (mysqli_connect_errno()) {
            throw new \RuntimeException('Could not connect to database');
        }

        $tableName = $configuration['database'][Constants::DB_PREFIX] !== null ? $configuration['database'][Constants::DB_PREFIX] . 'user' : 'user';

        $query = 'INSERT INTO ' . $tableName . ' SET login = ?, password = ?';

        if (false !== ($stmt = $db->prepare($query))) {
            $stmt->bind_param('ss', $configuration['user'][Constants::ADMIN_USER], password_hash($configuration['user'][Constants::ADMIN_PASSWORD], PASSWORD_DEFAULT));
            $stmt->execute();
            $stmt->close();
        } else {
            throw new \RuntimeException('Could not create user');
        }

        $db->close();
    }

    /**
     * @return array
     */
    private static function getDatabaseCreationQueries()
    {
        return [
            'configuration' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `key` varchar(255) NOT NULL,
            `value` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniquekey` (`key`)
        ) ENGINE=InnoDB;
            ',
            'event' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `guid` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
            `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `creator` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
            `content` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
            `link` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
            `unread` int(11) NOT NULL,
            `feed` int(11) NOT NULL,
            `favorite` int(11) NOT NULL,
            `pubdate` int(11) NOT NULL,
            `syncId` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `indexfeed` (`feed`),
            KEY `indexunread` (`unread`),
            KEY `indexfavorite` (`favorite`),
            KEY `dba_idx_leed_event_3` (`feed`,`unread`),
            KEY `dba_idx_leed_event_4` (`pubdate`),
            KEY `dba_idx_leed_event_5` (`guid`,`feed`),
            KEY `indexguidfeed` (`guid`,`feed`),
            KEY `dba_idx_leed_event_6` (`creator`),
            KEY `dba_idx_leed_event_7` (`feed`,`unread`,`pubdate`),
            KEY `dba_idx_leed_event_8` (`title`),
            KEY `dba_idx_leed_event_9` (`guid`),
            FULLTEXT KEY `title` (`title`),
            FULLTEXT KEY `title_2` (`title`)
        ) ENGINE=InnoDB;
    ',
            'feed' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
            `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
            `website` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
            `url` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
            `lastupdate` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
            `folder` int(11) NOT NULL,
            `isverbose` int(1) NOT NULL,
            `lastSyncInError` int(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `indexfolder` (`folder`),
            KEY `dba_idx_leed_feed_1` (`id`,`name`),
            KEY `dba_idx_leed_feed_2` (`name`,`id`),
            KEY `dba_idx_leed_feed_3` (`name`)
        ) ENGINE=InnoDB;
    ',
            'folder' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(225) NOT NULL,
            `parent` int(11) NOT NULL,
            `isopen` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;
    ',
            'plugin_feaditlater' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `event` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;
    ',
            'plugin_search' => '
        CREATE TABLE `{{table_name}}` (
            `search` varchar(255) NOT NULL,
            PRIMARY KEY (`search`)
        ) ENGINE=InnoDB;
    ',
            'user' => '
        CREATE TABLE `{{table_name}}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `login` varchar(225) NOT NULL,
            `password` varchar(225) NOT NULL,
            `otpSecret` varchar(225) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;
    '
        ];
    }
}
