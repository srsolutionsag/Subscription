<?php

/**
 * Class msToken
 *
 * TODO: Use ActiveRecord
 */
class msToken
{
    public const TABLE_NAME = 'rep_robj_xmsb_token';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }
}
