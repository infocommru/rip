<?php

namespace app\controllers;

use app\models\Record;
use app\models\Book;
use app\models\RecordHistory;
use app\models\HelperCache;

use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \avadim\FastExcelWriter\Excel;
use Yii;

/**
 * RecordController implements the CRUD actions for Record model.
 */
class RecordController extends Controller {

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
                            'allow' => false,
                            'roles' => ['?'],
                        ],
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
     * Lists all Record models.
     *
     * @return string
     */
    public function actionIndex() {
        $user = \app\models\User::findIdentity(Yii::$app->user->id);

        $bookId = intval($_GET['book']);
        $book = \app\models\Book::find()->andWhere(["id" => $bookId])->one();

        $record = new Record();
        $record->load($this->request->get());

        $query = Record::find()
                ->andWhere(['book_id' => $bookId])
                ->andWhere(['deleted' => 0]);
        if ($record->age) {
            $query->andWhere(["like", "age", $record->age]);
        }

        if ($record->fio) {
            $query->andWhere(["like", "fio", $record->fio]);
        }

        if ($record->numReg) {
            $query->andWhere(["like", "numReg", $record->numReg]);
        }

        if ($record->death_date) {
            $query->andWhere(["like", "death_date", $record->death_date]);
        }

        if ($record->rip_date) {
            $query->andWhere(["like", "rip_date", $record->rip_date]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'user' => $user,
            'model' => new Record(),
            'book_id' => $bookId,
            'book' => $book
        ]);
    }

    /**
     * @return string
     */
    public function actionVopros() {
        $user = \app\models\User::findIdentity(Yii::$app->user->id);

        $record = new Record();
        $record->load($this->request->get());

        $query = Record::find()->andWhere(['vopros' => 1]);
        if ($record->age) {
            $query->andWhere(["like", "age", $record->age]);
        }

        if ($record->fio) {
            $query->andWhere(["like", "fio", $record->fio]);
        }

        if ($record->numReg) {
            $query->andWhere(["like", "numReg", $record->numReg]);
        }

        if ($record->death_date) {
            $query->andWhere(["like", "death_date", $record->death_date]);
        }

        if ($record->rip_date) {
            $query->andWhere(["like", "rip_date", $record->rip_date]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('vopros', [
            'dataProvider' => $dataProvider,
            'user' => $user,
            'model' => new Record(),
        ]);
    }

    /**
     * @return string
     */
    public function actionDeleted() {
        $user = \app\models\User::findIdentity(Yii::$app->user->id);

        $flash = '';
        if (isset($_GET['a'])) {
            switch ($_GET['a']) {
                case "restore":
                    $rc = Record::find()->andWhere(['id' => $_GET['record_id']])->one();
                    $rc->deleted = 0;
                    $rc->save();
                    HelperCache::updateSearchRecord($rc);
                    $flash = "Запись была успешно восстановлена";
                    break;
                case "del":
                    $rc = Record::find()->andWhere(['id' => $_GET['record_id']])->one();
                    $rc->deleted = 2;
                    $rc->save();
                    $flash = "Запись помечена как окончательно удаленная";
                    break;
            }
        }

        $record = new Record();
        $record->load($this->request->get());

        $query = Record::find()->andWhere(['deleted' => 1]);
        if ($record->age) {
            $query->andWhere(["like", "age", $record->age]);
        }

        if ($record->fio) {
            $query->andWhere(["like", "fio", $record->fio]);
        }

        if ($record->numReg) {
            $query->andWhere(["like", "numReg", $record->numReg]);
        }

        if ($record->death_date) {
            $query->andWhere(["like", "death_date", $record->death_date]);
        }

        if ($record->rip_date) {
            $query->andWhere(["like", "rip_date", $record->rip_date]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('deleted', [
            'dataProvider' => $dataProvider,
            'user' => $user,
            'model' => new Record(),
            'flash' => $flash
        ]);
    }

    /**
     * Displays a single Record model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id) {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Record model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     * @param int $book_id
     */
    public function actionCreate($book_id) {
        $book = \app\models\Book::find()->andWhere(["id" => $book_id])->one();
        $model = new Record();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->book_id = $book_id;
                $model->user_id = Yii::$app->user->id;
                $model->updated_at = time();
                $model->filename = str_replace('/', '\\', (string)$model->filename);
                $model->vopros = 0;

                if ($model->save()) {                   
                    HelperCache::updateSearchRecord($model);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'book' => $book
        ]);
    }

    /**
     * Updates an existing Record model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id) {
        $user = \app\models\User::findIdentity(Yii::$app->user->id);

        $model = $this->findModel($id);
        $book = \app\models\Book::find()->andWhere(["id" => $model->book_id])->one();

        $next = Record::find()->andWhere("id > $id")->andWhere(['book_id' => $model->book_id])->orderBy("id")->one();
        $prev = Record::find()->andWhere("id < $id")->andWhere(['book_id' => $model->book_id])->orderBy("id desc")->one();
        $first = Record::find()->andWhere("user_id is null")->andWhere(['book_id' => $model->book_id])->orderBy("id")->one();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->fio = preg_replace('/\s+/', ' ', trim((string)$model->fio));
            $model->updated_at = time();
            $model->user_id = Yii::$app->user->id;
            $model->filename = strtr($model->filename, [
                "/" => "\\"
            ]);

            $model->vopros = 0;

            $model1 = $this->findModel($id);
            $rHistory = new RecordHistory();
            $rHistory->record_id = $model1->id;
            $rHistory->updated_at = time();
            $rHistory->user_id = Yii::$app->user->id;
            $rHistory->info = serialize($model1->attributes);
            $rHistory->save();

            if ($model->save()) {
                HelperCache::updateSearchRecord($model);

                $id_next = $next ? $next->id : $model->id;
                $pnum = 1;

                if (($next) && ($model->filename == $next->filename))
                    $pnum = $_POST['pageNum'];
            }
        }

        return $this->render('update', [
            'model' => $model,
            'next' => $next,
            'first' => $first,
            'prev' => $prev,
            'user' => $user
        ]);
    }

    /**
     * @param int $id
     * @return array<int, list<list<int|string|null>|string>>
     */
    private function exportData(int $id): array {
        $header = [
            'Номер записи',
            'ФИО',
            'Возраст',
            'Дата смерти',
            'Дата захоронения',
            'Регистрационный № кремации',
            '№ счета по кремации',
            'Номер документа ЗАГС',
            'ЗАГС',
            'Номер участка',
            'Номер ряда',
            'Номер могилы',
            'Родственники',
            'Файл',
            'Комментарий',
            'Захоронение',
        ];

        $header2 = [
            'NumReg',
            'Dead_FIO',
            'Age',
            'Death_Date',
            'RIP_Date',
            'num_crem_reg',
            'num_crem_account',
            'DocNum',
            'ZAGS',
            'Area_Num',
            'Row_Num',
            'RIP_Num',
            'Relativ_FIO_Adress',
            'FileName',
            'Comment',
            'RIP_Style',
        ];

        $data_all = [];
        $data_all[] = $header2;
        $list = Record::find()->andWhere(['book_id' => $id])->andWhere(['deleted' => 0])->all();

        foreach ($list as $elem) {
            $one = [];
            if ($elem->numReg) {
                $one[] = $elem->numReg;
            } else {
                $one[] = $elem->numLiteral;
            }

            $one[] = $elem->fio;
            $one[] = $elem->age;
            $one[] = $elem->death_date;
            $one[] = $elem->rip_date;
            $one[] = $elem->num_crem_reg;
            $one[] = $elem->num_crem_account;
            $one[] = $elem->docnum;
            $one[] = $elem->zags;
            $one[] = $elem->area_num;
            $one[] = $elem->row_num;
            $one[] = $elem->rip_num;
            $one[] = $elem->relative_fio;
            $one[] = $elem->filename;
            $one[] = $elem->comment;
            $one[] = $elem->rip_style == 1 ? "Гроб" : "Урна";
            $data_all[] = $one;
        }

        return [$data_all, $header];
    }

    /**
     * @return void
     * @param int $id
     */
    public function actionExportExcel($id) {
        $data = $this->exportData($id);
        $excel = Excel::create();
        $sheet = $excel->sheet();
        
        $sheet->writeRow($data[1]);
        $sheet->writeArrayTo('A2', $data[0]);

        Yii::$app->response->clearOutputBuffers();

        $excel->download('book_' . $id . '.xlsx');
        exit();
    }

    /**
     * Deletes an existing Record model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id) {
        $model = $this->findModel($id);

        $model->deleted = 1;
        $model->save();

        $sCache = \app\models\CacheRecords::find()->query(['term' => ['record_id' => $model->id]])->one();
        
        if ($sCache) {
            $sCache->delete();
        }

        return $this->redirect(['index', 'book' => $model->book_id]);
    }

    /**
     * Finds the Record model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Record the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = Record::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
