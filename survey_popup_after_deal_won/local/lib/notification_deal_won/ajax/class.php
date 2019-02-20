<?php

CModule::IncludeModule("CRM");
CModule::IncludeModule("tasks");
CModule::IncludeModule("im");

class CustomerSurvey{

    public function test(){
        $this->sentAnswer( 'class AjaxMainFunctions method test');
    }

    //запрос данных при открытии сделки
    public function getDealDataOnOpeningDeal($dealId){
        $result = [
            'DEAL' => false,
            'SHOW_BUTTON' => false,
        ];
        $message = '';

        $dealFilter = ['ID' => $dealId];
        $dealSelect = ['ID','TITLE','STAGE_ID','CATEGORY_ID','ASSIGNED_BY_ID','OPPORTUNITY',
            'UF_CRM_1550567125','UF_CRM_1550567255','UF_CRM_1550567291','UF_CRM_1550567357','UF_CRM_1550567461']; //Поля опроса слева направо = сделка -> сверху вниз
        $dealDataResult = $this->getDealDataByFilter($dealFilter,$dealSelect);

        if(!$dealDataResult) $message = 'Не удалось получить данные сделки!';
        else{

            $result['DEAL'] = $dealDataResult[0];
            $result['DEAL']['WON_STAGE_ID'] = $this->getCategoryFinishStage($dealDataResult[0]['CATEGORY_ID']);


            //если текущая стадия == выигрышной, то запрашиваем массив избранных из группы
            if($result['DEAL']['STAGE_ID'] !== $result['DEAL']['WON_STAGE_ID']) $message = 'Не та стадия, чтобы думать о кнопке опроса!';
            else{
                //получение id пользователей группы "Все видящих" и сравнение с id текущего пользователя
                $chiefGorupIds = $this->getUsersFromGroup(34);
                $result['Group'] = $chiefGorupIds;
                if(!$chiefGorupIds) $message = 'Не получены данные группы проводящих опросы или она пустая!';
                else{
                    //id текущего пользоватея
                    if(in_array($this->getCurUserId(),$chiefGorupIds)) {
                        $result['SHOW_BUTTON'] = true;
                        $result['USER_ID'] = $this->getCurUserId();

                        $message = 'Ну все, показываем кнопку опроса!';
                    }
                    else $message = 'Текущий пользователь не входит в группу Все Видящих!';
                }
            }
        }
        $this->sentAnswer(['result' => $result,'message' => $message]);
    }

    //запрос данных при нажатии кнопки опроса
    public function getDealFieldsAndOptionsOnSurveyButtonClick($dealId){
        $result = [
            'DEAL' => false,
            'QUALITY_OPTIONS' => false,
        ];
        $message = '';

        $dealFilter = ['ID' => $dealId];
        $dealSelect = ['ID','TITLE','STAGE_ID','CATEGORY_ID','ASSIGNED_BY_ID','OPPORTUNITY',
            'UF_CRM_1550567125','UF_CRM_1550567255','UF_CRM_1550567291','UF_CRM_1550567357','UF_CRM_1550567461']; //Поля опроса слева направо = сделка -> сверху вниз
        $dealDataResult = $this->getDealDataByFilter($dealFilter,$dealSelect);

        if(!$dealDataResult) $message = 'Не удалось получить данные сделки!';
        else{
            $result['DEAL'] = $dealDataResult[0];

            //Заполнение 2-х полей с датами и переформатирование дат для вставки в input
            !empty($result['DEAL']['UF_CRM_1550567357'])
                ? $result['DEAL']['UF_CRM_1550567357'] = date('Y-m-d',strtotime($result['DEAL']['UF_CRM_1550567357']))
                : $result['DEAL']['UF_CRM_1550567357'] = date('Y-m-d',strtotime('now'));
            !empty($result['DEAL']['UF_CRM_1550567461'])
                ? $result['DEAL']['UF_CRM_1550567461'] = date('Y-m-d',strtotime($result['DEAL']['UF_CRM_1550567461']))
                : $result['DEAL']['UF_CRM_1550567461'] = date('Y-m-d',strtotime('now'));

            //Получение options польз. поля сделки типа селект
            $surveyEnumFilter = ["USER_FIELD_NAME" => 'UF_CRM_1550567125'];
            $surveyEnumRes = $this->getPropertyOptions($surveyEnumFilter);

            if(count($surveyEnumFilter) > 0) {

                $selected_val = 141;

                foreach ($surveyEnumRes as $option){
                    if(!empty($result['DEAL']['SURVEY_QUALITY'])){
                        if($option['ID'] == $result['DEAL']['SURVEY_QUALITY']){
                            $result['QUALITY_OPTIONS'] .= '<option value="'.$option['ID'].'" selected="selected">'.$option['VALUE'].'</option>';
                        }
                        else
                            $result['QUALITY_OPTIONS'] .= '<option value="'.$option['ID'].'">'.$option['VALUE'].'</option>';
                    }
                    else
                        $result['QUALITY_OPTIONS'] .= '<option value="'.$option['ID'].'">'.$option['VALUE'].'</option>';

                }

            }
            else $message = 'Что-то не вышло получить options для поля "Оценка качества"';
        }

        $this->sentAnswer(['result' => $result,'message' => $message]);
    }


    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
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

    //получение id текущего пользователя
    private function getCurUserId(){
        global $USER;
        return $USER->GetID();
    }

    //возвращаем стадию "Выигрыш" по id направления
    private function getCategoryFinishStage($category_id){
        $winStageId = '';
        switch ($category_id){
            case(0):
                $winStageId = 'WON'; //направление "Клубные карты"
                break;
            /*case(1):
                $winStageId = 'C1:WON'; //направление "SPA"
                break;
            case(2):
                $winStageId = 'C2:WON'; //направление "Фитнесс"
                break;
            case(3):
                $winStageId = 'C3:WON'; //направление "Кафетерий"
                break;
            case(4):
                $winStageId = 'C4:WON'; // направление "Рассрочка"
                break;
            case(5):
                $winStageId = 'C5:WON'; // направление "Товары"
                break;*/
        }

        return $winStageId;
    }

    //получение options для select и др. полей, для вставки в поля
    private function getPropertyOptions($filter){

        $rsEnum = CUserFieldEnum::GetList(array(), $filter);
        $res = [];
        while($arEnum = $rsEnum->GetNext()){
            $res[] = [
                'ID' => $arEnum['ID'],
                'VALUE' => $arEnum['VALUE'],
                ];
        }
        return $res;
    }

}