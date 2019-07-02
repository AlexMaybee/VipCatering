<?php

/*Этот файл подключается в /bitrix/php_interface/init.php */

CModule::IncludeModule("im");

/*Подключение файла .js с кодом заполнения элемента списка карты клиента!*/
$arJsConfig = array(
    'addCustomerSurveyButtonAndPopup' => array(
        'js' => '/local/lib/notification_deal_won/js/addCustomerSurveyButtonAndPopUp.js',
        // 'css' => '/bitrix/js/custom/main.css',
        // 'rel' => array(),
    ),
    'deactivateDealSurveyFields' => array(
        'css' => '/local/lib/notification_deal_won/css/deactivateDealSurveyFields.css',
    ),
    'hideClientFromEveryBodyExceptChoser' => array(
        'js' => '/local/lib/notification_deal_won/js/hideClientFromEveryBodyExceptChoser_JS.js',
        'css' => '/local/lib/notification_deal_won/css/hideClientFromEveryBodyExceptChoser.css',
    ),
);

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

//Вызов библиотеки
CUtil::InitJSCore(array('addCustomerSurveyButtonAndPopup'));


//подключение стилей для деактивации полей опроса
$urlLine = $_SERVER['SCRIPT_URI'];
if(preg_match('/\/crm\/deal\/details\//i',$urlLine)) CUtil::InitJSCore(array('deactivateDealSurveyFields'));
//echo $urlLine;

//Скрытие клиента + компании из сделок - Включить здесь!
AddEventHandler("main", "OnBeforeProlog", "CheckUserBelongingtoChosenGroup", 50);

function CheckUserBelongingtoChosenGroup()
{
   // echo $GLOBALS['USER']->GetID();
    $object = new EvenDealHandler();
    $urlLine = $_SERVER['SCRIPT_URI'];

    $isBelongToChosen = $object->checkIfUserIsBelongToChosen($GLOBALS['USER']->GetID());
    if(!$isBelongToChosen){
        if(preg_match('/\/crm\/deal\/details\//i',$urlLine)) CUtil::InitJSCore(array('hideClientFromEveryBodyExceptChoser'));
        if(preg_match('/\/crm\/deal\/category\//i',$urlLine)) CUtil::InitJSCore(array('hideClientFromEveryBodyExceptChoser'));
        if(preg_match('/\/crm\/deal\/kanban\/category\//i',$urlLine)) CUtil::InitJSCore(array('hideClientFromEveryBodyExceptChoser'));
    }
     //print_r($isBelongToChosen);
}

/*Подключение файла .js с кодом заполнения элемента списка карты клиента!*/


//Событие обновления сделки
AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("EvenDealHandler", "checkIfDealBecomeWon"));


class EvenDealHandler{


    public function checkIfDealBecomeWon(&$arFields){
        $path = $_SERVER['DOCUMENT_ROOT'].'/UpdateDeal.log'; //доступ для создания лога в папке /local/lib/notification_deal_won ЗАКРЫТ!


        //достаем данные сделки до изменения
        $dealFilter = ['ID' => $arFields['ID']];
        $dealSelect = ['ID','TITLE','STAGE_ID','ASSIGNED_BY_ID','OPPORTUNITY'];
        $dealResult = self::getDealDataByFilter($dealFilter,$dealSelect);

        //Если есть данные в массиве сделок
        if($dealResult){

            //Переход на стадию 'WON' с другой
            if($arFields['STAGE_ID'] == 'WON' && $dealResult[0]['STAGE_ID'] != 'WON'){

                //ищем людей в группе № 34 и отправляем им уведомление о возможности фоформлени
                $controllersIds = self::getUsersFromGroup(34);

                if(count($controllersIds) > 0 ){
                    //уведомляем ответственного за сделку о разрешении рассрочки

                    foreach ($controllersIds as $controllerId){
                        $noteFields = Array(
                            "MESSAGE_TYPE" => "S",
                            "TO_USER_ID" => $controllerId,//Уведомление ответственному за сделку
                            "AUTHOR_ID" => $dealResult[0]['ASSIGNED_BY_ID'], //ID разрешившего рассрочку
                            "MESSAGE" => 'По <a href="/crm/deal/details/'.$dealResult[0]['ID'].'/">сделке '.$dealResult[0]['TITLE'].'</a> можно провести опрос качества!',
                            "NOTIFY_TYPE" => 4, // 1 - принять/отказаться; 2,3,5+ - нерабочие, 4 - обычное уведомление
                            "NOTIFY_TITLE" => 'Теперь можно опросить клиента относительно качества обслуживания по сделке № '.$dealResult[0]['ID'],
                        );
                        $noteID = self::createNotification($noteFields);
                    }

                    //Логирование
                 //   self::logData($path,[$arFields,$dealResult[0],$controllersIds]);

                }
            }
        }
    }




    //Тест для проверки скрытия контакта и компании из сделки
    public function checkIfUserIsBelongToChosen($curUserId){
        $result = false;

        $chosenUsersIds = $this->getUsersFromGroup(36); //Группа, которая видит клиентов в сделках
        if(count($chosenUsersIds) > 0){
            in_array($curUserId,$chosenUsersIds) ? $result = true : $result = false ;
        }
        
        return $result;

    }



    //Получение данных сделки по фильтру
    private function getDealDataByFilter($filter,$select){
        $db_list = CCrmDeal::GetListEx(array('ID' => 'DESC'), $filter, false, false, $select, array()); //получение пользовательских полей сделки по ID

        $result = array();
        while ($dealsList = $db_list->Fetch()) {
            $result[] = $dealsList;
        }

        if($result) return $result;
        return false;
    }

    //получение id сотрудников группы по ее ID
    public function getUsersFromGroup($group_id){
        $filter = Array("GROUPS_ID"=>$group_id);
        $rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $filter);
        while($arItem = $rsUsers->GetNext())
        {
            //убираем пользователей с пустым именем и фамилией из выпадающего списка
            if($arItem['LAST_NAME'] == '' && $arItem['NAME'] == '' ) continue;
            $users[] = $arItem['ID'];
        }
        return $users;
    }

    //создание уведомления справа сверху сотруднику
    private function createNotification($note_fields){
        return $mess = CIMMessenger::Add($note_fields);
    }

    //логирование
    private function logData($path,$data){
        file_put_contents($path, print_r($data, true), FILE_APPEND | LOCK_EX);
    }

}
