<?php

/**
 * Class msUserStatus
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class msUserStatus
{
    public const EMAIL = msSubscription::TYPE_EMAIL;
    public const MATRICULATION = msSubscription::TYPE_MATRICULATION;
    public const STATUS_ALREADY_ASSIGNED = 1;
    public const STATUS_USER_CAN_BE_ASSIGNED = 2;
    public const STATUS_ALREADY_INVITED = 3;
    public const STATUS_USER_CAN_BE_INVITED = 4;
    public const STATUS_USER_NOT_INVITABLE = 5;
    public const STATUS_USER_NOT_ASSIGNABLE = 6;
    //
    public const ROLE_NONE = 0;
    public const ROLE_MEMBER = 2;
    public const ROLE_TUTOR = 3;
    public const ROLE_ADMIN = 1;
    /**
     * @var int
     */
    protected $usr_id;
    /**
     * @var int
     */
    protected $status;
    /**
     * @var int
     */
    protected $role;
    /**
     * @var int
     */
    protected $type;
    /**
     * @var string
     */
    protected $input;
    /**
     * @var int
     */
    protected $crs_ref_id;

    public function __construct(string $input, int $type, int $crs_ref_id)
    {
        $this->setCrsRefId($crs_ref_id);
        $this->setInput($input);
        $this->setType($type);
        $this->initUsrId();
        $this->initStatus();
        $this->initRole();
    }

    protected function initUsrId()
    {
        $usr_id = false;
        switch ($this->getType()) {
            case self::EMAIL:
                $usr_id = self::lookupUsrIdByField('email', $this->getInput());
                break;
            case self::MATRICULATION:
                $usr_id = self::lookupUsrIdByField('matriculation', $this->getInput());
                break;
        }

        $this->setUsrId($usr_id);
    }

    protected function initStatus()
    {
        if ($this->getUsrId() !== 0) {
            if (ilCourseParticipants::_isParticipant($this->getCrsRefId(), $this->getUsrId())) {
                $this->setStatus(self::STATUS_ALREADY_ASSIGNED);

                return;
            } else {
                $this->setStatus(self::STATUS_USER_CAN_BE_ASSIGNED);

                return;
            }
        } elseif ($this->getType() == self::EMAIL) {
            $where = [
                'matching_string' => $this->getInput(),
                'obj_ref_id' => $this->getCrsRefId(),
                'invitations_sent' => '1',
            ];
            $op = [
                'matching_string' => 'LIKE',
                'obj_ref_id' => '=',
                'invitations_sent' => '=',
            ];
            if (msSubscription::where($where, $op)->hasSets()) {
                $this->setStatus(self::STATUS_ALREADY_INVITED);

                return;
            } else {
                if (msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
                    $this->setStatus(self::STATUS_USER_CAN_BE_INVITED);
                } else {
                    $this->setStatus(self::STATUS_USER_NOT_ASSIGNABLE);
                }

                return;
            }
        }

        $this->setStatus(self::STATUS_USER_NOT_INVITABLE);
    }

    protected function initRole()
    {
        /**
         * @var ilObjCourse $crs
         */
        $crs = ilObjectFactory::getInstanceByRefId($this->getCrsRefId());
        $members = ilCourseParticipants::_getInstanceByObjId($crs->getId());
        if ($this->getUsrId() !== 0) {
            if ($members->isAdmin($this->getUsrId())) {
                $this->setRole(self::ROLE_ADMIN);

                return;
            } elseif ($members->isTutor($this->getUsrId())) {
                $this->setRole(self::ROLE_TUTOR);

                return;
            } elseif ($members->isMember($this->getUsrId())) {
                $this->setRole(self::ROLE_MEMBER);

                return;
            }
        }

        $this->setRole(self::ROLE_NONE);
    }

    /**
     * @param string $mail
     * @param int    $crs_ref_id
     *
     * @return int
     * @deprecated
     */
    public static function getUserStatusForMail($mail, $crs_ref_id)
    {
        $usr_id = self::lookupUsrIdByEmail($mail);
        if ($usr_id) {
            if (ilCourseParticipants::_isParticipant($crs_ref_id, $usr_id)) {
                return self::STATUS_ALREADY_ASSIGNED;
            } else {
                return self::STATUS_USER_CAN_BE_ASSIGNED;
            }
        } else {
            $where = [
                'matching_string' => $mail,
                'obj_ref_id' => $crs_ref_id,
                'invitations_sent' => '1',
            ];
            $op = [
                'matching_string' => 'LIKE',
                'obj_ref_id' => '=',
                'invitations_sent' => '=',
            ];
            if (msSubscription::where($where, $op)->hasSets()) {
                return self::STATUS_ALREADY_INVITED;
            } else {
                return self::STATUS_USER_CAN_BE_INVITED;
            }
        }
    }

    /**
     * @param string $mail
     *
     * @return int
     * @deprecated
     */
    public static function getRoleForMail($mail, int $crs_id)
    {
        $usr_id = self::lookupUsrIdByEmail($mail);
        $crs = ilObjectFactory::getInstanceByRefId($crs_id);
        $members = ilCourseParticipants::_getInstanceByObjId($crs->getId());
        if ($usr_id) {
            if ($members->isAdmin($usr_id)) {
                return self::ROLE_ADMIN;
            } elseif ($members->isTutor($usr_id)) {
                return self::ROLE_TUTOR;
            } elseif ($members->isMember($usr_id)) {
                return self::ROLE_MEMBER;
            }

            return self::ROLE_NONE;
        } else {
            return self::ROLE_NONE;
        }
    }

    /**
     * @param string $mail
     * @param int    $crs_id
     *
     * @deprecated
     */
    public static function getRoleForMatriculation($mail, $crs_id): void
    {
    }

    /**
     * @param string $mail
     *
     * @return bool
     * @deprecated
     */
    public static function lookupUsrIdByEmail($mail)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $set = $ilDB->query('SELECT usr_id FROM usr_data WHERE email LIKE ' . $ilDB->quote($mail, 'text'));
        while ($rec = $ilDB->fetchObject($set)) {
            return $rec->usr_id;
        }

        return false;
    }

    /**
     * @param string $mail
     *
     * @return bool
     * @deprecated
     */
    public static function lookupUsrIdByMatriculation($mail)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $set = $ilDB->query('SELECT usr_id FROM usr_data WHERE email LIKE ' . $ilDB->quote($mail, 'text'));
        while ($rec = $ilDB->fetchObject($set)) {
            return $rec->usr_id;
        }

        return false;
    }

    /**
     * @param int $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param int $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $usr_id
     */
    public function setUsrId($usr_id): void
    {
        $this->usr_id = $usr_id;
    }

    /**
     * @return int
     */
    public function getUsrId()
    {
        $this->initUsrId();

        return $this->usr_id;
    }

    /**
     * @param string $input
     */
    public function setInput($input): void
    {
        $this->input = $input;
    }

    /**
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param int $crs_ref_id
     */
    public function setCrsRefId($crs_ref_id): void
    {
        $this->crs_ref_id = $crs_ref_id;
    }

    /**
     * @return int
     */
    public function getCrsRefId()
    {
        return $this->crs_ref_id;
    }


    //
    // Helpers
    //

    /**
     * @param string $field
     * @param string $value
     *
     * @return bool
     */
    public static function lookupUsrIdByField($field, $value)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'SELECT usr_id FROM usr_data WHERE ' . $field . ' LIKE ' . $ilDB->quote($value, 'text');
        $set = $ilDB->query($query);
        while ($rec = $ilDB->fetchObject($set)) {
            return $rec->usr_id;
        }

        return false;
    }
}
