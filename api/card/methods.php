<?php 

    function getSelfCards($user_id, $player_order, $duel_id){
        $opponent   = getOpponent($duel_id, $user_id);
        $cards      = getCards($user_id, $opponent["user_id"]);
        $opp_cards  = json_decode($cards[$opponent['player_order']], true); 
        $output = array(
            "self_cards"     => $cards[$player_order],
            "opp_fake_cards" => array()
        );

        for($i = 0; $i < count($opp_cards); $i++) {
            if (is_null($opp_cards[$i])){
                $output["opp_fake_cards"][] = null;
            }
            else {
                $output["opp_fake_cards"][] = "cover";
            }
        }
        if (count($opp_cards) < 7){
            $output["opp_fake_cards"][] = null;
        }
        return $output;
    }
    function getOppCards($user_id, $player_order, $duel_id){
        global $link;
        $result = $link->query("
            SELECT
                stage
            FROM 
                duels
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения состояния дуэли");
        }
        $duel_stage = $result->fetch_assoc()["stage"];
        if ($duel_stage < STAGE_DUEL_FINISH){
            throw new Brake("Ошибка получения карт. Еще не конец игры");
        }
        $opponent   = getOpponent($duel_id, $user_id);
        $cards      = getCards($user_id, $opponent["user_id"]);
        return $cards[$opponent['player_order']];
    }



    function checkOrder($duel_id, $player_order){
        $step = getStep($duel_id, true);
        if ($player_order != $step["order_value"]){
            throw new Brake("Ошибка пропуска. Не ваш ход");
        }
        return $step;
    }
    function passPhase($user_id, $player_order, $duel_id){
        global $link;
        $step = checkOrder($duel_id, $player_order);
        if ($step["stage"] != STAGE_STEP_PROTECT){
            throw new Brake("Ошибка пропуска. Не та фаза игры");
        }
        if ($step["exch_left"] != 2){
            throw new Brake("Ошибка пропуска. Вы уже перемещаете карты");
        }
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                exch_left   = 0, 
                stage       = ".STAGE_STEP_PASS."
            WHERE
                duel_step_id = {$step['duel_step_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния состояния шага");
        }
        phaseNext();
    }
    function correctSWP($order, &$p0, &$p1, $pulling = false){
        $p0 += 1;
        $p1 += 1;
        if ($pulling){
            $p0 += 7;
            $p1 += 7;
        }
        if ($order == 1){
            $p0 *= -1;
            $p1 *= -1;
        }
    }
    function swapCards($user_id, $player_order, $duel_id, $p0, $p1){
        global $link;
        $step = checkOrder($duel_id, $player_order);
        if ($step["stage"] != STAGE_STEP_PROTECT){
            throw new Brake("Ошибка изм. положения карт. Не та фаза игры");
        }
        if ($step["exch_left"] == 0){
            throw new Brake("Ошибка изм. положения карт. Нет ходов");
        }
        $cards = getCards($user_id);
        $cards = json_decode($cards[$player_order], true);
        $tmp_card   = $cards[$p0];
        $cards[$p0] = $cards[$p1];
        $cards[$p1] = $tmp_card;
        $exch       = $step["exch_left"]-1;
        

        $cards = json_encode($cards);
        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                json_cards = '{$cards}'
            WHERE
                user_id = {$user_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния положения карт");
        }

        correctSWP($player_order, $p0, $p1);
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                card_swap0   = {$p0},
                card_swap1   = {$p1},
                exch_left    = {$exch}
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния значений шага");
        }
        phaseNext();
    }
    function selectCard($user_id, $player_order, $duel_id, $p0){
        global $link;
        $step = checkOrder($duel_id, $player_order);
        if ($step["stage"] != STAGE_STEP_SELECT){
            throw new Brake("Ошибка выбора карты. Не та фаза игры");
        }

        if ($p0 < 0 || $p0 > 6){
            throw new Brake("Недопустимое значение");
        }
        // $opponent = getOpponent($duel_id, $user_id);
        // $cards = getCards($opponent["user_id"]);
        // $cards = json_decode($cards[$opponent["player_order"]], true);
        // if (is_null($cards[$p0])){
        //     throw new Brake("Ошибка выбора карты");
        // }

        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                selected_card = {$p0}
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния значений шага");
        }
        phaseNext();
    }
    function pullCard($user_id, $player_order, $duel_id, $p0){
        global $link;
        $step = checkOrder($duel_id, $player_order);
        if ($step["stage"] != STAGE_STEP_PULL){
            throw new Brake("Ошибка вытягивания карты. Не та фаза игры");
        }

        $opponent   = getOpponent($duel_id, $user_id);
        $cards      = getCards($user_id, $opponent["user_id"]);
        $self_cards = json_decode($cards[$player_order], true);
        $opp_cards  = json_decode($cards[$opponent["player_order"]], true);
        if (is_null($opp_cards[$p0])){
            throw new Brake("Ошибка выбора карты");
        }
        
        $output = array(
            "card"  => $opp_cards[$p0],
            "p0"    => $p0,
            "p1"    => null,
        ); 
        $opp_cards[$p0] = null;
        for($i = 0; $i < 6; $i++) {
            if (is_null($self_cards[$i])){
                $self_cards[$i] = $output['card'];
                $output["p1"] = $i;
            } 
        }
        if (is_null($output["p1"])){
            if (count($self_cards) < 7){
                $self_cards[] = $output['card'];
            }
            else {
                $self_cards[6] = $output['card'];
            }
            $output["p1"] = 6;
        }
        $opp_cards  = json_encode($opp_cards);
        $self_cards = json_encode($self_cards);

        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                json_cards = '{$opp_cards}'
            WHERE
                user_id = {$opponent['user_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка изменения карт соперника");
        }
        $result = $link->query("
            UPDATE
                duel_user_data
            SET 
                json_cards = '{$self_cards}'
            WHERE
                user_id = {$user_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка изменения собственных карт");
        }

        // $p0 = $output['p0'];
        // $p1 = $output['p1'];
        correctSWP($player_order, $output['p0'], $output['p1'], true);
        // $output['p0'] = $p0;
        // $output['p1'] = $p1;
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                exch_left       = 2,
                selected_card   = -1,
                card_swap0      = {$output['p0']},
                card_swap1      = {$output['p1']}
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния значений шага");
        }
        phaseNext();
        return $output;
    }


    function changeStepStage($step_id, $stage){
        global $link;
        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                stage       = {$stage}
            WHERE
                duel_step_id = {$step_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния состояния шага");
        }
    }

    function phaseNext(){
        global $link;
        $step           = getStep($_SESSION['duel_id'], true);
        $new_stage      = $step["stage"];
        $new_order      = $step["order_value"];
        // $new_selection  = $step["selected_card"];
        // $new_exch       = $step["exch_left"];
        $start_dt       = date("Y-m-d H:i:s", strtotime("now")+2);

        if ($step["stage"] == STAGE_STEP_PROTECT){
            if ($step["exch_left"] == 0){
                $new_stage = STAGE_STEP_PULL;
            }
            else {
                $new_stage = STAGE_STEP_SELECT;
            }
        }
        elseif ($step["stage"] == STAGE_STEP_SELECT){
            $new_stage = STAGE_STEP_PROTECT;
        }
        elseif ($step["stage"] == STAGE_STEP_PASS){
            $new_stage = STAGE_STEP_PULL;
        }
        elseif ($step["stage"] == STAGE_STEP_PULL){
            $new_stage      = STAGE_STEP_SELECT;
            // $new_exch       = 2;
            // $new_selection  = -1;
            if ($step["order_value"] == 0){
                $result = $link->query("
                    SELECT
                        stage
                    FROM 
                        duels
                    WHERE
                        duel_id = {$step['duel_id']}
                ");
                if (!$link->isResultIterable($result)){
                    throw new Brake("Ошибка получения состояния дуэли");
                }
                $duel_stage = $result->fetch_assoc()["stage"] + 1;
                if ($duel_stage < STAGE_DUEL_WIN0){
                    if ($duel_stage >= STAGE_DUEL_FINISH){
                        $duel_stage = STAGE_DUEL_FINISH;
                    }
                    $result = $link->query("
                        UPDATE
                            duels
                        SET 
                            stage = {$duel_stage}
                        WHERE
                            duel_id = {$step['duel_id']}
                    ");
                    if (!$link->isResultValid($result)){
                        throw new Brake("Ошибка измененния состояния дуэли");
                    }
                }
            }
        }

        $new_order = 0;
        if ($step["order_value"] == 0) $new_order = 1;

        // if ($new_order != $step["order_value"]){
        //     $new_selection = -1;
        // }

        $result = $link->query("
            UPDATE
                duel_steps
            SET 
                stage           = {$new_stage},
                order_value     = {$new_order},
                start_dt        = '{$start_dt}'
            WHERE
                duel_step_id = {$step['duel_step_id']}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния состояния шага");
        }
    }
    

    function getWinReason($duel_id){
        global $link;
        $result = $link->query("
            SELECT
                win_reason
            FROM 
                duels
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultIterable($result)){
            throw new Brake("Ошибка получения причины победы");
        }
        return $result->fetch_assoc()["win_reason"];
    }
?>