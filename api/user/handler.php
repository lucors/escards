<?php
    // USER REQUEST HANDLER
    require_once("handler_base.php");
    require_once("methods.php");
    
    switch ($request["op"]) {
        // case "getby_query":
        //     if (!isset($request["query"])){
        //         throw new Brake("Пустой запрос", -1);
        //     }
        //     $response["users_list"] = getUserByQuery($request["query"]);
        //     if (empty($response["users_list"])){
        //         $response["msg"] = "Не найдено";
        //     }
        //     break;
        // case "getby_room":
        //     if (!isset($request["room_id"])){
        //         throw new Brake("Не задан параметр ROOM_ID (room_id)");
        //     }
        //     $response["users_list"] = getRoomUsers($request["room_id"]);
        //     break;
        // case "getsome":
        //     if (!isset($request["list"])){
        //         throw new Brake("Не задан параметр USERS_ID_LIST (list)");
        //     }
        //     $response["users_list"] = getSomeUsers($request["list"]);
        //     break;
        // case "getsome":
        //     if (!isset($request["list"])){
        //         throw new Brake("Не задан параметр USERS_ID_LIST (list)");
        //     }
        //     $response["users_list"] = getSomeUsers($request["list"]);
        //     break;
        default:
            $response["msg"] = "Запрошена неизвестная операция OP";
            $response["result"] = False;
            break;
    }
?>