<?
CModule::IncludeModule("CRM");
CModule::IncludeModule("tasks");

class CustomQoutesTemplates{

    public function giveQuotesMarkedAsTemplate(){
        $result = [
            'result' => false,
            'error' => false,
        ];

        $quotesFilter = ['UF_CRM_1562060910' => 1];
        $quotesSelect = ['ID','ASSIGNED_BY_LAST_NAME','ASSIGNED_BY_NAME','TITLE'];
        $quotesResult = $this->getQuotesByFilter($quotesFilter,$quotesSelect);
        if(!$quotesResult)$result['error'] = 'Нет предложений, которые отмечены как шаблон!';
        else{
            /*foreach ($quotesResult as $quote){
                $title = '';
                if($quote['TITLE']) $title = $quote['TITLE'];
                else $title = 'Предложение by '.$quote['ASSIGNED_BY_LAST_NAME'].' '.$quote['ASSIGNED_BY_NAME'];
                $result['result'] .= '<option value="'.$quote['ID'].'">'.$title.'</option>';
            }*/
            $result['result'] = $quotesResult;
        }
        $this->sentAnswer($result);
    }

    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
    }

    //получение предложений по фильтру
    private function getQuotesByFilter($filter,$select){
        $result = false;
        $db_list = CCrmQuote::GetList(Array("ID" => "DESC"), $filter, false, false, $select, array());
        while($ar_result = $db_list->GetNext()) {
            $result[] = $ar_result;
        }
        return $result;
    }
}