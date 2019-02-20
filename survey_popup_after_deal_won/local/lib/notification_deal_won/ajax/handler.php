<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once ($_SERVER['DOCUMENT_ROOT'].'/local/lib/notification_deal_won/ajax/class.php');


//if($_POST['DEAL_ID']) echo json_encode($_POST);






$obj = new CustomerSurvey;

//Запрос данных при открытии сделки для отображения/не отображения кнопки опроса
if($_POST['ACTION'] === 'GIVE_ME_DEAL_DATA') $obj->getDealDataOnOpeningDeal($_POST['DEAL_ID']);


//Запрос при нажатии кнопки дынных полей сделки и options
if($_POST['ACTION'] === 'GIVE_ME_SURVEY_AND_OPTIONS_FIELDS') $obj->getDealFieldsAndOptionsOnSurveyButtonClick($_POST['DEAL_ID']);