<?php 
	// Проверяет изменения элемента [$key] по отношению к предыдущему ответу
	// function applyResponseChanges($key){
	// 	global $response, $lastResponse;
	// 	if ($response[$key] == $lastResponse[$key]){
	// 		unset($response[$key]);
	// 		return false;
	// 	}
	// 	$lastResponse[$key] = $response[$key];
	// 	return true;
	// }

    function updateOpponent($strict){
        global $link, $opponent, $response, $duel_data, $player;
        try {
            $opponent = getOpponent($duel_data['duel_id'], $player['user_id'], $strict);
        }
        catch (Brake $e) {
            $msg = $e->getMessage();
            Log::auto("[D] {$msg}"); 
            $response["fail"] = array(
                "msg"       => $msg,
                "stopSSE"   => true
            );
        }
    }

    function isMyTurn($order){
        global $player; 
        return (bool)($player["player_order"] == $order);
    }
    function isHost(){
        // global $player; 
        // return $player["player_order"] == 0;
        return isMyTurn(0);
    }


	function duelSelector(){
		global $link, $duel_data, $player, $opponent, $cycleTime;
		$output = array("stage" => SYNC_COMMON_ERROR);

		$result = $link->query("
            SELECT
                duel_id, stage
            FROM
                duels
            WHERE
                duel_id = {$duel_data['duel_id']}
		");
		if (!$link->isResultIterable($result)){
			$output["stage"] = SYNC_COMMON_ERROR;
            return $output;
		}
        $output["stage"] = $result->fetch_assoc()["stage"];
        $duel_data["stage"] = $output["stage"];

        if ($duel_data["stage"] == STAGE_DUEL_PLAYERS_READY){
            updateOpponent(true);
            if (isHost()){
                startDuel($duel_data["duel_id"]);
            }
        }

        if ($duel_data["stage"] == STAGE_DUEL_CARDSCREATED ||
            $duel_data["stage"] == STAGE_DUEL_CARDS0DONE ||
            $duel_data["stage"] == STAGE_DUEL_CARDS1DONE
            ){
            if (is_null($player["json_cards"]) || is_null($opponent["json_cards"])){
                $cards = getCards($player["user_id"], $opponent["user_id"]);
			    $player["json_cards"] = json_decode($cards[$player["player_order"]], true);
			    $opponent["json_cards"] = json_decode($cards[$opponent["player_order"]], true);
                $cycleTime = 0;
            }
        }

        if ($duel_data["stage"] == STAGE_DUEL_FINISH){
            if (isHost()){
                require_once(__DIR__."/../card/victory-handler.php");
                try {
                    calcWinner($duel_data["duel_id"], $player, $opponent);
                    $cycleTime = 0;
                }
                catch (Brake $e) {
                    $output["stage"] = SYNC_COMMON_ERROR;
                    return $output;
                }
            }
        }

		return $output;
	}

    function stepSelector(){
		global $link, $duel_data, $step_data, $player, $opponent, $cards_updated, $cycleTime;
		$output = array("stage" => SYNC_COMMON_ERROR);

		try {
			$data = getStep($duel_data['duel_id'], true);
		}
        catch (Brake $e) {
			$output["stage"] = SYNC_COMMON_ERROR;
            return $output;
		}
        
        if ($duel_data["stage"] < STAGE_DUEL_FINISH){
            $output["stage"] = $data["stage"];
            $output["order"] = $data["order_value"];
            $output["sel"]   = $data["selected_card"];
            $output["exch"]  = $data["exch_left"];
        }

        if (!is_null($data["card_swap0"])){
            if (!$cards_updated){
                // Ресурсная операция
                $cards = getCards($player["user_id"], $opponent["user_id"]);
                $player["json_cards"] = json_decode($cards[$player["player_order"]], true);
                $opponent["json_cards"] = json_decode($cards[$opponent["player_order"]], true);
                $cards_updated = true;
                $cycleTime = 0;
            }
            // Если инициатор ord1, то swp*-1. Если вытягивание, то swp+7 
            if (!isHost() && ($data["card_swap0"] > 0)){
                $output["swp0"] = $data["card_swap0"]-1;
                $output["swp1"] = $data["card_swap1"]-1;
                clearSwap($duel_data["duel_id"]);
                $cycleTime = 0;
            }
            elseif (isHost() && ($data["card_swap0"] < 0)){
                $output["swp0"] = ($data["card_swap0"]*-1)-1;
                $output["swp1"] = ($data["card_swap1"]*-1)-1;
                clearSwap($duel_data["duel_id"]);
                $cycleTime = 0;
            }
        }
        else {
            $cards_updated = false;
        }

        $step_data["old_stage"]     = $data["old_stage"];
        $step_data["stage"]         = $data["stage"];
        $step_data["order_value"]   = $data["order_value"];
        $step_data["start_dt"]      = $data["start_dt"];
        $step_data["selected_card"] = $data["selected_card"];
        
        if ($step_data["stage"] >= STAGE_STEP_PROTECT && $duel_data["stage"] < STAGE_DUEL_FINISH){
            if (!is_null($step_data["start_dt"])){
                $output["ping"] = strtotime("now") - strtotime($step_data["start_dt"]);
                // Время кончилось
                if ($output["ping"] > $duel_data["step_time"]){
                    // Пока отключил поведение при TIMEOUT
                    // changeStepStage($step_data['duel_step_id'], STAGE_STEP_TIMEOUT);
                    // $step_data["stage"] = STAGE_STEP_TIMEOUT;
                    $output["ping"] = $duel_data["step_time"];
                }
            }
        }
		return $output;
    }
?>