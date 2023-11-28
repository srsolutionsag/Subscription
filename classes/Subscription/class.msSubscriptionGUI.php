<?php

/**
 * GUI-Class msSubscriptionGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 *
 * @ilCtrl_isCalledBy msSubscriptionGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class msSubscriptionGUI
{
    public $tpl;
    public $ctrl;
    /**
     * @var null
     */
    public $parent;
    public $toolbar;
    /**
     * @var \ilSubscriptionPlugin
     */
    public $pl;
    public const CMD_CLEAR = 'clear';
    public const CMD_CONFIRM_DELETE = 'confirmDelete';
    public const CMD_DELETE = 'delete';
    public const CMD_EDIT = 'edit';
    public const CMD_INVITE = 'invite';
    public const CMD_KEEP = 'keep';
    public const CMD_LNG = 'updateLanguageKey';
    public const CMD_LIST_OBJECTS = 'listObjects';
    public const CMD_REINVITE = 'reinvite';
    public const CMD_REMOVE_UNREGISTERED = 'removeUnregistered';
    public const CMD_SEND_FORM = 'sendForm';
    public const CMD_SHOW_FORM = 'showForm';
    public const CMD_SUBSCRIBE = 'subscribe';
    public const CMD_TRIAGE = 'triage';
    public const SYSTEM_USER = 6;
    public const EMAIL_FIELD = 'sr_ms_email_list_field';
    public const MATRICULATION_FIELD = 'sr_ms_matriculation_list_field';
    /**
     * @var ilTabsGUI
     */
    protected $tabs;
    /**
     * @var ilPropertyFormGUI
     */
    protected $form;
    protected \ilObjectDefinition $obj_def;
    /**
     * @var int
     */
    protected $obj_ref_id = 0;
    /**
     * @var msSubscriptionTableGUI
     */
    protected $table;
    /**
     * @var \ilObjCourse|\ilObjGroup|null
     */
    protected ?\ilObject $obj;
    /**
     * @var ilObjUser
     */
    protected $usr;
    /**
     * @var ilAccessHandler
     */
    protected $access;
    private \ilGlobalTemplateInterface $main_tpl;

    /**
     * @param null $parent
     */
    public function __construct($parent = null)
    {
        global $DIC;
        global $DIC, $objDefinition;
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->parent = $parent;
        $this->toolbar = $DIC->toolbar();
        $this->tabs = $DIC->tabs();
        $this->usr = $DIC->user();
        $this->access = $DIC->access();
        $this->obj_def = $objDefinition;
        $this->pl = ilSubscriptionPlugin::getInstance();
        //		$this->pl->updateLanguageFiles();

        $this->obj_ref_id = $_GET['obj_ref_id'];
        $this->obj = ilObjectFactory::getInstanceByRefId($this->obj_ref_id);
        $class_name = $this->obj_def->getClassName($this->obj->getType());
        $this->ctrl->setParameterByClass('ilObj' . $class_name . 'GUI', 'ref_id', $this->obj_ref_id);
    }

    public function executeCommand(): void
    {
        if (!$this->pl->isActive()) {
            $this->main_tpl->setOnScreenMessage('failure', 'Active Plugin first', true); // TODO: Translate
            ilUtil::redirect('index.php');
        }

        $this->initHeader();
        $this->ctrl->saveParameter($this, 'obj_ref_id');
        //        $this->ctrl->setContext($this->obj->getId(), $this->obj->getType());
        $cmd = $this->ctrl->getCmd() ?: $this->getStandardCommand();
        $this->performCommand($cmd);
        $this->tpl->printToStdout();
    }

    private function initHeader(): void
    {
        global $DIC;
        // $list_gui->setParentRefId($_GET['obj_ref_id']);
        $this->tpl->setTitle($this->obj->getTitle());
        $this->tpl->setDescription($this->obj->getDescription());
        if (ilObject::_lookupType($this->obj->getId()) == 'crs') {
            ilObjectListGUIFactory::_getListGUIByType($this->obj->getType()); // currently not working
            if ($this->obj->getOfflineStatus()) {
                // $this->tpl->setAlertProperties($list_gui->getAlertProperties());
            }
        }
        $this->tpl->setTitleIcon(ilObjectPlugin::_getIcon($this->obj->getId(), 'big', $this->obj->getType()));
        $this->tabs->setBackTarget(
            $this->pl->txt('main_back'),
            $this->ctrl->getLinkTargetByClass(
                [
                    ilRepositoryGUI::class,
                    'ilObj' . $this->obj_def->getClassName($this->obj->getType()) . 'GUI',
                ],
                'members'
            )
        );

        $DIC["ilLocator"]->addRepositoryItems($this->obj_ref_id);
        $this->tpl->setLocator($DIC["ilLocator"]->getHTML());
        $this->tpl->addCss($this->pl->getDirectory() . '/templates/default/Subscription/main.css');
    }

    public function getStandardCommand(): string
    {
        return self::CMD_SHOW_FORM;
    }

    /**
     * @param string $cmd
     */
    public function performCommand($cmd): void
    {
        switch ($cmd) {
            case self::CMD_SHOW_FORM:
            case self::CMD_SEND_FORM:
            case self::CMD_LIST_OBJECTS:
            case self::CMD_TRIAGE:
            case self::CMD_REMOVE_UNREGISTERED:
            case self::CMD_CLEAR:
            case self::CMD_LNG:
                if (!$this->access->checkAccess('write', '', $this->obj_ref_id)) {
                    $this->main_tpl->setOnScreenMessage('failure', $this->pl->txt('main_no_access'));
                    ilUtil::redirect('index.php');

                    return;
                }
                $this->$cmd();
                break;
        }
    }

    public function showForm(): void
    {
        $this->initForm();
        $txt = $this->getInfoMessage();
        $this->main_tpl->setOnScreenMessage('info', $txt);
        $this->tpl->setContent($this->form->getHTML());
    }

    public function initForm(): void
    {
        $this->form = new  ilPropertyFormGUI();
        $this->form->setTitle($this->pl->txt('main_form_title_usage_' . msConfig::getUsageType()));
        //		$this->form->setDescription($this->pl->txt('main_form_info_usage_' . msConfig::getUsageType()));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
        if (msConfig::getValueByKey('use_email')) {
            $te = new ilTextAreaInputGUI($this->pl->txt('main_field_emails_title'), self::EMAIL_FIELD);
            $te->setInfo($this->pl->txt('main_field_emails_info'));
            $te->setRows(10);
            $te->setCols(100);
            $this->form->addItem($te);
        }
        if (msConfig::getValueByKey('use_matriculation')) {
            $te = new ilTextAreaInputGUI($this->pl->txt('main_field_matriculation_title'), self::MATRICULATION_FIELD);
            $te->setInfo($this->pl->txt('main_field_matriculation_info'));
            $te->setRows(10);
            $te->setCols(100);
            $this->form->addItem($te);
        }
        $this->form->addCommandButton(self::CMD_SEND_FORM, $this->pl->txt('main_send_form'));
    }

    public function sendForm(): void
    {
        $contextObjType = $this->obj->getType();
        switch ($contextObjType) {
            case 'grp':
                $context = msSubscription::CONTEXT_GRP;
                break;
            case 'crs':
                $context = msSubscription::CONTEXT_CRS;
                break;
        }
        foreach (msSubscription::seperateEmailString($_POST[self::EMAIL_FIELD]) as $mail) {
            msSubscription::insertNewRequests($this->obj_ref_id, $mail, msSubscription::TYPE_EMAIL, $context);
        }
        foreach (msSubscription::seperateMatriculationString($_POST[self::MATRICULATION_FIELD]) as $matriculation) {
            msSubscription::insertNewRequests(
                $this->obj_ref_id,
                $matriculation,
                msSubscription::TYPE_MATRICULATION,
                $context
            );
        }
        $this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
    }

    private function initTable(): void
    {
        $this->table = new msSubscriptionTableGUI($this, self::CMD_LIST_OBJECTS);
    }

    public function listObjects(): void
    {
        $this->initTable();
        $this->tpl->setContent($this->table->getHTML());
    }

    public function cancel(): void
    {
        $this->ctrl->redirect($this);
    }

    public function triage(): void
    {
        global $DIC;

        $post = $DIC->http()->request()->getParsedBody();
        foreach ($post as $k => $v) {
            if (preg_match("/obj_(\\d*)/um", $k, $m)) {
                /**
                 * @var msSubscription $obj
                 */
                $obj = msSubscription::find($m[1]);
                $obj->setRole((int) $v['role']);

                switch ($v['cmd']) {
                    case self::CMD_DELETE:
                        $obj->setDeleted(true);
                        break;
                    case '':
                    case self::CMD_KEEP:
                        $obj->setDeleted(msConfig::getValueByKey(msConfig::F_PURGE));
                        break;
                    case self::CMD_INVITE:
                    case self::CMD_REINVITE:
                        $this->sendMail($obj);
                        $obj->setInvitationsSent(true);
                        break;
                    case self::CMD_SUBSCRIBE:
                        $obj->assignToObject();
                        break;
                }
                $obj->update();
            }
        }
        if (msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
            $this->main_tpl->setOnScreenMessage(
                'info',
                $this->pl->txt('main_msg_emails_sent_usage_' . msConfig::getUsageType()),
                true
            );
        } else {
            $this->main_tpl->setOnScreenMessage('info', $this->pl->txt('main_msg_triage_finished'), true);
        }
        $this->listObjects();
        $this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
    }

    public function getCourseLink(): string
    {

        return ilLink::_getLink($this->obj->getRefId()) . '&cmd=members';
    }

    /**
     * @param bool $reinvite
     */
    public function sendMail(msSubscription $msSubscription, $reinvite = false): void
    {
        if (msConfig::getValueByKey(msConfig::F_SYSTEM_USER)) {
            $mail = new ilMail(msConfig::getValueByKey(msConfig::F_SYSTEM_USER));
        } else {
            $mail = new ilMail(self::SYSTEM_USER);
        }

        $sf = [
            'obj_title' => ilObject2::_lookupTitle(ilObject2::_lookupObjId($msSubscription->getObjRefId())),
            'role' => $this->pl->txt('main_role_' . $msSubscription->getRole()),
            'inv_email' => $msSubscription->getMatchingString(),
            'link' => ILIAS_HTTP_PATH . '/goto.php?target=subscr_' . $msSubscription->getToken(),
            'username' => $this->usr->getFullname(),
            'email' => $this->usr->getEmail(),
        ];

        $mail_body = vsprintf($this->pl->txt('main_notification_body'), $sf);
        $mail_body = preg_replace("/\\\\n/um", "\n", $mail_body);
        $subject = $reinvite ? $this->pl->txt('main_notification_subject_reinvite') : $this->pl->txt(
            'main_notification_subject'
        );
        $mail->sendMail($msSubscription->getMatchingString(), '', '', $subject, $mail_body, [], false);
    }

    protected function removeUnregistered(): void
    {
        $where = [
            'obj_ref_id' => $_GET['obj_ref_id'],
            'deleted' => false,
        ];
        /**
         * @var msSubscription $msSubscription
         */
        foreach (msSubscription::where($where)->get() as $msSubscription) {
            if ($msSubscription->isDeletable()) {
                $msSubscription->setDeleted(true);
                $msSubscription->update();
            }
        }

        $this->main_tpl->setOnScreenMessage('info', $this->pl->txt("remove_unregistered_info"), true);

        if (msSubscription::where($where)->count() > 0) {
            $this->ctrl->redirect($this, self::CMD_LIST_OBJECTS);
        } else {
            $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
        }
    }

    protected function clear()
    {
        $where = [
            'obj_ref_id' => $_GET['obj_ref_id'],
            'deleted' => false,
        ];

        /**
         * @var msSubscription $msSubscription
         */
        foreach (msSubscription::where($where)->get() as $msSubscription) {
            $msSubscription->setDeleted(true);
            $msSubscription->update();
        }

        $this->main_tpl->setOnScreenMessage('info', $this->pl->txt("clear_info"), true);

        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    /**
     * @return ilObjCourse|ilObjGroup
     */
    public function getObj()
    {
        return $this->obj;
    }

    /**
     * @return never
     */
    public function updateLanguageKey(): void
    {
        global $DIC;
        /**
         * @var ilLog $ilLog
         */
        $ilLog = $DIC["ilLog"];

        $ilLog->write('updateLanguageKey');
        $ilLog->write(print_r($_POST, true));
        exit;
    }

    /**
     * @return string
     */
    private function getInfoMessage()
    {
        if (msConfig::getUsageType() == msConfig::TYPE_USAGE_MAIL || msConfig::getUsageType(
        ) == msConfig::TYPE_USAGE_BOTH) {
            if (msConfig::getValueByKey(msConfig::F_ENABLE_SENDING_INVITATIONS)) {
                $txt = $this->pl->txt('main_form_info_usage_' . msConfig::TYPE_USAGE_MAIL);
            } else {
                $txt = $this->pl->txt('main_form_info_usage_' . msConfig::TYPE_USAGE_MAIL . "_no_invitation");
            }
        } else {
            $txt = $this->pl->txt('main_form_info_usage_' . msConfig::getUsageType());
        }

        return $txt;
    }
}
