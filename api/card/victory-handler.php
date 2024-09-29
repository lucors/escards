<?php 
    function sortByK0($a, $b){
        return $a[0] - $b[0];
    }

    //Обновляет состояние дуэли на основе решения calcWinReason
    function calcWinner($duel_id, $player, $opponent){
        global $link;

        $cards = getCards($player["user_id"], $opponent["user_id"]);
        $pc0 = json_decode($cards[$player["player_order"]], true);
        $pc1 = json_decode($cards[$opponent["player_order"]], true);
        $win_data = calcWinReason($pc0, $pc1);

        $winStage = STAGE_DUEL_WIN0;
        if ($win_data["order"] == 1)    $winStage = STAGE_DUEL_WIN1;
        if ($win_data["order"] == -1)   $winStage = STAGE_DUEL_DRAW;

        $p0won = "won";
        if ($winStage == STAGE_DUEL_WIN0) $p0won .= "+1";
        $result = $link->query("
            UPDATE
                ratings
            SET 
                total = total+1,
                won   = {$p0won}
            WHERE
                user_id = {$player['user_id']}
        ");

        $p1won = "won";
        if ($winStage == STAGE_DUEL_WIN1) $p1won .= "+1";
        $result = $link->query("
            UPDATE
                ratings
            SET 
                total = total+1,
                won   = {$p1won}
            WHERE
                user_id = {$opponent['user_id']}
        ");

        $result = $link->query("
            UPDATE
                duels
            SET 
                stage       = {$winStage},
                win_reason  = '{$win_data['reason']}'
            WHERE
                duel_id = {$duel_id}
        ");
        if (!$link->isResultValid($result)){
            throw new Brake("Ошибка измененния состояния дуэли");
        }
    }
    // Вычисляет у кого лучше покерная комбинация
    function calcWinReason($pc0, $pc1){
        $win_data = array(
            "order"  => 0,
            "reason" => array()
        );
        $poker0 = calcPoker($pc0);
        $poker1 = calcPoker($pc1);

        $win_data["reason"][0] = $poker0["reason"];
        $win_data["reason"][1] = $poker1["reason"];
        $win_data["reason"] = json_encode($win_data["reason"]);

        if ($poker1["code"] > $poker0["code"]){
            $win_data["order"] = 1;
        }
        if ($poker0["code"] == $poker1["code"]){
            if (in_array($poker0["code"], array(
                POKER_4OF_KIND, POKER_FLUSH, POKER_3OF_KIND,
                POKER_PAIR, POKER_HIGH_CARD, POKER_STRAIGHT, 
                POKER_STRAIGHT_FLUSH
                ))
            ){
                if ($poker1["card_val0"] > $poker0["card_val0"]){
                    $win_data["order"] = 1;
                }
                elseif ($poker1["card_val0"] == $poker0["card_val0"]){
                    $win_data["order"] = -1;
                }
            }
            elseif (in_array($poker0["code"], array(
                    POKER_FULL_HOUSE, POKER_TWO_PAIR
                ))
            ){
                if ($poker1["card_val0"] > $poker0["card_val0"]){
                    $win_data["order"] = 1;
                }
                elseif ($poker1["card_val0"] == $poker0["card_val0"]){
                    if ($poker1["card_val1"] > $poker0["card_val1"]){
                        $win_data["order"] = 1;
                    }
                    elseif ($poker1["card_val1"] == $poker0["card_val1"]){
                        $win_data["order"] = -1;
                    }
                }
            }
            else {
                $win_data["order"] = -1;
            }
        }
        return $win_data;
    }
    // Вычисляет покерные комбинации
    function calcPoker($cards){
        $output = array(
            "code" => -1,
            "reason" => null
        );

        $suits = array();
        $cards_count = count($cards);
        for ($i = 0; $i < $cards_count; $i++) {
            if (is_null($cards[$i])) continue;
            $cards[$i] = explode('_', $cards[$i]);
            $cards[$i][0] = (int)$cards[$i][0];
            $cards[$i][] = $i;
            if (!in_array($cards[$i][1], $suits)){
                $suits[] = $cards[$i][1];
            }
        }
        $suits_count = count($suits);
        usort($cards, 'sortByK0');
        
        $same_suit = getSameSuit($cards, $cards_count);
        if ($suits_count <= 2){
            if (checkRoyalFlush($output, $same_suit)) return $output;
            if (checkStraightFlush($output, $same_suit)) return $output;
        }

        $same = getSame($cards, $cards_count);
        if (check4ofKind($output, $same)) return $output;
        if (checkFullHouse($output, $same)) return $output;
        if ($suits_count <= 2){
            if (checkFlush($output, $same_suit)) return $output;
        }
        if (checkStraight($output, $cards, $cards_count)) return $output;
        if (check3ofKind($output, $same)) return $output;
        if (checkTwoPair($output, $same)) return $output;
        if (checkPair($output, $same)) return $output;
        if (checkHighCard($output, $cards, $cards_count)) return $output;

        return $output;
    }

    function getSame($cards, $end){
        $same = array();
        for ($i = 0; $i < $end; $i++) {
            if (array_key_exists($cards[$i][0], $same)){
                $same[$cards[$i][0]][] = $cards[$i];
            }
            else {
                $same[$cards[$i][0]] = array($cards[$i]);
            }
        }
        return $same;
    }
    function getSameSuit($cards, $end){
        $same = array();
        for ($i = 0; $i < $end; $i++) {
            if (array_key_exists($cards[$i][1], $same)){
                $same[$cards[$i][1]][] = $cards[$i];
            }
            else {
                $same[$cards[$i][1]] = array($cards[$i]);
            }
        }
        return $same;
    }

    
    // define("POKER_ROYAL_FLUSH", 			10); //Роял-флеш
    function checkRoyalFlush(&$output, $same_suit){
        $reason = array();
        foreach ($same_suit as $key => $value){
            if (count($value) >= 5){
                $start = 0;
                if ($value[0][0] > 10) return false;
                if ($value[0][0] < 10){
                    if ($value[1][0] != 10) return false;
                    else $start = 1;
                }
                for ($i = $start; $i < count($value); $i++) {
                    if ($i+1 < $end){
                        if ($value[$i][0]+1 != $value[$i+1][0]){
                            return false;
                        }
                    }
                    $reason[] = $value[$i][2];
                }
                break;
            }
        }
        if (count($reason) < 5) return false;

        $output["code"]      = POKER_ROYAL_FLUSH;
        $output["reason"]    = $reason;
        return true;
    }
    //     $reason = array();
    //     $start = 0;
    //     $last = null;
        
    //     if ($cards[0][0] > 10) return false;
    //     if ($cards[0][0] < 10){
    //         if ($cards[1][0] != 10) return false;
    //         else $start = 1;
    //     }

    //     for ($i = $start; $i < $end; $i++) {
    //         if ($i+1 < $end){
    //             if ($cards[$i][0]+1 != $cards[$i+1][0]){
    //                 if ($last === null) continue;
    //                 if ($last[0]+1 != $cards[$i+1][0]){
    //                     continue;
    //                 }
    //             } 
    //             if ($cards[$i][1] != $cards[$i+1][1]){
    //                 if ($last === null) continue;
    //                 if ($last[1] != $cards[$i+1][1]){
    //                     continue;
    //                 }
    //             }
    //         }
    //         $last = $cards[$i];
    //         $reason[] = $cards[$i][2];
    //     }
    //     if (count($reason) == 5) return false;
    //     $output["code"]   = POKER_ROYAL_FLUSH;
    //     $output["reason"] = $reason;
    //     return true;
    // }


    // define("POKER_STRAIGHT_FLUSH", 			9);  //Стрит-флеш
    function checkStraightFlush(&$output, $same_suit){
        $reason = array();
        $card_val0 = -1;
        foreach ($same_suit as $key => $value){
            if (count($value) >= 5){
                for ($i = 0; $i < count($value); $i++) {
                    if ($i+1 < count($value)){
                        if ($value[$i][0]+1 != $value[$i+1][0]){
                            if ($i == 0) continue;
                            return false;
                        }
                    }
                    if ($value[$i][0] > $card_val0) $card_val0 = $value[$i][0];
                    $reason[] = $value[$i][2];
                }
                break;
            }
        }
        if (count($reason) < 5) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]      = POKER_STRAIGHT_FLUSH;
        $output["reason"]    = $reason;
        return true;
    }
    // function checkStraightFlush(&$output, $cards, $end){
    //     $reason = array();
    //     $start = 0;
    //     for ($i = $start; $i < $end; $i++) {
    //         if (count($reason) == 5) break;
    //         if ($i+1 < $end){
    //             if ($cards[$i][0]+1 != $cards[$i+1][0]){
    //                 if ($i == $start) continue;
    //                 echo "\n POKER_STRAIGHT_FLUSH1\n";
    //                 echo ($cards[$i][0]+1).":".$cards[$i+1][0];
    //                 echo "rc:\n";
    //                 print_r($reason);
    //                 return false;
    //             }
    //             if ($cards[$i][1] != $cards[$i+1][1]){
    //                 if ($i == $start) continue;
    //                 echo "\n POKER_STRAIGHT_FLUSH2\n";
    //                 return false;
    //             }
    //         }
    //         $reason[] = $cards[$i][2];
    //     }
    //     $output["code"]   = POKER_STRAIGHT_FLUSH;
    //     $output["reason"] = $reason;
    //     return true;
    // }


    // define("POKER_4OF_KIND", 				8);  //Каре
    function check4ofKind(&$output, $same){
        $reason = array();
        $card_val0 = null;
        foreach ($same as $key => $value){
            if (count($value) == 4){
                foreach ($value as &$card) {
                    $card_val0 = $card[0];
                    $reason[] = $card[2];
                }
                break;
            }
        }
        if ($card_val0 === null) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]      = POKER_4OF_KIND;
        $output["reason"]    = $reason;
        return true;
    }


	// define("POKER_FULL_HOUSE", 				7);  //Фулл Хаус
    function checkFullHouse(&$output, $same){
        $reason = array();
        $card_val0 = null;
        $card_val1 = null;
        foreach ($same as $key => $value){
            if (count($value) == 3){
                if ($card_val0 === null){
                    foreach ($value as &$card) {
                        $card_val0 = $card[0];
                        $reason[] = $card[2];
                    }
                }
            }
            if (count($value) == 2){
                if ($card_val1 === null){
                    foreach ($value as &$card) {
                        $card_val1 = $card[0];
                        $reason[] = $card[2];
                    }
                }
            }
        }
        if ($card_val0 === null) return false;
        if ($card_val1 === null) return false;

        $output["card_val0"] = $card_val0;
        $output["card_val1"] = $card_val1;
        $output["code"]      = POKER_FULL_HOUSE;
        $output["reason"]    = $reason;
        return true;
    }


    // define("POKER_FLUSH", 					6);  //Флеш
    function checkFlush(&$output, $same_suit){
        $reason = array();
        $card_val0 = -1;
        foreach ($same_suit as $key => $value){
            if (count($value) == 5){
                foreach ($value as &$card) {
                    if ($card[0] > $card_val0)
                        $card_val0 = $card[0];
                    $reason[] = $card[2];
                }
                break;
            }
        }
        if ($card_val0 === -1) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]      = POKER_FLUSH;
        $output["reason"]    = $reason;
        return true;
    }


    // define("POKER_STRAIGHT", 				5);  //Стрит
    function checkStraight(&$output, $cards, $end){
        $reason = array();
        $card_val0 = -1;
        for ($i = 0; $i < $end; $i++) {
            if ($i+1 < $end){
                if ($cards[$i][0]+1 != $cards[$i+1][0]){
                    if ($cards[$i][0] == $cards[$i+1][0]) continue;
                    if ($i == $start) continue;
                    return false;
                } 
            }
            if ($card[$i][0] > $card_val0) $card_val0 = $card[$i][0];
            $reason[] = $cards[$i][2];
        }
        if (count($reason) < 5) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]   = POKER_STRAIGHT;
        $output["reason"] = $reason;
        return true;
    }


    // define("POKER_3OF_KIND", 				4);  //Тройка
    function check3ofKind(&$output, $same){
        $reason = array();
        $card_val0 = null;
        foreach ($same as $key => $value){
            if (count($value) == 3){
                foreach ($value as &$card) {
                    $card_val0 = $card[0];
                    $reason[] = $card[2];
                }
                break;
            }
        }
        if ($card_val0 === null) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]      = POKER_3OF_KIND;
        $output["reason"]    = $reason;
        return true;
    }


    // define("POKER_TWO_PAIR", 				3);  //Две пары
    function checkTwoPair(&$output, $same){
        $reason = array();
        $card_val0 = null;
        $card_val1 = null;
        foreach ($same as $key => $value){
            if (count($value) == 2){
                if ($card_val0 === null){
                    $card_val0 = $value[0][0];
                }
                else {
                    if ($card_val0 == $value[0][0])
                        continue;
                    $card_val1 = $value[0][0];
                }

                foreach ($value as &$card) {
                    $reason[] = $card[2];
                }
            }
        }
        if ($card_val0 === null) return false;
        if ($card_val1 === null) return false;

        $output["card_val0"] = $card_val0;
        $output["card_val1"] = $card_val1;
        $output["code"]      = POKER_TWO_PAIR;
        $output["reason"]    = $reason;
        return true;
    }


    // define("POKER_PAIR", 					2);  //Пара
    function checkPair(&$output, $same){
        $reason = array();
        $card_val0 = null;
        foreach ($same as $key => $value){
            if (count($value) == 2){
                foreach ($value as &$card) {
                    $card_val0 = $card[0];
                    $reason[] = $card[2];
                }
                break;
            }
        }
        if ($card_val0 === null) return false;

        $output["card_val0"] = $card_val0;
        $output["code"]      = POKER_PAIR;
        $output["reason"]    = $reason;
        return true;
    }


    // define("POKER_HIGH_CARD", 				1);  //Старшая карта
    function checkHighCard(&$output, $cards, $end){
        $reason = array($cards[0][2]);
        $card_val0 = $cards[0][0];
        for ($i = 0; $i < $end; $i++) {
            if ($card_val0 < $cards[$i][0]){
                $card_val0 = $cards[$i][0];
                $reason[0] = $cards[$i][2];
            }
        }
        $output["card_val0"] = $card_val0;
        $output["code"]   = POKER_HIGH_CARD;
        $output["reason"] = $reason;
        return true;
    }
?>