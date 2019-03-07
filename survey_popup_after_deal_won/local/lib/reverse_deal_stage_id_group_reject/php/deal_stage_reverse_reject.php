<?php

//сотрудник группы № 37 не может двигать стадии сделки направления "кейтеринг" в обратном направлении
//echo 'test stage reject';

AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("Group37ReverseStageIdMoveReject", "checkUserGroupIf37"));


class Group37ReverseStageIdMoveReject{


    //основной метод получения группы пользователя
    public function checkUserGroupIf37(&$arFields){

        $path = $_SERVER['DOCUMENT_ROOT'].'/UpdateDeal.log'; //для лога
        $error = false;

        if($arFields['ID'] > 0){
            //id текущего пользователя
            global $USER;
            $curUserId = $USER->GetID();

            if($curUserId > 0){
                $usersIdsfrom37group = self::getUsersFromGroup37(37);

                //если в 37-й группе есть пользователи
                if($usersIdsfrom37group){
                    //ищем id текущего пользователя в массиве группы
                    if(in_array($curUserId,$usersIdsfrom37group)){
                        //запрашиваем сохраненные раннее данные сделки (точнее, статусы)
                        $dealFilter = ['ID' => $arFields['ID']];
                        $dealSelect = ['ID','TITLE','STAGE_ID','CATEGORY_ID'];
                        $dealResult = self::getDealDataByFilterFor37($dealFilter,$dealSelect); //данные в элементе массива № 0

                        //если данные сделки получены, тогда берем только в направлении "Кейтеринг" == 0
                        if(count($dealResult) > 0){
                            //берем направление только "Кейтеринг" == 0
                            if($dealResult[0]['CATEGORY_ID'] == 0){

                                //сравниваем стадию до и после редактирования сделки
                                if($arFields['STAGE_ID'] != $dealResult[0]['STAGE_ID']){

                                    //берем стадию КП на рассмотрении у клиента
                                    if($dealResult[0]['STAGE_ID'] == 'PROPOSAL' && $arFields['STAGE_ID'] == 'NEW') $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';

                                    //берем стадию КП утверждено
                                    if($dealResult[0]['STAGE_ID'] == '1' && (in_array($arFields['STAGE_ID'],['NEW','PROPOSAL']))) $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';

                                    //берем стадию Мероприятие проводится
                                    if($dealResult[0]['STAGE_ID'] == '5' && (in_array($arFields['STAGE_ID'],['NEW','PROPOSAL',1]))) $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';

                                    //берем стадию Мероприятие завершено
                                    if($dealResult[0]['STAGE_ID'] == '4' && (in_array($arFields['STAGE_ID'],['NEW','PROPOSAL',1,5]))) $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';

                                    //берем стадию WON
                                    if($dealResult[0]['STAGE_ID'] == 'WON' && (in_array($arFields['STAGE_ID'],['NEW','PROPOSAL',1,5,'LOSE']))) $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';

                                    //берем стадию LOSE
                                    if($dealResult[0]['STAGE_ID'] == 'LOSE' && (in_array($arFields['STAGE_ID'],['NEW','PROPOSAL',1,5,'WON']))) $error = 'Движение в направлении "Кейтеринг" по воронке в обратно направлении запрещено!';


                                    if($error !== false) {

                                        $arFields['RESULT_MESSAGE'] = $error;
                                        return false;
                                    }
                                    else return true;
                                }
                            }
                        }
                    }
                }

            }
        }


       // self::logData($path,[$curUserId,$usersIdsfrom37group,$arFields,$dealResult,$test]);
    }

    private function getUsersFromGroup37($group_id){
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

    private function logData($path,$data){
        file_put_contents($path, print_r($data, true), FILE_APPEND | LOCK_EX);
    }

    //Получение данных сделки по фильтру
    private function getDealDataByFilterFor37($filter,$select){
        $db_list = CCrmDeal::GetListEx(array('ID' => 'DESC'), $filter, false, false, $select, array()); //получение пользовательских полей сделки по ID

        $result = array();
        while ($dealsList = $db_list->Fetch()) {
            $result[] = $dealsList;
        }

        if($result) return $result;
        return false;
    }

}