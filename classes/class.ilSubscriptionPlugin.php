<?php
require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');

/**
 * Class ilSubscriptionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionPlugin extends ilUserInterfaceHookPlugin {

	/**
	 * @var ilSubscriptionPlugin
	 */
	protected static $instance;


	/**
	 * @return ilSubscriptionPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @var ilDB
	 */
	protected $db;


	public function __construct() {
		parent::__construct();

		global $DIC;

		$this->db = $DIC->database();
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return 'Subscription';
	}


	protected function beforeUninstall() {
		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Config/class.msConfig.php';
		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/Subscription/class.msSubscription.php';

		$this->db->dropTable(msConfig::TABLE_NAME, false);
		$this->db->dropTable(msSubscription::TABLE_NAME, false);

		return true;
	}
}
