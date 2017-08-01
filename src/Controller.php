<?php

namespace verbi\yii2WebController;
use \Yii;
use verbi\yii2WebController\behaviors\DisplayTitleBehavior;
use verbi\yii2WebController\behaviors\DisplayReturnLinkBehavior;
use verbi\yii2Helpers\behaviors\base\AccessControl;
use verbi\yii2WebController\events\ControllerRenderEvent;
use yii\web\NotFoundHttpException;
/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-web-controller/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class Controller extends \yii\web\Controller {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    use \verbi\yii2Helpers\traits\ControllerTrait;
    const EVENT_BEFORE_RENDER = 'before_render';
    const EVENT_AFTER_RENDER = 'after_render';
    
    protected $modelClass;
    
    public function behaviors()
    {
        return array_merge(parent::behaviors(),[
            [
                'class' => 'yii\filters\HttpCache',
                'cacheControlHeader' => 'public, max-age=86400',
            ],
            'access' => [
                'class' => AccessControl::className(),
            ],
        ]);
    }
    
    public function getPkFromRequest() {
        $modelClass = $this->modelClass;
        $pk = [];
        foreach ($modelClass::primaryKey(true) as $key) {
            $pk[$key] = \Yii::$app->request->getQueryParam($key);
        }
        return $pk;
    }
    
    public function loadModel($id = null) {
        $modelClass = $this->modelClass;
        if ($id!==null) {
            $model = $modelClass::findOne($id);
            if ($model === null) {
                throw new NotFoundHttpException;
            }
            return $model;
        }
        return new $modelClass;
    }
    
    /**
     * @param string $id the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $module, $config = []) {
        if(isset($config['modelClass']))
        {
            $this->modelClass = $config['modelClass'];
            unset($config['modelClass']);
        }
        $displayTitle = true;
        $displayReturnLink = true;
        if (isset(Yii::$app->params['extendedWebController'])) {
            if (isset(Yii::$app->params['extendedWebController']['displayTitle'])) {
                $displayTitle = Yii::$app->params['extendedWebController']['displayTitle'];
            }
            if (isset(Yii::$app->params['extendedWebController']['displayReturnLink'])) {
                $displayReturnLink = Yii::$app->params['extendedWebController']['displayReturnLink'];
            }
        }
        if ($displayTitle) {
            $this->attachBehavior(DisplayTitleBehavior::className(), DisplayTitleBehavior::className());
        }
        if ($displayReturnLink) {
            $this->attachBehavior(DisplayReturnLinkBehavior::className(), DisplayReturnLinkBehavior::className());
        }
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders a static string by applying a layout.
     * @param string $content the static string being rendered
     * @return string the rendering result of the layout with the given static string as the `$content` variable.
     * If the layout is disabled, the string will be returned back.
     * @since 2.0.1
     */
    public function renderContent($content) {
        ob_start();
        $this->trigger(self::EVENT_BEFORE_RENDER);
        echo $content;
        $output = ob_get_contents();
        ob_end_clean();
        $this->afterRender($output);
        return parent::renderContent($output);
    }

    public function afterRender(&$output)
    {
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER)) {
            $event = new ControllerRenderEvent([
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER, $event);
            $output = $event->output;
        }
    }
}
