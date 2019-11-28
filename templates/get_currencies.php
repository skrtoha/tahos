<?php
  if ($view) require_once ("../class/database_class.php");
  else  require_once ("/home/skrichev/skrichevsky.in.ua/tahos/class/database_class.php");
  require_once('functions.php');
  $db = new DataBase();
  $date = date("d/m/Y"); // Сегодняшняя дата в необходимом формате
  $link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=$date"; // Ссылка на XML-файл с курсами валют
  $content = file_get_contents($link); // Скачиваем содержимое страницы
  $dom = new domDocument("1.0", "cp1251"); // Создаём DOM
  $dom->loadXML($content); // Загружаем в DOM XML-документ
  $root = $dom->documentElement; // Берём корневой элемент
  $childs = $root->childNodes; // Получаем список дочерних элементов
  $data = array('USD', 'EUR', 'JPY', 'CNY', 'UAH'); // Набор данных
  for ($i = 0; $i < $childs->length; $i++) {
    $childs_new = $childs->item($i)->childNodes; // Берём дочерние узлы
    for ($j = 0; $j < $childs_new->length; $j++) {
      /* Ищем интересующие нас валюты */
      $el = $childs_new->item($j);
      $code = $el->nodeValue;
      if (in_array($code, $data)) $data[] = $childs_new; // Добавляем необходимые валюты в массив
    }
  }
  /* Перебор массива с данными о валютах */
  for ($i = 0; $i < count($data); $i++) {
    $list = $data[$i];
    for ($j = 0; $j < $list->length; $j++) {
      $el = $list->item($j);
      /* Выводим курсы валют */
      if ($el->nodeName == "CharCode") $charcode = $el->nodeValue;
      elseif ($el->nodeName == "Value") $value = $el->nodeValue;
      if ($value and $charcode) $currencies[$charcode] = str_replace(',', '.', $value);
    }
  }
  foreach ($currencies as $charcode => $value){
    $val = $charcode == 'UAH' ? $value/10 : $value;
    $db->update('currencies', array('rate' => $val), "`charcode`='$charcode'");
  } 
  $db->update('settings', array('currencies_update' => time()), '`id`=1');
  if ($view){
    message('Курс валют успешно обновлен!');
    header('Location: ?view=currencies');
  }
?>