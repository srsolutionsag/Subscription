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
		if (! isset(self::$instance)) {
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
		if ($ilCtrl->lookupClassPath('ilRouterGUI') === NULL OR ! is_file($path . 'class.ActiveRecord.php') OR ! is_file($path
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


	public function updateLanguageFiles() {
		if (! in_array('SimpleXLSX', get_declared_classes())) {
			require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/lib/simplexlsx.class.php');
		}
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.xlsx')) {
			$file = $path . 'lang_custom.xlsx';
		} else {
			$file = $path . 'lang.xlsx';
		}
		$xslx = new SimpleXLSX($file);
		$new_lines = array();
		$keys = array();
		foreach ($xslx->rows() as $n => $row) {
			if ($n == 0) {
				$keys = $row;
				continue;
			}
			$data = $row;
			foreach ($keys as $i => $k) {
				if ($k != 'var' AND $k != 'part') {
					$new_lines[$k][] = $data[0] . '_' . $data[1] . '#:#' . $data[$i];
				}
			}
		}
		$start = '<!-- language file start -->' . PHP_EOL;
		$status = true;
		foreach ($new_lines as $lng_key => $lang) {
			$status = file_put_contents($path . 'ilias_' . $lng_key . '.lang', $start . implode(PHP_EOL, $lang));
		}

		if (! $status) {
			ilUtil::sendFailure('Language-Files coul\'d not be written');
		}
		$this->updateLanguages();
	}
}

?>
