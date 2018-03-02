<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * registration form for new users
 *
 * @author  Sascha Hofmann <shofmann@databay.de>
 * @version $Id: register.php 51708 2014-07-24 13:01:54Z fschmid $
 *
 * @package ilias-core
 */
chdir('../../../../../../../..');

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();
/**
 * @var $ilCtrl ilCtrl
 */
$ilCtrl->initBaseClass("ilStartUpGUI");
$ilCtrl->setTargetScript("/ilias.php");
$ilCtrl->setCmd("");
$ilCtrl->setCmdClass('ilTokenRegistrationGUI');
$ilCtrl->callBaseClass();
