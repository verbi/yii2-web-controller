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
class DisplayTitleBehavior extends Behavior {

    /**
     * Attaches the behavior object to the component.
     * The default implementation will set the [[owner]] property
     * and attach event handlers as declared in [[events]].
     * Make sure you call the parent implementation if you override this method.
     * @param Component $owner the component that this behavior is to be attached to.
     */
    public function attach($owner) {
        $result = parent::attach($owner);
        Yii::$app->set('view', ['class' => '\verbi\yii2WebView\web\View']);
        $className = $this->owner->className();
        $this->owner->on($className::EVENT_BEFORE_RENDER, [$this, 'renderContent']);
        return $result;
    }

    /**
     * 
     * @param Event $event
     * @return string
     */
    public function renderContent( $event ) {
        $titleContent = '';
        if ($this->owner->getView()->title) {
            $titleContent = Html::pageHeading(Yii::t( 'verbi', $this->owner->getView()->title ) );
        }
        echo $titleContent;
    }

}
