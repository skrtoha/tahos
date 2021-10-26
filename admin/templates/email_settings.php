<?php
use core\Setting;

if (!empty($_POST)){
    Setting::update('email', $_POST['email']);
    Setting::update('username', $_POST['username']);
    Setting::update('password', $_POST['password']);
    Setting::update('host', $_POST['host']);
    $array = $_POST;
}
else {
    $array['email'] = Setting::get('email');
    $array['username'] = Setting::get('username');
    $array['password'] = Setting::get('password');
    $array['host'] = Setting::get('host');
}
$page_title = "Настройки почты";
$status = "<a href='/admin'>Главная</a> > Администрирование > $page_title";
?>
<div class="t_form">
    <div class="bg">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="field">
                <div class="title">Email</div>
                <div class="value">
                    <input required type=text name="email" value="<?=$array['email']?>">
                </div>
            </div>
            <div class="field">
                <div class="title">Имя пользователя</div>
                <div class="value">
                    <input required type=text name="username" value="<?=$array['username']?>">
                </div>
            </div>
            <div class="field">
                <div class="title">Пароль</div>
                <div class="value">
                    <input required  type="password" name="password" value="<?=$array['password']?>">
                </div>
            </div>
            <div class="field">
                <div class="title">SMTP-сервер</div>
                <div class="value">
                    <input required type=text name="host" value="<?=$array['host']?>">
                </div>
            </div>
            <div class="field">
                <div class="title"></div>
                <div class="value"><input type="submit" class="button" value="Сохранить"></div>
            </div>
        </form>
    </div>
</div>
