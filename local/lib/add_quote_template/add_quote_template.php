<?

/*Подключение файла .js с кодом вывода предложений и шаблонов!*/
$arJsConfig = array(
    'addQuoteTemplWhenCreateNew' => array(
        'js' => '/local/lib/add_quote_template/js/add_quote_templ.js',
        'css' => '/local/lib/add_quote_template/css/add_quote_templ.css',
    )
);

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

//Вызов библиотеки
CUtil::InitJSCore(array('addQuoteTemplWhenCreateNew'));