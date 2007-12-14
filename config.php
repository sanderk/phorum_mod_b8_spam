<?php

if(!defined("PHORUM_ADMIN")) return;

# Do some configuration
$config_b8          = array('storage' => 'mysqli');
$config_storage     = array('database' => 'phorum',
                        'table_name' => 'b8_wordlist',
                        'host' => 'localhost',
                        'user' => 'root',
                        'pass' => '123');
$config_lexer       = array();
$config_degenerator = array();

?>