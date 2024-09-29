<?php
    function getRandomCards($count){
        if ($count > 52 || $count < 1){
            $count = 12;
        } 

        $dir = __DIR__."/../assets/img/game/cards/";
        $cards = array();
        foreach (glob($dir."*.png") as $filename) {
            $card = basename($filename, ".png");
            if ($card != "cover"){
                $cards[] = $card;
            }
        }
        shuffle($cards);
        return array_slice($cards, 0, $count);
    }
    // $cards = getRandomCards(6);

    // // POKER_ROYAL_FLUSH
    // $cards = array(
    //     "10_ussr",
    //     "14_2ch",
    //     "13_2ch",
    //     "12_2ch",
    //     "11_2ch", 
    //     "10_2ch",
    // );
    // // POKER_STRAIGHT_FLUSH
    // $cards = array(
    //     "10_2ch",
    //     "8_ussr",
    //     "7_ussr",
    //     "6_ussr",
    //     "5_ussr", 
    //     "4_ussr",
    // );
    // // POKER_4OF_KIND
    // $cards = array(
    //     "10_2ch",
    //     "14_2ch",
    //     "14_utan",
    //     "14_uvao",
    //     "14_ussr", 
    //     "4_ussr",
    // );
    // // POKER_FULL_HOUSE
    // $cards = array(
    //     "9_ussr",
    //     "10_utan",
    //     "10_2ch",
    //     "10_uvao",
    //     "5_utan", 
    //     "5_uvao",
    // );
    // // POKER_FLUSH
    // $cards = array(
    //     "9_ussr",
    //     "13_uvao",
    //     "11_uvao",
    //     "8_uvao",
    //     "7_uvao", 
    //     "5_uvao",
    // );
    // // POKER_STRAIGHT
    // $cards = array(
    //     "9_ussr",
    //     "10_ussr",
    //     "9_uvao",
    //     "8_utan",
    //     "7_uvao", 
    //     "6_2ch",
    // );
    // // POKER_3OF_KIND
    // $cards = array(
    //     "12_uvao",
    //     "10_ussr",
    //     "12_ussr",
    //     "8_utan",
    //     "12_utan", 
    //     "6_2ch",
    // );
    // // POKER_TWO_PAIR
    // $cards = array(
    //     "12_uvao",
    //     "13_ussr",
    //     "13_2ch",
    //     "8_uvao",
    //     "8_ussr", 
    //     "3_2ch",
    // );
    // // POKER_PAIR
    // $cards = array(
    //     "5_uvao",
    //     "13_ussr",
    //     "13_2ch",
    //     "7_uvao",
    //     "8_ussr", 
    //     "3_2ch",
    // );
    // // POKER_PAIR
    // $cards = array(
    //     "5_uvao",
    //     "13_ussr",
    //     "9_2ch",
    //     "7_uvao",
    //     "8_ussr", 
    //     "3_2ch",
    // );
    

    echo "\n CARDS\n";
    print_r($cards);

    // echo __DIR__;
    // echo "\n";
    require_once(__DIR__."/common.php");
    // echo "hi3\n";
    require_once(__DIR__."/card/victory-handler.php");
    // echo "hi4\n";
    $output = calcPoker($cards);
    
    echo "\n OUTPUT\n";
    print_r($output);
?>