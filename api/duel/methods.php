<?php
    // ROOM METHODS   

    function getDuel($duel_id){
        global $link;

        // Проверяем что у игрока уже есть пользовательские данные дуэли
        $result = $link->query("
            SELECT
                duel_id, stage, step_time, creation_dt
            FROM 
                duels
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Дуэль не найдена");
        }
        return $result->fetch_assoc();
    }

    function enterDuel($duel_id, $duelReady = false){
        global $link;

        try {
            $player_order = 0;

            // Задаем игроку очередность шага 
            $result = $link->query("
                SELECT
                    order_value
                FROM 
                    duel_steps
                WHERE
                    duel_id = {$duel_id}
            ");
            if (!$link->isResultIterable($result)){
                throw new Brake("Ошибка получения очереди шага");
            }
            $player_order = $result->fetch_assoc()["order_value"];
            $stepOrder = $player_order + 1;
            $result = $link->query("
                UPDATE
                    duel_steps
                SET 
                    order_value = {$stepOrder}
                WHERE
                    duel_id = {$duel_id}
            ");
            if (!$link->isResultValid($result)){
                throw new Brake("Ошибка обновления очереди шага");
            }

            // Проверяем что у игрока уже есть пользовательские данные дуэли
            $result = $link->query("
                SELECT
                    duel_user_data_id
                FROM 
                    duel_user_data
                WHERE
                    user_id = {$_SESSION['user_id']}
            ");
            if ($link->isResultIterable($result)){
                $data = $result->fetch_assoc();
                $duel_data_id = $data["duel_user_data_id"];

                $result = $link->query("
                    UPDATE
                        duel_user_data
                    SET 
                        order_value = {$player_order},
                        json_cards = null
                    WHERE
                        duel_user_data_id = {$duel_data_id}
                ");
                if (!$link->isResultValid($result)){
                    throw new Brake("Ошибка добавления игрока в дуэль");
                }
            }
            else {
                // Создаем запись в пользовательских данных дуэли 
                $result = $link->query("
                    INSERT INTO
                        duel_user_data (user_id, order_value)
                    VALUES 
                        ({$_SESSION['user_id']}, {$player_order})
                ");
                if (is_null($result)){
                    throw new Brake("Ошибка создания польз. данных дуэли");
                }
                $duel_data_id = $link->mysqli->insert_id;
            }

            $result = $link->query("
                UPDATE
                    users
                SET 
                    duel_id = {$duel_id}
                WHERE
                    user_id = {$_SESSION['user_id']}
            ");
            if (!$link->isResultValid($result)){
                throw new Brake("Ошибка добавления игрока в дуэль", 1);
            }

            if ($duelReady){
                $result = $link->query("
                    UPDATE
                        duels
                    SET 
                        stage = ".STAGE_DUEL_PLAYERS_READY."
                    WHERE
                        duel_id = {$duel_id}
                ");
                if (!$link->isResultValid($result)){
                    throw new Brake("Ошибка изменения состояния дуэли", 2);
                }
            }

            $_SESSION["duel_id"] = $duel_id;
            $_SESSION["player_order"] = $player_order;
            return array($duel_data_id, $player_order);
        }
        catch (Brake $e) {
            if ($e->getCode() > 0){
                $link->query("
                    DELETE FROM
                        duel_user_data
                    WHERE
                        duel_user_data_id = {$duel_data_id}
                ");
                if ($e->getCode() > 1){
                    $link->query("
                        UPDATE
                            users
                        SET 
                            duel_id = null
                        WHERE
                            user_id = {$_SESSION['user_id']}
                    ");
                }
            }
            throw $e;
        }
    }

    function createDuel(){
        global $link;

        try {
            // Проверяем что игрок еще не в игре
            $result = $link->query("
                SELECT
                    duel_id, tourn_id
                FROM 
                    users
                WHERE
                    user_id = {$_SESSION['user_id']}
            ");
            if ($link->isResultIterable($result)){
                $data = $result->fetch_assoc();
                if (!is_null($data["duel_id"]) || !is_null($data["tourn_id"])){
                    // throw new Brake("Вы уже в комнате. Обновите страницу");
                    header("Refresh:0");
                    exit;
                }
            }

            // Создаем запись в таблице дуэлей
            $result = $link->query("
                INSERT INTO
                    duels (stage, creation_dt)
                VALUES 
                    (".STAGE_DUEL_CREATED.", '".date('Y-m-d H:i:s')."')
            ");
            if (is_null($result)){
                throw new Brake("Ошибка создания дуэли");
            }
            $duel_id = $link->mysqli->insert_id;

            // Создаем запись в таблице шагов дуэлей
            $result = $link->query("
                INSERT INTO
                    duel_steps (stage, duel_id)
                VALUES 
                    (".STAGE_STEP_CREATED.", {$duel_id})
            ");
            if (is_null($result)){
                throw new Brake("Ошибка создания шага дуэли", 1);
            }
            $duel_user_data = enterDuel($duel_id);

            $duel_data =  array(
                "duel_id"     => $duel_id,
                "stage"       => 0,
                "step_time"   => 30,
                "duel_data_id" => $duel_user_data[0],
                "player_order" => $duel_user_data[1]
            );
            Log::msg("Создана дуэль: {
                duel_id:    {$duel_id}, 
                creator_id: {$_SESSION['user_id']}, 
                duel_data_id: {$duel_user_data[0]},
                player_order: {$duel_user_data[1]}
            }");
            return $duel_data;
        }
        catch (Brake $e) {
            if ($e->getCode() > 0){
                $link->query("
                    DELETE FROM
                        duels
                    WHERE
                        duel_id = {$duel_id}
                ");
            }
            throw $e;
        }
    }


    function findDuel(){
        global $link;

        // Проверяем что игрок еще не в игре
        $result = $link->query("
            SELECT
                duel_id, tourn_id
            FROM 
                users
            WHERE
                user_id = {$_SESSION['user_id']}
        ");
        if ($link->isResultIterable($result)){
            $data = $result->fetch_assoc();
            if (!is_null($data["duel_id"]) || !is_null($data["tourn_id"])){
                //У нас AJAX, это не сработает 
                // header("Refresh:0");
                // exit;

                $result = $link->query("
                    SELECT
                        duel_user_data_id, order_value
                    FROM
                        duel_user_data
                    WHERE
                        user_id = {$_SESSION['user_id']}
                ");
                if (!$link->isResultIterable($result)){
                    exit;
                }

                $duel_user_data = $result->fetch_assoc(); 
                Log::msg("Подключение к дуэли duel_id:{$data['duel_id']} uid:{$_SESSION['user_id']}");
                $duel_data = getDuel($data["duel_id"]);
                $_SESSION["duel_id"] = $data["duel_id"];
                $duel_data["duel_data_id"] = $duel_user_data["duel_user_data_id"];
                $duel_data["player_order"] = $duel_user_data["order_value"];
                $_SESSION["player_order"] = $duel_user_data["order_value"];
                return $duel_data;
            }
        }

        // Проверяем наличие доступных комнат
        $result = $link->query("
            SELECT
                duels.duel_id, COUNT(*) as playes_num, duels.creation_dt
            FROM
                duels
            INNER JOIN
                users
            ON
                users.duel_id = duels.duel_id
            WHERE
                duels.stage = ".STAGE_DUEL_CREATED."
            GROUP BY
                duel_id
            ORDER BY playes_num ASC, duels.creation_dt ASC
        ");
        if ($link->isResultIterable($result)){
            while ($data = $result->fetch_assoc()){
                if($data["playes_num"] < 2){
                    Log::msg("Подключение к дуэли duel_id:{$data['duel_id']} uid:{$_SESSION['user_id']}");
                    $duelReady = $data["playes_num"] > 0;
                    $duel_data = getDuel($data["duel_id"]);
                    
                    $duel_user_data = enterDuel($data["duel_id"], $duelReady);
                    $duel_data["duel_data_id"] = $duel_user_data[0];
                    $duel_data["player_order"] = $duel_user_data[1];
                    return $duel_data;
                } 
            }
        }
        return createDuel();
    }

    function exitDuel(){
        global $link;

        $duel_id = $_SESSION["duel_id"];
        $_SESSION["duel_id"] = -1;

        // Выставить duel_user_data в default
        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                order_value = DEFAULT(order_value),
                json_cards  = DEFAULT(json_cards)
            WHERE
                user_id = {$_SESSION['user_id']}
        ");
        if (!$link->isResultValid($result)){
            Log::warning("Ошибка обновления duel_user_data");
        }


        $result = $link->query("
            SELECT
                duels.duel_id, COUNT(*) as playes_num, 
                duels.creation_dt, duels.stage
            FROM
                duels
            INNER JOIN
                users
            ON
                users.duel_id = duels.duel_id
            WHERE
                duels.duel_id = {$duel_id}
            GROUP BY
                duel_id
        ");
        if ($link->isResultIterable($result)){
            $data = $result->fetch_assoc();
            if ($data["playes_num"] < 2 || $data["stage"] >= STAGE_DUEL_WIN0){
                $result = $link->query("
                    DELETE FROM
                        duels
                    WHERE
                        duels.duel_id = {$duel_id}
                ");
                if (!$link->isResultValid($result)){
                    Log::msg("Ошибка удаления дуэли duel_id:{$duel_id}");
                }
                else {
                    Log::msg("Удалена дуэль duel_id:{$duel_id}");
                }
            }
            else {
                $result = $link->query("
                    UPDATE
                        duels
                    SET 
                        stage = ".STAGE_DUEL_DEAD."
                    WHERE
                        duel_id = {$duel_id}
                ");
                if (!$link->isResultValid($result)){
                    throw new Brake("Ошибка смерти дуэли");
                }
            }
        }

        // Для ситуации когда выходит первый игрок
        $result = $link->query("
            UPDATE
                users
            SET 
                duel_id = null
            WHERE
                user_id = {$_SESSION['user_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка обновления поля duel_id");
        }
        Log::msg("Игрок uid:{$_SESSION['user_id']} покинул duel_id:{$duel_id}");
    }




    function getPlayers($duel_id){
        global $link;

        $result = $link->query("
            SELECT
                user_id, name, order_value
            FROM 
                users
            INNER JOIN 
                duel_user_data
            ON
                duel_user_data.user_id = users.user_id
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения игроков дуэли");
        }
        $players = array();
        while ($data = $result->fetch_assoc()){
            $players[] = $data;
        }
        return $players;
    }


    function getOpponent($user_id){
        global $link;

        $duel_id = getUserData($user_id)["duel_id"];

        $result = $link->query("
            SELECT
                user_id
            FROM 
                users
            WHERE
                duel_id = {$duel_id} AND user_id <> {$user_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения данных соперника");
        }
        $opponent_id = $result->fetch_assoc()["user_id"];

        return getUserData($opponent_id);
    }
    
    function getDuelUserData($user_id){
        global $link;

        $result = $link->query("
            SELECT
                duel_user_data.duel_user_data_id, order_value, json_cards, users.duel_id
            FROM 
                duel_user_data
            INNER JOIN 
                users
            ON
                duel_user_data.user_id = users.user_id
            WHERE
                users.user_id = {$user_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения польз. данных дуэли");
        }
        return $data = $result->fetch_assoc();
    }

    function cardsDone($user_id){
        global $link;

        $user_data = getDuelUserData($user_id);
        $duel = getDuel($user_data["duel_id"]);
        $setStage = null;

        if ($user_data["order_value"] == 0){
            if ($duel["stage"] == STAGE_DUEL_CARDSCREATED){
                $setStage = STAGE_DUEL_CARDS0DONE;
            }
            elseif ($duel["stage"] == STAGE_DUEL_CARDS1DONE) {
                $setStage = STAGE_DUEL_ROUND1;
            }
        }
        else {
            if ($duel["stage"] == STAGE_DUEL_CARDSCREATED){
                $setStage = STAGE_DUEL_CARDS1DONE;
            }
            elseif ($duel["stage"] == STAGE_DUEL_CARDS0DONE) {
                $setStage = STAGE_DUEL_ROUND1;
            }
        }
        if (is_null($setStage)) return;
        $result = $link->query("
            UPDATE
                duels
            SET 
                stage = {$setStage}
            WHERE
                duel_id = {$user_data['duel_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка смена состояния дуэли");
        }
    }
?>