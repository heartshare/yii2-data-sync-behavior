<?php

namespace yii2mod\datasync\commands;

use Yii;
use yii\console\Controller;
use yii\db\QueryBuilder;
use yii\helpers\Console;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * Class DataSyncCommand
 * Usage
 * ~~~
 *   php yii datasync - import data from all files in folder path
 *   php yii datasync/index 'app\models\UserModel' - import data only for `UserModel`
 * ~~~
 * @package yii2mod\datasync\commands
 */
class DataSyncCommand extends Controller
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
     * Initializes the object
     */
    public function init()
    {
        if (isset(Yii::$app->params['dataSyncFolder'])) {
            $this->folderPath = Yii::$app->params['dataSyncFolder'];
        } else {
            $this->folderPath = Yii::getAlias('@app/config/data');
        }
    }

    /**
     * The command to import data from files that were created by `DataSyncBehavior`
     * @param null $className model className
     * @return bool
     */
    public function actionIndex($className = null)
    {
        if (!is_dir($this->folderPath)) {
            $this->stdout("Folder {$this->folderPath} does not exist.\n", Console::FG_RED);
            return false;
        }
        $time = microtime(true);
        $files = new \DirectoryIterator($this->folderPath);
        $importData = [];
        //Collect files data from `folderPath`
        foreach ($files as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            //If class name not null, get only one file from `folderPath`
            if ($className !== null) {
                $fileName = Inflector::variablize($className) . '.' . $this->fileExt;
                if ($fileInfo->getBasename() === $fileName) {
                    $fileContent = file_get_contents($fileInfo->getRealPath());
                    $importData[] = Json::decode($fileContent);
                    break;
                }
            } else {
                $fileContent = file_get_contents($fileInfo->getRealPath());
                $importData[] = Json::decode($fileContent);
            }
        }
        // Delete all data from table and import data from file.
        if (!empty($importData)) {
            $queryBuilder = new QueryBuilder(Yii::$app->db);
            Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS=0;")->execute();
            foreach ($importData as $value) {
                $modelClass = $value['meta']['class'];
                $tableName = $modelClass::tableName();
                Yii::$app->db->createCommand()->delete($tableName)->execute();
                $columns = $value['meta']['columns'];
                $rows = $value['data'];
                $insertQuery = $queryBuilder->batchInsert($tableName, $columns, $rows);
                Yii::$app->db->createCommand($insertQuery)->execute();
            }
            Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS=1;")->execute();
        } else {
            $this->stdout("Import data is empty.\n", Console::FG_RED);
        }
        $this->stdout("DataSyncCommand finished (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n", Console::FG_GREEN);
    }
}