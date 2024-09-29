<?php
    // ROOM REQUEST HANDLER
    require_once("handler_base.php");
    require_once("methods.php");
    
    switch ($request["op"]) {
        case "create":
            $response["duel_data"] = createDuel();
            break;
        case "get":
            if (!isset($request["duel_id"])){
                throw new Brake("Не задан параметр DUEL_ID (duel_id)");
            }
            $response["duel_data"] = getDuel($request["duel_id"]);
            $duel_user_data = getDuelUserData($_SESSION['user_id']);
            $response["duel_data"]["duel_data_id"] = $duel_user_data["duel_user_data_id"];
            $response["duel_data"]["player_order"] = $duel_user_data["order_value"];
            break;
        case "enter":
            if (!isset($request["duel_id"])){
                throw new Brake("Не задан параметр DUEL_ID (duel_id)");
            }
            $duel_data = getDuel($request["duel_id"]);
            $duel_data["duel_data_id"] = enterDuel($request["duel_id"]);
            $response["duel_data"] = $duel_data;
            break;
        case "exit":
            exitDuel();
            break;
        case "find":
            $response["duel_data"] = findDuel();
            break;
        case "get_opponent":
            require_once("user/methods.php");
            $response["user_data"] = getOpponent($_SESSION['user_id']);
            break;
        case "get_players":
            if (!isset($request["duel_id"])){
                throw new Brake("Не задан параметр DUEL_ID (duel_id)");
            }
            $response["duel_players"] = getPlayers($request["duel_id"]);
            break;
        case "get_duel_user_data":
            $response["duel_user_data"] = getDuelUserData($_SESSION['user_id']);
            break;
        case "cards_done":
            cardsDone($_SESSION['user_id']);
            break;

            //now in duel-listen.php 
        // case "start":
        //     if (!isset($request["duel_id"])){
        //         throw new Brake("Не задан параметр DUEL_ID (duel_id)");
        //     }
        //     startDuel($request["duel_id"]);
        //     break;
        // case "test":
        //     $response["test"] = getRandomCards(12);
        //     $response["test1"]=json_encode(array_slice($response["test"], 0, 6));
        //     $response["test2"]=json_encode(array_slice($response["test"], 6, 6));
        //     break;
        default:
            $response["msg"] = "Запрошена неизвестная операция OP";
            $response["result"] = False;
            break;
    }
?>