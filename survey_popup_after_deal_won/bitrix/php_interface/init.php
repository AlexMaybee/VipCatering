<?php
define("CATALOG_ID", 51);
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log.txt");

function pre($val){
    if(!$val){
        echo "<pre>";
        var_dump($val);
        echo "</pre>";
    }else{
        echo "<pre>";
        print_r($val);
        echo "</pre>";
    }
}

function sendTurboSms($phone, $text)
{
    try {

        // Подключаемся к серверу
        $client = new SoapClient('http://turbosms.in.ua/api/wsdl.html');

        // Можно просмотреть список доступных методов сервера
        //print_r($client->__getFunctions());

        // Данные авторизации
        $auth = [
            'login' => 'cateringsms',
            'password' => '423702'
        ];

        // Авторизируемся на сервере
        $result = $client->Auth($auth);

        // Результат авторизации
        //echo $result->AuthResult . PHP_EOL;

        // Получаем количество доступных кредитов
        $result = $client->GetCreditBalance();
        //echo $result->GetCreditBalanceResult . PHP_EOL;

        // Текст сообщения ОБЯЗАТЕЛЬНО отправлять в кодировке UTF-8
        //$text = iconv('windows-1251', 'utf-8', 'Это сообщение будет доставлено на указанный номер');

        // Отправляем сообщение на один номер.
        // Подпись отправителя может содержать английские буквы и цифры. Максимальная длина - 11 символов.
        // Номер указывается в полном формате, включая плюс и код страны
        $sms = [
            'sender' => 'VIPcatering',
            'destination' => $phone,
            'text' => $text
        ];
        $result = $client->SendSMS($sms);
        return $result->SendSMSResult->ResultArray[0] . PHP_EOL;

    } catch(Exception $e) {
        return 'Ошибка: ' . $e->getMessage() . PHP_EOL;
    }
}


//Функционал отображения кнопки для проведения опроса после выигрыша сделки
require_once($_SERVER["DOCUMENT_ROOT"] ."/local/lib/notification_deal_won/checker.php");
