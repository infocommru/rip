<?php

namespace app\controllers;

use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use Yii;

class ImageViewerController extends Controller {
    /**
     * @inheritDoc
     */
    public function behaviors() {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                ],
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Открывает по указанному относительному пути скан через OpenSeadragon
     * @param string $path
     * @return string
     */
    public function actionIndex(string $path): string {
        $this->layout = 'viewer';

        $filePath = FileHelper::normalizePath(Yii::getAlias("@images/" . $path));

        if(!is_file($filePath))
            throw new NotFoundHttpException("Файл $path не найден");

        $path = Url::to([
            '/image-viewer/image',
            'filename' => str_replace('\\', '/', $path),
        ]);

        return $this->render('index', [
            'path' => $path,
        ]);
    }

    /**
     * Возвращает файл изображаения по указанному пути
     *
     * @param string $filename
     * @return string
     */
    public function actionImage(string $filename): string
    {
        $file = FileHelper::normalizePath(Yii::getAlias("@images/{$filename}"));

        if (!is_file($file)) {
            throw new \yii\web\NotFoundHttpException('Файл не найлен');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'image/jpeg');

        return file_get_contents($file);
    }
}