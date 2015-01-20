<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/UserStatus/class.msUserStatus.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Subscription/classes/AccountType/class.msAccountType.php');
require_once('./Modules/Group/classes/class.ilGroupMembershipMailNotification.php');

/**
 * msSubscription
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 *
 * @version
 */
class msSubscription extends ActiveRecord {

	/**
	 * @var array
	 */
	protected static $deletable_status = array(
		msUserStatus::STATUS_USER_NOT_ASSIGNABLE,
		msUserStatus::STATUS_USER_CAN_BE_INVITED,
	);
	const TYPE_EMAIL = 1;
	const TYPE_MATRICULATION = 2;
	const DELIMITER = '|';
	const CONTEXT_CRS = 1;
	const CONTEXT_GRP = 2;
	/**
	 * @var msUserStatus
	 */
	public $user_status_object;
	/**
	 * @var msAccountType
	 */
	public $account_type_object;


	public function afterObjectLoad() {
		$this->user_status_object = new msUserStatus($this->getMatchingString(), $this->getSubscriptionType(), $this->getObjRefId());
		$this->account_type_object = new msAccountType($this->getMatchingString(), $this->getSubscriptionType());
	}


	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	/**
	 * @return bool
	 */
	public function isDeletable() {
		return in_array($this->getUserStatus(), self::$deletable_status);
	}

	//
	// Factory
	//

	/**
	 * @param $token
	 *
	 * @return msSubscription
	 */
	public static function getInstanceByToken($token) {
		$where = array( 'token' => $token );
		$obj = self::where($where)->first();
		if ($obj) {
			$obj->read();
		}

		return $obj;
	}


	/**
	 * @return string
	 */
	public function lookupName() {
		if ($this->user_status_object->getUsrId()) {
			$lookupName = ilObjUser::_lookupName($this->user_status_object->getUsrId());

			return $lookupName['lastname'] . ', ' . $lookupName['firstname'];
		} else {
			return '&nbsp;';
		}
	}


	public function assignToObject() {
		/**
		 * @var $participants ilCourseParticipants
		 */
		$obj_id = ilObject::_lookupObjId($this->getObjRefId());

		switch ($this->getContext()) {
			case self::CONTEXT_CRS:
				$participants = new ilCourseParticipants($obj_id);
				break;
			case self::CONTEXT_GRP:
				$participants = new ilGroupParticipants($obj_id);
				break;
		}
		$usr_id = $this->user_status_object->getUsrId();
		$status = $participants->add($usr_id, $this->getRole());
		if ($status AND msConfig::get(msConfig::F_SEND_MAILS)) {
			switch ($this->getContext()) {
				case self::CONTEXT_CRS:
					$participants->sendNotification($participants->NOTIFY_ACCEPT_USER, $usr_id);
					break;
				case self::CONTEXT_GRP:
					$participants->sendNotification(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER, $usr_id);
					break;
			}
		}
		$this->setDeleted(true);
	}


	//
	// Helpers
	//
	/**
	 * @param      $email
	 * @param bool $use_old
	 *
	 * @return array
	 */
	public static function seperateEmailString($email, $use_old = false) {
		if ($use_old) {
			$result = preg_replace("/([;,\\n\\r ])/um", self::DELIMITER, $email);
			$result = str_ireplace(self::DELIMITER . self::DELIMITER, self::DELIMITER, $result);

			return explode(self::DELIMITER, $result);
		}
		preg_match_all("/[A-Za-z0-9_.-]+@[A-Za-z0-9_.-]+\\.[A-Za-z0-9_-][A-Za-z0-9_]+/uismx", $email, $matches);

		return $matches[0];
	}


	/**
	 * @param $matriculation
	 *
	 * @return array
	 */
	public static function seperateMatriculationString($matriculation) {
		$result = preg_replace("/([;,\\n\\r ])/um", self::DELIMITER, $matriculation);
		$result = str_ireplace(self::DELIMITER . self::DELIMITER, self::DELIMITER, $result);

		return explode(self::DELIMITER, $result);
	}


	/**
	 * @param     $obj_ref_id
	 * @param     $input
	 * @param int $type
	 * @param     $context
	 */
	public static function insertNewRequests($obj_ref_id, $input, $type = msSubscription::TYPE_EMAIL, $context) {
		$where = array(
			'matching_string' => $input,
			'obj_ref_id' => $obj_ref_id,
			'deleted' => false
		);
		$operators = array(
			'matching_string' => 'LIKE',
			'obj_ref_id' => '=',
			'deleted' => '='
		);
		if (!msSubscription::where($where, $operators)->hasSets() AND $input != '') {
			$msSubscription = new msSubscription();
			$msSubscription->setMatchingString($input);
			$status = new msUserStatus($input, $type, $obj_ref_id);
			$msSubscription->setObjRefId($obj_ref_id);
			$msSubscription->setSubscriptionType($type);
			$msSubscription->setAccountType(msAccountType::TYPE_ILIAS);
			$msSubscription->setUserStatus($status->getStatus());
			$msSubscription->setContext($context);
			$msSubscription->create();
		}
	}


	/**
	 * @return string
	 */
	public static function generateToken() {
		$token = sha1(microtime() * rand(1, 10000));
		while (self::where(array( 'token' => $token ))->hasSets()) {
			$token = sha1(microtime() * rand(1, 10000));
		}

		return $token;
	}


	public function create() {
		$this->setToken(self::generateToken());
		parent::create();
	}


	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           4
	 * @con_sequence        true
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           4
	 */
	protected $obj_ref_id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           50
	 */
	protected $matching_string;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $account_type;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $subscription_type = self::TYPE_EMAIL;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $user_status;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $role = IL_OBJ_MEMBER;
	/**
	 * @var bool
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $invitations_sent = false;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $token;
	/**
	 * @var bool
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $deleted = false;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $context = self::CONTEXT_CRS;


	/**
	 * @param int $account_type
	 */
	public function setAccountType($account_type) {
		$this->account_type = $account_type;
	}


	/**
	 * @return int
	 */
	public function getAccountType() {
		return $this->account_type_object->getAccountType();
	}


	/**
	 * @param int $obj_ref_id
	 */
	public function setObjRefId($obj_ref_id) {
		$this->obj_ref_id = $obj_ref_id;
	}


	/**
	 * @return int
	 */
	public function getObjRefId() {
		return $this->obj_ref_id;
	}


	/**
	 * @param string $matching_string
	 */
	public function setMatchingString($matching_string) {
		$this->matching_string = $matching_string;
	}


	/**
	 * @return string
	 */
	public function getMatchingString() {
		return $this->matching_string;
	}


	/**
	 * @param boolean $invitations_sent
	 */
	public function setInvitationsSent($invitations_sent) {
		$this->invitations_sent = $invitations_sent;
	}


	/**
	 * @return boolean
	 */
	public function getInvitationsSent() {
		return $this->invitations_sent;
	}


	/**
	 * @param int $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}


	/**
	 * @return int
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param int $user_status
	 */
	public function setUserStatus($user_status) {
		$this->user_status = $user_status;
	}


	/**
	 * @return int
	 */
	public function getUserStatus() {
		return $this->user_status_object->getStatus();
	}


	/**
	 * @param boolean $deleted
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}


	/**
	 * @return boolean
	 */
	public function getDeleted() {
		return $this->deleted;
	}


	/**
	 * @param string $token
	 */
	public function setToken($token) {
		$this->token = $token;
	}


	/**
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $subscription_type
	 */
	public function setSubscriptionType($subscription_type) {
		$this->subscription_type = $subscription_type;
	}


	/**
	 * @return int
	 */
	public function getSubscriptionType() {
		return $this->subscription_type;
	}


	/**
	 * @return int
	 */
	public function getContext() {
		return $this->context;
	}


	/**
	 * @param int $context
	 */
	public function setContext($context) {
		$this->context = $context;
	}


	/**
	 * @return string
	 */
	public function getContextAsString() {
		switch ($this->getContext()) {
			case self::CONTEXT_CRS:
				return 'crs';
			case self::CONTEXT_GRP:
				return 'grp';
		}
	}


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'rep_robj_xmsb_susc';
	}
}

?>
