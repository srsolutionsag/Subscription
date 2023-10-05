<?php

/**
 * Class msInvitation
 *
 * TODO: Use ActiveRecord
 */
class msInvitation
{
    public const TABLE_NAME = 'rep_robj_xmsb_invt';

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
