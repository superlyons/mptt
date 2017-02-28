<?php

namespace superlyons\mptt;

use Yii;
use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


class Module extends \yii\base\Module
{

    public $mainLayout = '@superlyons/mptt/views/layouts/main.php';

    public $defaultUrl;
    public $defaultUrlLabel;

    /*
    [
        ['label'=>'labelname', 'url'=>'pathURL']
    ]
    */
    public $navbar;

    private $_normalizeMenus;
    //key=ControllerID, value = i18n label
    private $_coreItems = [
        'mptt-data' => 'MPTT Admin',
    ];
    /*
    [
        'ControllerID'=>['label'=>'labelname', 'url'=>'pathURL']
    ]
    */
    private $_menus = [];

    public $languageSelfManage = false;

    public function init()
    {
        parent::init();

        $this->languageinit(true);

        if (!isset(Yii::$app->i18n->translations['mptt'])) {
            Yii::$app->i18n->translations['mptt'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => '@superlyons/mptt/messages'
            ];
        }

        if (!isset(Yii::$app->i18n->translations['rbac-admin'])) {
            Yii::$app->i18n->translations['rbac-admin'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en',
                'basePath' => '@mdm/admin/messages'
            ];
        }
        //user did not define the Navbar?
        if ($this->navbar === null && Yii::$app instanceof \yii\web\Application) {
            $this->navbar = [
                ['label' => Yii::t('mptt', 'MPTT Admin'), 'url' => ['mptt-data/index']],
                ['label' => Yii::t('mptt', 'Help'), 'url' => ['default/index']],
                ['label' => Yii::t('mptt', 'Application'), 'url' => Yii::$app->homeUrl]
            ];
        }
        
    }

    public function beforeAction($action)
    {
        $this->languageinit();
        if (parent::beforeAction($action)) {
            /* @var $action \yii\base\Action */
            $view = $action->controller->getView();

            $view->params['breadcrumbs'][] = [
                'label' => ($this->defaultUrlLabel ?: Yii::t('mptt', 'MPTT')),
                'url' => ['/' . ($this->defaultUrl ?: $this->uniqueId)]
            ];
            return true;
        }
        return false;
    }

    public function languageinit($init=false){
        if($this->languageSelfManage){
            $file = file_get_contents(Yii::getAlias('@superlyons/mptt/language.txt'));
            Yii::$app->language = $file;
            if(!$init){
                $url = Url::toRoute($_GET);
                $this->navbar[] = ['label' => $file , 'url' => ['default/language' , 'url'=>$url ] ];
            };
        }
    }

    public function setMenus($menus)
    {
        $this->_menus = array_merge($this->_menus, $menus);
        $this->_normalizeMenus = null;
    }

    public function getMenus()
    {
        if ($this->_normalizeMenus === null) {
            $mid = '/' . $this->getUniqueId() . '/';
            // resolve core menus
            $this->_normalizeMenus = [];

            foreach ($this->_coreItems as $id => $lable) {
                $this->_normalizeMenus[$id] = ['label' => Yii::t('mptt', $lable), 'url' => [$mid . $id]];
            }
            foreach (array_keys($this->controllerMap) as $id) {
                $this->_normalizeMenus[$id] = ['label' => Yii::t('mptt', Inflector::humanize($id)), 'url' => [$mid . $id]];
            }

            foreach ($this->_menus as $id => $value) {
                if (empty($value)) {
                    unset($this->_normalizeMenus[$id]);
                    continue;
                }
                if (is_string($value)) {
                    $value = ['label' => $value];
                }
                $this->_normalizeMenus[$id] = isset($this->_normalizeMenus[$id]) ? array_merge($this->_normalizeMenus[$id], $value)
                        : $value;
                if (!isset($this->_normalizeMenus[$id]['url'])) {
                    $this->_normalizeMenus[$id]['url'] = [$mid . $id];
                }
            }
        }
        return $this->_normalizeMenus;
    }

}
