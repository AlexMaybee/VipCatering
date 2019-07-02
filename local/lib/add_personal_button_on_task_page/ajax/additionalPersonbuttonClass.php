<?php

CModule::IncludeModule("CRM");
CModule::IncludeModule("tasks");

class AdditionalPersonButtonClass{

    //test function
    public function test($data1,$data2){
        $this->sentAnswer(['test' => [$data1,$data2]]);
    }

    //это главня функция для получения id сделки из задачи
    public function getDealIdfromTaskParams($data){
        $result = false;
        $message = 'start getDealIdfromTaskParams function!';

        //проверка task_id на правильность
        if(!$data['TASK_ID']) $message = 'Неправильный ID задачи для отображения кнопки!';
        else{
            //получаем данные задачи по id
            $taskData = $this->getTaskData($data['TASK_ID']);

            if(!$taskData) $message = 'Не удалось получить данные задачи по ID = '.$data['TASK_ID'];
            else{
                //Все нужные данные в массиве UF_CRM_TASK => ['0' => 'D_4530']
                //;

                if(count($taskData['UF_CRM_TASK']) > 0){
                    //проверка, что в массиве есть элемент привязки именно к сделке, полагаем, что задача не может быть привязана к нескольким сделкам
                    $taskParentDeals = [];
                    foreach ($taskData['UF_CRM_TASK'] as $item) {
                        if(preg_match('/^D_([\d]+)/',$item,$matches)) $taskParentDeals[] = $matches[1]; //отдаем именно ID без D_
                    }

                    //если задача прикреплена к сделке и есть ее id, тогда показывать кнопку
                    if(count($taskParentDeals) > 0){
                        if(count($taskParentDeals) > 1) $message = 'Задача прикреплена к нескольким сделкам, кнопку добавления персонала не показываем!';
                        else{
                            $result = $taskParentDeals[0];
                            //$result = $taskParentDeals; //возвращаем первый id сделки в массиве
                            $message = 'Получен ID сделки! Кнопку добавления персонала можно показывать!!!';
                        }

                    }

                }
                else $message = 'Задача не прикреплена к сущности crm!';

            }
          //  $result['taskData'] = $taskData;

        }
        //$result =  $this->getTaskData($data['TASK_ID']);


        $this->sentAnswer(['result' => $result, 'message' => $message]);
    }


    //пролучение инфы задачи по id
    private function getTaskData($id){
        $rsTask = CTasks::GetByID($id);
        if($ar_result = $rsTask->GetNext()) return $ar_result;
        else return false;
    }

    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
    }
}
