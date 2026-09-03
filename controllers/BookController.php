<?php

namespace app\controllers;

use app\models\Record;
use app\models\Book;
use app\models\HelperImg;
use app\models\HelperCache;

use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \avadim\FastExcelReader\Excel;
use yii\helpers\FileHelper;
use yii\validators\FileValidator;
use yii\web\UploadedFile;

/**
 * BookController implements the CRUD actions for Book model.
 */
class BookController extends Controller {

    /**
     * @inheritDoc
     */
    public function behaviors() {

        return array_merge(
                parent::behaviors(),
                [
                    'verbs' => [
                        'class' => VerbFilter::className(),
                        'actions' => [
                            'delete' => ['POST'],
                            'rename-folder' => ['POST'],
                            'download-images' => ['POST'],
                            'reload-image' => ['POST']
                        ],
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
     * Lists all Book models.
     *
     * @return string
     */
    public function actionIndex(): string {
        $book = new Book();
        $book->load($this->request->get());

        $query = Book::find()->andWhere(['deleted' => 0]);
        if ($book->name) {
            $query->andWhere(["like", "name", $book->name]);
        }

        if ($book->number) {
            $query->andWhere(["number" => $book->number]);
        }

        if ($book->svazka) {
            $query->andWhere(["svazka" => $book->svazka]);
        }

        if ($book->records) {
            $query->andWhere(["like", "records", $book->records]);
        }

        if ($book->year1) {
            $query->andWhere(["like", "year1", $book->year1]);
        }

        if ($book->year2) {
            $query->andWhere(["like", "year2", $book->year2]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'model' => $book
        ]);
    }

    /**
     * Displays a single Book model.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id): string|\yii\web\Response {
        if($this->request->isPost) {
            $file = UploadedFile::getInstanceByName('excel');
            $validator = new FileValidator([
                'extensions' => ['xlsx'],
                'mimeTypes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ]);

            if($validator->validate($file, $error)){
                set_time_limit(0);
                \app\models\HelperExcel::processBookExcel($id, $file->tempName);
                \Yii::$app->session->setFlash('success', 'Файл успешно обработан.');
            }
            else{
                \Yii::$app->session->setFlash('error', 'Ошибка! ' . $error);
                return $this->refresh();
            } 
        }

        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Book model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate(): string|\yii\web\Response {
        $model = new Book();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Book model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id): string|\yii\web\Response {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            \app\models\HelperCache::updateCache([$model]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Book model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete(int $id): \yii\web\Response {
        $book = $this->findModel($id);
        $book->deleted = 1;
        $book->save();

        \app\models\HelperCache::deleteBook($book->id);
        return $this->redirect(['index']);
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @return string
     */
    public function actionUploadImages(int $id): string {
        if (\app\models\User::findIdentity(\Yii::$app->user->id)->role != 1) {
            $this->redirect(['/']);
        }
    
        $model = $this->findModel($id);

        return $this->render('uploadImages', [
            'book' => $model,
        ]);
    }

    public function actionReloadImage(): \yii\web\Response {
        if (\app\models\User::findIdentity(\Yii::$app->user->id)->role != 1) {
            $this->redirect(['/']);
        }
    
        try {
            $file = UploadedFile::getInstanceByName('image');
            $id = $this->request->post('id');
            $filename = $this->request->post('filename');

            $validator = new FileValidator([
                'extensions' => ['pjp', 'jfif', 'jpe', 'pjpeg', 'jpeg', 'jpg'],
                'mimeTypes' => ['image/jpeg'],
                'maxFiles' => 200,
                'maxSize' => 2 * 1024 * 1024
            ]);

            if(!$validator->validate($file, $error))
                throw new \yii\base\UserException($error);

            $dirpath = FileHelper::normalizePath(\Yii::getAlias("@images/" .  $filename));

            if(!Record::find()->where(['filename' => $filename, 'book_id' => $id])->exists())
                throw new \yii\base\UserException("Скана нет в базе $file->name");

            if(!@$file->saveAs($dirpath))
                throw new \yii\base\UserException("Не удается сохранить скан $file->name");

            \Yii::$app->session->setFlash('success', "Скан $filename сохранен");
        }
        catch (\Throwable $exception){
            \Yii::$app->session->setFlash('error', "Ошибка! " .  $exception->getMessage());
        }

        return $this->redirect(\Yii::$app->request->referrer);
    }

    public function actionDownloadImages(): \yii\web\Response {
        if (\app\models\User::findIdentity(\Yii::$app->user->id)->role != 1) {
            $this->redirect(['/']);
        }

        $files = UploadedFile::getInstancesByName('images');
        $id = (int) $this->request->post('id');

        try {
            set_time_limit(0);

            $validator = new FileValidator([
                'extensions' => ['pjp', 'jfif', 'jpe', 'pjpeg', 'jpeg', 'jpg'],
                'mimeTypes' => ['image/jpeg'],
                'maxFiles' => 200,
                'maxSize' => 2 * 1024 * 1024
            ]);

            if(empty($files))
                throw new \yii\base\UserException("Загрузите файлы");

            if($id === 0)
                throw new \yii\base\UserException("Идентификатор книги неверный");

            $book = Book::findOne($id);

            if(!$book)
                throw new \yii\base\UserException("Идентификатор книги неверный");

            $orgPath = HelperImg::getImagesFilepath($book);

            if(!$orgPath['existed'])
                throw new \yii\base\UserException("Неверный путь к директории");

            $dirpath = FileHelper::normalizePath(\Yii::getAlias("@images/" .  $orgPath['path']));

            foreach ($files as $file){
                $filename = FileHelper::normalizePath($dirpath . '/' . $file->name);

                if(!$validator->validate($file, $error))
                    throw new \yii\base\UserException($error);

                if(is_file($filename))
                    throw new \yii\base\UserException("Файл $file->name уже существует");

                if(!@$file->saveAs($filename))
                    throw new \yii\base\UserException("Не удается сохранить файл $file->name");
            }

            \Yii::$app->session->setFlash('success', "Файлы сохранены");
        }
        catch (\Throwable $exception){
            \Yii::$app->session->setFlash('error', "Ошибка! " .  $exception->getMessage());
        }

        return $this->redirect(\Yii::$app->request->referrer);
    }

    /**
     * Undocumented function
     *
     * @return \yii\web\Response
     */
    public function actionRenameFolder(): \yii\web\Response {
        if (\app\models\User::findIdentity(\Yii::$app->user->id)->role != 1) {
            $this->redirect(['/']);
        }

        $id = (int) $this->request->post('id');
        $folderPath = $this->request->post('final-imagefolder');
        $folderPath = str_replace('/', '\\', $folderPath);

        try {
            if(!$folderPath)
                throw new \yii\base\UserException("Путь не может быть пустым");
            else if(mb_strlen($folderPath) > 256)
                throw new \yii\base\UserException("Путь не может быть длинее 256 символов");

            if($id === 0)
                throw new \yii\base\UserException("Идентификатор книги неверный");

            $book = Book::findOne($id);

            if(!$book)
                throw new \yii\base\UserException("Идентификатор книги неверный");

            $orgPath = HelperImg::getImagesFilepath($book, true)['path'];
            $originalPath = FileHelper::normalizePath(\Yii::getAlias("@images/" . $orgPath));
            $newPath = FileHelper::normalizePath(\Yii::getAlias("@images/" . $folderPath));

            if($newPath === FileHelper::normalizePath(\Yii::getAlias("@images")) || @file_exists($newPath))
                throw new \yii\base\UserException("Путь указан неверно");

            $updateValue = function() use ($orgPath, $folderPath, $id, $book){
                $safePath = addcslashes($orgPath, '%_\\') . '%';

                if($orgPath){
                    $command = \Yii::$app->db->createCommand()->update(
                        Record::tableName(),
                        [
                            // Привязываем параметры выражения прямо внутри Expression
                            'filename' => new \yii\db\Expression(
                                'REPLACE({{filename}}, :old_text, :new_text)', 
                                [
                                    ':old_text' => $orgPath,
                                    ':new_text' => $folderPath,
                                ]
                            )
                        ],
                        [
                            'and',
                            ['book_id' => $id],
                            ['like', 'filename', $safePath, false] 
                        ]
                    );
                }
                else {
                    $command = \Yii::$app->db->createCommand()->update(
                        Record::tableName(),
                        [ 'filename' => $folderPath, ],
                        [ 'book_id' => $id ]
                    );
                }

                $command->execute();
                HelperCache::updateCache([$book]);
            };

            if($originalPath !== FileHelper::normalizePath(\Yii::getAlias("@images")) 
                        && is_dir($originalPath)){
                if(!@rename($originalPath, $newPath))
                    throw new \yii\base\UserException("Не удалось переименовать директорию");

                \Yii::$app->session->setFlash('success', "Директория переименована!");
                $updateValue();
                
            }
            else {
                if(!@FileHelper::createDirectory($newPath))
                    throw new \yii\base\UserException("Не удалось создать директорию");

                \Yii::$app->session->setFlash('success', "Директория создана!");
                $updateValue();
            }
        }
        catch (\Throwable $exception) {
            \Yii::$app->session->setFlash('error', "Ошибка! " .  $exception->getMessage());
        }

        return $this->redirect(\Yii::$app->request->referrer);
    }

    /**
     * Displays a single Book model.
     * @param int $book_id ID
     * @return list<array<string, array<string, string>|string>>
     */
    public function actionGetImagesPath(int $book_id): array {
        $book = Book::findOne($book_id);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if(!$book)
            return [];

        return HelperImg::getImages($book);
    }

    /**
     * Finds the Book model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Book the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Book {
        if (($model = Book::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }

        return parent::beforeAction($action);
    }
}
