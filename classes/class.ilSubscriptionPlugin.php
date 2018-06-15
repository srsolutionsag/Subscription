<?php
require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilSubscriptionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionPlugin extends ilUserInterfaceHookPlugin {

	const PLUGIN_ID = 'subscription';
	const PLUGIN_NAME = 'Subscription';
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
		return self::PLUGIN_NAME;
	}


	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		$this->db->dropTable(msConfig::TABLE_NAME, false);
		$this->db->dropTable(msSubscription::TABLE_NAME, false);

		return true;
	}
}
