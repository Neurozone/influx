<?php

namespace Influx\Routing;

/**
 * Class InstallRouting
 */
class InstallRouting
{
    public const EMPTY = '';
    public const HOME_PAGE = '/';
    public const GENERAL_PAGE = '/general';
    public const DATABASE_PAGE = '/database';
    public const USER_PAGE = '/user';
    public const INSTALLATION_PAGE = '/installation';

    public static function getRoutes($prefix = null)
    {
        return [
            $prefix ? '/' . $prefix . self::EMPTY : self::EMPTY,
            $prefix ? '/' . $prefix . self::HOME_PAGE : self::HOME_PAGE,
            $prefix ? '/' . $prefix . self::GENERAL_PAGE : '/' . self::GENERAL_PAGE,
            $prefix ? '/' . $prefix . self::DATABASE_PAGE : '/' . self::HOME_PAGE,
            $prefix ? '/' . $prefix . self::USER_PAGE : '/' . self::HOME_PAGE,
            $prefix ? '/' . $prefix . self::INSTALLATION_PAGE : '/' . self::INSTALLATION_PAGE,
        ];
    }
}