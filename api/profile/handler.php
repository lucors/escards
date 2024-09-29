<?php
    // PROFILE REQUEST HANDLER
    require_once("handler_base.php");
    // inherits user's methods
    require_once("user/methods.php");
    require_once("methods.php");

    switch ($request["op"]) {
        case "get_self":
            $response["user_data"] = getUserData($_SESSION["user_id"]);
            break;        
        case "get_rating":
            $response["rating_data"] = getUserRating($_SESSION["user_id"]);
            break;
        // case "get_friends":
        //     $response["friends_list"] = getAllUserFriends($_SESSION["user_id"]);
        //     if (empty($response["friends_list"])){
        //         $response["msg"] = "Друзья не найдены";
        //     }
        //     break;
        case "get_role":
            $response["user_role"] = getUserRole($_SESSION["user_id"]);
            break;
        // case "get_avatar":
        //     $response["avatar_path"] = getUserAvatar($_SESSION["user_id"]);
        //     break;
        // case "get_settings":
        //     $response["settings_data"] = getProfileSettings();
        //     break;
        // case "set_settings":
        //     if (!isset($request["settings_data"])){
        //         throw new Brake("Заполните поле данных настроек (settings_data)");
        //     }
        //     setProfileSettings($request["settings_data"]);
        //     break;
        // case "setdefault_settings":
        //     setProfileSettingsDefault();
        //     break;
        case "set_avatar":
            $response["avatar_path"] = setAvatar($_SESSION["user_id"]);
            break;
        case "set_name":
            if (!isset($request["name"])){
                throw new Brake("Не задан параметр USER_NAME (name)");
            }
            setUserName($_SESSION["user_id"], $request["name"]);
            break;
        case "set_pass":
            if (!isset($request["old_pass"])){
                throw new Brake("Не задан параметр OLD_PASS (old_pass)");
            }
            if (!isset($request["pass"])){
                throw new Brake("Не задан параметр NEW_PASS (passs)");
            }
            setUserPass($_SESSION["user_id"],$request["old_pass"], $request["pass"]);
            break;
        default:
            $response["msg"] = "Запрошена неизвестная операция OP";
            $response["result"] = False;
            break;
    }
?>