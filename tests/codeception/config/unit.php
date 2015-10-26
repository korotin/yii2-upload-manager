<?php

return [
    'class' => 'yii\console\Application',
    'id' => 'test',
    'basePath' => __DIR__.'/../../',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '/',
            'baseUrl' => '/',
            'hostInfo' => 'http://test.loc',
        ],

        'uploads' => [
            'class' => 'herroffizier\yii2um\UploadManager',
        ],
    ],
];
