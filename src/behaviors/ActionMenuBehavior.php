<?php

namespace verbi\yii2WebController\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2WebController\events\ControllerRenderEvent;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2WebController\Controller;
use yii\bootstrap\Nav;
use yii\base\Action;
use yii\base\InlineAction;
use verbi\yii2Helpers\Html;

class ActionMenuBehavior extends Behavior {

    const EVENT_BEFORE_FILTER = 'before_filter';
    const EVENT_AFTER_FILTER = 'after_filter';

    public $actionButtons;
    public $events = [
        Controller::EVENT_AFTER_RENDER => 'EventAfterRender',
    ];

    public function EventAfterRender(ControllerRenderEvent $event) {
        $event->output = $this->owner->renderActionMenu() . $event->output;
    }

    public function getActionButtons() {
        $actions = $this->owner->filterActionsForButtons();
        return $this->owner->actionButtons === null ? array_map(function($action) {
                    $parameters = $this->owner->getActionParametersForUrl($action);
                    return [
                        'label' => $action->id,
                        'url' => array_merge([$action->id], $parameters),
                        'linkOptions' => [
//                            'data-method' => $action->actionMethod,
                        'data' => [
                            'method' => 'delete',
                            'confirm' => 'test',
                        ],
                            
                        ],
                    ];
                }, $actions) : $this->owner->actionButtons;
    }
    
    public function _actionMenuBehavior_getActionsForButtons() {
        return $this->owner->getActions();
    }
    
    public function getActionsForButtons() {
        return $this->_actionMenuBehavior_getActionsForButtons();
    }
    
    public function _actionMenuBehavior_filterActionsForButtons() {
        $event = new GeneralFunctionEvent;
        $this->trigger(self::EVENT_BEFORE_FILTER,$event);
        if($event->isValid) {
            $owner = $this->owner;
            $actions = $owner->getActionsForButtons();
            $filteredActions = array_filter($actions, function($action) use ($owner) {
                if (
                        $action===null
                        || $owner->action==$action
                        || $this->owner->getActionParametersForUrl($action) === null
                        ) {
                    return false;
                }
                return true;
            });
            $event->params = ['actions' => &$filteredActions,];
            $this->trigger(self::EVENT_AFTER_FILTER, $event);
            return $event->hasReturnValue() ? $event->getReturnValue() : $filteredActions;
        }
        return [];
    }
    
    public function filterActionsForButtons() {
        return $this->_actionMenuBehavior_filterActionsForButtons();
    }

    public function renderActionMenu() {
        $actionButtons = $this->owner->getActionButtons();
        return $actionButtons ? Nav::widget([
                    'items' => $actionButtons,
                ]) : '';
    }

    public function _actionMenuBehavior_getActionMenuReflectionParameters(Action $action) {
        if ($action instanceof InlineAction) {
            $method = $this->owner->getReflectionMethod($action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        return $method->getParameters();
    }

    public function _actionMenuBehavior_getCurrentActionParams() {
        return $this->owner->actionParams;
    }
    
    public function getCurrentActionParams() {
        return $this->_actionMenuBehavior_getCurrentActionParams();
    }
    
    public function _actionMenuBehavior_getActionMenuParametersForUrl(Action $action, bool $allowMissing = false) {
        $params = $this->owner->getCurrentActionParams();
        $args = [];
        $actionParams = [];
        foreach ($this->owner->_actionMenuBehavior_getActionMenuReflectionParameters($action) as $param) {
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

    public function getActionParametersForUrl(Action $action, bool $allowMissing = false) {
        return $this->owner->_actionMenuBehavior_getActionMenuParametersForUrl( $action, $allowMissing );
    }
}
