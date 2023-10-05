<?php

/**
 * Class msModelObjectTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 2.0.6
 */
abstract class msModelObjectTableGUI extends ilTable2GUI
{
    /**
     * @var ilLanguage|ilPlugin
     */
    protected $translator;
    /**
     * @var string
     */
    protected $table_id = 'sr';
    /**
     * @var string
     */
    protected $table_title = 'Table (override protected $table_title)';
    /**
     * @var string
     */
    protected $table_prefix = 'srx';
    /**
     * @var array
     */
    protected $filter_array = [];
    /**
     * @var ilTabsGUI
     */
    protected $tabs;
    /**
     * @var ilAccessHandler
     */
    protected $access;
    /**
     * @var ilObjUser
     */
    protected $usr;
    /**
     * @var int
     */
    public static $num = 0;
    protected \ilSubscriptionPlugin $pl;

    public function __construct(?object $a_parent_obj, string $a_parent_cmd)
    {
        global $DIC;
        $this->tabs = $DIC->tabs();
        $this->access = $DIC->access();
        $this->usr = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        if (!$this->initLanguage()) {
            $this->lng = $DIC->language();
            $this->translator = $DIC->language();
        }
        $this->pl = ilSubscriptionPlugin::getInstance();
        $this->initTableProperties();
        $this->setId($this->table_id);
        $this->setTitle($this->table_title);
        parent::__construct($a_parent_obj, $a_parent_cmd);
        if (!$this->initFormActionsAndCmdButtons()) {
            $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
        }
        $this->initTableFilter();
        $this->initTableData();
        if (!$this->initTableColumns()) {
            $this->initStandardTableColumns();
        }
        if (!$this->initTableRowTemplate()) {
            $this->setRowTemplate('tpl.std_row_template.html', strstr(__DIR__, 'Customizing'));
        }
    }

    /**
     * @return bool
     * @description  returns false, if no filter is needed, otherwise implement filters
     * @description  set custom metjosd for filtering and resetting
     *               ($this->setResetCommand('resetFilter'); and
     *               $this->setFilterCommand('applyFilter');)
     */
    abstract protected function initTableFilter();

    /**
     * @return void
     * @description $this->setData(Your Array of Data)
     */
    abstract protected function initTableData();

    /**
     * @return bool
     * @description returns false, if automatic columns are needed, otherwise implement your columns
     */
    abstract protected function initTableColumns(): bool;

    /**
     * @return bool
     * @description returns false or set the following
     * @description e.g. ovverride table id oder title: $this->table_id = 'myid',
     *              $this->table_title = 'My Title'
     */
    abstract protected function initTableProperties();

    /**
     * @return bool
     * @description return false or implements own form action and
     */
    abstract protected function initFormActionsAndCmdButtons(): bool;

    /**
     * @return bool
     * @description returns false, if dynamic template is needed, otherwise implement your own
     *              template by $this->setRowTemplate($a_template, $a_template_dir = "")
     */
    abstract protected function initTableRowTemplate(): bool;

    /**
     * @return bool
     * @description returns false, if global language is needed; implement your own language by
     *              setting $this->translator
     */
    abstract protected function initLanguage(): bool;

    /**
     * @param array $a_set
     *
     * @return bool
     * @description implement your woen fillRow or return false
     */
    abstract protected function fillTableRow($a_set): bool;

    final public function addFilterItemToForm(ilFormPropertyGUI $item): void
    {
        /**
         * @var ilTextInputGUI $item
         */
        $this->addFilterItem($item);
        $item->readFromSession();
        $this->filter_array[$item->getPostVar()] = $item->getValue();
    }

    /**
     * @return bool
     */
    final public function initStandardTableColumns()
    {
        $data = $this->getData();
        if ($data === []) {
            return false;
        }
        foreach (array_keys(array_shift($data)) as $key) {
            $this->addColumn($this->lng->txt($key), $key);
        }
        $this->addColumn($this->lng->txt('actions'), 'actions');

        return true;
    }

    /**
     *
     * @internal    param array $_set
     * @description override, when using own columns
     */
    protected function fillRow(array $a_set): void
    {
        if (!$this->fillTableRow($a_set)) {
            self::$num++;
            foreach ($a_set as $value) {
                $this->addCell($value);
            }
            $this->ctrl->setParameter($this->parent_obj, 'object_id', $a_set['id']);
            $actions = new ilAdvancedSelectionListGUI();
            $actions->setId('actions_' . self::$num);
            $actions->setListTitle($this->pl->txt('actions'));
            $actions->addItem(
                $this->pl->txt('edit'),
                'edit',
                $this->ctrl->getLinkTarget($this->parent_obj, msSubscriptionGUI::CMD_EDIT)
            );
            $actions->addItem(
                $this->pl->txt('delete'),
                'delete',
                $this->ctrl->getLinkTarget($this->parent_obj, msSubscriptionGUI::CMD_CONFIRM_DELETE)
            );
            $this->tpl->setCurrentBlock('cell');
            $this->tpl->setVariable('VALUE', $actions->getHTML());
            $this->tpl->parseCurrentBlock();
        }
    }

    /**
     * @param string $value
     */
    public function addCell($value): void
    {
        $this->tpl->setCurrentBlock('cell');
        $this->tpl->setVariable('VALUE', $value ?: '&nbsp;');
        $this->tpl->parseCurrentBlock();
    }

    /**
     * @return mixed
     */
    public function getNavStart()
    {
        return $this->getNavigationParameter('from');
    }

    /**
     * @return mixed
     */
    public function getNavStop()
    {
        return $this->getNavigationParameter('to');
    }

    /**
     * @return mixed
     */
    public function getNavSortField()
    {
        return $this->getNavigationParameter('sort_field');
    }

    /**
     * @return mixed
     */
    public function getNavorder()
    {
        return $this->getNavigationParameter('order');
    }

    /**
     * @return array{from: string|int, to: mixed, sort_field: string|false, order: string}
     */
    public function getNavigationParametersAsArray()
    {
        $hits = $this->usr->getPref('hits_per_page');
        $parameters = explode(':', $_GET[$this->getNavParameter()]);

        return [
            'from' => $parameters[2] ?: 0,
            'to' => $parameters[2] !== '' && $parameters[2] !== '0' ? $parameters[2] + $hits - 1 : $hits - 1,
            'sort_field' => $parameters[0] ?: false,
            'order' => $parameters[1] !== '' && $parameters[1] !== '0' ? strtoupper($parameters[1]) : 'ASC',
        ];
    }

    /**
     * @param string $param
     *
     * @return mixed
     */
    public function getNavigationParameter($param)
    {
        $array = $this->getNavigationParametersAsArray();

        return $array[$param];
    }
}
