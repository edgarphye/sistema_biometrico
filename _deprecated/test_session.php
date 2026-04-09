<?php
session_start();
$_SESSION['test'] = 'hello world';
echo "Session ID: " . session_id() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session saved to file: " . session_save_path() . '/sess_' . session_id() . "\n";
var_dump($_SESSION);
