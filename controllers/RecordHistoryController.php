<?php

namespace app\controllers;

use app\models\RecordHistory;
use app\models\Record;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * RecordHistoryController implements the CRUD actions for RecordHistory model.
 */
class RecordHistoryController extends Controller {

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
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all RecordHistory models.
     *
     * @return string|\yii\web\Response
     * @param int $record_id
     */
    public function actionIndex($record_id = 0) {
        if (!$record_id) {
            return $this->redirect("/web/search/index");
        }
        $dataProvider = new ActiveDataProvider([
            'query' => RecordHistory::find()
                ->andWhere(['record_id' => $record_id]),
        ]);

        $record = Record::find()->andWhere(['id' => $record_id])->one();
        $history = RecordHistory::find()
            ->andWhere(['record_id' => $record_id])
            ->orderBy("id desc")
            ->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'model' => $record,
            'history' => $history
        ]);
    }
    
    /**
     * Finds the RecordHistory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return RecordHistory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = RecordHistory::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
