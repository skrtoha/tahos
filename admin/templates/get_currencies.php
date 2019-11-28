<?php
  if ($view) require_once ("../class/database_class.php");
  else  require_once ("/home/skrichev/skrichevsky.in.ua/tahos/class/database_class.php");
  require_once('functions.php');
  $db = new DataBase();
  $date = date("d/m/Y"); // ����������� ���� � ����������� �������
  $link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=$date"; // ������ �� XML-���� � ������� �����
  echo "$link";
  $content = file_get_contents($link); // ��������� ���������� ��������
  // echo "$content";
  $dom = new domDocument("1.0", "cp1251"); // ������ DOM
  $dom->loadXML($content); // ��������� � DOM XML-��������
  $root = $dom->documentElement; // ���� �������� �������
  $childs = $root->childNodes; // �������� ������ �������� ���������
  $data = array('USD', 'EUR', 'JPY', 'CNY', 'UAH'); // ����� ������
  for ($i = 0; $i < $childs->length; $i++) {
    $childs_new = $childs->item($i)->childNodes; // ���� �������� ����
    for ($j = 0; $j < $childs_new->length; $j++) {
      /* ���� ������������ ��� ������ */
      $el = $childs_new->item($j);
      $code = $el->nodeValue;
      if (in_array($code, $data)) $data[] = $childs_new; // ��������� ����������� ������ � ������
    }
  }
  /* ������� ������� � ������� � ������� */
  for ($i = 0; $i < count($data); $i++) {
    $list = $data[$i];
    for ($j = 0; $j < $list->length; $j++) {
      $el = $list->item($j);
      /* ������� ����� ����� */
      if ($el->nodeName == "CharCode") $charcode = $el->nodeValue;
      elseif ($el->nodeName == "Value") $value = $el->nodeValue;
      if ($value and $charcode) $currencies[$charcode] = str_replace(',', '.', $value);
    }
  }
  debug($currencies);
  foreach ($currencies as $charcode => $value){
    switch($charcode){
      case 'UAH': $val = $value/10; break;
      case 'JPY': $val = $value / 100; break;
      default: $val = $value;
    }
    $db->update('currencies', array('rate' => $val), "`charcode`='$charcode'");
  } 
  $db->update('settings', array('currencies_update' => time()), '`id`=1');
  if ($view){
    message('���� ����� ������� ��������!');
    header('Location: ?view=currencies');
  }
?>