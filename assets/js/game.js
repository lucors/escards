let sseDuelListenConnection = null;
let opponent_data = null;
let swap_cards  = [];
let sfx_choose  = ["choose_card_1.ogg","choose_card_2.ogg"]; 
let sfx_deal    = ["deal_card_1.ogg","deal_card_2.ogg","deal_card_3.ogg","deal_card_4.ogg"];
let sfx_take    = ["take_card_1.ogg","take_card_2.ogg","take_card_3.ogg"];
gamestages.entryFunc[2] = gameEntry;
gamestages.soundFunc[2] = gameInitSound;


function gameEntry(duelData, fromCommon = false){
    gameInitSound();
    if (fromCommon){
        apiRequest({
            route: "duel",
            op: "get",
            duel_id: window.duel_data.duel_id
        }, {
            success: function(data){
                if (data.result){
                    return gamePrepare(data, true);
                }
                if ("msg" in data) showAlert(data.msg);
                else this.error();
            },
            error: function(){
                showAlert("Ошибка получения данных дуэли");
            }
        });
        return;
    }
    gamePrepare(duelData);
}
function gameInitSound(){
    return soundMaster.ambience.fadein({src: "dining_hall_empty.ogg", duration: 900});
}
function gamePrepare(duelData, fromCommon = false){
    $(".cards").addClass("inactive");
    window.user.player_order = duelData.duel_data.player_order;
    window.duel_data.set(duelData.duel_data);
    if (window.duel_data.duel_id == -1) {
        return showAlert("Ошибка данных дуэли", {
            okBtn: "Покинуть дуэль",
            ok: exitGame
        });
    }

    if (fromCommon){
        if (window.duel_data.stage == STAGE_DUEL_CREATED){
            hdc[STAGE_DUEL_CREATED] = true;
            showAlert("Ожидание соперника", {
                okBtn: "Отмена",
                ok: exitGame
            });
        }
        else if (window.duel_data.stage > STAGE_DUEL_CREATED){
            if (window.duel_data.stage >= STAGE_DUEL_PLAYERS_READY){
                hdc[STAGE_DUEL_PLAYERS_READY] = true;
                $("#stage-game > .bg").addClass("ingame");
                getGameOpponent();
            }
            if (window.duel_data.stage > STAGE_DUEL_CARDS1DONE){
                hdc[STAGE_DUEL_CARDSCREATED] = true;
                getPlayerCards();
            }
        }
    }
    sseStartDuelListener();
}

{//SSE 
    function sseStartDuelListener(){
        console.log(`[SSE START] слушатель дуэли №${window.duel_data.duel_id}`);
        sseDuelListenConnection = new EventSource(`api/daemon/duel-listen.php?duel_id=${window.duel_data.duel_id}`);
        // window.duel_data.duel_id = -1;
        // Сервер присылает сообщение только при изменениях
        sseDuelListenConnection.onmessage = function(event) {
            // if (event.data.length < 5){
            // 	return;
            // }
            var data = JSON.parse(event.data);
            sseResponseHandler(data);
        }
        sseDuelListenConnection.onerror = function(event) {
            if (sseDuelListenConnection.readyState == 2){
                showAlert("Сервер временно недоступен", {
                    okBtn: "Покинуть дуэль",
                    ok: exitGame
                });
            }
            // console.log("An error occurred while attempting to connect.");
        };
    }
    function sseStopDuelListener(){
        if (sseDuelListenConnection !== null && sseDuelListenConnection !== undefined){
            console.log("[SSE END] слушатель дуэли");
            sseDuelListenConnection.close();
            sseDuelListenConnection = null;
        }
    }
    function sseResponseHandler(response){
        var cout = "";
        if ("fail" in response){
            var options = {}
            if ("stopSSE" in response.fail){
                options = {
                    okBtn: "Покинуть дуэль",
                    ok: exitGame
                }
                sseStopDuelListener();
            }
            if ("msg" in response.fail){
                return showAlert(response.fail.msg, options);
            }
            return showAlert("Сервер передал ошибку", options);
        }
        if ("duel" in response){
            syncDuelHandler(response.duel);
            cout += `ds:${response.duel.stage} `;

            if ((response.duel.stage >= STAGE_DUEL_ROUND1) && ("step" in response)){
            	syncStepHandler(response.step);
                if ("stage" in response.step)   cout += `ss:${response.step.stage} `;
                if ("order" in response.step)   cout += `ord:${response.step.order} `;
                if ("ping" in response.step)    cout += `ping:${response.step.ping} `;
                if ("sel" in response.step)     cout += `sel:${response.step.sel} `;
                if ("swp0" in response.step)    cout += `swp0:${response.step.swp0}/swp1:${response.step.swp1} `;
            }
        }
        if (flags.logSSEData) console.log(cout);
    }
    function syncCommonError(){
        console.error("SYNC_GENERAL_ERROR");
        showAlert("Ошибка синхронизации дуэли", {
            okBtn: "Покинуть дуэль",
            ok: exitGame
        });
        sseStopDuelListener();
    }
    function syncDuelHandler(duel){ 
        duel.stage = Number(duel.stage);
        window.duel_data.stage = duel.stage;

        if (duel.stage >= STAGE_DUEL_WIN0){
            var finishlog = "Победа";
            sseStopDuelListener();
        }
        switch(duel.stage) {
            case SYNC_COMMON_ERROR: 
                return syncCommonError();
                break;
            case STAGE_DUEL_DEAD:
                if (hdc[STAGE_DUEL_DEAD]) break;
                hdc[STAGE_DUEL_DEAD] = true;
                showAlert("Соперник покинул игру", {
                    okBtn: "Покинуть дуэль",
                    ok: exitGame
                });
                break;
            case STAGE_DUEL_CREATED:
                if (hdc[STAGE_DUEL_CREATED]) break;
                hdc[STAGE_DUEL_CREATED] = true;
                showAlert("Ожидание соперника", {
                    okBtn: "Отмена",
                    ok: exitGame
                });
                break;
            case STAGE_DUEL_ROUND1:
                if (hdc[STAGE_DUEL_ROUND1]) break;
                hdc[STAGE_DUEL_ROUND1] = true;
                $("#circles-left .value").html('3');
                break;
            case STAGE_DUEL_ROUND2:
                if (hdc[STAGE_DUEL_ROUND2]) break;
                hdc[STAGE_DUEL_ROUND2] = true;
                $("#circles-left .value").html('2');
                break;
            case STAGE_DUEL_ROUND3:
                if (hdc[STAGE_DUEL_ROUND3]) break;
                hdc[STAGE_DUEL_ROUND3] = true;
                $("#circles-left .value").html('1');
                break;
            case STAGE_DUEL_WIN0:
                if (hdc[STAGE_DUEL_WIN0]) break;
                hdc[STAGE_DUEL_WIN0] = true;
                if (!isMyTurn(0)) finishlog = "Fail";
                break;
            case STAGE_DUEL_WIN1:
                if (hdc[STAGE_DUEL_WIN1]) break;
                hdc[STAGE_DUEL_WIN1] = true;
                if (!isMyTurn(1)) finishlog = "Fail";
                break;
            case STAGE_DUEL_DRAW:
                if (hdc[STAGE_DUEL_DRAW]) break;
                hdc[STAGE_DUEL_DRAW] = true;
                finishlog = "Ничья";
                break;
        }
        // Соперник найден
        if (duel.stage >= STAGE_DUEL_PLAYERS_READY && !hdc[STAGE_DUEL_PLAYERS_READY]){
            hdc[STAGE_DUEL_PLAYERS_READY] = true;
            if (duel.stage < STAGE_DUEL_WIN0){
                setGameSubStage("confirm", false);
            }
            $("#stage-game > .bg").addClass("ingame");
            getGameOpponent();
        }
        // Карты готовы, получаем карты
        if (duel.stage == STAGE_DUEL_CARDSCREATED || 
            duel.stage == STAGE_DUEL_CARDS0DONE || 
            duel.stage == STAGE_DUEL_CARDS1DONE ){
            if (!hdc[STAGE_DUEL_CARDSCREATED]){
                hdc[STAGE_DUEL_CARDSCREATED] = true;
                getPlayerCards();
            }
        }
        // Подведение итогов
        if (duel.stage >= STAGE_DUEL_FINISH && duel.stage < STAGE_DUEL_DEAD){
            if (!hdc[STAGE_DUEL_FINISH]){
                hdc[STAGE_DUEL_FINISH] = true;
                $(`#game-order .value, #circles-left .value,
                    #time-left .value, #exchan-left .value`).html('---');
                $("#game-phase .value span").html("Итоги");
                $(".card-arrow").removeClass("active");
                getOppCards();
            }
        }
        // Вывод итогов игры
        if (duel.stage >= STAGE_DUEL_WIN0 && duel.stage < STAGE_DUEL_DEAD){
            $("#finishlog")
                .html(finishlog)
                .addClass("active");
            $(".card").addClass("finish");
            // showAlert(finishlog);
            getWinReason();
        }
    }
    function syncStepHandler(step){
        if ("stage" in step){
            step.stage = Number(step.stage);
            window.duel_data.step_stage = step.stage;
        }
        else step.stage = window.duel_data.step_stage;

        // Если передано знач. таймера
        if ("ping" in step) $("#time-left .value").html(window.duel_data.step_time - step.ping);
        else $("#time-left .value").html("---");

        // Если передано знач. остатка обменов
        if ("exch" in step){
            if (step.exch > 0) $("#exchan-left .value").html(step.exch);
            else $("#exchan-left .value").html("---");
        }

        // Если переданы знач. смещений карт на поле
        // !Передаются только противнику, по отношению к ходу 
        if (("swp0" in step) && ("swp1" in step)){
            soundMaster.sfx.play({src:"cards/"+sfx_take.random()});
            if ((step.swp0 >= 7) && (step.swp1 >= 7)){
                step.swp0 -= 7; step.swp1 -= 7;
                var card = $(`.card[owner=0][pos=${step.swp0}]`).attr(
                    "src", "assets/img/game/cards/cover.png"
                );
                moveCard(card, 1, step.swp1);
            }
            else {
                swapCards(1, step.swp0, step.swp1);
            }
            // if ((step.swp0 >= 0) && (step.swp1 >= 0)){
            //     // Было вытягивание
            //     soundMaster.sfx.play({src:"cards/"+sfx_take.random()});
            //     if ((step.exch == 2) || (window.duel_data.stage >= STAGE_DUEL_FINISH)){
            //         // step.exch=2 выполняется после каждого STAGE_STEP_PULL,
            //         // а STAGE_DUEL_FINISH позволит передать финальное смещение карт

            //     }
            //     // Был обмен
            //     else {
            //     }
            // }
        }

        if ("sel" in step){
            $(".card-arrow").removeClass("active");
            if (step.sel >= 0){
                if (step.sel != window.duel_data.selected_card){
                    var target = null;
                    if (step.stage == STAGE_STEP_PROTECT){
                        target = $("#opp-cards");
                        if (isMyTurn(step.order)){
                            target = $("#self-cards");
                        }
                    }
                    else {
                        target = $("#self-cards");
                        if (isMyTurn(step.order)){
                            target = $("#opp-cards");
                        }
                    }
                    target.find(`.card-slot[pos=${step.sel}]`).siblings(".card-arrow").addClass("active");
                }
            }
        }
        
        if ("order" in step){
            if (step.order == -1) $("#game-order .value").html("---");
            else {
                if (isMyTurn(step.order)){
                    $("#game-order .value").html("Твой");
                }
                else {
                    $("#game-order .value").html("Чужой");
                }
            }
            
            if (window.duel_data.step_order != step.order){
                window.duel_data.step_order = step.order;
                hsc.clear();
                offCardsClickEvent();
                attachCardsClickEvent();
            }
        }
    

        switch(step.stage) {
            // case SYNC_COMMON_ERROR:
            //     return syncCommonError();
            //     break;
            case STAGE_STEP_PROTECT:
                if (hsc[STAGE_STEP_PROTECT]) break;
                hsc[STAGE_STEP_PROTECT] = true;

                $("#game-phase .value span").html("Защита");
                if (isMyTurn(step.order)){
                    if (step.exch == 2){
                        $("#game-phase .pass").addClass("active");
                    }
                    $(".card[owner=0]").addClass("active");
                }
                break;
            case STAGE_STEP_SELECT:
                if (hsc[STAGE_STEP_SELECT]) break;
                hsc[STAGE_STEP_SELECT] = true;

                $("#game-phase .value span").html("Захват");
                if (isMyTurn(step.order)){
                    $(".card[owner=1]").addClass("active");
                }
                break;
            case STAGE_STEP_PULL:
                if (hsc[STAGE_STEP_PULL]) break;
                hsc[STAGE_STEP_PULL] = true;

                $("#game-phase .value span").html("Вытягивание");
                if (isMyTurn(step.order)){
                    $(".card[owner=1]").addClass("active");
                }
                break;
        }
    }
}

function isMyTurn(order){
    return order == window.user.player_order;
}
function handleGameOpponent(data){
    opponent_data = data;

    $("#opp-avatar").attr(
        "src", opponent_data.avatar_path+"?timestamp=" + new Date().getTime()
    );
    $("#opp-name .value").html(opponent_data.name);
}
function getGameOpponent(){
    apiRequest({
        route: "duel",
        op: "get_opponent"
    }, {
        success: function(data){
            if (data.result){
                handleGameOpponent(data.user_data);
                return;
            }
            sseStopDuelListener();
            if (!("msg" in data)) data.msg = "Ошибка данных соперника";
            showAlert(data.msg, {
                okBtn: "Покинуть дуэль",
                ok: exitGame
            });
        },
        error: function(){
            sseStopDuelListener();
            showAlert("Ошибка данных соперника", {
                okBtn: "Покинуть дуэль",
                ok: exitGame
            });
        }
    });
}
function exitGame(){
    flags.clickLocked = true;
    sseStopDuelListener();
    apiRequest({
        route: "duel",
        op: "exit"
    }, {
        success: function(data){
            if (data.result){
                setGameSubStage(null, false);
                return setGameStage(1, {
                    halfpartCallback: clearGameField
                });
            }
            sseStartDuelListener();
            if ("msg" in data) showAlert(data.msg);
            else showAlert("Ошибка выхода из комнаты");
        },
        error: function(){
            sseStartDuelListener();
            setGameSubStage(null, false);
        },
        complete: function(){
            flags.clickLocked = false;
        }
    });
}

{//phase actions
    function phasePass(){
        flags.clickLocked = true;
        if (swap_cards.length > 0){
            var card = swap_cards[0];
            var pos = card.attr("pos");
            moveCard(card, 0, pos);
            swap_cards = [];
        }

        offCardsClickEvent();
        apiRequest({
            route: "card",
            op: "pass"
        }, {
            success: function(data){
                if (data.result){
                    return console.log("Phase passed")
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка пропуска");
            },
            complete: function(){
                flags.clickLocked = false;
            }
        });
    }
    function phaseSwap(p0, p1, behavior = {}){
        flags.clickLocked = true;
        offCardsClickEvent();
        return apiRequest({
            route: "card",
            op: "swap",
            p0: p0,
            p1: p1
        }, {
            success: function(data){
                if (data.result){
                    if ("success" in behavior) behavior.success();
                    return console.log("Cards swapped")
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка перемещения карт");
                if ("fail" in behavior) behavior.fail();
            },
            complete: function(){
                flags.clickLocked = false;
            }
        });
    }
    function phaseSelect(p0, behavior = {}){
        flags.clickLocked = true;
        offCardsClickEvent();
        return apiRequest({
            route: "card",
            op: "select",
            p0: p0
        }, {
            success: function(data){
                if (data.result){
                    if ("success" in behavior) behavior.success();
                    return console.log("Card selected")
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка выбора карты");
                if ("fail" in behavior) behavior.fail();
            },
            complete: function(){
                flags.clickLocked = false;
            }
        });
    }
    function phasePull(p0, behavior = {}){
        flags.clickLocked = true;
        offCardsClickEvent();
        return apiRequest({
            route: "card",
            op: "pull",
            p0: p0
        }, {
            success: function(data){
                if (data.result){
                    if ("success" in behavior) behavior.success(data.pull_data);
                    return console.log("Card pulled")
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка вытягивания карты");
                if ("fail" in behavior) behavior.fail();
            },
            complete: function(){
                flags.clickLocked = false;
            }
        });
    }
}

{//CARDS
    function handleWinReason(arrows){
        $(".card-arrow").removeClass("active");

        self_cards = $("#self-cards");
        opp_cards = $("#opp-cards");
        if (isMyTurn(1)){
            opp_cards = $("#self-cards");
            self_cards = $("#opp-cards");
        }

        for (var i = 0; i < arrows[0].length; i++) {
            self_cards
                .find(`.card-slot[pos=${arrows[0][i]}]`)
                .siblings(".card-arrow")
                .addClass("active");
        }
        for (var i = 0; i < arrows[1].length; i++) {
            opp_cards
                .find(`.card-slot[pos=${arrows[1][i]}]`)
                .siblings(".card-arrow")
                .addClass("active");
        }
    }
    function getWinReason(){
        apiRequest({
            route: "card",
            op: "get_win_reason"
        }, {
            success: function(data){
                if (data.result){
                    handleWinReason(JSON.parse(data.win_reason));
                    return;
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка получения причины выигрыша");
            }
        });
    }
    function handleOpponentCards(cards){
        for (var i = 0; i < cards.length; i++) {
            if (cards[i] === null){
                continue;
            }
            $(`.card[owner=1][pos=${i}]`).attr(
                "src", `assets/img/game/cards/${cards[i]}.png`
            );
        }
        offCardsClickEvent();
    }
    function getOppCards(){
        apiRequest({
            route: "card",
            op: "get_opp"
        }, {
            success: function(data){
                if (data.result){
                    handleOpponentCards(JSON.parse(data.opp_cards));
                    return;
                }
                if ("msg" in data) showAlert(data.msg);
                else showAlert("Ошибка получения карт соперника");
            }
        });
    }
    function initOpponentFakeCards(cards){
        var target = $("#cards-dynamic");
        for (var i = 0; i < cards.length; i++) {
            if (cards[i] === null){
                continue;
            }
            target.append(`
                <img class="card" owner=1 pos=${i} src="assets/img/game/cards/cover.png">
            `)
        }
    }
    function initPlayerCards(cards){
        window.user.json_cards = cards;
        
        var target = $("#cards-dynamic");
        for (var i = 0; i < cards.length; i++) {
            if (cards[i] === null){
                continue;
            }
            target.append(`
                <img class="card" owner=0 pos=${i} src="assets/img/game/cards/${cards[i]}.png">
            `)
        }
        spreadOutCards();

        if (window.duel_data.stage == STAGE_DUEL_CARDSCREATED ||
            window.duel_data.stage == STAGE_DUEL_CARDS0DONE || 
            window.duel_data.stage == STAGE_DUEL_CARDS1DONE ){
            apiRequest({
                route: "duel",
                op: "cards_done"
            }, {
                success: function(data){
                    if (!data.result){
                        if ("msg" in data) showAlert(data.msg);
                        else showAlert("Ошибка подтверждения карт");
                    }
                }
            });
        }
    }
    function getPlayerCards(){
        apiRequest({
            route: "card",
            op: "get_self"
        }, {
            success: function(data){
                if (data.result){
                    initOpponentFakeCards(data.opp_fake_cards, true);
                    initPlayerCards(JSON.parse(data.self_cards), true);
                    return;
                }
                sseStopDuelListener();
                if (!("msg" in data)) data.msg = "Ошибка получения карт";
                showAlert(data.msg, {
                    okBtn: "Покинуть дуэль",
                    ok: exitGame
                });
            },
            error: function(){
                sseStopDuelListener();
                showAlert("Ошибка получения карт", {
                    okBtn: "Покинуть дуэль",
                    ok: exitGame
                });
            }
        });
    }
    function initCards(){
        var target = $("#opp-cards");
        var pos = 0;
        var arrow = "up";
        for (var i = 0; i < 14; i++, pos++) {
            if (i == 7){
                target = $("#self-cards");
                pos = 0;
                arrow = "down";
            }
            if (pos == 6){
                target.prepend(`
                    <div class="card-item">
                        <div class="card-slot" pos=${pos}>
                        </div>
                        <img class="card-arrow" src="assets/img/game/${arrow}.png">
                    </div>
                `)
            }
            else {
                target.append(`
                    <div class="card-item">
                        <div class="card-slot" pos=${pos}>
                        </div>
                        <img class="card-arrow" src="assets/img/game/${arrow}.png">
                    </div>
                `)
            }
        }
    }
    function spreadOutCards(){
        soundMaster.sfx.play({src:"cards/"+sfx_deal.random()});
        $(".card")
            .removeClass("inactive")
            .each(function(index) {
                var owner = $(this).attr("owner");
                var pos = $(this).attr("pos");
                moveCard($(this), owner, pos);
            });
    }
    function spreadInCards(hide = false){
        if (hide){
            $(".card").addClass("inactive")
        }
        $(".card").each(function(index) {
            $(this).css({
                left: "calc(856px*var(--bgr))",
                top: "calc(383px*var(--bgr))"
            });
        });
    }
    function selectSelfCard(card){
        var pos = card.attr("pos");
        if (swap_cards.length > 0){
            if (swap_cards[0][0] == card[0]){
                soundMaster.sfx.play({src:"cards/"+sfx_choose.random()});
                moveCard(card, 0, pos);
                swap_cards = [];
                return;
            }
        }
        swap_cards.push(card);
        if (swap_cards.length >= 2) return;
        
        soundMaster.sfx.play({src:"cards/"+sfx_choose.random()});
        var target = "#self-cards";
        var slot = $(`${target} .card-slot[pos=${pos}]`);
        var slotPos = slot.position();
        var targetPos = $(target).position();
        slotPos = {
            left: (targetPos.left+slotPos.left)/bgratio,
            top: (targetPos.top+slotPos.top)/bgratio
        }
        card.css({
            left: `calc(${slotPos.left}px*var(--bgr))`,
            top: `calc(${slotPos.top}px*var(--bgr))`
        });
    }
    function swapCards(owner, p0, p1){
        var c0 = $(`.card[owner=${owner}][pos=${p0}]`);
        var c1 = $(`.card[owner=${owner}][pos=${p1}]`);
        moveCard(c0, owner, p1);
        moveCard(c1, owner, p0);
    }
    function moveCard(card, owner, pos){
        card.attr("owner", owner);
        card.attr("pos", pos);
        var target = "#self-cards";
        var hoff = 40;
        if (owner == 1){
            target = "#opp-cards";
            hoff = 0;
        }
        var slot = $(`${target} .card-slot[pos=${pos}]`);
        if (slot.length == 0) return false; 

        var slotPos = slot.position();
        var targetPos = $(target).position();
        slotPos = {
            left: (targetPos.left+slotPos.left)/bgratio,
            top: (targetPos.top+slotPos.top)/bgratio + hoff
        }
        // ANIM HERE?
        card.css({
            left: `calc(${slotPos.left}px*var(--bgr))`,
            top: `calc(${slotPos.top}px*var(--bgr))`
        });
        return true;
    }
    function onSelfCardsClick(){
        if (flags.clickLocked) return;
        if (!$(this).hasClass("active")) return;
        selectSelfCard($(this));
        if (swap_cards.length >= 2){
            soundMaster.sfx.play({src:"cards/"+sfx_take.random()});
            var p0 = swap_cards[0].attr("pos");
            var p1 = swap_cards[1].attr("pos");
            swap_cards = [];
            phaseSwap(p0, p1, {
                success: function(){
                    swapCards(0, p0, p1);
                },
                fail: function(){
                    moveCard($(`.card[owner=0][pos=${p0}]`), 0, p0);
                }
            });
        }
    }
    function onOppCardsClick(){
        if (flags.clickLocked) return;
        if (!$(this).hasClass("active")) return;
        // selectOppCard($(this));
        var pos = $(this).attr("pos");
        if (window.duel_data.step_stage == STAGE_STEP_SELECT){
            phaseSelect(pos);
        }
        else {
            phasePull(pos, {
                success: function(pull_data){
                    if (pull_data.p0 < 0){
                        pull_data.p0 *= -1; pull_data.p1 *= -1;
                    }
                    pull_data.p0 -= 8; pull_data.p1 -= 8;
                    soundMaster.sfx.play({src:"cards/"+sfx_take.random()});
                    var card = $(`.card[owner=1][pos=${pull_data.p0}]`).attr(
                        "src", `assets/img/game/cards/${pull_data.card}.png`
                    );
                    moveCard(card, 0, pull_data.p1);
                }
            });
        }
    }
    function offCardsClickEvent(){
        $(".card, #game-phase .pass").removeClass("active");
        $(".card[owner=0]").off("click");
        $(".card[owner=1]").off("click");
    }
    function attachCardsClickEvent(){
        $(".card").removeClass("active");
        $(".card[owner=0]").click(onSelfCardsClick);
        $(".card[owner=1]").click(onOppCardsClick);
    }
}
function clearGameField(){
    hdc.clear();
    hsc.clear();
    offCardsClickEvent();
    $(".card-arrow, #finishlog").removeClass("active")
    $(".card").remove();
    $("#stage-game > .bg").removeClass("ingame");
    handleGameOpponent({
        name: "---",
        avatar_path: "assets/img/avatars/default.png"
    });
    opponent_data = null;
    window.duel_data.clear();
    $("#game-phase .value span").html("---");
    $(".game-info").not("#game-phase").children(".value").html("---");
    spreadInCards(true);
}
function resizeGameElements(){}


//--------------------------------------------------------------------------------------------------
$(document).ready(function(){
    initCards();
    clearGameField();
    $(window).trigger("resize");
});
$(window).resize(function(){
    resizeGameElements();
});
$(window).on("beforeunload", function() {
	if (sseDuelListenConnection){
		sseDuelListenConnection.close();
	}
});
$("#game-phase .pass").click(function(){
    if (!$(this).hasClass("active")) return;
    if (flags.clickLocked) return;
    flags.clickLocked = true;
    phasePass();
})
$("#game-exit").click(function(){
    if (flags.clickLocked) return;
    flags.clickLocked = true;
    var confirmMsg = "Игра еще не окончена.<br>Вы уверены, что хотите покинуть дуэль?";
    if (window.duel_data.stage >= STAGE_DUEL_WIN0){
        confirmMsg = "Игра окончена.<br>Покинуть дуэль?";
    }
    showConfirm(confirmMsg, {
        accept: exitGame
    });
});
$("#game-settings").click(function(){
    if (flags.clickLocked) return;
    $("#substage-settings").addClass("out-auth");
    setGameSubStage("settings");
});