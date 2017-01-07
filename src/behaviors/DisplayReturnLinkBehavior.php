<?php

namespace verbi\yii2WebController\behaviors;

use \verbi\yii2Helpers\behaviors\base\Behavior;
use \Yii;
use verbi\yii2Helpers\Html;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-web-controller/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class DisplayReturnLinkBehavior extends Behavior {

    /**
     * Attaches the behavior object to the component.
     * The default implementation will set the [[owner]] property
     * and attach event handlers as declared in [[events]].
     * Make sure you call the parent implementation if you override this method.
     * @param Component $owner the component that this behavior is to be attached to.
     */
    public function attach($owner) {
        $result = parent::attach($owner);
        $viewClass = \verbi\yii2WebView\web\View::className();
        if(!(Yii::$app->get('view') instanceof $viewClass)) {
            Yii::$app->set('view', array_merge(get_object_vars(Yii::$app->get('view')),['class' => $viewClass]));
        }
        $className = $this->owner->className();
        $this->owner->on($className::EVENT_BEFORE_RENDER, [$this, 'renderContent']);
        return $result;
    }

    /**
     * 
     * @param Event $event
     * @return string
     */
    public function renderContent($event) {
        $urlContent = '';
        if (isset($this->owner->getView()->returnLinkText) 
                && $this->owner->getView()->returnLinkText 
                || isset($this->owner->getView()->returnLinkUrl) 
                && $this->owner->getView()->returnLinkUrl
        ) {
            $returnUrl = Yii::$app->user->returnUrl;
            if (isset($this->owner->getView()->returnLinkUrl) 
                    && $this->owner->getView()->returnLinkUrl) {
                $returnUrl = $this->owner->getView()->returnLinkUrl;
            }
            $returnText = 'Go back';
            if (isset($this->owner->getView()->returnLinkText) 
                    && $this->owner->getView()->returnLinkText) {
                $returnText = $this->owner->getView()->returnLinkText;
            }
            $urlContent = Html::a(Yii::t('verbi', 
                    $this->owner->getView()->returnLinkText), 
                    $this->owner->getView()->returnLinkUrl, 
            ['class' => 'return-url']);
        }
        echo $urlContent;
    }

}
