<?php

/**
 * Example configuration user interface class
 *
 * @author            Alex Killing <alex.killing@gmx.de>
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id$
 *
 * @ilCtrl_isCalledBy ilSubscriptionConfigGUI: ilObjComponentSettingsGUI
 */
class ilSubscriptionConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var \ilSubscriptionPlugin
     */
    public $pl;
    public const CMD_CANCEL = 'cancel';
    public const CMD_CONFIGURE = 'configure';
    public const CMD_SAVE = 'save';
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilLanguage
     */
    protected $lng;
    /**
     * @var ilGlobalTemplateInterface
     */
    protected $tpl;

    public function __construct()
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilSubscriptionPlugin::getInstance();
    }

    public function performCommand(string $cmd): void
    {
        switch ($cmd) {
            case self::CMD_CONFIGURE:
            case self::CMD_SAVE:
            case self::CMD_CANCEL:
                $this->$cmd();
                break;
        }
    }

    public function configure(): void
    {
        $form = new msConfigFormGUI($this);
        $form->fillForm();
        $this->tpl->setContent($form->getHTML());
    }

    protected function save()
    {
        $form = new msConfigFormGUI($this);
        $form->setValuesByPost();
        if ($form->saveObject()) {
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function cancel()
    {
        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }
}
