<?php
    // Уберите эти строчки перед выкатом 
    ini_set("error_reporting", E_ALL);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);

    // Старт сессии
    if (!isset($_SESSION)){
        session_start();
    }

    require_once("api/common.php");
    require_once("api/db.php");

    $link = new DBConnection(Common::$dbHost, Common::$dbUser, Common::$dbPass, Common::$dbName);
    require_once("api/user/methods.php");
    

    $_SESSION["duel_id"] = -1;
    $_SESSION["tourn_id"] = -1;
    if (isset($_SESSION["email"])){
        try {
            $user = getUserData($_SESSION["email"], false);
            
            // if ($user["role"] < 1){
            //     throw new Brake("Игра временно недоступна");
            // }
            $_SESSION["user_id"]        = $user["user_id"];
            $_SESSION["name"]           = $user["name"];
            $_SESSION["role"]           = $user["role"];
            $_SESSION["avatar_path"]    = $user["avatar_path"];
            if (!is_null($user["duel_id"])){
                $_SESSION["duel_id"] = $user["duel_id"];
            }
            if (!is_null($user["tourn_id"])){
                $_SESSION["tourn_id"] = $user["tourn_id"];
            }
            updateUserDT($_SESSION["user_id"]);
        }
        catch (Brake $e) {
            session_unset();
        }
    }
    $link->disconnect();
?>

<html>
    <head>
        <title>Бесконечные карты</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="description" content="Браузерная карточная мини-игра из Бесконечного лета">
        <meta name="keywords" content="игра онлайн-игра карточная мини-игра бесконечное лето es everlasting summer">

        <link rel="shortcut icon" href="assets/img/favicon.png"/>
        <link rel = "stylesheet" href = "assets/css/common.css">
        <link rel = "stylesheet" href = "assets/css/auth.css">
        <link rel = "stylesheet" href = "assets/css/menu.css">
        <link rel = "stylesheet" href = "assets/css/game.css">

        <script src="assets/js/lib/anime.min.js"></script>
        <script src="assets/js/lib/jquery.min.js"></script>
        <script src="assets/js/lib/js.cookie.min.js"></script>
        <script src="assets/js/const.js"></script>
        <script src="assets/js/common.js"></script>
        <script defer src="assets/js/auth.js"></script>
        <script defer src="assets/js/menu.js"></script>
        <script defer src="assets/js/game.js"></script>
    </head>

    <body>
        <script defer type="text/javascript">
            <?php
                if (isset($_SESSION["email"])){
                    echo "window.user.user_id           = {$_SESSION['user_id']};";
                    echo "window.user.name              = \"{$_SESSION['name']}\";";
                    echo "window.user.email             = \"{$_SESSION['email']}\";";
                    echo "window.user.role              = {$_SESSION['role']};";
                    echo "window.user.avatar_path       = \"{$_SESSION['avatar_path']}\";";
                    echo "window.duel_data.duel_id      = {$_SESSION['duel_id']};";
                    echo "window.tourn_data.tourn_id    = {$_SESSION['tourn_id']};";
                }
            ?>
        </script>
        <div id="content">
            <img id="blink-up" class="blink" src="assets/img/blink_up.png"/>
            <img id="blink-down" class="blink" src="assets/img/blink_down.png"/>

            <div id="substage-confirm" class="game-substage">
                <div id="confirm-content" class="block-content">
                    Сообщение тут
                </div>
                <div id="confirm-error" class="fail-msg"></div>
                <div id="actions-confirm">
                    <span class="yes action"></span>
                    <span class="ok action"></span>
                    <span class="no action"></span>
                </div>
            </div>


            <div id="substage-settings" class="game-substage">
                <div id="settings-back" class="back-button">
                    <img class="back-arrow" src="assets/img/menu/settings-arrow.png">
                    Назад
                </div>
                <div id="settings-content" class="block-content">

                    <div class="bc-block fields">
                        <div class="bc-header">Профиль</div>

                        <div id="profile-settings" class="bc-field">
                            <div id="edit-user-avatar" >
                                <img class="key" src="assets/img/avatars/default.png">
                                <input type="file" class="value summer" accept="image/png">
                                <img class="save" src="assets/img/menu/settings-save.png">
                            </div>

                            <div class="value">
                                <div id="edit-user-name" class="bc-field">
                                    <span class="key">Ваше имя</span>
                                    <input class="value summer" type="text">
                                    <img class="save" src="assets/img/menu/settings-save.png">
                                </div>
                                <div id="edit-user-pass" class="bc-field">
                                    <span class="key">Пароль</span>
                                    <input class="value summer" type="text">
                                    <img class="save" src="assets/img/menu/settings-save.png">
                                </div>
                                <div id="profile-stats" class="bc-field">
                                    <span class="key">Статистика дуэлей:</span>
                                    <div class="value"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bc-block fields out-auth">
                        <div class="bc-header">Режим экрана</div>
                        <div id="edit-screen-size" class="bc-field actions">
                            <div class="yes action clickable">
                                <img class="leaf" src="assets/img/leaf.png">
                                Во весь экран
                            </div>
                            <div class="no action clickable">
                                <img class="leaf" src="assets/img/leaf.png">
                                В окне
                            </div>
                        </div>
                    </div>
                    
                    <div class="bc-block fields out-auth">
                        <div class="bc-header">Громкость</div>
                        <div class="bc-field">
                            <span class="key clickable volume-test">Музыка</span>
                            <div class="range-parent value"> 
                                <input id="music-volume-input" class="range summer" type="range" min="0" max="100" value="100">
                                <div class="range-progress"></div>
                            </div>
                        </div>
                        <div class="bc-field">
                            <span class="key clickable volume-test">Звуки</span>
                            <div class="range-parent value"> 
                                <input id="sfx-volume-input" class="range summer" type="range" min="0" max="100" value="100">
                                <div class="range-progress"></div>
                            </div>
                        </div>
                        <div class="bc-field">
                            <span class="key clickable volume-test">Эмбиент</span>
                            <div class="range-parent value"> 
                                <input id="ambience-volume-input" class="range summer" type="range" min="0" max="100" value="100">
                                <div class="range-progress"></div>
                            </div>
                        </div>
                </div>
                </div>
            </div>


            <!-- STAGE AUTH -->
            <div id="stage-auth" class="game-stage">
                <img id="auth-logo" src="assets/img/auth/auth-logo.png">
                <img id="auth-settings" class="settings-ico" src="assets/img/menu/settings-ico.png">

                <img id="auth-substrate" src="assets/img/auth/auth-substrate.png">
                <div id="auth-content" class="block-content">
                    <div class="bc-header"></div>
                    <div class="bc-block fields">
                        <div id="auth-name" class="bc-field">
                            <span class="key">Имя</span>
                            <input class="value winter" type="text">
                        </div>
                        <div id="auth-email" class="bc-field">
                            <span class="key">Почта</span>
                            <input class="value winter" type="email">
                        </div>
                        <div id="auth-pass" class="bc-field">
                            <span class="key clickable" title="Нажмите, чтобы показать пароль">Пароль</span>
                            <input class="value winter" type="password">
                        </div>
                        <div id="auth-pass2" class="bc-field">
                            <span class="key clickable" title="Нажмите, чтобы показать пароль">Повторите</span>
                            <input class="value winter" type="password" title="Повторите пароль">
                        </div>
                    </div>
                    <div class="bc-block actions">
                        <div id="auth-forgot" class="bc-field action clickable">Забыли пароль?</div>
                        <div id="auth-create" class="bc-field action clickable"></div>
                    </div>
                </div>
                <div id="auth-fail-msg" class="fail-msg"></div>
                <img id="auth-login" src="assets/img/auth/login.png" alt="Войти">
            </div>

            <!-- STAGE MAIN MENU -->
            <div id="stage-menu" class="game-stage">
                <div id="substage-logout" class="game-substage">
                    <div id="about-logout">
                        Вы действительно хотите выйти из аккаунта?
                    </div>
                    <div id="actions-logout">
                        <span class="yes action">Да</span>
                        <span class="no action">Нет</span>
                    </div>
                </div>
                <canvas></canvas>
            </div>

            
            <!-- STAGE GAME -->
            <div id="stage-game" class="game-stage">
                <div class="bg"></div>
                <div id="game-actions">
                    <img id="game-settings" class="action" src="assets/img/menu/settings-ico.png">
                    <img id="game-exit" class="action" src="assets/img/exit-ico.png">
                    <!-- <div id="game-exit" class="back-button">
                        <img class="back-arrow" src="assets/img/menu/settings-arrow.png">
                        Выход
                    </div> -->
                </div>

                <div id="finishlog"></div>
                <div id="cards-dynamic"></div>
                <div id="opp-cards" class="cards-list"></div>
                <div id="self-cards" class="cards-list"></div>

                <img id="opp-avatar" src="assets/img/avatars/default.png">
                <div id="opp-name" class="game-info">
                    <div class="key">Соперник:</div>
                    <div class="value"></div>
                </div>
                <div id="time-left" class="game-info">
                    <div class="key">Осталось времени:</div>
                    <div class="value"></div>
                </div>

                <div id="game-order" class="game-info">
                    <div class="key">Чей ход:</div>
                    <div class="value"></div>
                </div>
                <div id="game-phase" class="game-info">
                    <div class="key">Фаза игры:</div>
                    <div class="value">
                        <span></span>
                        <div class="pass">X</div>
                    </div>
                </div>
                <div id="circles-left" class="game-info">
                    <div class="key">Кругов осталось:</div>
                    <div class="value"></div>
                </div>
                <div id="exchan-left" class="game-info">
                    <div class="key">Обменов осталось:</div>
                    <div class="value"></div>
                </div>
            </div>
        </div>
    </body>
</html>