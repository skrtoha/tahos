<?php
use core\Setting;

/* @var $db \core\Database */

if (!empty($_POST)){
    $data = [];
    foreach($_POST as $field => $value){
        foreach($value as $type => $v){
            $data[$type][$field] = $v;
        }
    }
    foreach($data as $type => $value){
        Setting::update('email', $type, json_encode($value));
    }
    $array = $data;
}
else {
    $result = $db->select('settings', '*', "`view` = 'email'");
    $array = [];
    foreach($result as $value){
        $array[$value['name']] = json_decode($value['value'], true);
    }
}
$page_title = "Настройки почты";
$status = "<a href='/admin'>Главная</a> > Администрирование > $page_title";
?>
<form action="" method="post" enctype="multipart/form-data">
    <?foreach($array as $type => $values){?>
        <h3 style="margin-top: 10px"><?=$type?></h3>
        <div class="t_form">
            <div class="bg">
                <div class="field">
                    <div class="title">Email</div>
                    <div class="value">
                        <input required type="text" name="email[<?=$type?>]" value="<?=$values['email']?>">
                    </div>
                </div>
                <div class="field">
                    <div class="title">Имя пользователя</div>
                    <div class="value">
                        <input required type=text name="username[<?=$type?>]" value="<?=$values['username']?>">
                    </div>
                </div>
                <div class="field">
                    <div class="title">Пароль</div>
                    <div class="value">
                        <input required  type="password" name="password[<?=$type?>]" value="<?=$values['password']?>">
                    </div>
                </div>
                <div class="field">
                    <div class="title">SMTP-сервер</div>
                    <div class="value">
                        <input required type=text name="host[<?=$type?>]" value="<?=$values['host']?>">
                    </div>
                </div>
            </div>
        </div>
    <?}?>
    <input style="margin-top: 20px" type="submit" class="button" value="Сохранить">
</form>