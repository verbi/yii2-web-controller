<?php

namespace verbi\yii2WebController\widgets;

use Yii;
use yii\bootstrap\ButtonDropdown;
use verbi\yii2Helpers\widgets\Widget;
use verbi\yii2WebController\behaviors\ActionMenuBehavior;

class ActionMenuButtons extends Widget {
    public static function widget($config = array()) {
        $controller = Yii::$app->controller;
        if ($controller->hasBehaviorByClass(ActionMenuBehavior::className())) {
            if (!isset($config['label'])) {
                $config['label'] = Yii::t('app', 'Actions');
            }
            if (!isset($config['items'])) {
                $config['items'] = $controller->getActionButtonsArray();
            }
            $actionButtons = $config['items'];
            unset($config['items']);

            return ButtonDropdown::widget(array_merge([
                    'dropdown' => [
                        'items' => $actionButtons,
                    ],
                    'containerOptions' => [
                        'class' => 'actionButtons',
                    ],
                ], $config));
        }
    }
}
