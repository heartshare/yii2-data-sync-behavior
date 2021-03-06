<?php

namespace yii2mod\datasync;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * Class DataSyncBehavior
 * @package yii2mod\datasync
 */
class DataSyncBehavior extends Behavior
{
    /**
     * @var string the path to the folder for save the data
     */
    public $folderPath;

    /**
     * @var string file extension
     */
    protected $fileExt = 'json';

    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'dataSynchronization',
            ActiveRecord::EVENT_AFTER_INSERT => 'dataSynchronization',
            ActiveRecord::EVENT_AFTER_DELETE => 'dataSynchronization',
        ];
    }

    /**
     * Initializes the object
     */
    public function init()
    {
        if (isset(Yii::$app->params['dataSyncFolder']) && $this->folderPath === null) {
            $this->folderPath = Yii::getAlias(Yii::$app->params['dataSyncFolder']);
        } else {
            $this->folderPath = Yii::getAlias('@app/config/data');
        }
        
        parent::init();
    }

    /**
     * Data synchronization
     * Write to file export data
     */
    public function dataSynchronization()
    {
        FileHelper::createDirectory($this->folderPath);

        file_put_contents($this->getFileName(), $this->getExportData());
    }

    /**
     * Return export data in json format
     * 
     * @return string
     */
    protected function getExportData()
    {
        $model = $this->owner;
        $className = $model::className();
        $rows = $className::find()->asArray()->all();
        
        return Json::htmlEncode([
            'meta' => [
                'class' => $className,
                'columns' => array_keys($model->getAttributes())
            ],
            'data' => $rows
        ]);
    }

    /**
     * Get file name with folder path
     * 
     * @return string
     */
    protected function getFileName()
    {
        $model = $this->owner;
        $className = $model::className();
        
        return $this->folderPath . DIRECTORY_SEPARATOR . Inflector::variablize($className) . "." . $this->fileExt;
    }
}
