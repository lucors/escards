let menucanvas = $("#stage-menu canvas")[0];
let menuctx = menucanvas.getContext("2d");
// let polytest = [[439, 266], [758, 263], [758, 852], [445, 903]];
let menuHoverImg = new Image();
let menuPolygons = [
    [[[440, 261],[775, 255],[776, 840],[447, 896]], clickClipDuel],
    [[[789, 255],[1073, 251],[1070, 792],[790, 838]], clickClipTour],
    [[[1083, 251],[1327, 248],[1320, 751],[1080, 790]], clickClipRules],
    [
        [[1444, 559],[1482, 513],[1586, 513],[1612, 544],
        [1609, 818],[1509, 842],[1444, 840]], clickClipLogout, hoverClipLogout
    ],
    // [
    //     [[189, 609],[280, 618],[287, 693],[261, 768],
    //     [199, 760],[162, 724]], clickClipOwl
    // ],
    [
        [[490, 129],[699, 124],[1281, 141],[1287, 208],[1125, 225],
        [1104, 213],[698, 212], [490, 215]], clickClipInfo
    ],
    [
        [[1064, 840],[1115, 833],[1115, 788],[1156, 787],[1178, 783],
        [1205, 782],[1229, 786],[1255, 786],[1266, 825],[1320, 847],
        [1322, 866],[1314, 873],[1311, 1028],[1177, 1051],[1073, 1017]],
        clickClipSettings
    ]
]
let currentClip = null;
menuHoverImg.src = "assets/img/menu/bg_mainmenu_hover.jpg";
gamestages.entryFunc[1] = menuEntry;
gamestages.soundFunc[1] = menuInitSound;


function menuEntry(){
    // menuInitSound();
    setTimeout(menuInitSound, 900);
}
function menuInitSound(){
    return soundMaster.music.play({src: "blow_with_the_fires.ogg"});
}


function isPointInsidePoly(point, poly, ratio = 1){
    //Point массив координат ([x, y])
    //Polygon массив из точек ([[x, y], ...])
    var result = false;
    var j = poly.length - 1;
    for (var i = 0; i < poly.length; i++) {
        if ( 
                (poly[i][1]*ratio < point[1] && poly[j][1]*ratio >= point[1] 
                    || 
                poly[j][1]*ratio < point[1] && poly[i][1]*ratio >= point[1]) 
            &&
                (poly[i][0]*ratio + 
                    (point[1] - poly[i][1]*ratio) / 
                    (poly[j][1]*ratio - poly[i][1]*ratio) * 
                    (poly[j][0]*ratio - poly[i][0]*ratio) < point[0]
                )
            )
            result = !result;
        j = i;
    }
    return result;
}
function drawPolyPath(poly, ratio = 1){
    menuctx.beginPath();
    menuctx.moveTo(poly[0][0]*ratio, poly[0][1]*ratio);
    for (var i = 1; i < poly.length; i++) {
        menuctx.lineTo(poly[i][0]*ratio, poly[i][1]*ratio);
    }
    menuctx.closePath();
    if (flags.strokePolyPath){
        menuctx.lineWidth = 5;
        menuctx.strokeStyle="red";
        menuctx.stroke();
    }
}
function getPolyClip(){
    for (var i = 0; i < menuPolygons.length; i++) {
        if (isPointInsidePoly(mouse, menuPolygons[i][0], bgratio)){
            return menuPolygons[i];
        }
    }
    return null;
}
function resizeMenuElements(){
    menucanvas.width = $("#stage-menu").width();
    menucanvas.height = $("#stage-menu").height();
}
function logout(){
    flags.clickLocked = true;
    apiRequest({
        route: "auth",
        op: "logout"
    }, {
        success: function(data){
            if (data.result){
                setGameStage(0, {
                    halfpart: 2, 
                    halfpartCallback: function(){
                        setGameSubStage(null, false);
                        $("#stage-menu canvas").trigger("mousemove");
                    }
                });
                window.user.clear();
                window.duel_data.clear();
                window.tourn_data.clear();
                return;
            }
            this.error();
            flags.clickLocked = false;
        },
        error: function(){
            setGameStage(1, {
                halfpart: 2,
                halfpartCallback: function(){
                    setGameSubStage(null, false);
                }
            });
            flags.clickLocked = false;
        }
    });
}
function changePass(currentPass, newPass){
    flags.clickLocked = true;
    $("#confirm-error").html("");
    apiRequest({
        route: "profile",
        op: "set_pass",
        old_pass: currentPass,
        pass: newPass
    }, {
        method: "POST",
        success: function(data){
            if (data.result){
                $("#confirm-error").html("");
                blink({direction: "reverse"});
                setGameSubStage("settings");
            }
            else {
                if ("msg" in data)  $("#confirm-error").html(data.msg);
                else $("#confirm-error").html("Ошибка смены пароля");
                flags.clickLocked = false;
            }
        },
        error: function(){
            flags.clickLocked = false;
        },
        complete: function(){
            $("#edit-user-pass input").val("");
        }
    });
}

//Далее функции, привязанные к polyclips
function clickClipDuel(){
    setGameStage(2, {halfpart: 1}).finished.then(function(){
        apiRequest({
            route: "duel",
            op: "find"
        }, {
            success: function(data){
                if (data.result){
                    setGameStage(2, {halfpart: 2, entryFuncArg: data});
                }
                else {
                    if ("msg" in data) showAlert(data.msg);
                    else showAlert("Ошибка начала дуэли");
                    this.error();
                }
            },
            error: function(){
                setGameStage(1, {halfpart: 2});
            }
            // complete: function(){
            // }
        });
    });
}
function clickClipTour(){
}
function clickClipRules(){
}
function clickClipSettings(){
    apiRequest({
        route: "profile",
        op: "get_rating"
    }, {
        success: function(data){
            if (data.result){
                $("#profile-stats .value").html(
                    `Всего ${data.rating_data.total}. Побед ${data.rating_data.won}.`
                );
            }
            else {
                $("#profile-stats .value").html("Ошибка");
                if ("msg" in data) console.warn(data.msg);
                else this.error();
            }
        },
        error: function(){
            $("#profile-stats .value").html("Ошибка");
            console.warn("Ошибка получения статистики");
        }
    });
    $("#edit-user-avatar .key").attr(
        "src", window.user.avatar_path+"?timestamp=" + new Date().getTime()
    );
    $("#edit-user-name input").val(window.user.name);
    $("#edit-user-pass input").val("");
    setGameSubStage("settings");
}
function clickClipLogout(){
    setGameSubStage("logout");
}
function hoverClipLogout(){
    soundMaster.sfx.play({src: "menu_gate.ogg"});
}
function clickClipInfo(){
}
// function clickClipOwl(){
//     $("#game-search-info").html("");
//     blink().finished.then(function(){
//         $("#game-search-info").html("Тут сова");
//     });
// }

//--------------------------------------------------------------------------------------------------
$(document).ready(function(){
    $(window).trigger("resize");
});
$(window).resize(function(){
    resizeMenuElements();
    $("#stage-menu canvas").trigger("mousemove");
});
menuHoverImg.onload = function(){
    menuctx.clip();
	// menuctx.drawImage(menuHoverImg, 0, 0, bgratio*1920, bgratio*1080);
	menuctx.drawImage(menuHoverImg, 0, 0, $("#content").width(), $("#content").height());
};

$("#stage-menu canvas").mousemove(function(event){
    menucanvas.width = menucanvas.width;
    mouse[0] = event.pageX-$(this).offset().left;
    mouse[1] = event.pageY-$(this).offset().top;

    var clip = getPolyClip();
    if (clip === null){
        currentClip = null;
    }
    else {
        if (clip[0] != currentClip){
            currentClip = clip[0];
            if (clip.length == 3) clip[2]();
        }
        drawPolyPath(clip[0], bgratio);
    }
    menuHoverImg.onload();
});
$("#stage-menu canvas").click(function(){
    if (flags.clickLocked) return;
    var clip = getPolyClip();
    if (clip !== null){
        clip[1]();
    }
});
$("#actions-logout .no").click(function(){
    if (flags.clickLocked) return;
    setGameSubStage("logout", false).finished.then(function(){
        $("#stage-menu canvas").trigger("mousemove");
    });
});
$("#actions-logout .yes").click(function(){
    if (flags.clickLocked) return;
    setGameStage(0, {halfpart: 1}).finished.then(logout);
});
$("#settings-content input").not(".range").on("input propertychange", function(){
    if ($(this).val() == "") return;
    $(this).siblings(".save").addClass("active");
});
$("#edit-user-avatar .key").click(function(){
    if (flags.clickLocked) return;
    $("#edit-user-avatar input")
        .val("")
        .click();
});
$("#edit-user-avatar .save").click(function(){
    if (flags.clickLocked) return;
    if(!$(this).hasClass("active")) return;
    $(this).removeClass("active");

    if ($("#edit-user-avatar input")[0].files.length == 0) return;
    var avatar = $("#edit-user-avatar input")[0].files;

    var data = new FormData();
    $.each(avatar, function(key, value){
		data.append(key, value);
	});
    data.append("route", "profile");
    data.append("op", "set_avatar");
    data.append("formData", true);

    flags.clickLocked = true;
    apiRequest(data, {
        method: "POST",
        cache: false,
        processData: false,
        contentType: false, 
        success: function(data){
            if (data.result){
                window.user.set(data);
                $("#edit-user-avatar .key").attr(
                    "src", window.user.avatar_path+"?timestamp=" + new Date().getTime()
                );
            }
            else {
                if ("msg" in data){
                    showAlert(data.msg, {
                        ok: function(){
                            setGameSubStage("settings");
                        }
                    });
                }
            }
        },
        complete: function(){
            $("#edit-user-avatar input").val("");
            flags.clickLocked = false;
        }
    });
});
$("#edit-user-name .save").click(function(){
    if (flags.clickLocked) return;
    if(!$(this).hasClass("active")) return;
    $(this).removeClass("active");

    var name = $("#edit-user-name input").val();
    if (name == "") return;

    flags.clickLocked = true;
    apiRequest({
        route: "profile",
        op: "set_name",
        name: name
    }, {
        method: "POST",
        success: function(data){
            if (data.result){
                window.user.name = name;
            }
        },
        complete: function(){
            $("#edit-user-name input").val(window.user.name);
            flags.clickLocked = false;
        }
    });
});
$("#edit-user-pass .save").click(function(){
    if (flags.clickLocked) return;
    if(!$(this).hasClass("active")) return;
    $(this).removeClass("active");

    var pass = $("#edit-user-pass input").val();
    if (pass == "") return;
    $("#edit-user-pass input").val("");
    
    var confirmMsg = `Для смены пароля введите в поле ниже свой старый пароль `;
    confirmMsg +=  `и нажмите "Продолжить"<br>`;
    confirmMsg += `<input id="old_pass" class="value summer" type="password">`;
    blink().finished.then(function(){
        showConfirm(confirmMsg, {
            zIndex: 100,
            yesBtn: "Продолжить",
            noBtn: "Отмена",
            accept: function(){
                changePass($("#old_pass").val(), pass)
            },
            deny: function(){
                $("#confirm-error").html("");
                blink({direction: "reverse"});
                setGameSubStage("settings");
            }
        });
    });
});
