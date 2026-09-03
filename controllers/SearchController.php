<?php

namespace app\controllers;

use app\models\Record;
use app\models\Book;
use app\models\Cemetery;
use app\models\HelperImg;
use app\models\HelperCache;

use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \avadim\FastExcelWriter\Excel;
use yii\web\Response;
use Yii;

/**
 * RecordController implements the CRUD actions for Record model.
 */
class SearchController extends Controller {
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
     *
     * @param string $q
     * @param string $variable
     * @return string
     */
    public function actionSearchSuggest($q, $variable)
    {
        $query = \app\models\CacheRecords::find();

        $elasticQuery = [
            'multi_match' => [
                'query' => $q,
                'type'  => 'bool_prefix',
                'fields' => [
                    $variable . '.autocomplete',
                    $variable . '.autocomplete._2gram',
                    $variable . '.autocomplete._3gram',
                ],
            ]
        ];

        $query->query($elasticQuery);
        $query->addCollapse(['field' => $variable . '.keyword']);

        $response = $query->orderBy(['_score' => SORT_DESC, 'record_id' => SORT_DESC])->all();
        $response = \yii\helpers\ArrayHelper::getColumn($response, $variable);

        return json_encode($response);
    }

    /**
     *
     * @param string $search_string
     * @param string $name_var
     * @return array<string, mixed>
     */
    protected function searchTermConditions($search_string, $name_var) {
        $condition = ['bool' => ['should' => [
            [
                'match_phrase' => [
                    $name_var => [
                        'query' => $search_string,
                    ]
                ]
            ],
            [
                'term' => [
                    $name_var . '.keyword' => [
                        'value' => mb_strtolower($search_string),
                        'boost' => 10
                    ]
                ]
            ]
        ]]];

        return $condition;
    }

    /**
     *
     * @param string $search_string
     * @param string $name_var
     * @return array<string, mixed>
     */
    protected function searchStartFromConditions($search_string, $name_var) {
        $condition = [
            'prefix' => [
                $name_var . '.keyword' => $search_string
            ]
        ];

        return $condition;
    }

    /**
     *
     * @param string $search_string
     * @param string $name_var
     * @return array<string, mixed>
     */
    protected function searchEndFromConditions($search_string, $name_var) {
        $condition = [
            'wildcard' => [
                $name_var . '.wildcard' => [
                    'value' => '*' . addcslashes($search_string, '*?\\'),
                ],
            ]   
        ];

        return $condition;
    }

    /**
     *
     * @param string $search_string
     * @param string $name_var
     * @return array<string, mixed>
     */
    protected function searchFuzzinessConditions($search_string, $name_var) {
        $condition = ['bool' => ['should' => [
            [
                'term' => [
                    $name_var . '.keyword' => [
                        'value' => mb_strtolower($search_string),
                        'boost' => 10
                    ]
                ]
            ],
            [
                'match_phrase' => [
                    $name_var => [
                        'query' => $search_string,
                        'boost' => 7
                    ]
                ]
            ],
            [
                'multi_match' => [
                    'query' => $search_string,
                    'type'  => 'bool_prefix',
                    'fields' => [
                        $name_var . '.autocomplete',
                        $name_var . '.autocomplete._2gram',
                        $name_var . '.autocomplete._3gram',
                    ],
                    'boost' => 5
                ]
            ],
            [
                'match' => [
                    $name_var => [
                        'query' => $search_string,
                        'fuzziness' => 'AUTO',
                        'boost' => 1
                    ]
                ]
            ],
        ],
        ]];
        return $condition;
    }

    /**
     *
     * @param int $switch
     * @param string $search_string
     * @param string $name_var
     * @return array<string, mixed>
     */
    protected function searchValue(int $switch, string $search_string, string $name_var): array{
        switch ($switch) {
            case 1:
                $condition = self::searchTermConditions($search_string, $name_var);
                break;
            case 2:
                $condition = self::searchFuzzinessConditions($search_string, $name_var);
                break;
            case 3:
                $condition = self::searchStartFromConditions($search_string, $name_var);
                break;
            case 4:
                $condition = self::searchEndFromConditions($search_string, $name_var);
                break;
            default:
                $condition = [];
                break;
        }

        return $condition;
    }

    /**
     * Формирует условие для поиска по регулярному выражению
     * @param array<string, mixed> $search
     * @return array<string, mixed>|false
     */
    protected function searchCemetery(array $search): array | bool{
        if (empty($search)) {
            return false;
        }

		$elasticQuery = [];
        $elasticQuery['bool']['must'][] = ['term' => ['cemetery_id' => $search['cemetery']]];

        if ($search['regnum']) {
            $condition = self::searchTermConditions($search['regnum'], 'regnum');
            $elasticQuery['bool']['must'][] = $condition;
        }

        if ($search['fam']) {
            $elasticQuery['bool']['must'][] = self::searchValue($search['fam_cont'], $search['fam'], 'fam');
        }

        if ($search['nam']) {
            $elasticQuery['bool']['must'][] = self::searchValue($search['nam_cont'], $search['nam'], 'nam');
        }

        if ($search['ot']) {
            $elasticQuery['bool']['must'][] = self::searchValue($search['ot_cont'], $search['ot'], 'ot');
        }

        if ($search['unknown']) {
        	$elasticQuery['bool']['must'][] = ['term' => ['unknown' => 1]];
        }

        if ($search['unknown_number']) {
            $elasticQuery['bool']['must'][] = self::searchTermConditions( $search['unknown_number'], 'unknown_number');
        }

        if ($search['age']) {
            $age = intval($search['age']);

            switch (intval($search['age_cmp'])) {
                case 3:
                    $condition = ['range' => ['age_int' => ['gt' => $age]]];
                    break;
                case 2:
                	$condition = ['range' => ['age_int' => ['lt' => $age]]];
                    break;
                default:
                	$condition = ['term' => ['age_int' => $age]];
            }
            $elasticQuery['bool']['must'][] = $condition;
        }

        if ($search['rip_style']) {
            $rStyle = intval($search['rip_style']);

            switch ($rStyle) {
                case 4:
                case 3:
                case 2:
                case 1:
                	$elasticQuery['bool']['must'][] = [
                	'bool' => [
		            	'should' => [
		            		[
		            			'bool' => [
		            				'must' => [
		            					['term' => ['rip_style' => $rStyle]],
		            			 		['term' => ['book_rip_style' => 0]]
		            			 	]
		            			 ]
		            		],
		            			['term' => ['book_rip_style' => $rStyle]]
		            		]
		            	]
		            ];
                    break;
                default:
                    break;
            }
        }

        if (!empty($search['dead_y'])) {
            $dead_year = abs(intval($search['dead_y']));

            $dead_m = (!empty($search['dead_m']) && $search['dead_m'] >= 1 && $search['dead_m'] <= 12) 
                ? abs(intval($search['dead_m']))
                : null;

            $dead_d = (!empty($search['dead_d']) && $search['dead_d'] >= 1 && $search['dead_d'] <= 31) 
                ? abs(intval($search['dead_d']))
                : null;

            $cmp = isset($search['dead_year_cmp']) ? intval($search['dead_year_cmp']) : 0;

            switch ($cmp) {
                case 3: // Позже даты
                    $m = $dead_m ? sprintf('%02d', $dead_m) : '12';
                    $d = $dead_d ? sprintf('%02d', $dead_d) : '31';
                    $dead_date = sprintf('%s/%s/%04d', $d, $m, $dead_year);
                    $condition = ['range' => ['dead_date.date' => ['gt' => $dead_date]]];
                    break;

                case 2: // Раньше даты
                    $m = $dead_m ? sprintf('%02d', $dead_m) : '01';
                    $d = $dead_d ? sprintf('%02d', $dead_d) : '01';
                    $dead_date = sprintf('%s/%s/%04d', $d, $m, $dead_year);
                    $condition = ['range' => ['dead_date.date' => ['lt' => $dead_date]]];
                    break;

                default: // Точное совпадение по отдельным полям
                    $mustConditions = [
                        ['term' => ['dead_year' => $dead_year]]
                    ];

                    if ($dead_m) {
                        $mustConditions[] = ['term' => ['dead_month' => $dead_m]];
                    }
                    if ($dead_d) {
                        $mustConditions[] = ['term' => ['dead_day' => $dead_d]];
                    }

                    $condition = ['bool' => ['must' => $mustConditions]];
                    break;
            }

            $elasticQuery['bool']['must'][] = $condition;
        }

        if (!empty($search['rip_y'])) {
            $rip_year = abs(intval($search['rip_y']));

            $rip_m = (!empty($search['rip_m']) && $search['rip_m'] >= 1 && $search['rip_m'] <= 12) 
                ? abs(intval($search['rip_m']))
                : null;

            $rip_d = (!empty($search['rip_d']) && $search['rip_d'] >= 1 && $search['rip_d'] <= 31) 
                ? abs(intval($search['rip_d']))
                : null;

            $cmp = isset($search['rip_year_cmp']) ? intval($search['rip_year_cmp']) : 0;

            switch ($cmp) {
                case 3: // Позже даты
                    $m = $rip_m ? sprintf('%02d', $rip_m) : '12';
                    $d = $rip_d ? sprintf('%02d', $rip_d) : '31';
                    $rip_date = sprintf('%s/%s/%04d', $d, $m, $rip_year);
                    $condition = ['range' => ['rip_date.date' => ['gt' => $rip_date]]];
                    break;

                case 2: // Раньше даты
                    $m = $rip_m ? sprintf('%02d', $rip_m) : '01';
                    $d = $rip_d ? sprintf('%02d', $rip_d) : '01';
                    $rip_date = sprintf('%s/%s/%04d', $d, $m, $rip_year);
                    $condition = ['range' => ['rip_date.date' => ['lt' => $rip_date]]];
                    break;

                default: // Точное совпадение по отдельным полям
                    $mustConditions = [
                        ['term' => ['rip_year' => $rip_year]]
                    ];

                    if ($rip_m)
                        $mustConditions[] = ['term' => ['rip_month' => $rip_m]];
                    if ($rip_d)
                        $mustConditions[] = ['term' => ['rip_day' => $rip_d]];

                    $condition = ['bool' => ['must' => $mustConditions]];
                    break;
            }

            $elasticQuery['bool']['must'][] = $condition;
        }

        if ($search['num_crem_reg']){
            $elasticQuery['bool']['must'][] = self::searchValue($search['num_crem_reg_cont'], $search['num_crem_reg'], 'num_crem_reg');
        }

        if ($search['num_crem_account']){
            $elasticQuery['bool']['must'][] = self::searchValue($search['num_crem_account_cont'], $search['num_crem_account'], 'num_crem_account');
        }
        
        if ($search['zags']){
            $elasticQuery['bool']['must'][] = self::searchValue($search['zags_cont'], $search['zags'], 'zags');
        }
        
        if ($search['docnum']) {
            $elasticQuery['bool']['must'][] = self::searchTermConditions($search['docnum'], 'docnum');
        }
        
        if ($search['comment']) {
            $elasticQuery['bool']['must'][] = self::searchTermConditions($search['comment'], 'comment');
        }

        if ($search['ext_search']) {
            if ($search['areanum']) {
                $elasticQuery['bool']['must'][] = self::searchValue($search['area_cont'], $search['areanum'], 'areanum');
            }

            if ($search['rownum']) {
                $elasticQuery['bool']['must'][] = self::searchValue($search['row_cont'], $search['rownum'], 'rownum');
            }

            if ($search['ripnum']) {
                $elasticQuery['bool']['must'][] = self::searchValue($search['rip_cont'], $search['ripnum'], 'ripnum');
            }

            if ($search['rel']) {
            	$elasticQuery['bool']['must'][] = self::searchTermConditions($search['rel'], 'relative');
            }
        }

        return $elasticQuery;
    }
    
    /**
     * 
     * @param array<string, mixed> $search
     * @param int $page
     * @param int $paginator
     * @return string
     */
    public function actionShowSearchResult(array $search, int $page = 1, int $paginator = 100): string 
    {
        $found = $this->searchCemetery($search);

        $query = \app\models\CacheRecords::find()
            ->query($found)
            ->orderBy(['_score' => SORT_DESC, 'record_id' => SORT_DESC]);

        $count = $query->count();

        // 4. Передаем именно $query в ActiveDataProvider
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,       // Yii2 считает страницы с 0
                'pageSize' => $paginator,
                'pageParam' => 'page',
            ],
        ]);

        // 5. Используем renderAjax для передачи фрагмента во вкладку
        return $this->renderAjax('search_result', [
            'dataProvider' => $dataProvider,
            'count_result' => $count,
            'search' => $search,
        ]);
    }

    /**
    * @param array<string, mixed> $search
    * @return array<int, array{
    *     id: int,
    *     name: string,
    *     exists: bool
    * }>
    */
    public function actionFoundResultExists(array $search): array{
        $result = [];

        if($search['cemetery'] == 0){
            $cemeteries = Cemetery::find()->select(['id', 'name'])->orderBy('name')->all();

            foreach($cemeteries as $cemetery){
                $search['cemetery'] = $cemetery->id;
                $found = $this->searchCemetery($search);
                $query = \app\models\CacheRecords::find();
                
                $search['cemetery'] =  $cemetery->id;
                $result[] = ['id' => $cemetery->id, 'name' => $cemetery->name, 'exists' => $query->query($found)->exists() ];
            }
        }
        else{
            $cemetery = Cemetery::find()->where(['id' => $search['cemetery']])->one();

            if(!$cemetery)
                return [];

            $found = $this->searchCemetery($search);
            $query = \app\models\CacheRecords::find();

            $result[] = ['id' => $cemetery->id, 'name' => $cemetery->name, 'exists' => $query->query($found)->exists() ];
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;
    }

    /**
     * @return string
     */
    public function actionIndex(): string {
        return $this->render('index', []);
    }

    /**
     * @param array<string, mixed> $search
     * @return array{0: array<int, array<int, mixed>>, 1: array<int, string>}
     */
    private function exportData(array $search): array {
        $query = $this->searchCemetery($search);

        $data = \app\models\CacheRecords::find()
            ->query($query)
            ->orderBy(['_score' => SORT_DESC, 'record_id' => SORT_DESC])
            ->limit(10000)
            ->asArray()
            ->all();
    
        $header = [
            'Номер записи',
            'ФИО',
            'Возраст',
            'Дата смерти',
            'Дата захоронения',
            'Регистрационный № кремации',
            '№ счета по кремации',
            'Документ',
            'ЗАГС',
            'Захоронение',
            'Номер участка',
            'Номер ряда',
            'Номер могилы',
            'Родственники',
            'Доп. инфо',
        ];

        $data_all = [];

        foreach ($data as $elem) {
            $one = [];

            $one[] = $elem['_source']['regnum'] ?? '';
            $one[] = $elem['_source']['fio_display'] ?? '';
            $one[] = $elem['_source']['age'] ?? '';
            $one[] = $elem['_source']['dead_date'] ?? '';
            $one[] = $elem['_source']['rip_date'] ?? '';
            $one[] = $elem['_source']['num_crem_reg'] ?? '';
            $one[] = $elem['_source']['num_crem_account'] ?? '';
            $one[] = $elem['_source']['docnum'] ?? '';
            $one[] = $elem['_source']['zags'] ?? '';
            $one[] = $elem['_source']['rip_style'] == 1 ? "Гроб" : "Урна";
            $one[] = $elem['_source']['areanum'] ?? '';
            $one[] = $elem['_source']['rownum'] ?? '';
            $one[] = $elem['_source']['ripnum'] ?? '';
            $one[] = $elem['_source']['relative'] ?? '';

            $dopInfo = "св. {$elem['_source']['svazka_num']}, кн. {$elem['_source']['book_num']}, стр. {$elem['_source']['page_num']}, п/п: {$elem['_source']['page_punkt']}";

            if ($elem['_source']['comment'])
                $dopInfo .= "\n " . $elem['_source']['comment'];

            $one[] = $dopInfo;
            $data_all[] = $one;
        }

        return [$data_all, $header];
    }

    /**
     * @param array<string, mixed> $search
     * @return void
     */
    public function actionExport(array $search): void {
        $data = $this->exportData($search);

        $excel = Excel::create();
        $sheet = $excel->sheet();
        
        $sheet->writeRow($data[1]);
        $sheet->writeArrayTo('A2', $data[0]);

        Yii::$app->response->clearOutputBuffers();
        $excel->download("search.xlsx");

        exit();
    }

    /**
     *
     * @param int $record_id
     * @return void
     */
    public function actionVopros($record_id) {
        $record = Record::find()->andWhere(['id' => $record_id])->one();
        $record->vopros = 1;
        $record->save();

        HelperCache::updateSearchRecord($record);
    }

    /**
     *
     * @param int $book_id
     * @return void
     */
    public function actionBookCover($book_id) {
        $book = Book::find()->andWhere(['id' => $book_id])->one();
        $titleBook = HelperImg::getTitleImage($book);
        
        if($titleBook){
            header("Location: $titleBook");
            exit;
        }
        else
            throw new NotFoundHttpException('Страница не найдена.');
    }
}