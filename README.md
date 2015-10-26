Yii2 Upload Manager
===================

[![Build Status](https://travis-ci.org/herroffizier/yii2-upload-manager.svg?branch=develop)](https://travis-ci.org/herroffizier/yii2-upload-manager) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/herroffizier/yii2-upload-manager/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/herroffizier/yii2-upload-manager/?branch=develop) [![Code Coverage](https://scrutinizer-ci.com/g/herroffizier/yii2-upload-manager/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/herroffizier/yii2-upload-manager/?branch=develop)

Yii2 Upload Manager is a small extension that organizes file uploads and takes control over upload paths and urls.

Features
--------

* Groups uploads into folders and folder structures with any depth. 
* Divides upload folders into subfolders to avoid storing many files in one folder.
* Fights against file name collisions.
* Uses transparent file name generation mechanism.
* 100% code coverage :-)

Installation
------------

Install extension with Composer:

```
composer require herroffizier/yii2-upload-manager
```

Add extension to your application config:
```php
'components' => [
    
    // ...

    'uploads' => [
        'class' => 'herroffizier\yii2um\UploadManager',
        // path to upload folder
        'uploadDir' => '@webroot/upload',
        // url to upload filder
        'uploadUrl' => '@web/upload',
    ],

    // ...
]
```

There is no need to create upload folder manually. Extension will make it automatically.

Usage
-----

### Simple cases

Extension provides two ways for storing files. First one is to store raw data as file. Look at example:

```php
$content = 'test';

$filePath = 
    Yii::$app->uploads->saveContent(
        // upload group
        'useless-files',
        // upload file name
        'file.txt',
        // file content
        $content
    );

echo $filePath;
```

This code will output ```useless-files/75/file.txt``` (as you may guess, subfolder ```75``` is a partition subfolder). 

```$filePath``` value is an unique upload id which will remain the same even if you move or rename root upload folder. So, if you want to save link to upload somewhere, you definitely should use value that ```saveContent()``` method returns.

Now, let's try to get absolute path and URL to our upload:

```php
// get absolute path
$absoluteFilePath = Yii::$app->uploads->getAbsolutePath($filePath);

// get url
$relativeUrl = Yii::$app->uploads->getUrl($filePath);
```

Okay, let's try to use ```\yii\web\UploadedFile``` instance to save file:

```php
$upload = \yii\web\UploadedFile::getInstance(/* ... */);

$filePath = 
    Yii::$app->uploads->saveUpload(
        // upload group
        'useless-files',
        // \yii\web\UploadedFile instance
        $upload
    );
```

```saveUpload()``` method works likewise ```saveContent()``` and returns unique upload id as well. Hovewer ```saveUpload()``` does not accept file name since it takes one from ```\yii\web\UploadedFile::$name``` property.

### Name collisions

By deafult if you'll try to save file that already exists extension will throw an exception. Such behavior is not always suitable and you definitely don't want to solve each collision manually. So you have two different strategies which solve collisions automatically.

First strategy is to overwrite existing file silently. Such approach may be suitable for saving user avatars, for example.

Second strategy is to add incremental index to file name in case of collision. This strategy may be applied when dealing with user file uploads and original file names are important.

Now let's find out how to apply these strategies.

Strategy (throw exception, overwrite, add index) is identified by constant defined in ```\herroffizier\yii2um\UploadManager``` class: ```\herroffizier\yii2um\UploadManager::STRATEGY_KEEP```, ```\herroffizier\yii2um\UploadManager::STRATEGY_OVERWRITE``` and ```\herroffizier\yii2um\UploadManager::STRATEGY_RENAME```. 

Also both methods ```saveContent``` and ```saveUpload``` have optional last parameter named ```$overwriteStrategy``` to which one of constants may be passed. Default value for ```$overwriteStrategy``` is ```\herroffizier\yii2um\UploadManager::STRATEGY_KEEP```.

To sum up, let's take a look at example. Following code will throw an exception:

```php
Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 1'
);

Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 2'
);

```

Hovewer, this code will work correctly because we applied overwrite strategy:

```php
Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 1'
);

Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 2', 
    \herroffizier\yii2um\UploadManager::STRATEGY_OVERWRITE
);

```

Now ```file.txt``` contains ```test 2``` string.

Finally, let's apply rename strategy:

```php
$filePath1 = Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 1'
);

$filePath2 = Yii::$app->uploads->saveContent(
    'useless-files', 
    'file.txt', 
    'test 2', 
    \herroffizier\yii2um\UploadManager::STRATEGY_RENAME
);

echo "$filePath1, $filePath2";
```

This code will also complete correctly and output ```useless-files/75/file.txt, useless-files/75/file-1.txt```. As you may see, second file has an index ```1``` at the end of its name.