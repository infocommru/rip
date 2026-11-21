<?php

namespace app\controllers;

use app\models\Cemetery;
use app\models\BookUpload;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\validators\FileValidator;
use yii\web\UploadedFile;

use Yii;

/**
 * CemeteryController implements the CRUD actions for Cemetery model.
 */
class CemeteryController extends Controller {

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
                        ],
                    ],
                    'access' => [
                        'class' => AccessControl::className(),
                        'rules' => [
                            [
                                'allow' => true,
                                'roles' => ['@'],
                                'matchCallback' => fn($rule, $action) =>
                                    Yii::$app->user->identity instanceof \app\models\User
                                        && (int)Yii::$app->user->identity->role === 1
                            ],
                        ],
                        'denyCallback' => function ($rule, $action) {
                            return Yii::$app->response->redirect(['/']);
                        },
                    ],
                ]
        );
    }

    /**
     * Lists all Cemetery models.
     *
     * @return string
     */
    public function actionIndex(): string {
        $cemetery = new Cemetery();
        $cemetery->load($this->request->get());

        $query = Cemetery::find()->andWhere(['deleted' => 0]);

        if ($cemetery->name) {
            $query->andWhere(["like", "name", $cemetery->name]);
        }

        if ($cemetery->description) {
            $query->andWhere(["like", "description", $cemetery->description]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'model' => $cemetery
        ]);
    }

    /**
     * Displays a single Cemetery model.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id) {
        $dir_book = \Yii::getAlias('@bookSources');

        if (!is_dir($dir_book))
            FileHelper::createDirectory($dir_book);
    	
        if ($this->request->isPost) {
            $file = UploadedFile::getInstanceByName('zipfile');
            $validator = new FileValidator([
                'extensions' => ['zip'],
                'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
            ]);

            if ($validator->validate($file, $error)) {
                $upload = new BookUpload();
                $upload->cemetery_id = $id;
                $upload->add_at = time();
                $upload->filename = $file->name;

                if ($upload->save()) {
                    $destination_path = FileHelper::normalizePath($dir_book . '/' . $upload->id . '.zip');
                    $file->saveAs($destination_path);
                    Yii::$app->session->setFlash('success', "Подгрузили архив книг");
                }
            }
            else {
                Yii::$app->session->setFlash('error', $error);
                return $this->refresh();
            }
        }

        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Cemetery model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate() {
        $model = new Cemetery();

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
     * Updates an existing Cemetery model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id) {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Cemetery model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete(int $id) {
        $cemetery = $this->findModel($id);
        $cemetery->deleted = 1;
        $cemetery->save();

        \app\models\HelperCache::deleteCemetery($cemetery->id);
        return $this->redirect(['index']);
    }

    /**
     * Finds the Cemetery model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Cemetery the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = Cemetery::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
