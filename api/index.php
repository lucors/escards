<?php
    // ESCARDS API
    if (!isset($_SESSION)){
        session_start();
    }

    require_once("common.php");
    require_once("log.php");
    require_once("db.php");

    // Переменная ответа на запрос
    $response = array(
        "result" => False
    ); 

    try {
        // Получение метода общения с клиентом
        $method = Common::getInputMethod();
        if ($method == INPUT_POST){
            if (isset($_POST["formData"])){
                $request =& $_POST;
            }
            else {
                $rawRequest = file_get_contents('php://input');
                $request = json_decode(urldecode($rawRequest), True);
            }
        }
        else {
            $request =& $_GET;
        }

        if (is_null($request)){
            throw new Brake("Пустой запрос");
        }
        if (!isset($request["route"])){
            throw new Brake("Не определен путь запроса (route)"); 
        }

        // Проверяем что пользователь авторизован 
        if (($request["route"] != "auth" && $request["route"] != "debug") &&
            (!isset($_SESSION["email"]) || !isset($_SESSION["user_id"]))){
            throw new Brake("Пользователь не авторизирован", -1);
        }
        // Подключение к БД (connect.php -> mysqli) 
        $link = new DBConnection(Common::$dbHost, Common::$dbUser, Common::$dbPass, Common::$dbName);
        if (is_null($link->mysqli)){
            throw new Brake("Ошибка подключения к БД");
        }

        // Подключаем обработчик по запрашиваемому маршруту
        $routeHandler = "{$request['route']}/handler.php";
        if (!file_exists($routeHandler)){
            throw new Brake("Неверный путь запроса (route)");
        }
        require_once($routeHandler);
        $link->disconnect();
    }
    catch (Brake $b){
        if($b->loglvl != LOG_LVL_NONE){
            Log::auto($b, $b->loglvl);
            // Log::auto($e->getMessage(), $e->getCode());
        }
        $response["result"] = False;
        $response["msg"]    = $b->getMessage();
        $response["code"]   = $b->getCode();
    }
    catch (Exception $e){
        $response["result"] = False;
        $response["msg"]    = "[НЕВЕРНЫЙ ОБРАБОТЧИК ИСКЛЮЧЕНИЯ]".$b->getMessage();
    }

    header('Content-type: application/json');
    echo json_encode($response);
?>