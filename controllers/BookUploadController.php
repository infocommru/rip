<?php

namespace app\controllers;

use app\models\BookUpload;
use app\models\Book;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\jobs\RunJob;
use Yii;

/**
 * BookUploadController implements the CRUD actions for BookUpload model.
 */
class BookUploadController extends Controller {

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
                        'start-search-cache' => ['POST'],
                        'start-upload-books' => ['POST'],
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
     * Lists all BookUpload models.
     *
     * @return string
     */
    public function actionIndex(): string {
        $dataProvider = new ActiveDataProvider([
            'query' => BookUpload::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionStartSearchCache(): \yii\web\Response {
        $cacheKey = 'task_search_cache_' . Yii::$app->security->generateRandomString(16);

        $taskId = Yii::$app->queue->push(new RunJob([
            'route' => 'search-cache/index',
            'cacheKey' => $cacheKey,
        ]));

        Yii::$app->cache->set("queue:{$taskId}:cacheKey", $cacheKey, 86400);

        return $this->redirect([
            'book-upload/task-status',
            'id' => $taskId
        ]);
    }

    /**
     *
     * @return \yii\web\Response
     */
    public function actionStartUploadBooks(): \yii\web\Response {
        $cacheKey = 'task_search_cache_' . Yii::$app->security->generateRandomString(16);

        $taskId = Yii::$app->queue->push(new RunJob([
            'route' => 'upload/index',
            'cacheKey' => $cacheKey,
        ]));

        Yii::$app->cache->set("queue:{$taskId}:cacheKey", $cacheKey, 86400);

        return $this->redirect([
            'book-upload/task-status',
            'id' => $taskId
        ]);
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function actionTaskStatus(?int $id = null): string{
        return $this->render('task-status', ['taskId' => $id]);
    }

    /**
     * @param string $id
     * @return void
     */
    public function actionCheckStatus(string $id): void
    {
        $response = Yii::$app->response;
    
        // 1. Устанавливаем заголовки для SSE
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Отключаем буферизацию Nginx

        // Закрываем сессию, чтобы не блокировать другие запросы пользователя
        if (Yii::$app->has('session')) {
            Yii::$app->session->close();
        }

        $response->send();

        set_time_limit(0);

        $cacheKey = Yii::$app->cache->get("queue:{$id}:cacheKey");
        $queue = Yii::$app->queue;
        
        // Переменная для отслеживания предыдущего статуса, чтобы слать данные только при изменениях
        $lastStatus = null;
        $lastName = null;
        $lastPercentage = null;
        $lastLogs = '';
        $lastPingTime = time();

        while (true) {
            if (connection_aborted()) {
                break;
            }

            $data = null;
            $shouldSend = false;

            if ($queue->isWaiting($id)) {
                $data = [
                    'status' => 'waiting',
                    'percentage' => 0,
                    'name' => '',
                    'message' => 'Задача ожидает в очереди...'
                ];
            } elseif ($queue->isReserved($id)) {
                $progressData = ($cacheKey) ? Yii::$app->cache->get($cacheKey) : null;
                $data = [
                    'status' => 'running',
                    'percentage' => ($progressData) ? $progressData['percentage'] : 0,
                    'name' => ($progressData) ?  $progressData['name'] : '',
                    'message' => 'Задача выполняется...',
                    'logs' => ($progressData) ? $progressData['logs'] : ''
                ];
            } elseif ($queue->isDone($id)) {
                $progressData = ($cacheKey) ? Yii::$app->cache->get($cacheKey) : null;

                if ($progressData && $progressData['error'] === false) {
                    $data = [
                        'status' => 'done',
                        'message' => 'Готово! Задача успешно завершена.',
                    ];
                } else {
                    $data = [
                        'status' => 'unknown',
                        'message' => 'Статус неизвестен или задача упала с ошибкой.',
                        'logs' => ($progressData) ? $progressData['logs'] : ''
                    ];
                }
            } else {
                $progressData = ($cacheKey) ? Yii::$app->cache->get($cacheKey) : null;
                $data = [
                    'status' => 'unknown',
                    'message' => 'Статус неизвестен или задача упала с ошибкой.',
                    'logs' => ($progressData) ? $progressData['logs'] : ''
                ];
            }

            // --- Оптимизация трафика ---
            // Отправляем данные только если изменился статус или процент выполнения
            $currentStatus = $data['status'];
            $currentName = $data['name'] ?? null;
            $currentPercentage = $data['percentage'] ?? null;
            $currentLogs = !empty($data['logs']) ? md5($data['logs']) : '';

            if ($currentStatus !== $lastStatus || $currentName !== $lastName 
                        || $currentPercentage !== $lastPercentage || $lastLogs !== $currentLogs) {

                $shouldSend = true;
                $lastStatus = $currentStatus;
                $lastName = $currentName;
                $lastPercentage = $currentPercentage;
                $lastLogs = $currentLogs;
            }

            if ($shouldSend) {
                // Отправляем JSON-строку
                echo "data: " . json_encode($data) . "\n\n";
                
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $lastPingTime = time();
            }
            else {
                // Если данные не менялись более 5 секунд, отправляем пинг для поддержания соединения
                if (time() - $lastPingTime >= 5) {
                    echo ": ping\n\n";
                    flush();
                    $lastPingTime = time();
                }
            }

            // Если задача завершена (успешно или с ошибкой) — прерываем цикл и закрываем SSE-соединение
            if (in_array($data['status'], ['done', 'unknown'])) {
                break;
            }

            // Пауза перед следующей проверкой (например, 1 секунда)
            sleep(1);
        }

        Yii::$app->end();
    }
}
