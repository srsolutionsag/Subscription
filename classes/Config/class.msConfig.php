<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');

/**
 * Class msConfig
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class msConfig extends ActiveRecord {

	const TYPE_NO_USAGE = 0;
	const TYPE_USAGE_MAIL = 1;
	const TYPE_USAGE_MATRICULATION = 2;
	const TYPE_USAGE_BOTH = 3;
	const ENBL_INV = 'enable_invitation';
	const F_ALLOW_REGISTRATION = 'allow_registration';
	const F_ASK_FOR_LOGIN = 'ask_for_login';
	const F_FIXED_EMAIL = 'fixed_email';
	const F_SHIBBOLETH = 'shibboleth';
	const F_METADATA_XML = 'metadata_xml';
	const F_USE_MATRICULATION = 'use_matriculation';
	const F_SHOW_NAMES = 'show_names';
	const F_SYSTEM_USER = 'system_user';
	const F_USE_EMAIL = 'use_email';
	const F_SEND_MAILS = 'send_mails';
	const F_PURGE = 'purge';
	const F_ACTIVATE_GROUPS = 'activate_groups';
	const F_ACTIVATE_COURSES = 'activate_courses';
	const F_IGNORE_SUBTREE = 'ignore_subtree';
	const F_IGNORE_SUBTREE_ACTIVE = 'activate_ignore_subtree';
	const TABLE_NAME = 'rep_robj_xmsb_conf';
	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}


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
	 * @param $key
	 *
	 * @return array|string
	 */
	public static function get($key) {
		$obj = new self($key);

		return $obj->getConfigValue();
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public static function set($name, $value) {
		$obj = new self($name);
		$obj->setConfigValue($value);
		if (self::where(array( 'config_key' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	/**
	 * @return bool
	 */
	public static function checkShibboleth() {
		return self::get(self::F_SHIBBOLETH) AND is_readable(self::get(self::F_METADATA_XML));
	}


	/**
	 * @return bool
	 */
	public static function isOldILIAS() {
		require_once('./include/inc.ilias_version.php');
		require_once('./Services/Component/classes/class.ilComponent.php');

		return !ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999');
	}


	/**
	 * @return string
	 */
	public static function getPath() {
		return strstr(ILIAS_HTTP_PATH, 'Customizing', true);
	}


	/**
	 * @return int
	 */
	public static function getUsageType() {
		$usage_type = self::TYPE_NO_USAGE;
		if (self::get(self::F_USE_EMAIL)) {
			$usage_type = self::TYPE_USAGE_MAIL;
		}
		if (self::get(self::F_USE_MATRICULATION)) {
			$usage_type = self::TYPE_USAGE_MATRICULATION;
		}
		if (self::get(self::F_USE_MATRICULATION) AND self::get(self::F_USE_EMAIL)) {
			$usage_type = self::TYPE_USAGE_BOTH;
		}

		return $usage_type;
	}


	/**
	 * @param $check_ref_id
	 *
	 * @return bool
	 */
	public static function isInIgnoredSubtree($check_ref_id) {
		if (!self::get(self::F_IGNORE_SUBTREE_ACTIVE)) {
			return false;
		}
		if (isset(self::$ignore_chache[$check_ref_id])) {
			return self::$ignore_chache[$check_ref_id];
		}

		global $tree;
		/**
		 * @var $tree ilTree
		 */
		$subtrees = explode(',', self::get(self::F_IGNORE_SUBTREE));
		if (!is_array($subtrees) OR count($subtrees) == 0) {
			self::$ignore_chache[$check_ref_id] = false;

			return false;
		}

		$return = false;
		foreach ($subtrees as $ref_id) {
			if (!$ref_id) {
				continue;
			}
			if ($tree->isGrandChild($ref_id, $check_ref_id)) {
				$return = true;
			}
		}

		self::$ignore_chache[$check_ref_id] = $return;

		return self::$ignore_chache[$check_ref_id];
	}


	/**
	 * @param string $config_key
	 */
	public function setConfigKey($config_key) {
		$this->config_key = $config_key;
	}


	/**
	 * @return string
	 */
	public function getConfigKey() {
		return $this->config_key;
	}


	/**
	 * @param string $config_value
	 */
	public function setConfigValue($config_value) {
		$this->config_value = $config_value;
	}


	/**
	 * @return string
	 */
	public function getConfigValue() {
		return $this->config_value;
	}
}

?>
