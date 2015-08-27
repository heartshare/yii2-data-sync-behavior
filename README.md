# Yii2 data synchronization behavior
Behavior for export data to local files from database tables. Changes are tracked using model events.

Installation 
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2mod/yii2-data-sync-behavior "*"
```

or add

```
"yii2mod/yii2-data-sync-behavior": "*"
```

to the require section of your `composer.json` file.

## Recording changes

Attach the behavior to the model:

```php
use yii2mod\datasync\DataSyncBehavior;

public function behaviors()
{
    return [
        ....
        'dataSync' => [
                'class' => DataSyncBehavior::className(),
        ],
    ];
}

```
* After model events(AFTER_UPDATE, AFTER_INSERT, AFTER_DELETE) behavior will be automatically create files in default path(@app/config/data) with data from this model. 
* Files will be created in json format.
 
## Import changes

1. Add `datasync` command to console config:

 ```
    'controllerMap' => [
        .....
        'datasync' => 'yii2mod\datasync\commands\DataSyncCommand',
    ],
  ```
2. Execute command from console. For example:

   ```
   php yii datasync - import data from all files stored files
   php yii datasync/index 'app\models\UserModel' - import data only for `UserModel`
   ```
