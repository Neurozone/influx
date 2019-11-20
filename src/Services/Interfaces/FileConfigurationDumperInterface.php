<?php

namespace Influx\Services\Interfaces;

interface FileConfigurationDumperInterface
{
    public function dumpFile(string $filePath, array $configuration): void;
}