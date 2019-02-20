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
);

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

//Вызов библиотеки
CUtil::InitJSCore(array('addCustomerSurveyButtonAndPopup'));

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
    private function getUsersFromGroup($group_id){
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
    protected function createNotification($note_fields){
        return $mess = CIMMessenger::Add($note_fields);
    }

    //логирование
    private function logData($path,$data){
        file_put_contents($path, print_r($data, true), FILE_APPEND | LOCK_EX);
    }

}