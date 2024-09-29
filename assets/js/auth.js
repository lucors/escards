gamestages.entryFunc[0] = authEntry;
gamestages.soundFunc[0] = authInitSound;

function authEntry(){
    authInitSound();
    toggleCreateAccount(true);
    // setTimeout(authInitSound, 900.2);
}
function authInitSound(){
    return soundMaster.ambience.fadein({src: "auth_ambience.ogg", duration: 900});
}
function toggleCreateAccount(forced = null){
    flags.authLogining = !flags.authLogining;
    if (forced !== null) flags.authLogining = forced;
    if (flags.authLogining){
        $("#auth-content, #auth-substrate, #auth-login, #auth-fail-msg")
            .removeClass("sign")
            .addClass("auth");
        $("#auth-substrate").attr("src", "assets/img/auth/auth-substrate.png");
        // $("#auth-name, #auth-pass2").hide();
        // $("#auth-forgot").show();
        $("#auth-content .bc-header").text("Авторизация");
        $("#auth-create").text("Создать аккаунт");
        // $("#auth-content .bc-block.fields").css({
        //     height: "calc(110px*var(--bgr))"
        // });
        return;
    }
    $("#auth-content, #auth-substrate, #auth-login, #auth-fail-msg")
        .removeClass("auth")
        .addClass("sign");     
    $("#auth-substrate").attr("src", "assets/img/auth/sign-substrate.png");       
    // $("#auth-name, #auth-pass2, #auth-forgot").show();
    // $("#auth-forgot").hide();
    $("#auth-content .bc-header").text("Регистрация");
    $("#auth-create").text("У меня есть аккаунт");
    // $("#auth-content .bc-block.fields").css({
    //     height: "calc(180px*var(--bgr))"
    // });
}

function handleLoginData(data){
    if (data.duel_id == null) data.duel_id = -1;
    if (data.tourn_id == null) data.tourn_id = -1;

    window.user.set(data);
}
function authLogin(){
    var data = {
        email: $("#auth-email input").val(),
        pass: $("#auth-pass input").val()
    }
    var errorTargetName = null;
    try {
        if (!data.email){
            errorTargetName = "email";
            throw new Error("Эл. Почта");
        }
        else if (!data.pass){
            errorTargetName = "pass";
            throw new Error("Пароль");
        }
    }
    catch (error){
        anime({
            targets: `#auth-${errorTargetName}`,
            scale: [
                {value: 1},
                {value: 0.85},
                {value: 1}
            ],
            duration: 400,
            easing: "easeInOutQuad"
        });
        $("#auth-fail-msg").html(`Заполните поле "${error.message}"`);
        flags.clickLocked = false;
        return;
    }

    $("#auth-fail-msg").html("");
    apiRequest({
        route: "auth",
        op: "login",
        email: data.email,
        pass: data.pass
    }, {
        method: "POST",
        success: function(data){
            if (data.result){
                handleLoginData(data.user_data);
                return setGameStage(1);
            }
            if ("msg" in data) $("#auth-fail-msg").html(data.msg);
            else $("#auth-fail-msg").html("Ошибка входа");
            flags.clickLocked = false;
        },
        error: function(){
            $("#auth-fail-msg").html("Ошибка входа");
            flags.clickLocked = false;
        }
        // complete: function(){
        //     flags.clickLocked = false;
        // }
    });
}
function authCreate(){
    var data = {
        name: $("#auth-name input").val(),
        email: $("#auth-email input").val(),
        pass: $("#auth-pass input").val(),
        pass2: $("#auth-pass2 input").val()
    }
    var errorTargetName = null;
    try {
        if (!data.name){
            errorTargetName = "name";
            throw new Error("Имя");
        }
        else if (!data.email){
            errorTargetName = "email";
            throw new Error("Эл. Почта");
        }
        else if (!data.pass){
            errorTargetName = "pass";
            throw new Error("Пароль");
        }
        else if (!data.pass2){
            errorTargetName = "pass2";
            throw new Error("Повторение пароля");
        }
    }
    catch (error){
        anime({
            targets: `#auth-${errorTargetName}`,
            scale: [
                {value: 1},
                {value: 0.85},
                {value: 1}
            ],
            duration: 400,
            easing: "easeInOutQuad"
        });
        $("#auth-fail-msg").html(`Заполните поле "${error.message}"`);
        flags.clickLocked = false;
        return;
    }
    if (data.pass != data.pass2){
        anime({
            targets: "#auth-pass, #auth-pass2",
            scale: [
                {value: 1},
                {value: 0.85},
                {value: 1}
            ],
            delay: anime.stagger(100),
            duration: 400,
            easing: "easeInOutQuad"
        });
        $("#auth-fail-msg").html("Пароли не совпадают");
        flags.clickLocked = false;
        return;
    }  

    $("#auth-fail-msg").html("");
    apiRequest({
        route: "auth",
        op: "signup",
        name: data.name,
        email: data.email,
        pass: data.pass
    }, {
        method: "POST",
        success: function(data){
            if (data.result){
                handleLoginData(data.user_data);
                setGameStage(1);
            }
            else {
                if ("msg" in data) $("#auth-fail-msg").html(data.msg);
                else $("#auth-fail-msg").html("Ошибка создания аккаунта");
                flags.clickLocked = false;
            }
        },
        error: function(){
            $("#auth-fail-msg").html("Ошибка создания аккаунта");
            flags.clickLocked = false;
        }
        // complete: function(){
        // }
    });
    // setGameStage(1);
}

//--------------------------------------------------------------------------------------------------
$(document).ready(function(){
    $(window).trigger("resize");
});
onEnter($("#auth-name input"), function(){
    $("#auth-email input").focus();
});
onEnter($("#auth-email input"), function(){
    $("#auth-pass input").focus();
});
onEnter($("#auth-pass input"), function(){
    if (flags.authLogining){
        return $("#auth-login").click();
    }
    $("#auth-pass2 input").focus();
})
onEnter($("#auth-pass2 input"), function(){
    $("#auth-login").click();
});
$("#auth-create").click(function(){
    if (flags.clickLocked) return;
    $("#auth-fail-msg").html("");
    toggleCreateAccount();
});
$("#auth-login").click(function(){
    if (flags.clickLocked) return;
    flags.clickLocked = true;

    $("#auth-fail-msg").html("");
    if (flags.authLogining){
        return authLogin();
    }
    return authCreate();
});
$("#auth-pass > .key, #auth-pass2 > .key").click(function(){
    if (flags.clickLocked) return;
    var passinputs = $("#auth-pass > input, #auth-pass2 > input");
    if (passinputs.attr("type") == "password"){
        return passinputs.attr("type","text");
    }
    return passinputs.attr("type","password");
});
$("#auth-settings").click(function(){
    if (flags.clickLocked) return;
    $("#substage-settings").addClass("out-auth");
    setGameSubStage("settings");
});