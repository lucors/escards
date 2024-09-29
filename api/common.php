<?php
	// Макс. кол-во процессов 
	define("ESCARDS_PROC_MAX", 40); //Не логировать

    // Уровни логирования
	define("LOG_LVL_NONE", 	0); //Не логировать
	define("LOG_LVL_ERR", 	1); //Логировать ошибки
	define("LOG_LVL_WARN", 	2); //Логировать предупреждения и ниже
	define("LOG_LVL_MSG", 	3); //Логировать сообщения и ниже
	define("LOG_LVL_DEBUG", 4); //Логировать всё

	// Обший код ошибки
	define("SYNC_COMMON_ERROR", 			0);	// Общий. Ошибка

	// Коды состояний дуэли
	define("STAGE_DUEL_CREATED", 			1); //Дуэль создана, ожидает соперника
	define("STAGE_DUEL_PLAYERS_READY", 		2); //Соперник найден
	define("STAGE_DUEL_CARDSCREATED", 		3); //Карты сгенерированы
	define("STAGE_DUEL_CARDS0DONE", 		4); //Карты получены (игрок order=0) 
	define("STAGE_DUEL_CARDS1DONE", 		5); //Карты получены (игрок order=1) 
	define("STAGE_DUEL_ROUND1", 			6); //Круг 1
	define("STAGE_DUEL_ROUND2", 			7); //Круг 2
	define("STAGE_DUEL_ROUND3", 			8); //Круг 3
	define("STAGE_DUEL_FINISH", 			9); //Подведение итогов
	// 
	define("STAGE_DUEL_WIN0", 				15); //Выиграл игрок order=0
	define("STAGE_DUEL_WIN1", 				16); //Выиграл игрок order=1
	define("STAGE_DUEL_DRAW", 				17); //Ничья
	define("STAGE_DUEL_DEAD", 				18); //Игрок покинул комнату

	// Коды состояний шага дуэли
	define("STAGE_STEP_CREATED", 			1);
	define("STAGE_STEP_TIMEOUT", 			2);
	define("STAGE_STEP_PROTECT", 			3);
	define("STAGE_STEP_SELECT", 			4);
	define("STAGE_STEP_PASS", 				5);
	define("STAGE_STEP_PULL", 				6);

	// Коды покерных комбинаций
	define("POKER_HIGH_CARD", 				1);  //Старшая карта
	define("POKER_PAIR", 					2);  //Пара
	define("POKER_TWO_PAIR", 				3);  //Две пары
	define("POKER_3OF_KIND", 				4);  //Тройка
	define("POKER_STRAIGHT", 				5);  //Стрит
	define("POKER_FLUSH", 					6);  //Флеш
	define("POKER_FULL_HOUSE", 				7);  //Фулл Хаус
	define("POKER_4OF_KIND", 				8);  //Каре
	define("POKER_STRAIGHT_FLUSH", 			9);  //Стрит-флеш
	define("POKER_ROYAL_FLUSH", 			10); //Роял-флеш


    class Common {
    	public static $allowLogging = true; //Разрешить вести лог
		public static $loggingPath 	= "/assets/log/"; //Путь к логам
		public static $loggingLevel = LOG_LVL_MSG; //Уровень логирования

		// Данные соединения с БД по умолчанию
		public static $dbHost = "localhost";
		public static $dbUser = "u3905860fl_escards";
		public static $dbPass = "7KBpFTuK";
		public static $dbName = "u3905860fl_escards";

		// Возвращает метод общения с клиентом
		public static function getInputMethod(){
			if ($_SERVER["REQUEST_METHOD"] === "POST"){
				return INPUT_POST;
			}
			return INPUT_GET;
		}
		// Возвращает путь к файлу если тот существует, иначе путь к $default
		// !$path.$default обязан существовать
		public static function correctPath($path, $file, $default){
			if (file_exists(__DIR__."/../".$path.$file)){
				return $path.$file;
			}
			return $path.$default;
		}
		// Вернуть путь к аватару клиента. Если нет, то default.png
		public static function getCorrectAvatarPath($id){
			return Common::correctPath("assets/img/avatars/", "{$id}.png", "default.png");
		}
    }
	
	// Стопорное исключение с уровнем логирования (по умолч. LOG_LVL_WARN)
    class Brake extends Exception {
    	public $loglvl = LOG_LVL_WARN;

		public function __construct($message, $code = 0, $loglvl = LOG_LVL_WARN, Throwable $previous = null) {
			$this->loglvl = $loglvl;
			parent::__construct($message, $code, $previous);
		}
		public function __toString() {
			// Пытается определить папку и имя файла
			$dirs = explode(DIRECTORY_SEPARATOR, $this->file);
			$file = basename($this->file);
			if (count($dirs)-2 > 0){
				$file = $dirs[count($dirs)-2]."/".$file;
			}

			return "{$this->message} {$file}[{$this->line}]";
			// return ": [{$this->code}]: {$this->message}\n";
		}
	}
?>