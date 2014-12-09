<?php
require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/class.ilDynamicLanguage.php');

/**
 * Class ilSubscriptionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilSubscriptionPlugin extends ilUserInterfaceHookPlugin implements ilDynamicLanguageInterface {

	/**
	 * @return string
	 */
	public function getCsvPath() {
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.csv')) {
			$file = $path . 'lang_custom.csv';
		} else {
			$file = $path . 'lang.csv';
		}

		return $file;
	}


	/**
	 * @return string
	 */
	public function getAjaxLink() {
		return false;
	}


	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getDynamicTxt($key) {
		return ilDynamicLanguage::getInstance($this, ilDynamicLanguage::MODE_PROD)->txt($key);
	}


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
	 * @return string
	 */
	public function getPluginName() {
		return 'Subscription';
	}


	/**
	 * @return bool
	 */
	public static function checkPreconditions() {
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$path = strstr(__FILE__, 'Services', true) . 'Libraries/ActiveRecord/';
		global $ilCtrl;
		if ($ilCtrl->lookupClassPath('ilRouterGUI') === NULL OR !is_file($path . 'class.ActiveRecord.php') OR !is_file($path
				. 'class.ActiveRecordList.php')
		) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function beforeActivation() {
		return self::checkPreconditions();
	}
}

?>
