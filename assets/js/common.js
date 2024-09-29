"use strict";

let gamestages = {
    tags: ["auth", "menu", "game"],
    entryFunc: [null, null, null],
    soundFunc: [null, null, null]
}
let currentstage = 0;
let mouse = [0, 0];
let flags = {
    clickLocked:    false,
    audioAllowed:   false,
    strokePolyPath: false,
    authLogining:   false,
    logSSEData:     true
};
let bgratio = 1;
let contentratio = 1;
let contentWidth = null;
let contentHeight = null;
anime.suspendWhenDocumentHidden = false;


// УПРАВЛЯЕМЫЕ СЕРВЕРОМ НИЖЕ
window.user = {
    user_id:        null,
    name:           null,
    email:          null,
    role:           null,
    avatar_path:    null,
    player_order:   null,
    json_cards:     null,
    set: function(data){
        var keys = Object.keys(window.user);
        $.each(data, function(index, value) {
            if (keys.includes(index)){
                window.user[index] = value;
            }
        });
    },
    clear: function(){
        $.each(Object.keys(this), function(index, value) {
            if (typeof window.user[value] != 'function'){
                window.user[value] = null;
            }
        });
    }
};
window.duel_data = {
    duel_id: -1,
    step_time: -1,
    stage: -1,
    duel_data_id: -1,
    opponent_id: -1,
    selected_card: -1,
    step_order: -1,
    step_stage: -1,
    set: function(data){
        var keys = Object.keys(window.duel_data);
        $.each(data, function(index, value) {
            if (keys.includes(index)){
                window.duel_data[index] = value;
            }
        });
    },
    clear: function(){
        $.each(Object.keys(this), function(index, value) {
            if (typeof window.duel_data[value] != 'function'){
                window.duel_data[value] = -1;
            }
        });
    }
};
window.tourn_data = {
    tourn_id: -1,
    set: function(data){
        var keys = Object.keys(window.tourn_data);
        $.each(data, function(index, value) {
            if (keys.includes(index)){
                window.tourn_data[index] = value;
            }
        });
    },
    clear: function(){
        $.each(Object.keys(this), function(index, value) {
            if (typeof window.tourn_data[value] != 'function'){
                window.tourn_data[value] = -1;
            }
        });
    }
};

// GET RANDOM ARRAY ELEMENT 
Array.prototype.random = function(){
    return this[Math.floor((Math.random()*this.length))];
}

// SOUND MASTER BELOW
function _loopAudio(){
    this.currentTime = 0;
    this.play();
}
function _defaultAudio(options){
    // path, looped, volume
    this.cookie = options.cookie;
    this.path = options.path;
    this.looped = null;
    this.uservolume = null;
    this.audio = new Audio();

    this.audio.addEventListener("error", function(){
        console.error("SoundMaster: audio load error");
    }, false);
    this.stop = function(){
        this.audio.pause();
        this.audio.currentTime = 0.0;
    };
    this.pause = function(){
        this.audio.pause();
    };
    this.play = function(options = {}){
        if (!("looped" in options)) options.looped = this.looped;
        // options.looped = options.looped || looped;
        if (this.audio.volume == 0){
            this.audio.volume = this.uservolume;
        }
        return soundMaster._play(this, options);
    };
    this.volume = function(value = 1){
        this.uservolume = value;
        this.audio.volume = this.uservolume;
        Cookies.set(this.cookie, this.audio.volume);
    };
    this.fadein = function(options = {}){
        options.direction = "reverse";
        if (options.src){
            options.loaded = this.fadeout.bind(this, options);
            return this.play(options);
        }
        
        if (this.audio.paused) this.audio.play();
        return this.fadeout(options);
        // options.loaded();
        // if (this.audio.paused) this.audio.play();
        // options.direction = "reverse";
    }
    this.fadeout = function(options = {}){
        return anime({
            targets: this.audio,
            direction: options.direction || "normal",
            duration: options.duration || 1000,
            volume: [this.uservolume, 0],
            easing: "linear",
            complete: function(anim){
                if (this.direction == "normal"){
                    this.animatables[0].target.pause();
                }
            }
        });
    }
    this.setLooped = function(looped = null, write = true){
        if (looped === null) looped = this.looped;

        if (write) this.looped = looped;
        if (looped){
            if (typeof this.audio.loop == "boolean"){
                this.audio.loop = true;
            }
            else {
                this.audio.addEventListener("ended", _loopAudio, false);
            }
        }
        else {
            if (typeof this.audio.loop == "boolean"){
                this.audio.loop = false;
            }
            else {
                this.audio.removeEventListener("ended", _loopAudio, false);
            }
        }
    }
    this.setLooped(options.looped);
    this.volume(options.volume);
}
let soundMaster = {
    ready: false,
    sfx: null,
    music: null,
    ambience: null,
    init: function(options = {}){
        options.path = options.path || {};

        options.loop = options.loop || {};
        if (!("sfx" in options.loop)) options.loop.sfx = false;
        if (!("music" in options.loop)) options.loop.music = true;
        if (!("ambience" in options.loop)) options.loop.ambience = true;

        if (options.volume){
            if (!("sfx" in options.volume)) options.volume.sfx = 0.5;
            if (!("music" in options.volume)) options.volume.music = 0.5;
            if (!("ambience" in options.volume)) options.volume.ambience = 0.5;
        }
        else {
            options.volume = {
                sfx: Cookies.get("vsfx") || 0.5,
                music: Cookies.get("vmus") || 0.5,
                ambience: Cookies.get("vamb") || 0.5,
            }
        }

        this.sfx = new _defaultAudio({
            cookie: "vsfx",
            path: options.path.sfx || "sfx", 
            looped: options.loop.sfx,
            volume: options.volume.sfx
        });
        $("#sfx-volume-input").val(options.volume.sfx*100);
        this.music = new _defaultAudio({
            cookie: "vmus",
            path: options.path.music || "music", 
            looped: options.loop.music,
            volume: options.volume.music
        });
        $("#music-volume-input").val(options.volume.music*100);
        this.ambience = new _defaultAudio({
            cookie: "vamb",
            path: options.path.ambience || "ambience", 
            looped: options.loop.ambience,
            volume: options.volume.ambience
        });
        $("#ambience-volume-input").val(options.volume.ambience*100);
        this.ready = true;
        console.log("SoundMaster ready");
    },
    fadein: function(options){
        this.sfx.fadein(options);
        this.music.fadein(options);
        this.ambience.fadein(options);
    },
    fadeout: function(options){
        this.sfx.fadeout(options);
        this.music.fadeout(options);
        this.ambience.fadeout(options);
    },
    stop: function(){
        this.sfx.stop();
        this.music.stop();
        this.ambience.stop();
    },
    pause: function(){
        this.sfx.audio.pause();
        this.music.audio.pause();
        this.ambience.audio.pause();
    },
    _play: function(target, options){
        if (!this.ready || !flags.audioAllowed) return false;
        if (!options.src || (target.src == options.src)) return target.audio.play();

        target.setLooped(options.looped, false);

        if (("ended" in options) && !options.looped && !target.looped){
            target.audio.addEventListener("ended", options.ended, {
                once: true, capture:false
            });
        }

        target.audio.src = `assets/sound/${target.path}/${options.src}`;
        target.audio.addEventListener("loadeddata", function(){
            this.play();
            if (options.loaded) options.loaded();
        }, {once: true, capture: false});
        return true;
    }
}


//--------------------------------------------------------------------------------------------------
function apiRequest(data, options={}){
    options = Object.assign({
        method: "GET",
        url: "api/",
        dataType: "json",
        data: data,
    }, options)

    if (!("processData" in options)){
        options.processData = true;
    }
    if (options.method == "POST"){
        if (options.processData){
            options.data = encodeURIComponent(JSON.stringify(options.data));
        }
    }
    return $.ajax(options);
}
//--------------------------------------------------------------------------------------------------


// CONFIRM BOX 
function showConfirmBox(msg, zIndex = 10){
    $("#substage-confirm").css({"z-index": zIndex});
    $("#confirm-content").html(msg);
    $("#actions-confirm").removeClass("single");
    var actions = $("#actions-confirm .action");
    actions.off("click").removeClass("active");
    actions.filter(".yes").html("Да");
    actions.filter(".no").html("Нет");
    actions.filter(".ok").html("Ок");
    return actions;
}
function showConfirm(msg, options = {}){
    options.zIndex =  options.zIndex || 10;
    var actions = showConfirmBox(msg, options.zIndex);
    actions.filter(".yes, .no").addClass("active");
    if ("yesBtn" in options) actions.filter(".yes").html(options.yesBtn);
    if ("noBtn"  in options) actions.filter(".no").html(options.noBtn);

    if ("accept" in options){
        actions.filter(".yes").click(function(){
            if (flags.clickLocked) return;
            options.accept();
        });
    }
    actions.filter(".no").click(function(){
        if (flags.clickLocked) return;
        if ("deny" in options){
            return setGameSubStage("confirm", false).finished.then(options.deny);
        }
        return setGameSubStage("confirm", false);
    });
    return setGameSubStage("confirm");
}
function showAlert(msg, options = {}){
    options.zIndex =  options.zIndex || 10;
    var actions = showConfirmBox(msg, options.zIndex);
    $("#actions-confirm").addClass("single");
    actions.filter(".ok").addClass("active");
    if ("okBtn" in options) actions.filter(".ok").html(options.okBtn);

    actions.filter(".ok").click(function(){
        if (flags.clickLocked) return;
        if ("ok" in options){
            return setGameSubStage("confirm", false).finished.then(options.ok);
        }
        return setGameSubStage("confirm", false);
    });
    return setGameSubStage("confirm");
}
function showMessage(msg){
    options.zIndex =  options.zIndex || 10;
    var actions = showConfirmBox(msg, options.zIndex);
    return setGameSubStage("confirm");
}

function checkFullscreen(){
    var fs = !((document.fullScreenElement !== undefined && document.fullScreenElement === null) 
        || (document.msFullscreenElement !== undefined && document.msFullscreenElement === null)
        || (document.mozFullScreen !== undefined && !document.mozFullScreen)
        || (document.webkitIsFullScreen !== undefined && !document.webkitIsFullScreen));
    var fs2 = (window.innerWidth == screen.width && window.innerHeight == screen.height);
    fs = fs || fs2;
    if (!fs2){
        fs = false;
    }

    var actions = $("#edit-screen-size .action").removeClass("active");
    if (fs){
        actions.filter(".yes").addClass("active");
    }
    else{
        actions.filter(".no").addClass("active");
    }
    return fs;
}
function requestFullscreen(){
    $("#edit-screen-size .action")
        .removeClass("active")
        .filter(".yes")
        .addClass("active");

    var elem = document.body;
    if (elem.requestFullScreen) {
        elem.requestFullScreen();
    } else if (elem.mozRequestFullScreen) {
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullScreen) {
        elem.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    }
}
function cancelFullscreen(){
    $("#edit-screen-size .action")
        .removeClass("active")
        .filter(".no")
        .addClass("active");
    if (document.cancelFullScreen) {
        document.cancelFullScreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitCancelFullScreen) {
        document.webkitCancelFullScreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }
}
function toggleFullScreen(){
    if (checkFullscreen()) {
        requestFullscreen();
    } 
    else {
        cancelFullscreen();
    }
}
function onEnter(element, callback){
    element.keydown(function(event){
        var kcode = (event.keyCode ? event.keyCode : event.which);
        if (kcode == 13){
            callback();
        }
    });
}
function blink(options = {}){
    flags.clickLocked = true;
    //  dir = 1, duration = 900, delay = 0
    return anime.timeline({
        endDelay: options.delay || 0,
        duration: options.duration || 900,
        direction: options.direction || "normal",
        easing: "easeInOutQuad",
        complete: function(){
            flags.clickLocked = false;
        }
    })
    .add({targets: "#blink-up",  top: [`-${1080*bgratio}px`, 0]}, 0)
    .add({targets: "#blink-down",  bottom: [`-${1080*bgratio}px`, 0]}, 0);
}
function setGameStage(newstage, options={}){
    var otherPartFunc = function(){
        currentstage = newstage;
        $(".game-stage")
            .removeClass("active")
            .addClass("inactive");
        $("#stage-"+gamestages.tags[currentstage])
            .removeClass("inactive")
            .addClass("active");
        if ("halfpartCallback" in options){
            if (options.halfpartCallback !== null){
                options.halfpartCallback();
            }
        }
        if ("entryFuncArg" in options){
            gamestages.entryFunc[currentstage](options.entryFuncArg);
        }
        else {
            gamestages.entryFunc[currentstage]();
        }
        return blink({direction: "reverse"});
    };

    if ("halfpart" in options){
        if (options.halfpart == 1){
            otherPartFunc = null;
        }
        else {
            return otherPartFunc();
        }
    }
    
    soundMaster.fadeout({duration: 900});
    if (otherPartFunc !== null){
        return blink().finished.then(otherPartFunc);
    }
    return blink();
}
function setGameSubStage(selector, open = true){
    flags.clickLocked = true;
    var direction = "reverse";
    if (selector !== null) selector = `#substage-${selector}`;
    if (open){
        direction = "normal";
        $(selector)
            .removeClass("inactive")
            .addClass("active");
    }
    $(".game-substage").not(selector)
        .removeClass("active")
        .addClass("inactive")
        .css({opacity: 0});
        
    return anime({
        targets: selector, 
        direction: direction,
        opacity: [0, 1],
        duration: 300,
        easing: "linear",
        complete: function(){
            if (direction == "reverse"){
                $(selector)
                    .removeClass("active")
                    .addClass("inactive");
            }
            flags.clickLocked = false;
        }
    })
}
function calculateRatio(){
    contentratio = 1920/1080;
    contentWidth = Math.round($(document.body).height()*contentratio);
    contentHeight = $(document.body).height();
    if (contentWidth > $(document.body).width()){
        contentratio = 1080/1920;
        contentWidth = $(document.body).width();
        contentHeight = Math.round($(document.body).width()*contentratio);
    }
    bgratio = contentWidth/1920;
    document.body.style.setProperty("--bgr", bgratio);

    if ($(document.body).width() <= 800
        || $(document.body).height() <= 400){
        document.body.style.setProperty("--cursor", "auto");
    }
    else {
        document.body.style.setProperty("--cursor", "url(assets/img/cursor.cur), auto");
    }
}
function resizeCommonElements(){
    $("#content, #blink-down, #blink-up").css({
        width: contentWidth+"px",
        height: contentHeight+"px"
    });

    if ($("#blink-up").css("top") != "0px"){
        $("#blink-up").css({top: `-${1080*bgratio}px`});
        $("#blink-down").css({bottom: `-${1080*bgratio}px`});
    }
    
    $(".range").each(function(index){
        var width = ($(this).val()/100*($(this).width()-(30*bgratio)));
        $(this)
            .siblings(".range-progress")
            .css({
                width: width+"px",
                top: `calc(${$(this).position().top}px + 3px*var(--bgr))`,
                left: `calc(${$(this).position().left}px + 3px*var(--bgr))`
            })
    });
}
function chooseInitialStage(){
    currentstage = 0;
    if (window.user.user_id !== null){
        currentstage = 1;
        if (window.duel_data.duel_id > -1
            || window.tourn_data.tourn_id > -1){
            currentstage = 2;
        }
    }

    $("#stage-"+gamestages.tags[currentstage]).addClass("active");
    $("#blink-up").css({top: 0});
    $("#blink-down").css({bottom: 0});
    gamestages.entryFunc[currentstage]({}, true);
    blink({direction: "reverse"});
}
function init(){
    $(window).trigger("resize");
    $("html").css("cursor: url('assets/img/cursor.cur'), auto");
    soundMaster.init();
    chooseInitialStage();
}


//--------------------------------------------------------------------------------------------------
$(window).resize(function(){
    checkFullscreen();
    calculateRatio();
    resizeCommonElements();
});
$(window).click(function(){
    flags.audioAllowed = true;
    if (!gamestages.soundFunc[currentstage]()) return;
    $(window).off("click");
});
//--------------------------------------------------------------------------------------------------
$(document).ready(function(){
    init();

    $("#settings-back.back-button").click(function(){
        if (flags.clickLocked) return;
        setGameSubStage("settings", false).finished.then(function(){
            $("#substage-settings").removeClass("out-auth");
        });
    });
    $("#edit-screen-size .action").click(function(){
        if (flags.clickLocked) return;
        if ($(this).hasClass("yes")){
            return requestFullscreen();
        }
        return cancelFullscreen();
    });
    $(".volume-test").click(function(){
        soundMaster.sfx.play({src:"test.ogg", ended:function(){
            console.log("Sound test");
        }})
    });
    $("#sfx-volume-input").on("input propertychange", function(){ 
        soundMaster.sfx.volume($(this).val()/100);
    });
    $("#music-volume-input").on("input propertychange", function(){ 
        soundMaster.music.volume($(this).val()/100);
    });
    $("#ambience-volume-input").on("input propertychange", function(){ 
        soundMaster.ambience.volume($(this).val()/100);
    });
    $(".range").on("input", function(){
        var progress =  $(this).siblings(".range-progress");
        // var width = `calc(${$(this).val()}% - 3px*var(--bgr))`;
        var width = ($(this).val()/100*($(this).width()-(30*bgratio)));
        progress.css({
            width: width+"px"
        });
    }); 
});
