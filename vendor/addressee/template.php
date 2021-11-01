<div id="set-address" class="popup">
    <div id="set-address-wrapper" >
        <div id="address_multiple_fields" class="left">
            <input type="hidden" name="address_id">
            <form class="js-form-address">
                <div class="flex">
                    <div class="half">
                        <div class="input">
                            <label>Индекс</label>
                            <input autocomplete="off" type="text" name="zip" placeholder="Будет определен автоматически">
                        </div>
                        <div class="input">
                            <label>Область</label>
                            <input autocomplete="off" type="text" name="region">
                        </div>
                        <div class="input">
                            <label>Регион / район</label>
                            <input autocomplete="off" type="text" name="district">
                        </div>
                    </div>
                    <div class="half">
                        <div class="input">
                            <label>Город / населенный пункт</label>
                            <input autocomplete="off" type="text" name="city">
                        </div>
                        <div class="input">
                            <label>Улица</label>
                            <input autocomplete="off" type="text" name="street">
                        </div>
                        <div class="input input_litl">
                            <label>Дом</label>
                            <input autocomplete="off" type="text" name="building">
                        </div>
                    </div>
                </div>
                <div class="input">
                    <input type="button" value="Сохранить">
                </div>
                <div id="tooltip" style="display: none;"><b></b><span></span></div>
            </form>
        </div>
        <div class="right">
            <?$userAddresses = $db->select(
                'user_addresses',
                '*',
                "`user_id` = {$_SESSION['user']}",
                'created'
            );
            if (!empty($userAddresses)){
                foreach($userAddresses as $row){
                    $data = json_decode($row['json'], true);?>
                    <?=\core\UserAddress::getHtmlString($row['id'], $data, $row['is_default'])?>
                <?}
            }?>
        </div>
        <button title="Close (Esc)" type="button" class="bt_close">×</button>
    </div>
</div>