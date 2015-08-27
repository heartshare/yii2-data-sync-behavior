<?php

namespace yii2mod\datasync;

use Yii;
use yii\base\Behavior;
use yii\base\Model;
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
            $this->folderPath = Yii::$app->params['dataSyncFolder'];
        } else {
            $this->folderPath = Yii::getAlias('@app/config/data');
        }
    }

    /**
     * Data synchronization
     * Write to file export data
     */
    public function dataSynchronization()
    {
        FileHelper::createDirectory($this->folderPath);
        $exportData = $this->getExportData();
        $fileName = $this->getFileName();
        file_put_contents($fileName, $exportData);
    }

    /**
     * Return export data in json format
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
                'date' => Yii::$app->formatter->asDatetime(time()),
                'columns' => array_keys($model->getAttributes())
            ],
            'data' => $rows
        ]);
    }

    /**
     * Get file name with folder path
     * @return string
     */
    protected function getFileName()
    {
        /* @var $model Model */
        $model = $this->owner;
        $className = $model::className();
        return $this->folderPath . DIRECTORY_SEPARATOR . Inflector::variablize($className) . "." . $this->fileExt;
    }
}