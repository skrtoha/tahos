<?
/* @var int $user_id */
/* @var Database $db */
/* @var string $form */

use core\Database;

?>
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
                    <input type="button" value="Добавить">
                </div>
                <div id="tooltip" style="display: none;"><b></b><span></span></div>
            </form>
        </div>
        <div class="right"></div>
        <button title="Close (Esc)" type="button" class="bt_close">×</button>
    </div>
</div>
<? if ($user_id){
    $userAddresses = [];
    $resultUserAddresses = $db->select(
        'user_addresses',
        '*',
        "`user_id` = {$user_id}",
        'created'
    );
    if (!empty($resultUserAddresses)){
        foreach($resultUserAddresses as $value){
            $userAddresses[] = [
                'id' => $value['id'],
                'is_default' => $value['is_default'],
                'data' => json_decode($value['json'], true)
            ];
        }
    }
}
?>
<script src="/vendor/addressee/jquery.fias.min.js"></script>
<script src="/vendor/addressee/script.js"></script>
<script>
    const form = '<?=$form?>';
    $(function(){
        const userAddresses = JSON.parse('<?=json_encode($userAddresses)?>');
        if (userAddresses !== null){
            $.each(userAddresses, (i, item) => {
                $('#set-address-wrapper .right').append(
                    getHtml(item.data, item.id, +item.is_default)
                );
            })
        }
    })
</script>