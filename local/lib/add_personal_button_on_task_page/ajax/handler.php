<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once ($_SERVER['DOCUMENT_ROOT'].'/local/lib/add_personal_button_on_task_page/ajax/additionalPersonbuttonClass.php');


$obj = new AdditionalPersonButtonClass;

if($_POST['ACTION'] == 'GIVE_ME_CURRENT_TASK_DATA'){
   // $obj->test($_POST['CURRENT_USER_ID'],$_POST['TASK_ID']);
    $obj->getDealIdfromTaskParams($_POST);
}

?>