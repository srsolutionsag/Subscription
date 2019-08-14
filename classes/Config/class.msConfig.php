<?php

/**
 * Class msConfig
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class msConfig extends ActiveRecord
{

    const TYPE_NO_USAGE = 0;
    const TYPE_USAGE_MAIL = 1;
    const TYPE_USAGE_MATRICULATION = 2;
    const TYPE_USAGE_BOTH = 3;
    const F_ENABLE_SENDING_INVITATIONS = 'enable_invitation';
    const F_ALLOW_REGISTRATION = 'allow_registration';
    const F_ASK_FOR_LOGIN = 'ask_for_login';
    const F_FIXED_EMAIL = 'fixed_email';
    const F_SHIBBOLETH = 'shibboleth';
    const F_METADATA_XML = 'metadata_xml';
    const F_USE_MATRICULATION = 'use_matriculation';
    const F_SHOW_NAMES = 'show_names';
    const F_SYSTEM_USER = 'system_user';
    const F_USE_EMAIL_FOR_USERS = 'use_email';
    const F_SEND_MAILS_FOR_COURSE_SUBSCRIPTION = 'send_mails';
    const F_PURGE = 'purge';
    const F_ACTIVATE_GROUPS = 'activate_groups';
    const F_ACTIVATE_COURSES = 'activate_courses';
    const F_IGNORE_SUBTREE = 'ignore_subtree';
    const F_IGNORE_SUBTREE_ACTIVE = 'activate_ignore_subtree';
    const TABLE_NAME = 'rep_robj_xmsb_conf';


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


    /**
     * @var bool
     */
    protected $ar_safe_read = false;
    /**
     * @var array
     */
    protected static $ignore_chache = array();
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $config_key;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           1000
     */
    protected $config_value;


    /**
     * @param string $key
     *
     * @return array|string
     */
    public static function getValueByKey($key)
    {
        $obj = self::findOrGetInstance($key);

        return $obj->getConfigValue();
    }


    /**
     * @param string $name
     * @param string $value
     */
    public static function set($name, $value)
    {
        $obj = new self($name);
        $obj->setConfigValue($value);
        if (self::where(array('config_key' => $name))->hasSets()) {
            $obj->update();
        } else {
            $obj->create();
        }
    }


    /**
     * @return bool
     */
    public static function checkShibboleth()
    {
        return self::getValueByKey(self::F_SHIBBOLETH) AND is_readable(self::getValueByKey(self::F_METADATA_XML));
    }


    /**
     * @return string
     */
    public static function getPath()
    {
        return strstr(ILIAS_HTTP_PATH, 'Customizing', true);
    }


    /**
     * @return int
     */
    public static function getUsageType()
    {
        $usage_type = self::TYPE_NO_USAGE;

        if (self::getValueByKey(self::F_USE_EMAIL_FOR_USERS)) {
            $usage_type = self::TYPE_USAGE_MAIL;
        }
        if (self::getValueByKey(self::F_USE_MATRICULATION)) {
            $usage_type = self::TYPE_USAGE_MATRICULATION;
        }
        if (self::getValueByKey(self::F_USE_MATRICULATION) AND self::getValueByKey(self::F_USE_EMAIL_FOR_USERS)) {
            $usage_type = self::TYPE_USAGE_BOTH;
        }

        return $usage_type;
    }


    /**
     * @param int $check_ref_id
     *
     * @return bool
     */
    public static function isInIgnoredSubtree($check_ref_id)
    {
        if (!self::getValueByKey(self::F_IGNORE_SUBTREE_ACTIVE)) {
            return false;
        }
        if (isset(self::$ignore_chache[$check_ref_id])) {
            return self::$ignore_chache[$check_ref_id];
        }

        global $DIC;

        $subtrees = explode(',', self::getValueByKey(self::F_IGNORE_SUBTREE));
        if (!is_array($subtrees) OR count($subtrees) == 0) {
            self::$ignore_chache[$check_ref_id] = false;

            return false;
        }

        $return = false;
        foreach ($subtrees as $ref_id) {
            if (!$ref_id) {
                continue;
            }
            if ($DIC->repositoryTree()->isGrandChild($ref_id, $check_ref_id)) {
                $return = true;
            }
        }

        self::$ignore_chache[$check_ref_id] = $return;

        return self::$ignore_chache[$check_ref_id];
    }


    /**
     * @param string $config_key
     */
    public function setConfigKey($config_key)
    {
        $this->config_key = $config_key;
    }


    /**
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }


    /**
     * @param string $config_value
     */
    public function setConfigValue($config_value)
    {
        $this->config_value = $config_value;
    }


    /**
     * @return string
     */
    public function getConfigValue()
    {
        return $this->config_value;
    }
}
