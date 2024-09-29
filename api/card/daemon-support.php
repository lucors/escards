<?php 
    function getOpponent($duel_id, $user_id, $strict = true){
        global $link;
        // Получение данных соперника
        $result = $link->query("
            SELECT
                users.user_id, order_value as player_order, json_cards
            FROM
                users
            INNER JOIN
                duel_user_data
            ON
                users.user_id = duel_user_data.user_id
            WHERE
                duel_id = {$duel_id} 
                AND 
                users.user_id <> {$user_id}
        ");
        if (!$link->isResultIterable($result)){
            if ($strict){
                throw new Brake("Ошибка получения данных соперника");
            }
        }
        return $result->fetch_assoc();
    }
    
    function getStep($step_id, $byduel = false){
        global $link;
        $where = "duel_step_id = {$step_id}";
        if ($byduel){
            $where = "duel_id = {$step_id}";
        }
        $result = $link->query("
            SELECT
                duel_step_id, stage, duel_id, order_value, exch_left,
                card_swap0, card_swap1, selected_card, start_dt
            FROM 
                duel_steps
            WHERE
                {$where}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения шага");
        }
        return $result->fetch_assoc();
    }

    function getRandomCards($count){
        if ($count > 52 || $count < 1){
            $count = 12;
        } 
        $dir = __DIR__."/../../assets/img/game/cards/";
        $cards = array();
        foreach (glob($dir."*.png") as $filename) {
            $card = basename($filename, ".png");
            if ($card != "cover"){
                $cards[] = $card;
            }
        }
        shuffle($cards);
        return array_slice($cards, 0, $count);
    }

    function getCards($uid_1, $uid_2 = null){
        global $link;
        $where = "OR user_id = {$uid_2}";
        if (is_null($uid_2)){
            $where = "";
        }
        $result = $link->query("
            SELECT
                order_value, json_cards
            FROM 
                duel_user_data
            WHERE
                user_id = {$uid_1} {$where}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения карт");
        }
        $output = array(0 => null, 1 => null);
        while ($data = $result->fetch_assoc()){
            $output[$data["order_value"]] = $data["json_cards"];
        }
        return $output;
    }
    
    function startDuel($duel_id){
        global $link;
        $result = $link->query("
            SELECT
                duel_id
            FROM 
                users
            WHERE
                duel_id = {$duel_id} AND user_id = {$_SESSION['user_id']}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка проверки нахождения в дуэли");
        }
        $start_dt = date("Y-m-d H:i:s", strtotime("now")+5);
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                order_value     = 1,
                start_dt        = '{$start_dt}',
                stage           = ".STAGE_STEP_SELECT.",
                selected_card   = -1
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка установки очереди шага в ноль");
        }
        $result = $link->query("
            SELECT
                duels.duel_id, users.user_id, duel_user_data.order_value
            FROM
                duels
            INNER JOIN
                users
            ON
                users.duel_id = duels.duel_id
            INNER JOIN
                duel_user_data
            ON
                duel_user_data.user_id = users.user_id
            WHERE
                duels.duel_id = {$duel_id}
            GROUP BY
                duel_id, user_id, order_value
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка поиска оппонента игры");
        }
        $opponent = array();
        while ($data = $result->fetch_assoc()){
            if ($data["user_id"] != $_SESSION['user_id']){
                $opponent = $data;
            }
        }
        $cards = getRandomCards(12);
        $playerCards = json_encode(array_slice($cards, 0, 6));
        $opponent['cards'] = json_encode(array_slice($cards, 6, 6));
        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                json_cards = '{$playerCards}'
            WHERE
                user_id = {$_SESSION['user_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка раздачи карт 1");
        }
        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                json_cards = '{$opponent['cards']}'
            WHERE
                user_id = {$opponent['user_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка раздачи карт 2");
        }
        $result = $link->query("
            UPDATE
                duels
            SET 
                stage = ".STAGE_DUEL_CARDSCREATED."
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка смены состояния дуэли");
        }
        return true;
    }
    
    function clearSwap($duel_id){
        global $link;
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                card_swap0   = NULL,
                card_swap1   = NULL
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния значений шага");
        }
    }
?>