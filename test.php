<?php
require_once "vendor/autoload.php";
use hexpang\moviebotb3t\MovieBot;

$bot = new MovieBot();
var_dump($bot->loadMovies(1));
?>
