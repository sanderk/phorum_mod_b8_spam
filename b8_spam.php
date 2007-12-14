<?php

// ----------------------------------------------------------------------
// Initialization code.
// ----------------------------------------------------------------------

// Check if we are loaded from the Phorum code.
// Direct access to this file is not allowed.
if (! defined("PHORUM")) return;

require_once('./mods/b8_spam/b8/b8.php');
require_once("./mods/b8_spam/config.php");


$phorum_mod_b8_spam_threshold = 0.0;


function phorum_mod_b8_spam_setup()
{
    global $config_b8, $config_storage, $config_lexer, $config_degenerator;

    # Create a new b8 instance
    try {
        $b8 = new b8($config_b8, $config_storage, $config_lexer, $config_degenerator);

        return $b8;
    }
    catch(Exception $e) {
        //do_something();
        return false;
    }
}

function phorum_mod_b8_spam_prepare_text($data)
{
    // Creates the text to use for classification
    $text = array();
    $text[] = $data['author'];
    $text[] = $data['body'];
    //$text[] = $data['ip'];
    $text[] = $data['subject'];

    $text = implode(" ",$text);

    return $text;
}

function phorum_mod_b8_spam_classify_text($data)
{
    global $phorum_mod_b8_spam_threshold;

    $b8 = phorum_mod_b8_spam_setup();
    if(!$b8) return false;

    $text = phorum_mod_b8_spam_prepare_text($data);

    $score = $b8->classify($text);

    if($score >= $phorum_mod_b8_spam_threshold){
        phorum_mod_b8_spam_handle_spam($data, $score);
    }

}


function phorum_mod_b8_spam_handle_spam($data, $score)
{

    // Try to insert a new spamhurdles record.
    $res = phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO b8_log
                (message_id, score)
         VALUES (" .
            "'".phorum_db_interact(DB_RETURN_QUOTED, $data['message_id'])."', " .
            "'".phorum_db_interact(DB_RETURN_QUOTED, $score)."')",
        NULL,
        DB_DUPKEYOK | DB_MASTERQUERY
    );

}


function phorum_mod_b8_spam_after_post($data)
{
    phorum_mod_b8_spam_classify_text($data);

    return $data;
}


function phorum_mod_b8_spam_after_edit($data)
{
    phorum_mod_b8_spam_classify_text($data);

    return $data;
}


function phorum_mod_b8_spam_after_approve($data)
{
    //TODO: Do a retraining of the data including this good example

    return $data;
}


?>