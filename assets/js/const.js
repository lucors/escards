let SYNC_COMMON_ERROR           = 0;

// duel stage 
let STAGE_DUEL_CREATED          = 1;
let STAGE_DUEL_PLAYERS_READY    = 2;
let STAGE_DUEL_CARDSCREATED     = 3;
let STAGE_DUEL_CARDS0DONE       = 4;
let STAGE_DUEL_CARDS1DONE       = 5;
let STAGE_DUEL_ROUND1           = 6;
let STAGE_DUEL_ROUND2           = 7;
let STAGE_DUEL_ROUND3           = 8;
let STAGE_DUEL_FINISH           = 9;
let STAGE_DUEL_WIN0             = 15;
let STAGE_DUEL_WIN1             = 16;
let STAGE_DUEL_DRAW             = 17;
let STAGE_DUEL_DEAD             = 18;
// Handled Duels Codes
let hdc = {
    clear: function(){
        $.each(Object.keys(this), function(index, value) {
            if (typeof hdc[value] != 'function'){
                hdc[value] = false;
            }
        });
    }
}
hdc[String(STAGE_DUEL_CREATED)]         = false;
hdc[String(STAGE_DUEL_PLAYERS_READY)]   = false;
hdc[String(STAGE_DUEL_CARDSCREATED)]    = false;
hdc[String(STAGE_DUEL_CARDS0DONE)]      = false;
hdc[String(STAGE_DUEL_CARDS1DONE)]      = false;
hdc[String(STAGE_DUEL_ROUND1)]          = false;
hdc[String(STAGE_DUEL_ROUND2)]          = false;
hdc[String(STAGE_DUEL_ROUND3)]          = false;
hdc[String(STAGE_DUEL_FINISH)]          = false;
hdc[String(STAGE_DUEL_WIN0)]            = false;
hdc[String(STAGE_DUEL_WIN1)]            = false;
hdc[String(STAGE_DUEL_DRAW)]            = false;
hdc[String(STAGE_DUEL_DEAD)]            = false;

// duel_step stage
let STAGE_STEP_CREATED          = 1;
let STAGE_STEP_TIMEOUT          = 2;
let STAGE_STEP_PROTECT          = 3;
let STAGE_STEP_SELECT           = 4;
let STAGE_STEP_PASS             = 5;
let STAGE_STEP_PULL             = 6;
// Handled Steps Codes
let hsc = {
    clear: function(){
        $.each(Object.keys(this), function(index, value) {
            if (typeof hsc[value] != 'function'){
                hsc[value] = false;
            }
        });
    }
}
hsc[String(STAGE_STEP_CREATED)] = false;
hsc[String(STAGE_STEP_TIMEOUT)] = false;
hsc[String(STAGE_STEP_PROTECT)] = false;
hsc[String(STAGE_STEP_SELECT)]  = false;
hsc[String(STAGE_STEP_PASS)]    = false;
hsc[String(STAGE_STEP_PULL)]    = false;
