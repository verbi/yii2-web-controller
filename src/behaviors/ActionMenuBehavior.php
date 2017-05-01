<?php

namespace verbi\yii2WebController\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2WebController\events\ControllerRenderEvent;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2WebController\Controller;
use yii\bootstrap\Nav;
use yii\base\Action;
use yii\base\InlineAction;
use yii\filters\VerbFilter;
use verbi\yii2ConfirmationFilter\behaviors\ConfirmationFilterBehavior;
use verbi\yii2Helpers\Html;

class ActionMenuBehavior extends Behavior {

    const EVENT_BEFORE_FILTER = 'before_filter';
    const EVENT_AFTER_FILTER = 'after_filter';
    const EVENT_BEFORE_GENERATE_CONFIG_FOR_ACTION_BUTTONS = 'before_generate_config_for_action_buttons';
    const EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS = 'after_generate_config_for_action_buttons';

    public $actionButtons;
    public $events = [
        Controller::EVENT_AFTER_RENDER => 'EventAfterRender',
        self::EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS => 'verfFilter_afterGenerateConfigForActionButtons',
    ];

    public function EventAfterRender(ControllerRenderEvent $event) {
        $event->output = $this->owner->renderActionMenu() . $event->output;
    }

    public function getActionButtonsArray() {
        $actions = $this->owner->filterActionsForButtons();
        return $this->owner->actionButtons === null ? array_map(function($action) {
                    $array = null;
                    $event = new GeneralFunctionEvent;
                    $this->owner->trigger(self::EVENT_BEFORE_GENERATE_CONFIG_FOR_ACTION_BUTTONS, $event);
                    if ($event->isValid) {
                        $parameters = $this->owner->getActionParametersForUrl($action);

                        $array = [
                            'label' => $action->id,
                            'url' => array_merge([$action->id], $parameters),
                        ];
                        $event = new GeneralFunctionEvent;
                        $event->setParams([
                            'action' => $action,
                            'output' => &$array,
                        ]);
                        $this->owner->trigger(self::EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS, $event);
                    }
                    return $array;
                }, $actions) : $this->owner->actionButtons;
    }

    public function verfFilter_afterGenerateConfigForActionButtons(GeneralFunctionEvent $event) {
        $params = $event->getParams();
        $action = $params['action'];
        $method='get';
        foreach ($this->owner->getBehaviorsByClass(VerbFilter::className()) as $behavior) {
            if (isset($behavior->actions[$action->id]) && is_array($behavior->actions[$action->id]) && sizeof($behavior->actions[$action->id]) && false === array_search($method, $behavior->actions[$action->id])) {
                $params = $event->getParams();
                $params['output']['linkOptions']['data']['method'] = array_values($behavior->actions[$action->id])[0];
            }
        }
    }

    public function _actionMenuBehavior_getActionsForButtons() {
        return $this->owner->getActions();
    }

    public function getActionsForButtons() {
        return $this->_actionMenuBehavior_getActionsForButtons();
    }

    public function _actionMenuBehavior_filterActionsForButtons() {
        $event = new GeneralFunctionEvent;
        $this->trigger(self::EVENT_BEFORE_FILTER, $event);
        if ($event->isValid) {
            $owner = $this->owner;
            $actions = $owner->getActionsForButtons();
            $filteredActions = array_filter($actions, function($action) use ($owner) {
                if ($action === null || $owner->action == $action || $this->owner->getActionParametersForUrl($action) === null) {
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
        $actionButtons = $this->owner->getActionButtonsArray();
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
        return $this->owner->_actionMenuBehavior_getActionMenuParametersForUrl($action, $allowMissing);
    }

}
