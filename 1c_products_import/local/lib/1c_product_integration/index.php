<?php

define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('NO_AGENT_CHECK', true);
define("STATISTIC_SKIP_ACTIVITY_CHECK", true);
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/local/lib/1c_product_integration/class.php');

/** @global CMain $APPLICATION */
global $APPLICATION;


IncludeModuleLangFile(__FILE__);

$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
$exch1cEnabled = ($exch1cEnabled === 'Y');
if ($exch1cEnabled)
{
    if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
    {
        preg_match("/(project|tf)$/is", $license_name, $matches);
        if (strlen($matches[0]) > 0)
            $exch1cEnabled = false;
    }
}
CModule::IncludeModule('crm');
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog'); //это для типа цен


//Методы!!!

$obj = new Products1C;


//получаем данные и переводим из Std-класса в
$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($data), true);

if($data['action'] == 'export_products'){
    $toGo = $obj->doActionWithProduct($data);
    $obj->log($data);
    echo json_encode($toGo); //ответ
}
else{
    $obj->log($data);
    echo json_encode($error = array('error' => 'Request error: wrong request action!'));
}
