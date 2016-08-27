<?php
use hexpang\moviebotb3t\MovieBot;

class InstancetestCase extends \PHPUnit_Framework_TestCase{
  public function testInstance(){
    $bot = new MovieBot();
    $this->assertInstanceOf("hexpang\moviebotb3t\MovieBot",$bot);
  }
}
?>
