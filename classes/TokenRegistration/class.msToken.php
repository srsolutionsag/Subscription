<?php

/**
 * Class msToken
 *
 * TODO: Use ActiveRecord
 */
class msToken
{

    const TABLE_NAME = 'rep_robj_xmsb_token';


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
