<?php
namespace verbi\yii2WebController\events;
use yii\base\Event;
class ControllerRenderEvent extends Event {
    public $output;
}