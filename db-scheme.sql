CREATE DATABASE u390581pln_escards;


DROP TABLE IF EXISTS duel_user_data;
DROP TABLE IF EXISTS duel_steps;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS duels;

-- КОМНАТЫ ДУЭЛИ
-- STAGE: состояние дуэли в данный момент (common.php)
CREATE TABLE IF NOT EXISTS duels (
	duel_id 	    INTEGER AUTO_INCREMENT,
	stage 		    TINYINT(20) NOT NULL DEFAULT 0,
	step_time 	    TINYINT(40) NOT NULL DEFAULT 30,
	creation_dt 	DATETIME,
    win_reason      JSON DEFAULT NULL,

    PRIMARY KEY (duel_id)
) ENGINE=InnoDB;



-- STAGE: состояние шага в данный момент (common.php)
CREATE TABLE IF NOT EXISTS duel_steps (
    duel_step_id    INTEGER AUTO_INCREMENT,
	-- old_stage 		TINYINT(20) NOT NULL DEFAULT 0,
	stage 		    TINYINT(20) NOT NULL DEFAULT 0,
    duel_id         INTEGER DEFAULT NULL,
    order_value     TINYINT(10) NOT NULL DEFAULT 0,
    card_swap0      TINYINT(20) DEFAULT NULL,
    card_swap1      TINYINT(20) DEFAULT NULL,
    selected_card   TINYINT(20) NOT NULL DEFAULT -1,
    exch_left       TINYINT(5)  NOT NULL DEFAULT 2,
	start_dt 	    DATETIME,

    PRIMARY KEY (duel_step_id),
    FOREIGN KEY (duel_id) REFERENCES duels(duel_id) 
        ON DELETE CASCADE ON UPDATE CASCADE 
) ENGINE=InnoDB;


-- ПОЛЬЗОВАТЕЛИ
CREATE TABLE IF NOT EXISTS users (
    user_id     INTEGER AUTO_INCREMENT,
    name        VARCHAR(70) NOT NULL UNIQUE,
    email       VARCHAR(80) NOT NULL UNIQUE,
    passhash    VARCHAR(70) NOT NULL,
	last_dt 	DATETIME,
    duel_id     INTEGER DEFAULT NULL,
    tourn_id    INTEGER DEFAULT NULL,
	role 		TINYINT DEFAULT 0,

    PRIMARY KEY (user_id),
    FOREIGN KEY (duel_id) REFERENCES duels(duel_id) 
        ON DELETE SET NULL ON UPDATE CASCADE 
) ENGINE=InnoDB;


-- РЕЙТИНГ
CREATE TABLE IF NOT EXISTS ratings (
	rating_id 	INTEGER AUTO_INCREMENT,
	user_id 	INTEGER DEFAULT NULL,
	total 		INTEGER DEFAULT 0,
	won 		INTEGER DEFAULT 0,

    PRIMARY KEY (rating_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE 
) ENGINE=InnoDB;

-- Общее для игроков
-- selected INYINT(10) DEFAULT -1 

-- ДАННЫЕ ИГРОКА ДУЭЛИ
CREATE TABLE IF NOT EXISTS duel_user_data (
    duel_user_data_id   INTEGER AUTO_INCREMENT,
    user_id             INTEGER DEFAULT NULL,
    order_value         TINYINT(10) NOT NULL DEFAULT -1,
    json_cards          JSON DEFAULT NULL,

    PRIMARY KEY (duel_user_data_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- isMysqliResultValid == isResultIterable
-- isQueryResultValid  == isResultValid