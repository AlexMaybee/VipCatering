<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');
include ($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/itlogic/estimate.element.add/templates/.default1'.'/lang/'.strtolower($_GET["lang"].'/ajax.php'));

$lang_id = ( !empty($_GET["lang"]) ) ? strtolower($_GET["lang"]) : "ru";


use Bitrix\Main\Localization\Loc;

Loc::setCurrentLang($lang_id);
Loc::loadMessages(__FILE__);

$langPref='';
if($_GET["lang"]!='UA')
    $langPref=$_GET["lang"].'_';

$er=true;
if(isset($_POST['QUOTE']) && $_POST['QUOTE']>0){
    $POST = json_decode($_POST['data']);

    $quote= CCrmDeal::GetByID($_POST['QUOTE']);
    //print_r($quote);
    //exit;
    $el = new CIBlockElement;
    if($quote['LEAD_ID']>0)
        $subj="LEAD_".$quote['LEAD_ID'];
    else if($quote['COMPANY_ID']>0)
        $subj="COM_".$quote['COMPANY_ID'];
    else if($quote['CONTACT_ID']>0)
        $subj="CONT_".$quote['CONTACT_ID'];
    else
        $subj=0;
    $company=($quote['COMPANY_ID']>0)?$quote['COMPANY_ID']:$quote['CONTACT_ID'];

    $PROP = array();
    $PROP['DISCOUNT'] = $_POST['discount'];  // предложение
    $PROP['QUOTE'] = $_POST['QUOTE'];  // предложение
    $PROP['CLIENT'] = $company;        // клиент
    $PROP['COST'] = $_POST['allSectSum'];        // сумма
    $i=1;
    $elements=array();
    foreach($POST as $k=>$v){
        $PROP['STEP_'.$i]=	$k;
        $PROP['PERS_STEP_'.$i]=	$v->PERS;
        foreach($v->ELEMENTS as $elt){
            $elements['STEP_'.$i][]=$elt;
        }

        $i++;
    }
    $arLoadProductArray = Array(
        "IBLOCK_ID"      => $_POST['MENUBL'],
        "PROPERTY_VALUES"=> $PROP,
        "NAME"           => $quote["TITLE"].' - '.date('d.m.Y H:i'),
        "ACTIVE"         => "Y",            // активен
    );

    if(!$PRODUCT_ID = $el->Add($arLoadProductArray))
        $er=false;

    //print_r($elements);
    foreach($elements as $k=>$val){
        foreach($val as $v){
            $PROP['SMETA']=	$PRODUCT_ID;
            $PROP['ETAP']=	$k;
            $PROP['BLUDO']=	$v->ID;
            $PROP['QUANT']=	$v->quant;
            $arLoadProductArray = Array(
                "IBLOCK_ID"      => $_POST['MENUELBL'],
                "PROPERTY_VALUES"=> $PROP,
                "NAME"           => $v->name.' - '.$quote["TITLE"].' '.$k,
                "SORT"           => $v->SORT,            // активен
                "ACTIVE"         => "Y",            // активен
            );
            if(!$el->Add($arLoadProductArray))
                $er=false;
        }
    }
    if($er)
        echo 'ok';
    else
        echo 'err';
}
print_r($_GET);
// download ######################
if(isset($_GET["menu"])&&$_GET["menu"]>0):

    // блюда смет
    $arSelect = Array("*");
    $arFilter = Array("IBLOCK_ID"=>53,"PROPERTY_SMETA"=>IntVal($_GET['menu']));
    $res = CIBlockElement::GetList(Array('SORT'=>'ASC'), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNext())
    {
        $resProp = CIBlockElement::GetProperty(53, $ob['ID'], "sort", "asc", array());
        while ($propOb = $resProp->GetNext())
        {
            $ob['PROPERTYS'][$propOb['CODE']] = $propOb;
        }
        $arElementsID[]=$ob['PROPERTYS']["BLUDO"]["VALUE"];
        $result['BLUDA'][$ob['PROPERTYS']["ETAP"]["VALUE"]][$ob['ID']]=$ob;
    }
    // блюда
    $arSelect = Array("*");
    $arFilter = Array("IBLOCK_ID"=>51,"ID"=>$arElementsID);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNext())
    {
        $resProp = CIBlockElement::GetProperty(51, $ob['ID'], "sort", "asc", array());
        while ($propOb = $resProp->GetNext())
        {
            $ob['PROPERTYS'][$propOb['CODE']] = $propOb;
        }
        if($_GET['lang']=='UA')
            $ob['NAME']=$ob['PROPERTYS']['RU_NAME']["VALUE"];
        /*if($_GET['lang']=='RU')
            $ob['NAME']=$ob['NAME'];*/
        if($_GET['lang']=='EN')
            $ob['NAME']=$ob['PROPERTYS'][$langPref.'NAME']["VALUE"];
        $arElements[$ob['ID']]=$ob;
    }
    $persCnt=0;
    // budget
    $res = CIBlockElement::GetByID($_GET["menu"]);
    $menu = $res->GetNext();
    $resProp = CIBlockElement::GetProperty(52, $_GET["menu"], "sort", "asc", array());
    while ($propOb = $resProp->GetNext())
    {
        if(strpos($propOb["CODE"],"PERS_STEP")!==false)
            $persCnt=$propOb['VALUE']+$persCnt;
        $menu['PROPERTYS'][$propOb["CODE"]] = $propOb;
        //	print_r($propOb);
    }
    // sections
    $rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), array("IBLOCK_ID"=>51),false,array("UF_EN_NAME","UF_RU_NAME"));
    while ($section = $rsSections->Fetch())
    {
        if($_GET['lang']=='UA')
            $section['NAME']=($section['UF_RU_NAME']!='')?$section['UF_RU_NAME']:$section['NAME'];
        if($_GET['lang']=='RU')
            $section['NAME']=($section['NAME']!='')?$section['NAME']:$section['UF_RU_NAME'];
        if($_GET['lang']=='EN')
            $section['NAME']=($section['UF_'.$langPref.'NAME']!='')?$section['UF_'.$langPref.'NAME']:$section['NAME'];
        $arSection[$section["ID"]]=$section;
    }
    //QUOTE
    $quote=CCrmDeal::GetByID($_GET['quote']);
    if(intval($quote['LEAD_ID'])>0)
        $contactArr=array('ENTITY_ID'=>'LEAD','ELEMENT_ID'=>$quote['LEAD_ID']);
    elseif(intval($quote['CONTACT_ID'])>0)
        $contactArr=array('ENTITY_ID'=>'CONTACT','ELEMENT_ID'=>$quote['CONTACT_ID']);
    elseif(intval($quote['COMPANY_ID'])>0)
        $contactArr=array('ENTITY_ID'=>'COMPANY','ELEMENT_ID'=>$quote['COMPANY_ID']);
    else
        $contactArr=0;

    //$company=($quote['COMPANY_ID']>0)?$quote['COMPANY_ID']:$quote['CONTACT_ID'];

    $file = "logo.png";
    if($fp = fopen($file,"rb", 0))
    {
        $picture = fread($fp,filesize($file));
        fclose($fp);
        // base64 encode the binary data, then break it
        // into chunks according to RFC 2045 semantics
        $imgbase64 = chunk_split(base64_encode($picture));

    }

// Подключаем класс для работы с excel
    require_once($_SERVER["DOCUMENT_ROOT"].'/include/phpexcel/PHPExcel.php');
// Подключаем класс для вывода данных в формате excel
    require_once($_SERVER["DOCUMENT_ROOT"].'/include/phpexcel/PHPExcel/Writer/Excel5.php');

// Создаем объект класса PHPExcel
    $xls = new PHPExcel();
// Устанавливаем индекс активного листа
    $xls->setActiveSheetIndex(0);
// Получаем активный лист
    $sheet = $xls->getActiveSheet();
// Подписываем лист
    $sheet->setTitle(Loc::getMessage("FIRST_LIST", null, $lang_id));

# массив с параметрами
    $table_title = array(
        'fill' => array(
            'type'       => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => '0c00d0'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> true,
            'color' 	=> array('rgb' => 'FFFFFF'),
            'size'  	=> 18,
            'name'  	=> 'Century Gothic'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        )
    );
    $table_head = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'FFFFFF'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 10,
            'name'  	=> 'Century Gothic'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $table_footer = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'aebbc0'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 14,
            'name'  	=> 'Times New Roman'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $table_contacts = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 18,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $category_title = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '0f13cb'),
            'size'  	=> 14,
            'name'  	=> 'Century Gothic'
        )
    );
    $category_title_en = array(
        'font'  => array(
            'bold'  	=> false,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 14,
            'name'  	=> 'Century Gothic'
        )
    );
    $table_contacts_info = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '0f00df'),
            'size'  	=> 11,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $table_footer_without_border = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'aebbc0'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 14,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $table_contacts_back = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'f4f4f4'
            )
        )
    );
    $table_footer_without_border1 = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 11,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        )
    );
    $new_price = array(
        'font'  => array(
            'italic'  	=> false,
            'bold'  	=> true,
            'color' 	=> array('rgb' => '0c00d0'),
            'size'  	=> 12,
            'name'  	=> 'Century Gothic'
        )
    );
    $nameFuterStyleHead = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 11,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $nameFuterStyleHead1 = array(
        'font'  => array(
            'bold'  	=> false,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 11,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        ),
    );
    $section_style = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'f1f1f1'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 16,
            'name'  	=> 'Times New Roman'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        )
    );

    $back_fon = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        )
    );

    $sectionRow = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'size'  => 11,
            'name'  => 'Century Gothic'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        ),
    );


    $sectionRowTABLE = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'size'  => 11,
            'name'  => 'Century Gothic'
        ),
        'borders' => array(
            'vertical' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'right' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'top' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        ),
    );

    $sectionRowTABLE1 = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'size'  => 11,
            'name'  => 'Century Gothic'
        ),
        'borders' => array(
            'vertical' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'right' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
            'bottom' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            ),
        ),
    );


    $sectionRowOdd = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'deeaf6'
            )
        ),
        'font'  => array(
            'size'  => 11,
            'name'  => 'Century Gothic'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        ),
    );

    $leftRow = array(
        'borders' => array(
            'left' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        )
    );
    $rightRow = array(
        'borders' => array(
            'right' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array(
                    'rgb' => '000000'
                )
            )
        )
    );
    $total_footer = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'f4f4f4'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 10,
            'name'  	=> 'Century Gothic'
        ),
    );
    $tech_info_ua = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => 'ff0000'),
            'size'  	=> 12,
            'name'  	=> 'Century Gothic'
        ),
    );
    $tech_info_ua1 = array(
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '0000ff'),
            'size'  	=> 12,
            'name'  	=> 'Century Gothic'
        ),
    );
    $price_s = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'f4f4f4'
            )
        ),
        'font'  => array(
            'bold'  	=> true,
            'italic'  	=> false,
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 11,
            'name'  	=> 'Century Gothic'
        ),
    );
    $default_text = array(
        'font'  => array(
            'color' => array('rgb' => '000000'),
            'size'  => 12,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $default_text_value = array(
        'font'  => array(
            'color' => array('rgb' => '000000'),
            'size'  => 18,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $tech_info = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            //'color' => array('rgb' => 'de0c0c'),
            'size'  => 14,
            'bold'  => true,
            'name'  => 'Century Gothic'
        )
    );
    $alert_text = array(
        'font'  => array(
            'color' => array('rgb' => 'a94442'),
            'size'  => 14,
            'upper'  => true,
            'bold'  => true,
            'name'  => 'Times New Roman'
        )
    );
    $default_title = array(
        'font'  => array(
            'color' => array('rgb' => '000000'),
            'size'  => 16,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ),
    );
    $white_bg= array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
    );
    $underline_title = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 18,
            'bold'  	=> true,
            'name'  	=> 'Times New Roman',
            'underline' => true
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $table_second_title = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'color' 	=> array('rgb' => '1014c8'),
            'size'  	=> 12,
            'bold'  	=> true,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true,
            'indent'        => 3
        )
    );
    $second_subtitle = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'font'  => array(
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 16,
            'bold'  	=> true,
            'name'  	=> 'Times New Roman'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $second_def_text = array(
        'fill' => array(
            'type'    => PHPExcel_Style_Fill::FILL_SOLID,
            'color'   => array(
                'rgb' => 'ffffff'
            )
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'  => array(
            'color' 	=> array('rgb' => '000000'),
            'size'  	=> 11,
            'bold'  	=> true,
            'name'  	=> 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );

    $sectionFuterStyleHead = array(
        'font' => array(
            'size' => 18,
            'bold' => true,
            'name' => 'Century Gothic',
            'color' 	=> array('rgb' => 'ffffff')
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );

    $total_menu = array(
        'font' => array(
            'size' => 18,
            'bold' => true,
            'name' => 'Century Gothic',
            'color' 	=> array('rgb' => '000000')
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );

    $total_menu_sum = array(
        'font' => array(
            'size' => 12,
            'bold' => true,
            'name' => 'Century Gothic',
            'color' 	=> array('rgb' => '000000')
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )
    );
    $sectionFuterStyleHead1 = array(
        'font' => array(
            'size' => 14,
            'bold' => true,
            'name' => 'Century Gothic',
            'color' 	=> array('rgb' => '000000')
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    $sectionFuterStyleHead_MENU = array(
        'font' => array(
            'size' => 14,
            'bold' => true,
            'name' => 'Century Gothic',
            'color' 	=> array('rgb' => '1300d6')
        )
    );

    $titleTopLeft = array(
        'font'  => array(
            'bold'  => true,
            'color' => array('rgb' => '000000'),
            'size'  => 14,
            'name'  => 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )

    );
    $titleTopLeft1 = array(
        'font'  => array(
            'bold'  => true,
            'color' => array('rgb' => '000000'),
            'size'  => 12,
            'name'  => 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )

    );
    $titlemanager = array(
        'font'  => array(
            'bold'  => true,
            'color' => array('rgb' => '000000'),
            'size'  => 14,
            'name'  => 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )

    );
    $titlemanagerpro = array(
        'font'  => array(
            'color' => array('rgb' => '000000'),
            'size'  => 14,
            'name'  => 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )

    );
    $titlemanageslug = array(
        'font'  => array(
            'color' => array('rgb' => '2334d3'),
            'size'  => 14,
            'name'  => 'Century Gothic'
        ),
        "alignment" => array(
            'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' 			=> true
        )

    );
    $textTopLeft = array(
        'font'  => array(
            'color' => array('rgb' => '304560'),
            'size'  => 12,
            'name'  => 'Times New Roman'
        ));
    $sectionStyle = array(
        'font'  => array(
            'color' => array('rgb' => '304560'),
            'size'  => 22,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ));
    $sectionHeadStyle = array(
        'font'  => array(
            'color' => array('rgb' => '000000'),
            'size'  => 12,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array (
                'rgb' => '4F81BC'
            )
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $sectionFuterStyle = array(
        'font'  => array(
            'color' => array('rgb' => 'FFFFFF'),
            'size'  => 16,
            'bold'  => true,
            'name'  => 'Times New Roman'
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array (
                'rgb' => '3367A5'
            )
        )
    );
    $sectionBodyStyle = array(
        'font'  => array(
            'size'  => 14,
            'name'  => 'Times New Roman'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    // contact data
    if ( $contactArr !== 0 ) {
        $res = CCrmFieldMulti::GetList(array(),$contactArr);
        while($arRes = $res->GetNext()){
            $contact[$arRes['TYPE_ID']][]=$arRes['~VALUE'];
        }
    }



    $contactArr=array('ENTITY_ID'=>'CONTACT','ELEMENT_ID'=>$quote['ASSIGNED_BY_ID']);
    /*$manager = array();
    // manager data
    $res = CCrmFieldMulti::GetList(array(),$contactArr);
    while($arRes = $res->GetNext()){
        $manager[$arRes['TYPE_ID']][]=$arRes['~VALUE'];
    }*/
// Вставляем текст в ячейку A1 #304560
    $sheet->getColumnDimension('A')->setWidth(54);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(23);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(14);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(18);
//$sheet->getColumnDimension('H')->setWidth(20);
//$sheet->getColumnDimension('I')->setWidth(12);



    /*$imagePath=$_SERVER['DOCUMENT_ROOT'] . '/upload/logo_excel.jpg';
    $logo = new PHPExcel_Worksheet_Drawing();
    $logo->setPath($imagePath);
    $logo->setCoordinates('A1');
    $logo->setOffsetX(0);
    $logo->setOffsetY(0);
    $logo->setWidthAndHeight(1046,623);
    $logo->setResizeProportional(false);
    $logo->setWorksheet($sheet);*/




    $sheet
        ->getStyle('A32:C55')
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('f2f2f2');




    $sheet->setCellValue("A33", Loc::getMessage("INFO_EVENT_TYPE", array(), $lang_id).':');$sheet->getStyle('A33')->applyFromArray($titleTopLeft);
    $data = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("CRM_DEAL", "UF_DATE_EVENT", $quote['ID']);
    $place = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("CRM_DEAL", "UF_CRM_1502715322", $quote['ID']);
    $manager = Bitrix\Main\UserTable::getById($quote["ASSIGNED_BY_ID"])->fetch();





    if($quote['TYPE_ID']=='SALE') {
        $TYPE_ID ='Кейтеринг';
    } elseif($quote['TYPE_ID']=='2'){
        $TYPE_ID ='Аренда персонала';
    } elseif($quote['TYPE_ID']=='3'){
        $TYPE_ID ='Доставка закусок';
    } elseif($quote['TYPE_ID']=='4'){
        $TYPE_ID ='Mill Hub';
    }


    $sheet->mergeCells("A35:C35");
    getBoldHeader(Loc::getMessage("NAME_HEAD", array(), $lang_id).': ',$quote['TITLE'],35,$sheet);

    $sheet->mergeCells("A37:C37");
    getBoldHeader(Loc::getMessage("NAME_TYPE", array(), $lang_id).': ',$TYPE_ID,37,$sheet);

    getBoldHeader(Loc::getMessage("DATE", array(), $lang_id) . ': ',$data,38,$sheet);

    $sheet->mergeCells("A39:C39");
    getBoldHeader(Loc::getMessage("COMPANY_NAME", array(), $lang_id) . ': ',$quote['COMPANY_TITLE'],39,$sheet);

    getBoldHeader(Loc::getMessage("GUESTS_NUM", array(), $lang_id) . ': ',$menu['PROPERTYS']['PERS_STEP_1']["VALUE"],40,$sheet);

    getBoldHeader(Loc::getMessage("LOCATION_EVENT", array(), $lang_id) . ': ',$place,41,$sheet);

    $sheet->mergeCells("A43:C43");
    getBoldHeader(Loc::getMessage("CLIENT", array(), $lang_id) . ': ',$quote['CONTACT_FULL_NAME'],43,$sheet);
    getBoldHeader(Loc::getMessage("TEL", array(), $lang_id) . ': ',reset($contact['PHONE']),44,$sheet);
    getBoldHeader('E-mail: ',reset($contact['EMAIL']),45,$sheet);


    $sheet->mergeCells("A47:C47");
    $sheet->mergeCells("A49:C49");
    getBoldHeader(Loc::getMessage("MANAGER", array(), $lang_id) . ': ',$quote['ASSIGNED_BY_LAST_NAME'] . ' ' . $quote['ASSIGNED_BY_NAME'],47,$sheet);
    getBoldHeader(Loc::getMessage("TEL", array(), $lang_id) . ': ','(044) 221-42-41',48,$sheet);
    getBoldHeader('E-mail: ', $manager['EMAIL'],49,$sheet);







    //$sheet->setCellValue("A49", 'E-mail: '.$manager['EMAIL']);

    $sheet->getStyle('A35:A49')->applyFromArray($titleTopLeft1);

    $sheet->getStyle('A35:A49')->getAlignment()->setIndent(3);

    //$sheet->mergeCells("D32:H60");
    $sheet
        ->getStyle('D32:H55')
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('ffffff');
    $sheet->mergeCells("E47:G47");
    $sheet->mergeCells("E48:G48");
    $sheet->mergeCells("E49:G49");
    $sheet->mergeCells("E53:H53");



    $imagemanager=$_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($quote["ASSIGNED_BY_PERSONAL_PHOTO"]);
    if($quote["ASSIGNED_BY_PERSONAL_PHOTO"]=='') {
        $imagemanager=$_SERVER['DOCUMENT_ROOT'] . '/upload/logo_footer_excel.jpg';
    }


    $logo = new PHPExcel_Worksheet_Drawing();
    $logo->setPath($imagemanager);
    $logo->setCoordinates('E33');
    $logo->setOffsetX(0);
    $logo->setOffsetY(0);
    $logo->setWidthAndHeight(266, 269);
    $logo->setResizeProportional(false);
    $logo->setWorksheet($sheet);

    $sheet->setCellValue("E47", $quote['ASSIGNED_BY_LAST_NAME'] . ' ' . $quote['ASSIGNED_BY_NAME']);$sheet->getStyle('E47')->applyFromArray($titlemanager);
    $sheet->setCellValue("E48", $quote['ASSIGNED_BY_WORK_POSITION']);$sheet->getStyle('E48')->applyFromArray($titlemanagerpro);
    $sheet->setCellValue("E49", 'VIP CATERING');$sheet->getStyle('E49')->applyFromArray($titlemanagerpro);


    $sheet->setCellValue("E53", Loc::getMessage("SLUG", array(), $lang_id));$sheet->getStyle('E53')->applyFromArray($titlemanageslug);



    $line=56;
    $allSum=0;
    $allPers=0;
    $firstPers=0;
    $allGPers=0;
    $sumCellGPers='';
    $sumCellLPers='';
    $SecSumRow=array();
    $PersSumRow=array();
    $GrPersSumRow=array();
    $MlPersSumRow=array();
    $sumDopUslugi='';
    $allRowLA='';
    $allRowLNA='';
    $allRowLHot='';
    $full_sum = array();
    $full_sum_itogo = array();
    $with_drinks = false;


    $la_drinks = array();
    foreach($result['BLUDA'] as $section=>$value){
        // товары этапа

        $i=1;
        $values=array();
        foreach($value as $bludo1){
            $elId=$bludo1['PROPERTYS']['BLUDO']["VALUE"];
            $values[$arElements[$elId]["IBLOCK_SECTION_ID"]][] = $bludo1;

        }
        $elId=reset($values)[0]['PROPERTYS']['BLUDO']['VALUE'];
        $secId=$arElements[$elId]["IBLOCK_SECTION_ID"];

        $allSecSum=0;
        $allSecPers=0;
        $allSecGPers=0;
        // этап и шапка
        $pers = $menu['PROPERTYS']['PERS_'.$section]["VALUE"];

        $rowS=0;
        $rowE=0;
        $rowS=$line;
        $rowG='';
        $rowLA='';
        $rowLNA='';
        $rowLHot='';
        $catID=0;
        $la_drinks[$section] = array(
            "DATA" => $value
        );
        $prev_sect = $secId;

        $goods_counter = 0;

        // достаем из массива алкоголь
        foreach($values as $sect=>$secvalue) {
            foreach($secvalue as $k => $bludo) {
                $elId=$bludo['PROPERTYS']['BLUDO']["VALUE"];
                $vyhod = $arElements[$elId]['PROPERTYS']['VYHOD']["VALUE"];
                $quant = $bludo['PROPERTYS']['QUANT']["VALUE"];
                $price = $arElements[$elId]['PROPERTYS']['PRICE']["VALUE"];

                if( $arSection[231]['LEFT_MARGIN']<$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['LEFT_MARGIN'] && $arSection[231]['RIGHT_MARGIN']>$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['RIGHT_MARGIN'] ){
                    $rowLA.=($rowLA=='')?"=F$line":"+F$line";

                    $arElements[$elId]["QUANTITY"] = $quant;
                    $arElements[$elId]["PERSONS"] = $pers;
                    $la_drinks[$section]["ITEMS"][] = $arElements[$elId];
                    $with_drinks = true;

                    unset($values[ $sect ][ $k ]);
                } else {
                    ++$goods_counter;
                }
            }
        }

        if($firstPers===0){
            $firstPers=$pers;
        }

        if ( $goods_counter == 0 ) {
            continue;
        }

        $sheet->mergeCells("A$line:H$line");
        $sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $value = explode('|',$menu['PROPERTYS'][$section]["VALUE"])[0] . " " . Loc::getMessage("FOR", array(), $lang_id) . " " . $pers . " " . Loc::getMessage("PERS", array(), $lang_id);
        $sheet->setCellValue("A$line", $value);

        $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_title);
        $sheet->getRowDimension($line)->setRowHeight(40);

        $persCell=$line;
        $line++;
        //echo $arSection[348]['LEFT_MARGIN'].'>'.$secId.$arSection[$secId]['LEFT_MARGIN'].' && '.$arSection[348]['RIGHT_MARGIN'].'<'.$arSection[$secId]['RIGHT_MARGIN'].'<br>';

        // обслуживание
        if($arSection[348]['LEFT_MARGIN']<$arSection[$secId]['LEFT_MARGIN'] && $arSection[348]['RIGHT_MARGIN']>$arSection[$secId]['RIGHT_MARGIN']):

            $sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));
            $sheet->setCellValue("B$line", Loc::getMessage("SIZE_G", array(), $lang_id));
            $sheet->setCellValue("C$line", Loc::getMessage("PICTURE", array(), $lang_id));
            $sheet->setCellValue("D$line", Loc::getMessage("QUANT_PORTION", array(), $lang_id));
            $sheet->setCellValue("E$line", Loc::getMessage("G_ON_PERS", array(), $lang_id));
            $sheet->setCellValue("F$line", Loc::getMessage("PRICE_HRN", array(), $lang_id));
            $sheet->setCellValue("G$line", Loc::getMessage("SUM_HRN", array(), $lang_id));

            $sheet->setCellValue("H$line", "ID");

            $line++;

        else:
            $sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));
            $sheet->setCellValue("B$line", Loc::getMessage("SIZE_G", array(), $lang_id));
            $sheet->setCellValue("C$line", Loc::getMessage("PICTURE", array(), $lang_id));
            $sheet->setCellValue("D$line", Loc::getMessage("QUANT_PORTION", array(), $lang_id));
            $sheet->setCellValue("E$line", Loc::getMessage("G_ON_PERS", array(), $lang_id));
            $sheet->setCellValue("F$line", Loc::getMessage("PRICE_HRN", array(), $lang_id));
            $sheet->setCellValue("G$line", Loc::getMessage("SUM_HRN", array(), $lang_id));

            $sheet->setCellValue("H$line", "ID");

            $sheet->getRowDimension($line)->setRowHeight(40);
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_head);

            $line++;
        endif;


        foreach($values as $sect=>$secvalue){
            $sectionMargin=array();
            $sectionMargin=array('LEFT'=>$arSection[$sect]['LEFT_MARGIN'],'RIGHT'=>$arSection[$sect]['RIGHT_MARGIN']);
            $thisCatID=getCat($sect,$arSection);
            if($thisCatID!=$catID && $thisCatID != 231 ){
                $sheet->mergeCells("B$line:H$line");



                //getBold1($arSection[$thisCatID]['NAME'],$arSection[$thisCatID]['UF_EN_NAME'],$line,$sheet);
                //getBold1($arSection[$thisCatID]['NAME'],$arSection[$thisCatID]['UF_EN_NAME'],$line,$sheet);
                $sheet->setCellValue("A$line", $arSection[$thisCatID]['NAME']);
                $sheet->setCellValue("B$line", $arSection[$thisCatID]['UF_EN_NAME']);


                $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($category_title);
                $xls->getActiveSheet()->getStyle("B$line")->applyFromArray($category_title_en);


                $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);
                $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($line)->setRowHeight(40);
                /*$sheet
                    ->getStyle("A$line:H$line")
                    ->getFill()
                    ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('0c00d0');*/

                $line++;
                $catID=$thisCatID;
            }

            if ( $prev_sect != $secId ) {
                $col_row = 0;
            }

            foreach($secvalue as $bludo){
                if($arSection[348]['LEFT_MARGIN']<$arSection[$secId]['LEFT_MARGIN'] && $arSection[348]['RIGHT_MARGIN']>$arSection[$secId]['RIGHT_MARGIN']){
                    $sheet->mergeCells("B$line:C$line");
                    $sheet->mergeCells("D$line:F$line");
                }

                $elId=$bludo['PROPERTYS']['BLUDO']["VALUE"];
                $vyhod = $arElements[$elId]['PROPERTYS']['VYHOD']["VALUE"];
                $quant = $bludo['PROPERTYS']['QUANT']["VALUE"];
                $price = $arElements[$elId]['PROPERTYS']['PRICE']["VALUE"];

                /*if( $arSection[231]['LEFT_MARGIN']<$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['LEFT_MARGIN'] && $arSection[231]['RIGHT_MARGIN']>$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['RIGHT_MARGIN'] ){
                    $rowLA.=($rowLA=='')?"=F$line":"+F$line";

                    $arElements[$elId]["QUANTITY"] = $quant;
                    $arElements[$elId]["PERSONS"] = $pers;
                    $la_drinks[$section]["ITEMS"][] = $arElements[$elId];
                    $with_drinks = true;
                    continue;
                }
                else */if($arElements[$elId]['IBLOCK_SECTION_ID']==359){
                    $rowLNA.=($rowLNA=='')?"=E$line":"+E$line";
                    $allRowLNA.=($allRowLNA=='')?"=E$line":"+E$line";
                }
                else if($arElements[$elId]['IBLOCK_SECTION_ID']==360){
                    $rowLHot.=($rowLHot=='')?"=E$line":"+E$line";
                    $allRowLHot.=($allRowLHot=='')?"=E$line":"+E$line";
                }
                else{
                    $rowG.=($rowG=='')?"=E$line":"+E$line";
                }

                // сумма доп услуг
                if($arSection[348]['LEFT_MARGIN']<$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['LEFT_MARGIN'] && $arSection[348]['RIGHT_MARGIN']>$arSection[$arElements[$elId]['IBLOCK_SECTION_ID']]['RIGHT_MARGIN']){
                    $sumDopUslugi=$sumDopUslugi+($price*$quant);
                }






                if($arElements[$elId]["NAME"]!='') {
                    $sheet->setCellValue("A$line", html_entity_decode($arElements[$elId]["NAME"]));
                } //elseif($_GET["lang"]=='RU') {               }


                //if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]=='RU') {
                if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]!='EN') {
                    $liner = $line + 1;
                    $sheet->setCellValue("A$liner",html_entity_decode(trim($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"])));
                    $xls->getActiveSheet()->getStyle("A$liner:H$liner")->applyFromArray($sectionRowTABLE1);
                    $sheet->getStyle("A$liner")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
                }
                $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($nameFuterStyleHead);


                $sheet->setCellValue("B$line", $vyhod);
                $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                if($quant==''){
                    $quant=1;
                }
                $sheet->setCellValue("D$line", $quant);
                $sheet->getStyle("D$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sum='=B'.$line.'*D'.$line.'/'.$pers;

                $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setCellValue("F$line", $price);


                $sheet->setCellValue("G$line", "=F".$line."*D".$line);
                $sheet->getStyle("G$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("G$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $sheet->setCellValue("E$line", '=ROUND(B'.$line.'*D'.$line.'/'.$pers . ", 2)");
                $sheet->getStyle("E$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $sheet->setCellValue("H$line", $elId);
                $sheet->getStyle("H$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $imgObj = CFile::GetFileArray($arElements[$elId]["PREVIEW_PICTURE"]);
                if( !empty($imgObj["SRC"]) ) {
                    $img = $_SERVER["DOCUMENT_ROOT"].$imgObj["SRC"];
                    $sheet->getRowDimension($line)->setRowHeight(100);
                } else {
                    $img = '';
                }


                $allSum=$allSum+($price*$quant);
                $allSecSum=$allSecSum+($price*$quant);

                $allPers=$allPers+$pers;
                $allSecPers=$allPers+$pers;

                $allGPers=$allGPers+($vyhod*$quant);
                $allSecGPers=$allSecGPers+($vyhod*$quant);

                $style_array = ( ($col_row++)%2 == 0 ) ? $sectionRow : $sectionRow;
                //if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]=='RU') {
                if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]!='EN') {
                    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionRowTABLE);
                } else {
                    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($style_array);
                }



                $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ( strlen($img) > 0 ) {
                    $iDrowing = new PHPExcel_Worksheet_Drawing();
                    $iDrowing->setPath($img);
                    $iDrowing->setOffsetX(15);
                    $iDrowing->setOffsetY(15);
                    $iDrowing->setHeight(90);
                    $sheet->getRowDimension(2);
                    $iDrowing->setCoordinates("C$line");
                    $iDrowing->setWorksheet($xls->getActiveSheet());
                }


                //$sheet->getStyle("A$line")->applyFromArray($sectionStyle);

                $i++;
                $line++;
                //if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]=='RU') {
                if($arElements[$elId]['PROPERTYS']['EN_NAME']["VALUE"]!='' && $_GET["lang"]!='EN') {
                    $line++;
                }
            }
            $rowE=$line-1;
        }
        $SecSumRow[$line]=$line;

        // ########## Вывод информации по каждому этапу #########

        $sheet->mergeCells("C$line:D$line");
        $sheet->getRowDimension($line)->setRowHeight(30);
        $full_sum[] = "F$line";
        $full_sum_itogo[] = "F$line";
        $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_MENU", array(), $lang_id));
        $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->setCellValue("F$line", '=SUM(G'.$rowS.':G'.$rowE.')');
        $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
        $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
        $line++;

        if($arSection[348]['LEFT_MARGIN']<$sectionMargin['LEFT'] && $arSection[348]['RIGHT_MARGIN']>$sectionMargin['RIGHT']){

        }
        else{
            $sheet->mergeCells("C$line:D$line");
            $sheet->getRowDimension($line)->setRowHeight(30);
            $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_HRN_PERS", array(), $lang_id));
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->setCellValue("F$line", '=F'.($line-1).'/'.$pers);
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
            $line++;

            $sheet->mergeCells("C$line:D$line");
            $sheet->getRowDimension($line)->setRowHeight(30);
            $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_G_PERS", array(), $lang_id));
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->setCellValue("F$line", $rowG);
            $GrPersSumRow["F$line"]="F$line";
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
            $line++;


            $sheet->mergeCells("C$line:D$line");
            $sheet->getRowDimension($line)->setRowHeight(30);
            $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_L_PERS_NA", array(), $lang_id));
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->setCellValue("F$line", ( !empty($rowLNA) ) ? $rowLNA : 0 );
            $MlPersSumRow["F$line"]="F$line";
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
            $line++;

            if($rowLHot!=''){
                $sheet->mergeCells("C$line:D$line");
                $sheet->getRowDimension($line)->setRowHeight(30);
                $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_L_PERS_HOT", array(), $lang_id));
                $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->setCellValue("F$line", $rowLHot);
                $MlPersSumRow["F$line"]="F$line";
                $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
                $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
                $line++;
            }
        }
    }




    if ( count($SecSumRow) > 0 ) {


        $SumRowlist = array_shift($SecSumRow);
        $tmpRlist = $SumRowlist;
        $SumRowlist = '=F' . $SumRowlist;
        foreach ($SecSumRow as $sr) {
            $SumRowlist .= '+F' . $sr;
        }
        array_unshift($SecSumRow, $tmpRlist);

        $sumPers = array_shift($PersSumRow);
        $tmpPers = $sumPers;
        foreach ($PersSumRow as $pr) {
            $sumPers .= "+$pr";
        }
        array_unshift($PersSumRow, $tmpPers);

        $gPers = array_shift($GrPersSumRow);
        $tmpGpers = $gPers;
        foreach ($GrPersSumRow as $gpr) {
            $gPers .= "+$gpr";
        }
        array_unshift($GrPersSumRow, $tmpGpers);

        $gPers = array_shift($MlPersSumRow);
        $tmpGpers = $gPers;
        foreach ($MlPersSumRow as $gpr) {
            $gPers .= "+$gpr";
        }
        array_unshift($MlPersSumRow, $tmpGpers);




    }

    // ################## 2. Итого, грн. по алкогольным напиткам (без НДС) ##############
    if ( $with_drinks ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage("TOTAL_DRINK_TITLE", array(), $lang_id));
        $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
        $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);;
        $sheet->getRowDimension($line)->setRowHeight(40);
        $sheet
            ->getStyle("A$line:H$line")
            ->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('0c00d0');
        $line++;

        foreach ( $la_drinks as $arItem ) {
            if ( count($arItem["ITEMS"]) == 0 ) {
                continue;
            }



            $sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));
            $sheet->setCellValue("B$line", Loc::getMessage("SIZE_G", array(), $lang_id));
            $sheet->setCellValue("C$line", Loc::getMessage("PICTURE", array(), $lang_id));
            $sheet->setCellValue("D$line", Loc::getMessage("QUANT_PORTION", array(), $lang_id));
            $sheet->setCellValue("E$line", Loc::getMessage("G_ON_PERS", array(), $lang_id));
            $sheet->setCellValue("F$line", Loc::getMessage("PRICE_HRN", array(), $lang_id));
            $sheet->setCellValue("G$line", Loc::getMessage("SUM_HRN", array(), $lang_id));
            $sheet->setCellValue("H$line", "ID");



            $sheet->getRowDimension($line)->setRowHeight(40);
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_head);
            $line++;

            $allSecSum=0;
            $allSecPers=0;
            $allSecGPers=0;

            $i = 1;
            $allSum = 0;

            $line_mas = array($line);
            foreach ( $arItem["ITEMS"] as $item ) {
                $with_drinks = true;
                $allRowLA.=($allRowLA=='')?"=E$line":"+E$line";



                $sheet->setCellValue("A$line", html_entity_decode($item["NAME"]));
                $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($nameFuterStyleHead);

                $sheet->setCellValue("B$line", $item["PROPERTYS"]["VYHOD"]["VALUE"]);
                $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                if($item["QUANTITY"]==''){
                    $item["QUANTITY"]=1;
                }
                $sheet->setCellValue("D$line", $item["QUANTITY"]);
                $sheet->getStyle("D$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sum='=B'.$line.'*D'.$line.'/'.$item["PERSONS"];

                $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->setCellValue("F$line", $item["PROPERTYS"]["PRICE"]["VALUE"]);
                $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $sheet->setCellValue("G$line", "=F".$line."*D".$line);
                $sheet->getStyle("G$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("G$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                //$sheet->getStyle("F$line")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                $sheet->setCellValue("E$line", '=ROUND(B'.$line.'*D'.$line.'/'.$item["PERSONS"] . ", 2)");
                $sheet->getStyle("E$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $sheet->setCellValue("H$line", $i++);
                $sheet->getStyle("H$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                //$sheet->getStyle("H$line")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);



                $allSum=$allSum+($item["PROPERTYS"]["PRICE"]["VALUE"]*$item["QUANTITY"]);

                $allPers=$allPers+$item["PERSONS"];
                $allSecPers=$allPers+$item["PERSONS"];

                $allGPers=$allGPers+($item["PROPERTYS"]["VYHOD"]["VALUE"]*$item["QUANTITY"]);
                $allSecGPers=$allSecGPers+($item["PROPERTYS"]["VYHOD"]["VALUE"]*$item["QUANTITY"]);

                $style_array = ( $line%2 != 0 ) ? $sectionRow : $sectionRow;
                $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($style_array);

                $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $imgObj1 = CFile::GetFileArray($item["PREVIEW_PICTURE"]);
                $img1 = $_SERVER["DOCUMENT_ROOT"].$imgObj1["SRC"];

                if(strlen($img1)>strlen($_SERVER["DOCUMENT_ROOT"]))
                    $sheet->getRowDimension($line)->setRowHeight(100);

                $iDrowing = new PHPExcel_Worksheet_Drawing();
                $iDrowing->setPath($img1);
                $iDrowing->setOffsetX(15);
                $iDrowing->setOffsetY(15);
                $iDrowing->setHeight(100);
                $sheet->getRowDimension(2);
                $iDrowing->setCoordinates("C$line");
                $iDrowing->setWorksheet($xls->getActiveSheet());
                $line++;
            }

            $line_mas[] = $line - 1;
            $full_sum[] = "F$line";


            $sheet->mergeCells("C$line:D$line");

            $sheet->setCellValue("C$line", Loc::getMessage("TOTAL_DRINK_PRICE", array(), $lang_id));
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->setCellValue("F$line", "=SUM(G" . $line_mas[0] . ":G" . $line_mas[1] . ")");
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);

            $itogoMenuCel = "F$line";
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($line)->setRowHeight(30);
            $line++;

            $sheet->mergeCells("C$line:D$line");

            $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_MENU_HRN_PERS", array(), $lang_id));
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);;

            $sheet->setCellValue("F$line", '=F' . ($line - 1) . '/' . $firstPers . '');
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($line)->setRowHeight(30);
            $line++;

            $sheet->mergeCells("C$line:D$line");

            $sheet->setCellValue("C$line", Loc::getMessage("ITOGO_L_PERS_A", array(), $lang_id));
            $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
            $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->setCellValue("F$line", $allRowLA);
            $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($price_s);
            $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($line)->setRowHeight(30);
            $line++;
        }
    }



    $line++;

    //Итого
    $sheet->setCellValue("A$line", Loc::getMessage("TOTAL_MENU_ON", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($total_menu);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('d0cece');


    $sheet->setCellValue("F$line", "=ROUND(SUM(". implode(",", array_merge($full_sum_itogo)) . "), 2)");
    $xls->getActiveSheet()->getStyle("F$line")->applyFromArray($total_menu_sum);
    $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $line++;
    $line++;





    // ################### 3. Логистика и обслуживание, грн. (без НДС): ###########

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("FOR_LOGISTIC", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');

    $line++;


    $sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));
    //$sheet->setCellValue("B$line", 'Кол-во');
    $sheet->setCellValue("B$line", Loc::getMessage("SUM_HRN", array(), $lang_id));



    $sheet->getRowDimension($line)->setRowHeight(40);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->applyFromArray($table_head);
    $line++;

    //$sheet->setCellValue("A$line", '');

    //$sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));

    //$sheet->setCellValue("C$line", Loc::getMessage("SUM_HRN", array(), $lang_id));
    // $xls->getActiveSheet()->getStyle("A$line:C$line")->applyFromArray($table_head);
    //$line++;

    $line_mas = array($line);

    $sheet->setCellValue("A$line", Loc::getMessage("LOG_SERVICE_MANAGER", array(), $lang_id));
    //$sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    //$sheet->getStyle("A$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($nameFuterStyleHead);



    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    //$xls->getActiveSheet()->getStyle("E$line")->applyFromArray($nameFuterStyleHead);
    //$sheet->setCellValue("E$line", "1000");
    $sheet->getRowDimension($line)->setRowHeight(60);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->applyFromArray($style_array);
    $line++;



    $line_mas[] = $line;
    //$sheet->setCellValue("A$line", "4");
    //$sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    //$sheet->getStyle("A$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    $sheet->setCellValue("A$line", Loc::getMessage("LOG_DELIVERY", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($nameFuterStyleHead);

    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    //$sheet->setCellValue("E$line", "1000");
    $sheet->getRowDimension($line)->setRowHeight(60);
    //$xls->getActiveSheet()->getStyle("E$line")->applyFromArray($nameFuterStyleHead);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->applyFromArray($style_array);
    $line++;







    $dopService = array("B$line");
    /*$sheet->mergeCells("A$line:B$line");
    $sheet->setCellValue("A$line", Loc::getMessage("LOG_TOTAL_PAY", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->applyFromArray($total_footer);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);*/

    //$sheet->mergeCells("C$line:D$line");

    $sheet->setCellValue("A$line", Loc::getMessage("LOG_TOTAL_PAY", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
    $xls->getActiveSheet()->getStyle("B$line")->applyFromArray($price_s);
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);





    /*$sheet->getStyle("C$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->getStyle("C$line")->applyFromArray($total_footer);
    $sheet->getStyle("C$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->setCellValue("C$line", "=SUM(C" . $line_mas[0] . ":C" . $line_mas[1] . ")");*/


    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->setCellValue("B$line", "=SUM(B" . $line_mas[0] . ":B" . $line_mas[1] . ")");
    $sheet->getRowDimension($line)->setRowHeight(30);
    $line++;

    $sheet->mergeCells("A$line:H$line");

    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($tech_info);

    /*if($_GET["lang"]!='UA') {
        $objRichText = new PHPExcel_RichText();
        $run1 = $objRichText->createTextRun(Loc::getMessage("TECHNICAL_INFO", array(), $lang_id));
        $run1->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED))->setName('Century Gothic')->setSize(12)->setBold(true);
        $run2 = $objRichText->createTextRun(Loc::getMessage("TECHNICAL_INFO1", array(), $lang_id));
        $run2->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLUE))->setName('Century Gothic')->setSize(12)->setBold(true);
        $sheet->setCellValue("A$line", $objRichText);


    //$sheet->setCellValue("A$line", "A$line".' '.Loc::getMessage("TECHNICAL_INFO1", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($tech_info);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getRowDimension($line)->setRowHeight(110);
    $line++;
    } else {*/
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($tech_info_ua);
    $sheet->setCellValue("A$line", Loc::getMessage("TECHNICAL_INFO", array(), $lang_id));
    $sheet->getStyle("A$line:H$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getRowDimension($line)->setRowHeight(30);
    $line++;
    $sheet->mergeCells("A$line:H$line");
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($tech_info_ua1);
    $sheet->setCellValue("A$line", Loc::getMessage("TECHNICAL_INFO1", array(), $lang_id));
    $sheet->getStyle("A$line:H$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getRowDimension($line)->setRowHeight(30);
    $line++;
    //}

    // ################### 4. Аренда необходимого оборудования, грн.  без НДС: #################
    /*$sheet->mergeCells("A$line:F$line");
    $sheet->setCellValue("A$line", Loc::getMessage("ARENDA_TITLE", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:F$line")->applyFromArray($default_title);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($line)->setRowHeight(40);*/

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("ARENDA_TITLE", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);;
    $sheet->getRowDimension($line)->setRowHeight(40);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');



    $line++;


    $sheet->setCellValue("A$line", Loc::getMessage("NAMES", array(), $lang_id));
    $sheet->setCellValue("B$line", 'Кол-во');
    //$sheet->setCellValue("C$line", '');
    $sheet->setCellValue("C$line", Loc::getMessage("ED_IZM", array(), $lang_id));
    $sheet->setCellValue("D$line", Loc::getMessage("PRICE_HRN", array(), $lang_id));
    $sheet->setCellValue("E$line", Loc::getMessage("SUM_HRN", array(), $lang_id));
    //$sheet->setCellValue("F$line", Loc::getMessage("SUM_HRN", array(), $lang_id));
    //$sheet->setCellValue("G$line", '');
    // $sheet->setCellValue("H$line", "");



    $sheet->getRowDimension($line)->setRowHeight(40);
    $xls->getActiveSheet()->getStyle("A$line:E$line")->applyFromArray($table_head);
    $line++;

    //$sheet->setCellValue("A$line", '');

    //$sheet->setCellValue("B$line", Loc::getMessage("NAMES", array(), $lang_id));

    //$sheet->setCellValue("C$line", Loc::getMessage("RATIO", array(), $lang_id));

    //$sheet->setCellValue("D$line", Loc::getMessage("QUANT", array(), $lang_id));

    //$sheet->setCellValue("E$line", Loc::getMessage("PRICE_HRN", array(), $lang_id));

    //$sheet->setCellValue("F$line", Loc::getMessage("SUM_HRN", array(), $lang_id));
    // $xls->getActiveSheet()->getStyle("A$line:F$line")->applyFromArray($table_head);
    // $line++;

    $lang_mas = array(
        array(
            "NAME" 		=> "ARENDA_SHATER_4",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "1500",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_SHATER_8",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "2500",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_B_STOLA",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "400",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_B_STULA",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "190",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_B_STOLA_V_CHEHLE",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "220",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_STEKLA",
            "RATIO" 	=> "RATIO_SH",
            "PRICE" 	=> "3",
            "QUANTITY" 	=> ""
        ),
        array(
            "NAME" 		=> "ARENDA_LED",
            "RATIO" 	=> "RATIO_KG",
            "PRICE" 	=> "10",
            "QUANTITY" 	=> ""
        )
    );
    $line_mas = array($line);
    foreach ( $lang_mas as $key => $lang ) {
        $pos = (intval($key) + 1);
        if ( $pos == count($lang_mas) ) {
            $line_mas[] = $line;
        }

        $sheet->setCellValue("A$line", Loc::getMessage($lang["NAME"], array(), $lang_id));
        $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($nameFuterStyleHead);

        //$sheet->setCellValue("C$line", Loc::getMessage($lang["RATIO"], array(), $lang_id));
        //$sheet->getStyle("C$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
        //$sheet->getStyle("C$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        if ( intval($lang["QUANTITY"]) > 0 ) {
            //$sheet->setCellValue("B$line", $lang["QUANTITY"]);
        } else {
            //$sheet->setCellValue("B$line", '1');
        }
        $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


        if ( floatval($lang["PRICE"]) > 0 ) {
            //$sheet->setCellValue("D$line", $lang["PRICE"]);
        }
        $sheet->getStyle("D$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle("D$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


        $sheet->getStyle("E$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle("E$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $sheet->setCellValue("E$line", "=B" . $line . "*D" . $line);
        $xls->getActiveSheet()->getStyle("A$line:E$line")->applyFromArray($style_array);
        $line++;
    }

    $dopService[] = "D$line";
    /*$sheet->mergeCells("A$line:E$line");
    $sheet->setCellValue("A$line", Loc::getMessage("RATIO_TOTAL_PAY", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $xls->getActiveSheet()->getStyle("A$line:B$line")->applyFromArray($total_footer);
    $xls->getActiveSheet()->getStyle("A$line:E$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    $sheet->getStyle("F$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("F$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->getStyle("F$line")->applyFromArray($total_footer);
    $sheet->getStyle("F$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->setCellValue("F$line", "=SUM(F" . $line_mas[0] . ":F" . $line_mas[1] . ")");
    $line++;
    $line++;*/

    $sheet->mergeCells("B$line:C$line");

    $sheet->setCellValue("B$line", Loc::getMessage("RATIO_TOTAL_PAY", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($total_footer);
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);

    $sheet->getStyle("D$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("D$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->setCellValue("D$line", "=SUM(E" . $line_mas[0] . ":E" . $line_mas[1] . ")");
    $xls->getActiveSheet()->getStyle("D$line")->applyFromArray($price_s);
    $sheet->getRowDimension($line)->setRowHeight(30);
    $line++;



    //Информационный лист
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", '');
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_COOPERATION_TITLE", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);;
    $sheet->getRowDimension($line)->setRowHeight(40);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');
    $line++;

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_COOPERATION_INCLUDE", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead1);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);;
    $sheet->getRowDimension($line)->setRowHeight(30);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('f2f2f2');
    $line++;


    //Текстовая информация
    // ############## Раздел "Обслуживание и логистика" ###############
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_TEXT_LOGIC", array(), $lang_id));
    $sheet->getStyle("A$line")->applyFromArray($table_second_title);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $lang_mas = array("SL_TEXT_CONSULTATION", "SL_TEXT_WORK", "SL_TEXT_ORGANIZATION", "SL_TEXT_SERVICE", "SL_TEXT_CLEANING",
        "SL_TEXT_REPORT", "SL_TEXT_DELIVERY", "SL_TEXT_TECHINC");
    foreach ( $lang_mas as $key => $lang ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage($lang, array(), $lang_id));
        $sheet->getStyle("A$line:H$line")->applyFromArray($second_def_text);
        $sheet->getRowDimension($line)->setRowHeight(40);
        $line++;
    }

    // ############## Раздел "Аренда необходимого технологического оборудования" ###############
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_TEXT_ARENDA", array(), $lang_id));
    $sheet->getStyle("A$line")->applyFromArray($table_second_title);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $lang_mas = array("SL_TEXT_FURNITURE", "SL_TEXT_TECHSTILE", "SL_TEXT_DISHES", "SL_TEXT_ACCESSORIES", "SL_TEXT_DOP_FURNITURE",
        "SL_TEXT_AL_INFO");
    foreach ( $lang_mas as $key => $lang ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage($lang, array(), $lang_id));
        $sheet->getStyle("A$line:H$line")->applyFromArray($second_def_text);
        $sheet->getRowDimension($line)->setRowHeight(40);
        $line++;
    }

    // ############## Раздел "Необходимое технологическое оборудования для банкетного типа мероприятия" ###############
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_TITLE_TECHNIC", array(), $lang_id));
    $sheet->getStyle("A$line")->applyFromArray($table_second_title);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $lang_mas = array("SL_TEXT_DISHES", "SL_TEXT_DOP_FURNITURE", "SL_TEXT_AL_INFO");
    foreach ( $lang_mas as $key => $lang ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage($lang, array(), $lang_id));
        $sheet->getStyle("A$line:H$line")->applyFromArray($second_def_text);
        $sheet->getRowDimension($line)->setRowHeight(40);
        $line++;
    }

    // ############## Раздел "Дополнительное предложение по декор-оформлению" ###############
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_TITLE_DOP_PROPOSE", array(), $lang_id));
    $sheet->getStyle("A$line")->applyFromArray($table_second_title);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $lang_mas = array("SL_TEXT_VARS", "SL_TEXT_ANIMATION", "SL_TEXT_DECORATION_WORK");
    foreach ( $lang_mas as $key => $lang ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage($lang, array(), $lang_id));
        $sheet->getStyle("A$line:H$line")->applyFromArray($second_def_text);
        $sheet->getRowDimension($line)->setRowHeight(40);
        $line++;
    }

    // ############## Раздел "Условия оплаты, корректировки и аннуляции мероприятий" ###############
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SL_TITLE_PAYMENT", array(), $lang_id));
    $sheet->getStyle("A$line")->applyFromArray($table_second_title);
    $sheet->getStyle("A$line")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getRowDimension($line)->setRowHeight(40);
    $line++;

    $lang_mas = array("SL_TEXT_CONDITION", "SL_TEXT_DOCUMENTATION", "SL_TEXT_ORDER", "SL_TEXT_EVENT", "SL_TEXT_EVENT_CONFIRM",
        "SL_TEXT_CHANGE_MENU", "SL_TEXT_HAPPENING", "SL_TEXT_PRICES");
    foreach ( $lang_mas as $key => $lang ) {
        $sheet->mergeCells("A$line:H$line");
        $sheet->setCellValue("A$line", Loc::getMessage($lang, array(), $lang_id));
        $sheet->getStyle("A$line:H$line")->applyFromArray($second_def_text);
        if ( in_array($lang, array("SL_TEXT_DOCUMENTATION", "SL_TEXT_HAPPENING")) ) {
            $sheet->getRowDimension($line)->setRowHeight(110);
        } else {
            $sheet->getRowDimension($line)->setRowHeight(40);
        }

        $line++;
    }




    // ################### Формирование сметы, грн.: #################

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("SMETA_MER", array(), $lang_id));
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setIndent(4);;
    $sheet->getRowDimension($line)->setRowHeight(40);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');
    $line++;


    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("FOR_SMET", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A$line")->getAlignment()->setIndent(3);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($new_price);
    $sheet->getRowDimension($line)->setRowHeight(35);
    $line++;


    $sheet->setCellValue("A$line", Loc::getMessage("SUM_NAL", array(), $lang_id));
    $sheet->mergeCells("B$line:H$line");
    $sheet->setCellValue("B$line", "=ROUND(SUM(". implode(",", array_merge($full_sum, $dopService)) . "), 2)");
    $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getRowDimension($line)->setRowHeight(35);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);


    if($menu['PROPERTYS']['DISCOUNT']['VALUE']>0){
        $line++;
        $disc = $menu['PROPERTYS']['DISCOUNT']['VALUE'];
        $sheet->setCellValue("A$line", Loc::getMessage("PARTNER_SALE", array(), $lang_id).' '.$disc.'% ');
        $sheet->mergeCells("B$line:H$line");
        //$sheet->setCellValue("E$line", $menu['PROPERTYS']['DISCOUNT']['VALUE']);
        //$sheet->setCellValue("F$line", "%");
        $sheet->getStyle("E$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $prevLine=$line-1;

        if($sumDopUslugi>0){
            //$sheet->setCellValue("B$line", "=ROUND((B$prevLine-SUM(" . implode(",", $dopService) . ")-$sumDopUslugi)*($disc/100), 2)");
            $sheet->setCellValue("B$line", "=ROUND((B$prevLine*$disc/100), 2)");
        }
        else{
            //$sheet->setCellValue("B$line", "=ROUND((B$prevLine-SUM(" . implode(",", $dopService) . "))*($disc/100), 2)");
            $sheet->setCellValue("B$line", "=ROUND((B$prevLine*$disc/100), 2)");
        }
        $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
        $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getRowDimension($line)->setRowHeight(35);
        $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);
        $line++;

        $endSumCell="B$line";
        $sheet->setCellValue("A$line", Loc::getMessage("SUM_NOVAT_SALE", array(), $lang_id));
        $sheet->mergeCells("B$line:H$line");
        $sheet->setCellValue("B$line", "=ROUND(B".($line-2)."-B".($line-1).", 2)");
        $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
        $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $sheet->getRowDimension($line)->setRowHeight(35);
        $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);
        $sheet
            ->getStyle("A$line:H$line")
            ->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('d0cece');
        $line++;
    }
    else{$endSumCell="B$line";$line++;}

    $sheet->setCellValue("A$line", Loc::getMessage("SUM_NOVAT_PERS", array(), $lang_id));
    $sheet->mergeCells("B$line:H$line");

    // $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sumPers=array_shift($PersSumRow);
    $tmpPers=$sumPers;
    if(is_array($PersSumRow)){
        foreach($PersSumRow as $pr){$sumPers.="+$pr";}
    }
    array_unshift($PersSumRow,$tmpPers);

    $sheet->setCellValue("B$line", "=ROUND(B".($line-1)."/".$firstPers.", 2)");
    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
    $sheet->getRowDimension($line)->setRowHeight(35);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle("A$line")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle("H$line")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $line++;

    // pay variation
    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", Loc::getMessage("FORM_PAY", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A$line")->getAlignment()->setIndent(3);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($new_price);
    $sheet->getRowDimension($line)->setRowHeight(35);

    $line++;

    /* Сумма , грн. наличная оплата (без НДС)
	$sheet->setCellValue("B$line", Loc::getMessage("PAY_KACHE", array(), $lang_id));
	$sheet->setCellValue("H$line", "=".$endSumCell);
	$xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border);
	$xls->getActiveSheet()->getStyle("A$line:H$line")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
	$sheet->getStyle("A$line")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
	$sheet->getStyle("H$line")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
	$line++;
    */

    /*$sheet->setCellValue("B$line", Loc::getMessage("SUM_FOP", array(), $lang_id));
    $sheet->getStyle("B$line")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);

    $sheet->setCellValue("H$line", "=ROUND(".$endSumCell."*1.07, 2)");
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle("A$line")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle("H$line")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);*/


    $sheet->setCellValue("A$line", Loc::getMessage("SUM_FOP", array(), $lang_id));
    $sheet->mergeCells("B$line:H$line");
    $sheet->setCellValue("B$line", "=ROUND(".$endSumCell."*1.07, 2)");
    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
    $sheet->getRowDimension($line)->setRowHeight(35);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);






    $line++;


    $sheet->setCellValue("A$line", Loc::getMessage("SUM_VAT", array(), $lang_id));
    $sheet->mergeCells("B$line:H$line");
    $sheet->setCellValue("B$line", "=ROUND(".$endSumCell."*1.2, 2)");
    $sheet->getStyle("B$line")->getAlignment()->setIndent(3);
    $sheet->getStyle("B$line")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $sheet->getRowDimension($line)->setRowHeight(35);
    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($table_footer_without_border1);


    $line++;

    $sheet->mergeCells("A$line:H$line");
    $sheet->setCellValue("A$line", '');

    $xls->getActiveSheet()->getStyle("A$line:H$line")->applyFromArray($sectionFuterStyleHead);
    $sheet
        ->getStyle("A$line:H$line")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');
    $sheet->getRowDimension($line)->setRowHeight(30);
    $line++;
    $line1=$line+10;
    $xls->getActiveSheet()->getStyle("A$line:H$line1")->applyFromArray($table_contacts_back);
    $sheet->getRowDimension($line)->setRowHeight(40);


    $sheet->setCellValue("A$line", Loc::getMessage("CONTACTS_INFO", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($table_contacts);



    $line++;
    $line++;

    $sheet->setCellValue("A$line", Loc::getMessage("CONTACTS_CITY", array(), $lang_id));
    $sheet->getStyle("A$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($table_contacts_info);
    $sheet->mergeCells("B$line:C$line");
    $sheet->setCellValue("B$line", "(044) 221 42 41");
    $sheet->getStyle("B$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("B$line")->applyFromArray($table_contacts_info);


    $line_i = $line-1;
    $sheet->mergeCells("F$line_i:G$line_i");
    $imagePath1=$_SERVER['DOCUMENT_ROOT'] . '/upload/logo_footer_excel.jpg';
    $logo = new PHPExcel_Worksheet_Drawing();
    $logo->setPath($imagePath1);
    $logo->setCoordinates("F$line_i");
    $logo->setOffsetX(0);
    $logo->setOffsetY(0);
    $logo->setWidthAndHeight(143,127);
    $logo->setResizeProportional(false);
    $logo->setWorksheet($sheet);

    $line++;
    $line++;
    $sheet->setCellValue("A$line", "www.vipcatering.com.ua");
    $sheet->getStyle("A$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($table_contacts_info);
    $sheet->mergeCells("B$line:C$line");
    $sheet->setCellValue("B$line", "(067) 327 94 35");
    $sheet->getStyle("B$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("B$line")->applyFromArray($table_contacts_info);
    $line++;
    $line++;

    $sheet->setCellValue("A$line", "info@vipcatering.com.ua");
    $sheet->getStyle("A$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("A$line")->applyFromArray($table_contacts_info);
    $sheet->mergeCells("B$line:C$line");
    $sheet->setCellValue("B$line", "(095) 232 45 00");
    $sheet->getStyle("B$line")->getAlignment()->setIndent(2);
    $xls->getActiveSheet()->getStyle("B$line")->applyFromArray($table_contacts_info);





    $line++;
    $line_blue=$line+3;
    $sheet->mergeCells("A$line_blue:H$line_blue");
    $sheet->setCellValue("A$line_blue", '');

    $xls->getActiveSheet()->getStyle("A$line_blue:H$line_blue")->applyFromArray($sectionFuterStyleHead);
    $sheet
        ->getStyle("A$line_blue:H$line_blue")
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setRGB('0c00d0');
    $sheet->getRowDimension($line_blue)->setRowHeight(20);











    $xls->setActiveSheetIndex(0);


    header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');
    header('Content-transfer-encoding: binary');
    header('Content-Disposition: attachment; filename='.str_replace([',',';',':'],'',rus2translit($menu["NAME"]).'_'.$_GET["lang"]).'.xls');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

// Выводим содержимое файла
    $objWriter = new PHPExcel_Writer_Excel5($xls);
    $objWriter->save('php://output');



endif;

function setMasStyles($sheet, $mas, $line, $style) {
    foreach ( $mas as $val ) {
        $sheet->getStyle($val . $line)->applyFromArray($style);
    }
}

function rus2translit($text)
{
    // Русский алфавит
    $rus_alphabet = array(
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й',
        'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф',
        'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й',
        'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф',
        'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
        ' ', 'і'
    );

    // Английская транслитерация
    $rus_alphabet_translit = array(
        'A', 'B', 'V', 'G', 'D', 'E', 'IO', 'ZH', 'Z', 'I', 'I',
        'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F',
        'H', 'C', 'CH', 'SH', 'SH', '`', 'Y', '`', 'E', 'IU', 'IA',
        'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'i',
        'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f',
        'h', 'c', 'ch', 'sh', 'sh', '`', 'y', '`', 'e', 'iu', 'ia',
        '_', 'i'
    );

    return str_replace($rus_alphabet, $rus_alphabet_translit, $text);
}

function getCat($cat,$arCat){
    if($arCat[$cat]["DEPTH_LEVEL"]==1)
        return $arCat[$cat]["ID"];
    else
        return getCat($arCat[$arCat[$cat]["IBLOCK_SECTION_ID"]]["ID"],$arCat);
    //IBLOCK_SECTION_ID DEPTH_LEVEL
}

/*function getBold($name_ru,$name_en,$lines,$sheet){
    $objRichText = new PHPExcel_RichText();
    $run1 = $objRichText->createTextRun(html_entity_decode($name_ru));
    $run1->getFont()->setBold(true);
    $run2 = $objRichText->createTextRun("\n\n".html_entity_decode($name_en));
    $run2->getFont()->setBold(false);
    $sheet->setCellValue("A$lines", $objRichText);
}*/

function getBold1($name_ru,$name_en,$lines,$sheet){
    /*$objRichText = new PHPExcel_RichText();
    $run1 = $objRichText->createTextRun($name_ru.'        ');
    $run1->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_BLUE ) )->setName('Century Gothic')->setSize(14)->setBold(true);
    $run2 = $objRichText->createTextRun("\n\n".html_entity_decode($name_en));
    $run2->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLACK))->setName('Century Gothic')->setSize(14)->setBold(false);
    $sheet->setCellValue("A$lines", $objRichText);*/
    $sheet->setCellValue("A$lines", $name_ru);
    $sheet->setCellValue("B$lines", $name_en);

}

function getBoldHeader($name_ru,$name_en,$lines,$sheet){

    //$sheet->setCellValue("A$lines", $name_ru.$name_en);

    $objRichText = new PHPExcel_RichText();
    $run1 = $objRichText->createTextRun($name_ru);
    $run1->getFont()->setSize(12)->setBold(true);
    $run2 = $objRichText->createTextRun($name_en);
    $run2->getFont()->setSize(12)->setBold(false);
    $sheet->setCellValue("A$lines", $objRichText);

}


?>