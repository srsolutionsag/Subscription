<?php

/**
 * Class msInvitation
 *
 * TODO: Use ActiveRecord
 */
class msInvitation
{

    const TABLE_NAME = 'rep_robj_xmsb_invt';


    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @return string
     * @deprecated
     */
    public static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }
}
