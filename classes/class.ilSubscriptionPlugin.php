<?php

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/vendor/autoload.php');

/**
 * Class ilSubscriptionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionPlugin extends ilUserInterfaceHookPlugin
{
    public const PLUGIN_ID = 'subscription';
    public const PLUGIN_NAME = 'Subscription';
    /**
     * @var ilSubscriptionPlugin
     */
    protected static $instance;


    /**
     * @return ilSubscriptionPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @return bool
     */
    protected function beforeUninstall(): bool
    {
        $this->db->dropTable(msConfig::TABLE_NAME, false);
        $this->db->dropTable(msInvitation::TABLE_NAME, false);
        $this->db->dropTable(msSubscription::TABLE_NAME, false);
        $this->db->dropTable(msToken::TABLE_NAME, false);

        return true;
    }
}
