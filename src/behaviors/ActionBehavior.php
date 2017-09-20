<?php
namespace verbi\yii2WebController\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2WebController\events\ControllerRenderEvent;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2WebController\Controller;
use verbi\yii2WebController\widgets\ActionMenuButtons;
use yii\bootstrap\Nav;
use yii\base\Action;
use yii\base\InlineAction;
use yii\filters\VerbFilter;
use verbi\yii2ConfirmationFilter\behaviors\ConfirmationFilterBehavior;
use verbi\yii2Helpers\Html;

class ActionBehavior extends Behavior {
    public function getActionMenuReflectionParameters(Action $action) {
        if ($action instanceof InlineAction) {
            $method = $this->owner->getReflectionMethod($action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }
        return $method->getParameters();
    }
    
    public function getActionUrl(Action $action) {
        return array_merge(['//' . $action->getUniqueId()], $this->owner->getActionParametersForUrl($action));
    }
    
    public function getActionParametersForUrl(Action $action, bool $allowMissing = false) {
        $params = $this->owner->getCurrentActionParams();
        $args = [];
        $actionParams = [];
        foreach ($this->owner->getActionMenuReflectionParameters($action) as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array) $params[$name];
                } elseif (!is_array($params[$name])) {
                    $args[] = $actionParams[$name] = $params[$name];
                }
                unset($params[$name]);
            } elseif (!$param->isDefaultValueAvailable()) {
                if (!$allowMissing) {
                    return null;
                }
            }
        }
        return $actionParams;
    }
    
    public function getMethodsForAction(Action $action, $method = 'get') {
        foreach ($this->owner->getBehaviorsByClass(VerbFilter::className()) as $behavior) {
            if (isset($behavior->actions[$action->id]) && is_array($behavior->actions[$action->id]) && sizeof($behavior->actions[$action->id]) && false === array_search($method, $behavior->actions[$action->id])) {
                return array_values($behavior->actions[$action->id])[0];
            }
        }
        return null;
    }
    
    public function getCurrentActionParams() {
        $params = $this->owner->actionParams;
        if(
                !sizeof($params) &&
                $this->owner!=\Yii::$app->controller &&
                sizeof(\Yii::$app->controller->actionParams)
                ) {
            $params = \Yii::$app->controller->actionParams;
        }
        return $params;
    }
}