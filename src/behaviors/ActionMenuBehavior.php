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

class ActionMenuBehavior extends Behavior {

    const EVENT_BEFORE_FILTER = 'before_filter';
    const EVENT_AFTER_FILTER = 'after_filter';
    const EVENT_BEFORE_GENERATE_CONFIG_FOR_ACTION_BUTTONS = 'before_generate_config_for_action_buttons';
    const EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS = 'after_generate_config_for_action_buttons';
    const EVENT_AFTER_GET_ACTION_BUTTONS_ARRAY = 'after_get_action_buttons_array';

    public $actionButtons;
    public $render = true;
    
    /**
     * @var Array|null An Array of strings containing the allowed verbs
     */
    public $allowedVerbs = [
        'post',
        'put',
        'delete',
        'patch',
    ];
    
    public $events = [
        Controller::EVENT_AFTER_RENDER => 'eventAfterRender',
        self::EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS => '_filter_afterGenerateConfigForActionButtons',
        self::EVENT_AFTER_GET_ACTION_BUTTONS_ARRAY => '_filterActionsForButtonsByAccess',
    ];

    public function eventAfterRender(ControllerRenderEvent $event) {
        if($this->render) {
            $event->output = $this->owner->renderActionMenu() . $event->output;
        }
    }

    public function getActionButtonsArray() {
        $owner = $this->owner;
        $object = $this;
        $actions = $owner->filterActionsForButtons();
        $actionButtons = $this->owner->actionButtons === null ? array_map(function($action) use ($owner, $object) {
                    $array = null;
                    $event = new GeneralFunctionEvent;
                    $owner->trigger(self::EVENT_BEFORE_GENERATE_CONFIG_FOR_ACTION_BUTTONS, $event);
                    if ($event->isValid) {
                        $parameters = $owner->getActionParametersForUrl($action);

                        $array = [
                            'label' => $action->id,
                            'url' => array_merge([$action->id], $parameters),
                            'parameters' => $parameters,
                            'action' => $action,
                        ];
                        $event = new GeneralFunctionEvent;
                        $event->setParams([
                            'action' => $action,
                            'output' => &$array,
                        ]);
                        $owner->trigger(self::EVENT_AFTER_GENERATE_CONFIG_FOR_ACTION_BUTTONS, $event);
                    }
                    return $array;
                }, $actions) : $owner->actionButtons;
        $event = new GeneralFunctionEvent;
        $event->setParams([
                            'actionButtons' => &$actionButtons,
                        ]);
        $object->trigger(self::EVENT_AFTER_GET_ACTION_BUTTONS_ARRAY, $event);
        return $actionButtons;
    }

    public function _filter_afterGenerateConfigForActionButtons(GeneralFunctionEvent $event) {
        $params = $event->getParams();
        $action = $params['action'];
        $method = 'get';
        foreach ($this->owner->getBehaviorsByClass(VerbFilter::className()) as $behavior) {
            if (isset($behavior->actions[$action->id]) && is_array($behavior->actions[$action->id]) && sizeof($behavior->actions[$action->id]) && false === array_search($method, $behavior->actions[$action->id])) {
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
        $owner = $this->owner;
        $owner->trigger(self::EVENT_BEFORE_FILTER, $event);
        if ($event->isValid) {
            $actions = $owner->getActionsForButtons();
            $filteredActions = array_filter($actions, function($action) use ($owner) {
                if ($action === null
                        || $owner->action == $action
                        || $owner->getActionParametersForUrl($action) === null
                        || !$owner->_actionMenuBehavior_checkVerbForAction($action)
                    ) {
                    return false;
                }
                return true;
            });
            $event->params = ['actions' => &$filteredActions,];
            $owner->trigger(self::EVENT_AFTER_FILTER, $event);
            return $event->hasReturnValue() ? $event->getReturnValue() : $filteredActions;
        }
        return [];
    }

    public function filterActionsForButtons() {
        return $this->_actionMenuBehavior_filterActionsForButtons();
    }
    
    public function _filterActionsForButtonsByAccess(GeneralFunctionEvent $event) {
        $params = $event->getParams();
//        $actionButtons = $params['actionButtons'];
        foreach($params['actionButtons'] as $key => $actionButton) {
            if($this->owner->hasBehaviorByClass(\verbi\yii2Helpers\behaviors\base\AccessControl::className()) && !$this->owner->checkAccess($actionButton['action'], $actionButton['parameters'], isset($actionButton['linkOptions'])?$actionButton['linkOptions']['data']['method']:'get'))
                unset($params['actionButtons'][$key]);
        }
    }
    
    public function _actionMenuBehavior_checkVerbForAction($action) {
        if(is_array($this->allowedVerbs)) {
//            $method = 'get';
            foreach ($this->owner->getBehaviorsByClass(VerbFilter::className()) as $behavior) {
                if (isset($behavior->actions[$action->id])
                        && is_array($behavior->actions[$action->id])
                        && sizeof($behavior->actions[$action->id])
//                        && false === array_search($method, $behavior->actions[$action->id])
                        
                        ) {
                    if(empty(array_intersect(array_values($behavior->actions[$action->id]), $this->allowedVerbs))) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    public function renderActionMenu() {
        $actionButtons = $this->owner->getActionButtonsArray();
        return $actionButtons ? ActionMenuButtons::widget([
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
