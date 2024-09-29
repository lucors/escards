<?php
    // USER METHODS   


    function getUserData($field, $byuid = true){
        global $link;
        $whereStatement = "user_id = {$field}";
        if (!$byuid){
            $whereStatement = "email = '{$field}'";
        }
        $result = $link->query("
            SELECT
                user_id, name, email, passhash, role, duel_id, tourn_id
            FROM
                users
            WHERE
                $whereStatement
            LIMIT 1
        ");
        // Если результат запроса провалился
        if (!$link->isResultIterable($result)){
            throw new Brake("Не найден пользователь \"{$field}\"");
        }
        $user = $result->fetch_assoc();
        $user["avatar_path"] = Common::getCorrectAvatarPath($user["user_id"]);
        return $user;
    }

    function updateUserDT($user_id){
        global $link;
        $result = $link->query("
            UPDATE
                users
            SET
                last_dt = '".date('Y-m-d H:i:s')."'
            WHERE
                user_id = {$user_id}
        ");
        if (!$link->isResultValid($result)){
            Log::warning("Не обнов. поле last_dt для пользователя \"{$_SESSION["email"]}\"");
            return false;
        }
        return true;
    }

    function setAvatar($user_id){
        $files = $_FILES;
        $done_files = array();
        $uploaddir = __DIR__."/../../"."assets/img/avatars/";

        $file_name = "{$user_id}.png";
        $ext = pathinfo($files[0]["name"], PATHINFO_EXTENSION);
        if ($ext != "png"){
            throw new Brake("Некорректное расширение файла.<br> Поддерживается только PNG", 0, LOG_LVL_MSG);
        }

        if(move_uploaded_file($files[0]['tmp_name'], "{$uploaddir}{$file_name}" ) ){
            $done_files[] = realpath("{$uploaddir}{$file_name}");
        }
        if (count($done_files) === 0){
            throw new Brake("Ошибка смены аватара");
        }

        $img = imagecreatefrompng($done_files[0]);
        $imgResized = imagescale($img , 300, 300);
        imagepng($imgResized, $done_files[0]);

        return Common::getCorrectAvatarPath($user_id);
    }


    function getUserRole($userID){
        global $link;
        $result = $link->query("
            SELECT
                role
            FROM
                users
            WHERE
                user_id = {$userID}
            LIMIT 1
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения роли пользователя");
        }
        return $result->fetch_assoc()["role"];
    }

    function getUserRating($userID){
        global $link;
        $result = $link->query("
            SELECT
                total, won
            FROM
                ratings
            WHERE
                user_id = {$userID}
            LIMIT 1
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения статистики пользователя");
        }
        return $result->fetch_assoc();
    }

    function setUserName($userID, $name){
        global $link;
        $result = $link->query("
            UPDATE
                users
            SET 
                name = '{$name}'
            WHERE
                user_id = {$userID}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка смены имени");
        }
        if ($_SESSION["user_id"] == $userID){
            $_SESSION["name"] = $name;
        }
    }

    function setUserPass($userID, $old_pass, $pass){
        global $link;
        if (empty($old_pass)){
            throw new Brake("Старый пароль пуст");
        }
        if (empty($pass)){
            throw new Brake("Новый пароль пуст");
        }
        $result = $link->query("
            SELECT
                passhash
            FROM
                users
            WHERE
                user_id = {$userID}
            LIMIT 1
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения старого пароля");
        }
        $passhash = $result->fetch_assoc()["passhash"];
        
        if (!password_verify($old_pass, $passhash)){
            throw new Brake("Неверный старый пароль");
        }
        $passhash = password_hash($pass, PASSWORD_DEFAULT);

        $result = $link->query("
            UPDATE
                users
            SET 
                passhash = '{$passhash}'
            WHERE
                user_id = {$userID}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка смены пароля");
        }
    }
?>