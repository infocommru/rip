<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Book;
use app\models\Cemetery;
use app\models\BookUpload;
use app\models\HelperExcel;
use yii\helpers\FileHelper;
use \avadim\FastExcelReader\Excel;
use yii\helpers\StringHelper;
use Yii;

class UploadController extends Controller {

    /**
     * Creates the search index.
     * @return array{number: string, svazka: string, name: string}
	 * @param string $bookline
     */
    public function getBookinfo(string $bookline): array {

        $result = ['number' => "", 'svazka' => ''];

        $book = StringHelper::basename($bookline);
        $book = pathinfo($book, PATHINFO_FILENAME);
        $result['name'] = $book;

        if (preg_match("#.*?(\d+).*?(\d+).*?#", $book, $match)) {

            $result['number'] = $match[2];
            $result['svazka'] = $match[1];
        }
        
        return $result;
    }

    /**
     * Creates the search index.
     * @return void
	 * @param BookUpload $upload
     * @param (\Closure(string=): void)|null $updateStatus
     */
    public function processUpload(BookUpload $upload, ?\Closure $updateStatus = null): void {
        $dirPath = \Yii::getAlias('@app/web/upload/book/' . $upload->id);
        $dirPath = FileHelper::normalizePath($dirPath);
        $extractFile = \Yii::getAlias('@app/web/upload/book/' . $upload->id . '.zip');
        $extractFile = FileHelper::normalizePath($extractFile);

        if (is_dir($dirPath)) {
            FileHelper::removeDirectory($dirPath);
        }
        else {
            FileHelper::createDirectory($dirPath);
        }

        exec("unzip ". "$extractFile -d $dirPath");

        // Ищем файлы рекурсивно
        $excelFiles = FileHelper::findFiles($dirPath, [
            'only' => ['*.xlsx'], // Искать только файлы с расширением .xlsx
            'recursive' => true,  // Заходить во все вложенные папки
        ]);

        foreach ($excelFiles as $bookline) {
            $bookData = $this->getBookinfo($bookline);

            $book = new Book();
            $book->cemetery_id = $upload->cemetery_id;
            $book->name = $bookData['name'];

            $bookLast = Book::find()
                ->andWhere(['cemetery_id'=>$upload->cemetery_id])
                ->andWhere(['name'=>$bookData['name']])
                ->andWhere(['deleted'=>0])
                ->one();

            if($bookLast)$book = $bookLast;

            $book->number = $bookData['number'];
            $book->svazka = $bookData['svazka'];
            $book->records = '0';
            $book->save();

            $statInfo = HelperExcel::processBookExcel($book->id, $bookline, $updateStatus);

            $book->year1 = $statInfo['year1'] . '';
            $book->year2 = $statInfo['year2'] . '';
            $book->records = $statInfo['records'] . '';
            $book->per_page = $statInfo['per_page'];
            $book->save();
        }
    }

    /**
     * Creates the search index.
     * @return void
     * @param string|null $cacheKey
     */
    public function actionIndex(?string $cacheKey = null): void {
        $uploads = BookUpload::find()->andWhere(['status' => 0])->all();
        $totalUploads = count($uploads);

        for ($upload = 0; $upload !== $totalUploads; ++$upload) {
            if(Cemetery::find()->andWhere(['id'=>$uploads[$upload]->cemetery_id])->andWhere(['deleted'=>0])->one() == null){
                $uploads[$upload]->status = 3;
                $uploads[$upload]->save();
                continue;
            }

            $updateStatus = null;

            if($cacheKey){
				$uploadName = $uploads[$upload]->filename;
				$percentage = round(($upload / $totalUploads) * 100);

				$updateStatus = function(string $logs = '') use ($uploadName, $percentage, $cacheKey) {
					$oldLogs = Yii::$app->cache->get($cacheKey);
            		$oldLogs = ($oldLogs) ? $oldLogs['logs'] : '';

                    if($logs)
                        $oldLogs = ($oldLogs !== '') ? $oldLogs . PHP_EOL . $logs : $logs;

                    $oldLogs = mb_substr($oldLogs, -50000);

					Yii::$app->cache->set($cacheKey, [
						'name' => $uploadName,
						'percentage' => $percentage,
						'error' => false,
						'logs' => $oldLogs
					], 360);
				};
			}

            $uploads[$upload]->status = 1;
            $uploads[$upload]->save();

            $this->processUpload($uploads[$upload], $updateStatus);

            $uploads[$upload]->status = 2;
            $uploads[$upload]->save();
        }
    }
}
