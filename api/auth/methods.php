<?php
    // AUTH METHODS   

    // $psList = shell_exec("ps -A");
    // $psCount = count(preg_split("/\n/", $psList)) -2;
    $psCount = 0;

    function escardsLogin($email, $pass){
        global $link, $psCount;

        if ($psCount >= 40){
            throw new Brake("Сервера перегружены, повторите попытку позже", 0, LOG_LVL_ERR);
        }

        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === False){
            throw new Brake("Некорректный формат email");
        }
        
        $output = array();
        if (isset($_SESSION["email"])){
            $output["msg"] = "Вы уже вошли в систему";
        }
        session_unset(); 
        $data = getUserData($email, false);
        // Проверяем совпадения пароля с хешом из БД
        if (password_verify($pass, $data["passhash"])){
            // if ($data["role"] < 1){
            //     throw new Brake("Игра временно недоступна");
            // }
            // Признак авторизации -- наличие аттрибута "email" в супер.глоб. массиве $_SESSION
            $_SESSION["user_id"]  =  $output["user_id"]  = $data["user_id"];
            $_SESSION["name"]     =  $output["name"]     = $data["name"];
            $_SESSION["email"]    =  $output["email"]    = $data["email"];
            $_SESSION["role"]     =  $output["role"]     = $data["role"];
            $_SESSION["duel_id"]  =  $output["duel_id"]  = $data["duel_id"];
            $_SESSION["tourn_id"] =  $output["tourn_id"] = $data["tourn_id"];
            $_SESSION["avatar_path"] = $output["avatar_path"] = $data["avatar_path"];
            Log::msg("ВХОД Пользователь \"{$_SESSION["email"]}\"");
            // updateStageInMenu($email);
        }
        else {
            // throw new Brake("Введен неверный пароль пользователя \"{$email}\"");
            throw new Brake("Некорректные данные");
        }
        return $output;
    }

    //TODO: ИЗМЕНИТЬ МЕХАНИЗМ ВЫХОДА ИЗ АКК.
    // МЕНЯТЬ room_id и stage ???
    function escardsLogout(){
        global $link;

        if (!isset($_SESSION["email"])){
            throw new Brake("Вы не вошли в систему");
        }
        $result = $link->query("
            UPDATE
                users
            SET
                last_dt = '".date('Y-m-d H:i:s')."'
            WHERE
                email = '{$_SESSION["email"]}'
        ");
        if (!$link->isResultValid($result)){
            Log::warning("Не обнов. поле last_dt для пользователя \"{$_SESSION["email"]}\"");
        }
        Log::msg("ВЫХОД Пользователь \"{$_SESSION["email"]}\"");
        session_unset();
    }

    function escardsSignup($email, $pass, $name){
        global $link, $psCount;

        // throw new Brake("Игра временно недоступна");
        if ($psCount >= 40){
            throw new Brake("Сервера перегружены, повторите попытку позже", 0, LOG_LVL_ERR);
        }
        
        if (isset($_SESSION["email"])){
            throw new Brake("Вы уже вошли в систему");
        }
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === False){
            throw new Brake("Некорректный формат email");
        }
        // if ($name[0] == "#"){
        //     throw new Brake("Недопустимый символ \"#\" в никнейме");
        // }
        $passhash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Добавление пользователя
        $result = $link->query("
            INSERT INTO
                users (name, email, passhash, last_dt)
            VALUES 
                ('{$name}', '{$email}', '{$passhash}', '".date('Y-m-d H:i:s')."')
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка регистрации");
        }
        $userID = $link->mysqli->insert_id;

        $output = array();
        try {
            // // УСТАНОВКА ПОЛЬЗОВ. НАСТРОЕК ПО УМОЛЧ.
            // $result = $link->query("
            //     INSERT INTO
            //         users_settings(user_id)
            //     VALUES
            //         ({$userID})
            // ");
            // if (!$link->isResultValid($result)){
            //     throw new Brake("Ошибка первоначальной настройки", 1);
            // }

            // УСТАНОВКА ПОЛЬЗОВ. РЕЙТИНГА ПО УМОЛЧ.
            $result = $link->query("
                INSERT INTO
                    ratings(user_id)
                VALUES
                    ({$userID})
            ");
            if (!$link->isResultValid($result)){
                throw new Brake("Ошибка первоначальной настройки", 1);
            }
            
            $_SESSION["user_id"]  =  $output["user_id"]  = $userID;
            $_SESSION["name"]     =  $output["name"]     = $name;
            $_SESSION["email"]    =  $output["email"]    = $email;
            $_SESSION["role"]     =  $output["role"]     = 0;
            $_SESSION["duel_id"]  =  $output["duel_id"]  = -1;
            $_SESSION["tourn_id"] =  $output["tourn_id"] = -1;
            $_SESSION["avatar_path"] = $output["avatar_path"] = Common::getCorrectAvatarPath($userID);
            // updateStageInMenu($email);
            Log::msg("СОЗДАН Пользователь \"{$_SESSION["email"]}\"");
        }
        catch (Brake $e){
            // Требуется откат изменений
            if ($e->getCode() == 1){
                $result = $link->query("
                    DELETE FROM
                        users
                    WHERE 
                        user_id = {$userID}
                ");
                if (!$link->isResultValid($result)){
                    Log::error("Ошибка удаления невалидного пользователя \"{$email}\"");
                }
            }
            throw $e;
        }
        return $output;
    }

?>