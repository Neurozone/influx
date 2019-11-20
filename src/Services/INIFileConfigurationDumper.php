<?php

namespace Influx\Services;

use Influx\Constants;
use Influx\Services\Interfaces\FileConfigurationDumperInterface;

/**
 * Class INIFileConfigurationDumper
 */
class INIFileConfigurationDumper implements FileConfigurationDumperInterface
{
    public function dumpFile(string $filePath, array $configuration): void
    {
        $dataToWrite = [
            Constants::GENERAL_LANGUAGE => $configuration['general'][Constants::GENERAL_LANGUAGE],
            Constants::GENERAL_TEMPLATE => $configuration['general'][Constants::GENERAL_TEMPLATE],
            Constants::DB_HOST => $configuration['database'][Constants::DB_HOST],
            Constants::DB_LOGIN => $configuration['database'][Constants::DB_LOGIN],
            Constants::DB_PASSWORD => $configuration['database'][Constants::DB_PASSWORD],
            Constants::DB_DATABASE => $configuration['database'][Constants::DB_DATABASE],
            Constants::DB_PREFIX => $configuration['database'][Constants::DB_PREFIX],
        ];

        $lines_to_write = [];

        foreach ($dataToWrite as $key => $data) {
            $lines_to_write[] = $key . '=' . $data;
        }

        if ($fp = fopen($filePath, 'w')) {
            fwrite($fp, implode("\r\n", $lines_to_write));
        }

        fclose($fp);
    }
}