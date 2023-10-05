<?php

/**
 * Class ilSubscriptionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionPlugin extends ilUserInterfaceHookPlugin
{
    public const PLUGIN_ID = 'subscription';
    public const PLUGIN_NAME = 'Subscription';

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) {
            global $DIC;
            // ILIAS 8
            if (isset($DIC['component.factory']) && $DIC['component.factory'] instanceof ilComponentFactory) {
                $component_factory = $DIC['component.factory'];
                self::$instance = $component_factory->getPlugin(self::PLUGIN_ID);
            } // ILIAS 7
            else {
                self::$instance = new self();
            }
        }

        return self::$instance;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    protected function beforeUninstall(): bool
    {
        $this->db->dropTable(msConfig::TABLE_NAME, false);
        $this->db->dropTable(msInvitation::TABLE_NAME, false);
        $this->db->dropTable(msSubscription::TABLE_NAME, false);
        $this->db->dropTable(msToken::TABLE_NAME, false);

        return true;
    }
}
