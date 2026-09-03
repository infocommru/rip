<?php
namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;
use Throwable;

class RunJob extends BaseObject implements JobInterface
{
    // Параметры, которые мы хотим передать в консольную команду
    /**
     * @var string $route
     */
    public $route; // Например: 'migrate/up' или 'my-custom/process'
    /**
     * @var string $cacheKey
     */
    public $cacheKey;

    /**
     * Creates the search index.
     * @return void
	 * @param Queue $queue
     * @throws \yii\base\Exception
     */
    public function execute($queue)
    {
        // Нам нужен доступ к консольному приложению. 
        // Если воркер запущен через `yii queue/listen`, то Yii::$app уже является консольным.
        try {
            if (Yii::$app instanceof \yii\console\Application) {
                // Запускаем команду. Метод возвращает exit status (0 - успех, >0 - ошибка)
                $exitCode = 1;

                if($this->route === 'search-cache/index')
                    $exitCode = Yii::$app->runAction($this->route, [ $this->cacheKey ]);
                else if($this->route === 'upload/index')
                    $exitCode = Yii::$app->runAction($this->route, [ $this->cacheKey ]);

                if ($exitCode !== 0) {
                    // Если команда завершилась с ошибкой, бросаем исключение,
                    // чтобы Yii2-Queue пометил эту задачу как проваленную и залогировал её.
                    throw new \yii\base\Exception("Консольная команда {$this->route} завершилась с ошибкой. Код: {$exitCode}");
                }

                $oldLogs = Yii::$app->cache->get($this->cacheKey);
                $oldLogs = ($oldLogs) ? $oldLogs['logs'] : '';

                Yii::$app->cache->set($this->cacheKey, [
                    'name' => '',
					'percentage' => 100,
                    'error' => false,
                    'logs' => $oldLogs
                ], 120);
                
            } else {
                throw new \yii\base\Exception("Критическая ошибка: Очередь запущена не в консольном контексте.");
            }
        }
        catch (Throwable $e) {
            $oldLogs = Yii::$app->cache->get($this->cacheKey);
            $oldLogs = ($oldLogs) ? $oldLogs['logs'] : '';
            $oldLogs = ($oldLogs !== '') ? $oldLogs . PHP_EOL : '';

            Yii::$app->cache->set($this->cacheKey, [
                'logs' => $oldLogs . (string)$e,
                'error' => true,
            ], 120);
        }
    }
}
?>