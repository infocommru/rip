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
     * @throws NotFoundHttpException если путь некорректен, выходит за пределы @images или файл не найден
     */
    public function actionIndex(string $path): string {
        $this->layout = 'viewer';
        $this->resolveImagePath($path);

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
        $file = $this->resolveImagePath($filename);

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'image/jpeg');

        return file_get_contents($file);
    }

    /**
     * Безопасно преобразует относительный путь в абсолютный путь внутри каталога @images.
     *
     * @param string $relativePath
     * @return string абсолютный путь к существующему файлу
     * @throws NotFoundHttpException если путь некорректен, выходит за пределы @images или файл не найден
     */
    private function resolveImagePath(string $relativePath): string
    {
        $notFound = new NotFoundHttpException("Файл $relativePath не найден");

        if ($relativePath === '' || str_contains($relativePath, "\0"))
            throw $notFound;

        $baseDir = Yii::getAlias('@images');
        if ($baseDir === false)
            throw $notFound;

        $requested = FileHelper::normalizePath($baseDir . '/' . str_replace('\\', '/', $relativePath));
        // Файл должен находиться строго внутри каталога @images
        if (!str_starts_with($requested, $baseDir . DIRECTORY_SEPARATOR))
            throw $notFound;
        
        if ($requested === false || !is_file($requested) || !$this->isJpeg($requested))
            throw $notFound;

        return $requested;
    }

    /**
     * Проверяет расширение и MIME-тип файла по его содержимому через FileHelper.
     */
    private function isJpeg(string $file): bool
    {
        return in_array(mb_strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['pjp', 'jfif', 'jpe', 'pjpeg', 'jpeg', 'jpg'], true)
            && FileHelper::getMimeType($file) === 'image/jpeg';
    }
}