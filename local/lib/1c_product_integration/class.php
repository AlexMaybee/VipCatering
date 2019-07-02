<?php

class Products1C{

    public function test(){
        return 'This is test Function!<br>';
    }


    //главный метод для товаров
    public function doActionWithProduct($dataMassive){
        $result = array(
            'result' => false,
            'error' => '',
        );

        if(!$dataMassive['products']) $result['error'] = "Products massive is empty!";
        else{

            //Ищем товар в базе битрикса по уникальному коду из 1С (Pror_310)
            foreach ($dataMassive['products'] as $product){
                if(empty($product['1C_ID'])) continue; //Если не заполнено ID в 1С, пропускаем
                else {
                    $prodActionRes = $this->checkProductOrAddNew($product);
                    if($prodActionRes['result']) $result['result'][] = $prodActionRes['result'];
                    else $result['error'][] = $prodActionRes['error'];
                }
            }
        }
        return $result;
    }

    private function checkProductOrAddNew($productData){

        $result = array(
            'result' => false,
            'error' => '',
        );

        //поиск товара в базе по уникальному коду из 1С (Pror_310)
        $prodFilter = array(
            "IBLOCK_ID"=> 59,
            'PROPERTY_ID_V_1C' => $productData['1C_ID'],
        );
        $prodSelect = array(
            'ID','NAME'/*,"IBLOCK_SECTION_ID",'DETAIL_TEXT','DETAIL_PICTURE','PURCHASING_PRICE'*/
        );
        $prodSearchRes = $this->searchProductInBase($prodFilter,$prodSelect);

        //если товар найден то просто обновляем его поля !!! Все, кроме кода из 1С - PROPERTY_ID_V_1C
        if($prodSearchRes[0]['ID'] > 0){

          /*  $result['result']['ID'] = $prodSearchRes[0]['ID'];
            $result['result']['prod'] = $prodSearchRes[0];*/

            $prodUpdateRows = array(
                'NAME' => $productData['NAME'],
                'PURCHASING_PRICE' => $productData['PRICE_CATERING'], //Все равно при создании не залетает
                'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
                'PRICE' => $productData['PRICE_CATERING'],
                'CURRENCY' => 'UAH',
                'DESCRIPTION' => $productData['DESCRIPTION'], //Поле пояснения товара вкладки "подробно"
                'MEASURE' => $productData['MEASURE'], //Ед. измерения, шт.
               // 'STORE_AMOUNT' => $productData['STORE_AMOUNT'], //ОСТАТКИ на складе шт
                "PROPERTY_VALUES" => array(
                    'NAZVANIE_UA' => $productData['NAME_UA'], //Укр Название
                    'NAZVANIE_EN' => $productData['NAME_EN'], //Eng Название
                    'MEASURE_CODE' => $productData['MEASURE_CODE'], //Ед. изм
                   // 'SLOJNOST_BLUDA_LEVEL' => $productData['DISH_LEVEL'], //Сложность от 1 до 5 //Убрано 12.03.2019
                    'ID_V_1C' => $productData['1C_ID'], //ID в 1С //НЕ ОБНОВЯЯЕМ НИ В КОЕМ СЛУЧАЕ!!!
                    'PRODUCT_ART' => $productData['ARTICUL'], //Арт товара
                    'PRODUCT_RECIEVED' => $productData['PRODUCT_TO_SYNCHRON'], //Поступил товар Да/Нет
                ),
            );

            //Если поле картиинки не пустое, то при создании заполняем картинку
//            if(!empty($productData['IMAGE'])){
//                $newImgId = $this->getIncomeFile($productData['IMAGE']);
//                $prodUpdateRows['DETAIL_PICTURE'] = $newImgId;
//                $prodUpdateRows['PREVIEW_PICTURE'] = $newImgId;
//                // $this->log(array($productData['IMAGE'],$createProdFields));
//            }

            /*$result['result']['new_prod_rows'] = $prodUpdateRows;*/

            //обновление товара
            $updPriceRes = $this->updateProduct($prodSearchRes[0]['ID'],$prodUpdateRows);

            if(!$updPriceRes) $result['error'] .= 'Product '.$prodSearchRes[0]['ID'].' was found in Bitrix but was not updated!';
            else{

                $result['result'] = $productData['1C_ID'].' was updated!';

                //обновление цен и валют, которые не обновляются методом выше
                $updatePricesArr = array(
                    'PURCHASING_PRICE' => $productData['PRICE_CATERING'],
                    'PURCHASING_CURRENCY' => 'UAH',
                    'WEIGHT' => $productData['WEIGHT'], //ВЕС ШТАТНОЕ!
                );

                $updatePricesRes = $this->updateProductAndPurchasingPrice($prodSearchRes[0]['ID'],$updatePricesArr);

                if(!$updatePricesRes) $result['error'] .= ' Product '.$prodSearchRes[0]['ID']
                    .' purchasing price, currency, weight were not updated!';

                //обновление баз. цены и баз. валюты
                $this->setBaseCurrency($prodSearchRes[0]['ID'],$productData['PRICE'],$productData['CURRENCY']);


                //Создание/обновление типов цен (3 шт)
                if($productData['PRICE_CATERING'] > 0){
                    $catPirceRes = $this->addOrUpdatePriceTypeById($prodSearchRes[0]['ID'],3,$productData['PRICE_CATERING']);
                    if($catPirceRes) $result['result'] .= ' '.$catPirceRes['result'];
                    else $result['error'] .= ' '.$catPirceRes['error'];
                }
                if($productData['PRICE_FOOD_BOX'] > 0){
                    $foodBoxRes = $this->addOrUpdatePriceTypeById($prodSearchRes[0]['ID'],4,$productData['PRICE_FOOD_BOX']);
                    if($foodBoxRes) $result['result'] .= ' '.$foodBoxRes['result'];
                    else $result['error'] .= ' '.$foodBoxRes['error'];
                }
                if($productData['PRICE_PLANNED'] > 0){
                    $pricePlanned = $this->addOrUpdatePriceTypeById($prodSearchRes[0]['ID'],5,$productData['PRICE_PLANNED']);
                    if($pricePlanned) $result['result'] .= ' '.$pricePlanned['result'];
                    else $result['error'] .= ' '.$pricePlanned['error'];
                }
                //Создание/обновление типов цен (3 шт)



             /*   //обновление остатков на складе
                $updStoreFields = array(
                    'PRODUCT_ID' => $prodSearchRes[0]['ID'],
                    'STORE_ID' => 1, //у них 1 склад, его ID = 1
                    'AMOUNT' => $productData['STORE_AMOUNT'], //кол-во товара
                );

                $updStoreRes = $this->addOrUpdateProductAmountToStore($updStoreFields);
                if(!$updStoreRes) $result['error'] .= ' Product '.$prodSearchRes[0]['ID'].' store Amount was not updated!';
                //else $result['result'] = $prodSearchRes[0]['ID'].' updated!';
                else $result['result'] .= ' Store amount updated too!';*/

              //  $result['result']['is_store_upd'] = $updStoreRes;
            }


        }

        //Если товар не найден, создаем!
        else{

            //создаем товар
            $createProdFields = array(
                'NAME' => $productData['NAME'],
                'PURCHASING_PRICE' => $productData['PRICE_CATERING'], //Все равно при создании не залетает
                'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
                'PRICE' => $productData['PRICE_CATERING'],
                'CURRENCY' => 'UAH',

                'DESCRIPTION' => $productData['DESCRIPTION'], //Поле пояснения товара вкладки "подробно"
                'MEASURE' => $productData['MEASURE'], //Ед. измерения, шт.
             //   'STORE_AMOUNT' => $productData['STORE_AMOUNT'], //ОСТАТКИ на складе шт
                "PROPERTY_VALUES" => array(
                    'NAZVANIE_UA' => $productData['NAME_UA'], //Укр Название
                    'NAZVANIE_EN' => $productData['NAME_EN'], //Eng Название
                    'MEASURE_CODE' => $productData['MEASURE_CODE'], //Ед. изм
                  //  'SLOJNOST_BLUDA_LEVEL' => $productData['DISH_LEVEL'], //Сложность от 1 до 5 //Убрано 12.03.2019
                    'ID_V_1C' => $productData['1C_ID'], //ID в 1С //НЕ ОБНОВЯЯЕМ НИ В КОЕМ СЛУЧАЕ!!!
                    'PRODUCT_ART' => $productData['ARTICUL'], //Арт товара
                    'PRODUCT_RECIEVED' => $productData['PRODUCT_TO_SYNCHRON'], //Поступил товар Да/Нет
                ),
            );

            //Если поле картиинки не пустое, то при создании заполняем картинку
             if(!empty($productData['IMAGE'])){
                 $newImgId = $this->getIncomeFile($productData['IMAGE']);
                 $createProdFields['DETAIL_PICTURE'] = $newImgId;
                 $createProdFields['PREVIEW_PICTURE'] = $newImgId;
                // $this->log(array($productData['IMAGE'],$createProdFields));
             }

            $createProdResID = $this->createNewProductWithProp($createProdFields); //возвращает ID нов. товара

            if(!$createProdResID) $result['error'] .= ' New product'.$productData['1C_ID'].' was not created in Bitrix!';
            else{

                $result['result'] = $productData['1C_ID'].' was imported as '.$createProdResID.'!';

                //обновление цен и валют, которые не обновляются методом выше
                $updatePricesArr = array(
                    'PURCHASING_PRICE' => $productData['PRICE_CATERING'],
                    'PURCHASING_CURRENCY' => 'UAH',
                    'WEIGHT' => $productData['WEIGHT'], //ВЕС ШТАТНОЕ!
                );

                $updatePricesRes = $this->updateProductAndPurchasingPrice($createProdResID,$updatePricesArr);

                //обновление баз. цены и баз. валюты
                $this->setBaseCurrency($createProdResID,$productData['PRICE'],$productData['CURRENCY']);



                //Создание/обновление типов цен (3 шт)
                if($productData['PRICE_CATERING'] > 0){
                    $catPirceRes = $this->addOrUpdatePriceTypeById($createProdResID,3,$productData['PRICE_CATERING']);
                    if($catPirceRes) $result['result'] .= ' '.$catPirceRes['result'];
                    else $result['error'] .= ' '.$catPirceRes['error'];
                }
                if($productData['PRICE_FOOD_BOX'] > 0){
                    $foodBoxRes = $this->addOrUpdatePriceTypeById($createProdResID,4,$productData['PRICE_FOOD_BOX']);
                    if($foodBoxRes) $result['result'] .= ' '.$foodBoxRes['result'];
                    else $result['error'] .= ' '.$foodBoxRes['error'];
                }
                if($productData['PRICE_PLANNED'] > 0){
                    $pricePlanned = $this->addOrUpdatePriceTypeById($createProdResID,5,$productData['PRICE_PLANNED']);
                    if($pricePlanned) $result['result'] .= ' '.$pricePlanned['result'];
                    else $result['error'] .= ' '.$pricePlanned['error'];
                }
                //Создание/обновление типов цен (3 шт)





               /* if(!$updatePricesRes) $result['error'] .= ' New product '.$productData['1C_ID']
                    .' purchasing price, currency, weight were not updated!';
                else{
                    $updStoreFields = array(
                        'PRODUCT_ID' => $createProdResID,
                        'STORE_ID' => 1, //у них 1 склад, его ID = 1
                        'AMOUNT' => $productData['STORE_AMOUNT'], //кол-во товара
                    );

                    $updStoreRes = $this->addOrUpdateProductAmountToStore($updStoreFields);
                    if(!$updStoreRes) $result['error'] .= 'New product '.$productData['1C_ID'].' store Amount was not added!';
                    else $result['result'] .= ' Store Amount was added!';
                }*/

            }
        }

        return $result;
    }

    //метод для проверки и добавления типа цен
    private function addOrUpdatePriceTypeById($productId,$price_type_id,$price){
        //Создание типов цен (3 шт)
        $result = [
            'result' => false,
            'error' => false,
        ];

            //поля для создания/обновления типа цены
            $priceTypeFields = Array(
                "PRODUCT_ID" => $productId,
                "CATALOG_GROUP_ID" => $price_type_id,
                "PRICE" => $price,
                "CURRENCY" => "UAH",
            );

            //проверка, что цена не записана
            $filter = ["PRODUCT_ID" => $productId,"CATALOG_GROUP_ID" => $price_type_id];
            $priceTypeRes = $this->getPriceTypeByFilter($filter);

            if($priceTypeRes){
                //если найден, обновляем
                $upd = $this->updatePriceType($priceTypeRes['ID'],$priceTypeFields);
                if($upd) $result['result'] = 'PriceType # '.$price_type_id.' for product '.$productId.' was updated!';
                else $result['error'] = 'PriceType # '.$price_type_id.' for product '.$productId.' update failed!';

            }
            else{
                //иначе создаем (на самом деле заполняем)
                $add = $this->addPriceType($priceTypeFields);
                if($add) $result['result'] = 'PriceType # '.$price_type_id.' for product '.$productId.' was added!';
                else $result['error'] = 'PriceType # '.$price_type_id.' for product '.$productId.' add failed!';
            }

            return $result;

        //Создание типов цен (3 шт)
    }


    //метод для поиска в каталоге товаров/списке расписаний по id товара в laravel
    private function searchProductInBase($arFilter,$arSelect){

        //сортировка по ID, новые сверху (на всяк случай)
        $res = CIBlockElement::GetList(Array('ID'=>'DESC'),$arFilter, false, false, $arSelect);
        $prods = array();
        while($ob = $res->GetNext()){
            $prods[] = $ob;
        }
        if($prods) return $prods;
        else return false;
    }

    //метод добаления нового товара с ценой и свойствами PROPERTY_, возвращает ID созданного товара
    private function createNewProductWithProp($newProdFields){
        return $newProduct = CCrmProduct::Add($newProdFields);
    }

    //обновление полей товара
    private function updateProduct($prodId,$fields){
        return $res = CCrmProduct::Update($prodId, $fields);
    }

    //обновление полей товара
    private function updateProductAndPurchasingPrice($prodId,$fields){
        return $res = CCatalogProduct::Update($prodId, $fields);
    }


    //обновление остатков на складе
    private function addOrUpdateProductAmountToStore($fields){
        return $res = CCatalogStoreProduct::UpdateFromForm($fields);
    }

    //Пробуем записать правильно Валюту Базовой цены (пока именно она не меняется)
    private function setBaseCurrency($productId,$basePrice,$baseCurrency){
        return CPrice::SetBasePrice($productId,$basePrice,$baseCurrency);
    }

    //Получение типа цены по ID и фильтру
    private function getPriceTypeByFilter($filter){
        $massive = CPrice::GetList(array(),$filter);
        $arr = $massive->Fetch();
        if($arr) return $arr;
        else return false;
    }

    //Создание типа цены + поля
    private function addPriceType($fields){
        return $add = CPrice::add($fields);
    }

    //Обновление типа цены по ID + поля
    private function updatePriceType($price_type_id,$fields){
        return $upd = CPrice::Update($price_type_id,$fields);
    }

    //метод логирования данных
    public function log($data){
        $file = $_SERVER['DOCUMENT_ROOT'].'/products.log';
        file_put_contents($file, print_r(array('date' => date('d.m.Y H:i:s'),$data), true), FILE_APPEND | LOCK_EX);
    }

    //Сюда грузим картинки - не пригодился, т.к. конфликтовал и не давал создавать товар;
    /*public function saveFile($path){
      return $newId = CFile::SaveFile($path, "/catalog/products");
    }*/

   // картинки он сохраняет в свою папку и отдает ее ID
    private function getIncomeFile($foreignPath){
        return $newId = CFile::MakeFileArray($foreignPath);
    }
}