<?php
    // AUTH REQUEST HANDLER
    require_once("handler_base.php");
    // inherits user's methods
    require_once("user/methods.php");
    require_once("methods.php");
    
    switch ($request["op"]) {
        case "login":
            if (!isset($request["email"]) || !isset($request["pass"])){
                throw new Brake("Введите email и пароль");
            }
            $response["user_data"] = escardsLogin(
                $request["email"],
                $request["pass"]
            );
            if (key_exists("msg", $response["user_data"])){
                $response["msg"] = $response["user_data"]["msg"];
                unset($response["user_data"]["msg"]);
            }
            break;
        case "signup":
            if (!isset($request["email"]) 
                || !isset($request["pass"]) 
                || !isset($request["name"])){
                throw new Brake("Введите email, пароль и name");
            }
            $response["user_data"] = escardsSignup(
                $request["email"],
                $request["pass"],
                $request["name"]
            );
            break;
        case "logout":
            escardsLogout();
            break;
        default:
            $response["msg"] = "Запрошена неизвестная операция OP";
            $response["result"] = False;
            break;
    }
?>