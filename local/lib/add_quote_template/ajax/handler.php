<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once ($_SERVER['DOCUMENT_ROOT'].'/local/lib/add_quote_template/ajax/сustomQoutesTemplatesclass.php');


$obj = new CustomQoutesTemplates;

if($_POST['ACTION'] == 'GIVE_ME_QUOTES_WHICH_MARKED_AS_TEMPLATES'){
    $obj->giveQuotesMarkedAsTemplate();
}
