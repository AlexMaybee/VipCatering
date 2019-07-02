<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule("crm");
CModule::IncludeModule("tasks");

$CCrmLead = new CCrmLead();
$CCrmDeal = new CCrmDeal();
$CTasks = new CTasks();

$data = $_POST['data'];

if(isset($data['PHONE_WORK'])){
    $leadID = checkLeed($data['PHONE_WORK']);
    if(!$leadID){
        echo "Lead</br>";
        $arLeadFields = array(
            'TITLE' => $data['TITLE'],
            'NAME' => $data['NAME'],
            'SOURCE_ID' => 'WEB',
            'ASSIGNED_BY_ID' => 1,
            "FM" => Array(
                "PHONE" => Array(
                    "n1" => Array(
                        "VALUE" => $data['PHONE_WORK'],
                        "VALUE_TYPE" => "WORK",
                        )
                    ),
                "EMAIL" => Array(
                    "n1" => Array(
                        "VALUE" => $data['EMAIL_WORK'],
                        "VALUE_TYPE" => "WORK",
                        )
                    )
                )
            );
        $leadID = $CCrmLead->Add($arLeadFields);
       $href = 'http://my.vipcatering.com.ua/crm/lead/show/'.$leadID.'/';
    }else{

        echo "Deal</br>";
        $contact = CCrmContact::GetList(array(), array('LEAD_ID' => $leadID))->Fetch();
        $arDealFields = array(
            'TITLE' => $data['TITLE'],
            'LEAD_ID' => $leadID,
            'CONTACT_ID' => $contact['ID'],
            'ASSIGNED_BY_ID' => 1,
            );
        $dealId = $CCrmDeal->Add($arDealFields);
        $arLeadFields = array(
            'DEAL_ID' => $dealId
            );
        $CCrmLead->Update($leadID, $arLeadFields);
        $href = 'http://my.vipcatering.com.ua/crm/deal/show/'.$dealId.'/';
        // $filename = __DIR__.'/log.log';
        // file_put_contents($filename, "\narDealFields\n", FILE_APPEND);
        // file_put_contents($filename, print_r($arDealFields, 1), FILE_APPEND);
        // file_put_contents($filename, "\arLeadFields\n", FILE_APPEND);
        // file_put_contents($filename, print_r($arLeadFields, 1), FILE_APPEND);



        // pre($dealId);
        // pre($arDealFields);
    }
    $arTaskFields = array(
        "TITLE" => "Новая зявка с сайта ".date("Y-m-d H:i:s", time() + 60*60*3),
        "DESCRIPTION" => date("Y-m-d H:m:s"). " поступила новая <a href='".$href."'>заявка</a> с сайта.",
        "RESPONSIBLE_ID" => 1,
        "CREATED_BY" => 1,
        "GROUP_ID" => 13
    );
    $task = $CTasks->Add($arTaskFields);

}else{
    echo "phone is required";
}

function checkLeed($phone){

    $phoneInfo = CCrmFieldMulti::GetList(array(), array("VALUE" => $phone))->Fetch();
    return ($phoneInfo) ? $phoneInfo['ELEMENT_ID'] : false;
}