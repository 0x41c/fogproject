<?php
require_once('../commons/base.inc.php');
if (isset($_SESSION['delitems']) && !in_array($_REQUEST['sub'], array('deletemulti', 'deleteconf'))) unset($_SESSION['delitems']);
$currentUser = FOGCore::getClass('User',(int)$_SESSION['FOG_USER']);
if ($currentUser->isValid()) $currentUser->isLoggedIn();
$FOGPageManager = FOGCore::getClass('FOGPageManager');
FOGCore::getClass('ProcessLogin')->processMainLogin();
$Page = FOGCore::getClass('Page');
if (!in_array($_REQUEST['node'],array('schemaupdater','client')) && !in_array($_REQUEST['sub'],array('configure','authorize','requireClientInfo')) && ($node == 'logout' || !$currentUser->isValid())) {
    $currentUser->logout();
    $Page->setTitle($foglang['Login']);
    $Page->setSecTitle($foglang['ManagementLogin']);
    $Page->startBody();
    FOGCore::getClass('ProcessLogin')->mobileLoginForm();
    $Page->endBody();
    $Page->render();
} else {
    $Page->setTitle($FOGPageManager->getFOGPageTitle());
    $Page->setSecTitle($FOGPageManager->getFOGPageName());
    $Page->startBody();
    $FOGPageManager->render();
    $Page->endBody();
    $Page->render();
}
