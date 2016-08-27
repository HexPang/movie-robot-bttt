<?php
use hexpang\moviebotb3t\MovieBot;

class DumpMovietestCase extends \PHPUnit_Framework_TestCase{
  public function testDump(){
    $bot = new MovieBot();
    $movies = $bot->loadMovies(1);
    $this->assertCount(3,$movies);
  }
}
?>
