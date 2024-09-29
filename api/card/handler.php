<?php
    // CARDS REQUEST HANDLER
    require_once("handler_base.php");
    require_once("daemon-support.php");
    require_once("methods.php");
    
    switch ($request["op"]) {
        case "get_self":
            $output = getSelfCards($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"]);
            $response["self_cards"]     = $output["self_cards"];
            $response["opp_fake_cards"] = $output["opp_fake_cards"];
            break;
        case "get_opp":
            $response["opp_cards"] = getOppCards($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"]);
            // $response["self_cards"] = $output["self_cards"];
            // $response["opp_cards"]  = $output["opp_cards"];
            break;
        case "swap":
            if (!isset($request["p0"])){
                throw new Brake("Не задан параметр p0");
            }
            if (!isset($request["p1"])){
                throw new Brake("Не задан параметр p1");
            }
            swapCards($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"], $request["p0"], $request["p1"]);
            break;
        case "get_win_reason":
            // require_once("victory-handler.php");
            $response["win_reason"] = getWinReason($_SESSION["duel_id"]);
            break;
        case "select":
            if (!isset($request["p0"])){
                throw new Brake("Не задан параметр p0");
            }
            selectCard($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"], $request["p0"]);
            break;
        case "pull":
            if (!isset($request["p0"])){
                throw new Brake("Не задан параметр p0");
            }
            $response["pull_data"] = pullCard($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"], $request["p0"]);
            break;
        case "pass":
            passPhase($_SESSION["user_id"], $_SESSION["player_order"], $_SESSION["duel_id"]);
            break;
        default:
            $response["msg"] = "Запрошена неизвестная операция OP";
            $response["result"] = False;
            break;
    }
?>