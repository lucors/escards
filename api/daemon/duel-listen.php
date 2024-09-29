<?php
	if(!isset($_SESSION)){
	    session_start(["read_and_close" => true]);
	}
    require_once("../common.php");
    $psList = shell_exec("ps -A");
    $psCount = count(preg_split("/\n/", $psList)) - 2;
	if ($psCount >= ESCARDS_PROC_MAX){
		http_response_code(503);
		exit;
	}
	header("Cache-Control: no-cache");
	header("Content-Type: text/event-stream\n\n");


    require_once("../log.php");
    require_once("../db.php");
    Log::debug("Запущен sse слушатель дуэли [duel-listen.php]"); 


	// Переменные
	{
		$sleepTime = 0.7;
		$cycleTime = $sleepTime;

		// DUEL SELECTOR DATA
		$duel_data = array();
		$step_data = array();
		
		$cards_updated = false;
		$player = array(
			"user_id" 		=> $_SESSION["user_id"],
			"player_order" 	=> $_SESSION["player_order"],
			"json_cards"	=> null
		);
		$opponent =  array(
			"user_id" 		=> null,
			"player_order" 	=> ($player["player_order"] == 0) ? 1 : 0,
			"json_cards"	=> null
		);

		$canListen 		= true;						// Флаг цикла
		$link 			= null; 					// Соедиение с БД
		$response 		= array();					// Переменная ответа
		// Последний ответ сервера
		$lastResponse 	= array(
			"step" 			=>		null,
			"position" 		=>		null
		);					
	}


	// Точка входа
	{
		// Наследование методов card/daemon-support
		require_once(__DIR__."/../card/daemon-support.php");
		// Методы запросов к БД (Напрямую, без API)
		require_once("duel-listen-methods.php");


		// Проверка авторизации игрока
		if (!isset($_SESSION["email"]) || !isset($_SESSION["user_id"])){
			Log::die("Попытка запуска duel-listen без авторизации"); 
		}
		$duel_data["duel_id"] = filter_input(INPUT_GET, "duel_id"); // Ожидаем ID комнаты
		// Не передан обязательный параметр 
		if (!isset($duel_data["duel_id"])){
			Log::die("Попытка запуска duel-listen без параметра duel_id"); 
		}

        $link = new DBConnection("p:".Common::$dbHost, Common::$dbUser, Common::$dbPass, Common::$dbName);


		// Получение данных комнаты
		$result = $link->query("
			SELECT
				duel_id, stage, step_time
			FROM
				duels
			WHERE
				duel_id = {$duel_data['duel_id']}
		");
		if (!$link->isResultIterable($result)){
			Log::auto("[D] Ошибка получения данных дуэли"); 
			$response["fail"] = array(
				"msg"       => "Ошибка получения данных дуэли",
				"stopSSE"   => true
			);
		}
		$duel_data = $result->fetch_assoc();
		
		if ($duel_data["stage"] >= STAGE_DUEL_PLAYERS_READY && $duel_data["stage"] < STAGE_DUEL_WIN0){
			updateOpponent(true);
		}
		if ($duel_data["stage"] >= STAGE_DUEL_ROUND1 && $duel_data["stage"] < STAGE_DUEL_WIN0){
			$cards = getCards($player["user_id"], $opponent["user_id"]);
			$player["cards"] = json_decode($cards[$player["player_order"]], true);
			$opponent["cards"] = json_decode($cards[$opponent["player_order"]], true);
		}

		// Получение данных шага
		try {
			$step_data = getStep($duel_data['duel_id'], true);
		}
        catch (Brake $e) {
            $msg = $e->getMessage();
            Log::auto("[D] {$msg}"); 
            $response["fail"] = array(
                "msg"       => $msg,
                "stopSSE"   => true
            );
		}


		// Пока флаг $canListen и есть соединение с клиентом
		while ($canListen && !connection_aborted()){
			$cycleTime = $sleepTime;

            // $response["dt"] = date('s');
			$response["duel"] = duelSelector();

			// if ($response["duel"]["stage"] >= STAGE_DUEL_WIN0){
			// 	// $response["fail"] = array(
            //     //     "msg"       => "ИГРА ОКОНЧЕНА",
            //     //     "stopSSE"   => true
            //     // );
			// 	// $response["position"] = positionSelector(true);
			// }
			// else
			if ($response["duel"]["stage"] >= STAGE_DUEL_ROUND1){
				$response["step"] = stepSelector();
				// applyResponseChanges("step");
			}

			// Отправляем данные
			echo "data: ".json_encode($response)."\n\n";

			// Очищаем буфер
			ob_end_flush();
			flush();
			// Очищаем ответ
			// unset($response["dt"]);
			unset($response["duel"]);
			unset($response["fail"]);
			unset($response["step"]);
			// unset($response["position"]);

			// Часто обновления $cycleTime*1сек.
			usleep($cycleTime * 1000000);
		}

		$link->disconnect();
	}
?>