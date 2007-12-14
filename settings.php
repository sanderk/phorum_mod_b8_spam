<?php

    if(!defined("PHORUM_ADMIN")) return;

    // Load the configuration
    require_once('./mods/b8_spam/b8/b8.php');
    require_once("./mods/b8_spam/config.php");

    //if(count($_POST) && $_POST["retrain"]!="")
    //{
        // Create a new b8 instance
        $b8 = new b8($config_b8, $config_storage, $config_lexer, $config_degenerator);

        // Reset Word Classification
        $rows = phorum_db_interact(
            DB_RETURN_RES,
            "TRUNCATE TABLE b8_wordlist"
        );

        $rows = phorum_db_interact(
            DB_RETURN_RES,
            "INSERT INTO b8_wordlist (token, count_ham) VALUES ('b8*dbversion',3)"
        );


        // Build new Word Classification on Training Data
        $rows = phorum_db_interact(
            DB_RETURN_ASSOCS,
            "SELECT id,text,classification
             FROM   b8_messages"
        );
        foreach($rows as $row)
        {
            $classification = b8::SPAM;
            if($row['classification']=='HAM') $classification = b8::HAM;

            $b8->learn($row['text'],$classification);             
        }

    //}        



    if(count($_POST) && $_POST["search"]!="" && $_POST["replace"]!=""){

        $item = array("search"=>$_POST["search"], "replace"=>$_POST["replace"], "pcre"=>$_POST["pcre"]);

        if($_POST["curr"]!="NEW"){
            $PHORUM["mod_replace"][$_POST["curr"]]=$item;
        } else {
            $PHORUM["mod_replace"][]=$item;
        }

        if(empty($error)){
            if(!phorum_db_update_settings(array("mod_replace"=>$PHORUM["mod_replace"]))){
                $error="Database error while updating settings.";
            } else {
                echo "Replacement Updated<br />";
            }
        }
    }

    if(isset($_GET["curr"])){
        if(isset($_GET["delete"])){
            unset($PHORUM["mod_replace"][$_GET["curr"]]);
            phorum_db_update_settings(array("mod_replace"=>$PHORUM["mod_replace"]));
            echo "Replacement Deleted<br />";
        } else {
            $curr = $_GET["curr"];
        }
    }


    if($curr!="NEW"){
        extract($PHORUM["mod_replace"][$curr]);
        $title="Edit Replacement";
        $submit="Update";
    } else {
        settype($string, "string");
        settype($type, "int");
        settype($pcre, "int");
        $title="Add A Replacement";
        $submit="Add";
    }




    include_once "./include/admin/PhorumInputForm.php";

    $frm =& new PhorumInputForm ("", "post", $submit);

    $frm->hidden("module", "modsettings");

    $frm->hidden("mod", "replace");

    $frm->hidden("curr", "$curr");

    $frm->addbreak($title);

    $frm->addrow("String To Match", $frm->text_box("search", $search, 50));

    $frm->addrow("Replacement", $frm->text_box("replace", $replace, 50));

    $frm->addrow("Compare As", $frm->select_tag("pcre", $match_types, $pcre));

    $frm->show();


/*
    $frm =& new PhorumInputForm ("", "post", "Retrain");
    
    $frm->hidden("module", "modsettings");

    $frm->hidden("mod", "b8_spam");    

    $frm->hidden("retrain","go");
    
    $frm->show();
*/  


    echo "If using PCRE for comparison, \"Sting To Match\" should be a valid PCRE expression. See <a href=\"http://php.net/pcre\">the PHP manual</a> for more information.";

    if($curr=="NEW"){

        echo "<hr class=\"PhorumAdminHR\" />";

        if(count($rows)){

            echo "<table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=\"PhorumAdminTable\" width=\"100%\">\n";
            echo "<tr>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Search</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Replace</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">Compare Method</td>\n";
            echo "    <td class=\"PhorumAdminTableHead\">&nbsp;</td>\n";
            echo "</tr>\n";

            foreach($rows as $key => $item){
                echo "<tr>\n";
                echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item["id"])."</td>\n";
                echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars(substr($item["text"],0,150))."...</td>\n";
                echo "    <td class=\"PhorumAdminTableRow\">".htmlspecialchars($item["classification"])."</td>\n";
                echo "    <td class=\"PhorumAdminTableRow\"><a href=\"$_SERVER[PHP_SELF]?module=modsettings&mod=replace&curr=$key&?edit=1\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$_SERVER[PHP_SELF]?module=modsettings&mod=replace&curr=$key&delete=1\">Delete</a></td>\n";
                echo "</tr>\n";
            }

            echo "</table>\n";

        } else {

            echo "No replacements in list currently.";

        }

    }

?>