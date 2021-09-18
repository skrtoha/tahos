<?php
namespace core;
class UserAddress{
    public static function edit($params){
        $json = is_array($params['json']) ? json_encode($params['json']) : $params['json'];
        if (isset($params['address_id']) && $params['address_id']){
            $GLOBALS['db']->update(
                'user_addresses',
                ['json' => $json],
                "`id` = {$params['address_id']}"
            );
            return $params['address_id'];
        }
        $GLOBALS['db']->insert(
            'user_addresses',
            [
                'user_id' => $params['user_id'],
                'json' => $json
            ]
        );
        return $GLOBALS['db']->last_id();
    }
    
    public static function getHtmlString($address_id, array $data, $isDefault = 0){
        $checked = $isDefault ? 'checked' : '';
        $output = "
            <div class='address' id='{$address_id}'>
                <input $checked type='radio' name='isDefault' value='$address_id'>
        ";
        foreach($data as $row){
            $output .= "<span kladr_id='{$row['kladr_id']}' name='{$row['name']}'>{$row['value']}</span>";
        }
        $output .= '<i class="fa fa-times delete_address" aria-hidden="true"></i>';
        $output .= "</div>";
        return $output;
    }
    
    public static function getString($address_id, array $data){
        $output = "";
        foreach($data as $row){
            $output .= "{$row['value']}, ";
        }
        return substr($output, 0, -2);
    }
}
