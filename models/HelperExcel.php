<?php

namespace app\models;

use app\models\Book;
use \avadim\FastExcelReader\Excel;

class HelperExcel {
    /**
     * @return void
     * @param int $bookId
     */
	private static function updateBookIndex($bookId){
		$book = Book::find()
        	->andWhere(['id' => $bookId])
        	->one();
                   
		\app\models\HelperCache::updateCache([$book]);
	}
    /**
     * @return array<int>
     * @param int $bookId
     * @param string $filepath
     * @param (\Closure(string=): void)|null $updateStatus
     */
    public static function processBookExcel(int $bookId, string $filepath, ?\Closure $updateStatus = null): array {
        $error_print = false;

        $excel = Excel::open($filepath);
        $excel->dateFormatter(fn($value) => gmdate('d/m/Y', $value));//форматирование даты в формате d/m/Y
        $sheet = $excel->sheet()->setReadAreaColumns('A:Q');

        $statInfo = [
            'year1' => null,
            'year2' => null,
            'records' => 0,
            'per_page' => 0
        ];

        $filenames = [];

        $saveDataNames = [
            'book_id',
            'numReg',
            'numLiteral',
            'fio',
            'age',
            'death_date',
            'rip_date',
            'docnum',
            'zags',
            'area_num',
            'row_num',
            'rip_num',
            'relative_fio',
            'filename',
            'comment',
            'rip_style',
            'num_crem_reg',
            'num_crem_account',
        ];

        $saveData = [];

        $logStr = '';
        
        foreach ($sheet->nextRow() as $rowNum => $row) {
            $query_last = \app\models\Record::find()->andWhere(['book_id' => $bookId]);
            $record = new \app\models\Record();
            $record->book_id = $bookId;
            $valid = true;
            $nReg = false;

            if($rowNum === 2){
                foreach ($row as $k => $v) {
                    if((string)$v === 'RIPER'){
                        $resultStr = $filepath . PHP_EOL . 'Have record RIPER, drop saving:';

                        if($updateStatus)
                            $updateStatus($resultStr);

                        echo $resultStr . PHP_EOL;
                        return $statInfo;
                    }
                }
            }
            
            if($rowNum <= 2)
                continue;

            foreach ($row as $k => $v) {
                $v = $v ?? '';

                switch ($k) {
                    case 'A':
                        if (filter_var((string)$v, FILTER_VALIDATE_INT) !== false) {
                            $v = intval($v);
                            $v = ($v > 1000000) ? 0 : $v;
                            
                            $record->numReg = $v;
                            $record->numLiteral = '';
                            $nReg = $v;
                            $query_last->andWhere(['numReg' => $v]);
                        }
                        else {
                            $record->numLiteral = (string)$v;
                            $nReg = (string)$v;

                            if($v !== '')
                                $query_last->andWhere(['numLiteral' => $record->numLiteral]);
                            else{
                                 $query_last->andWhere([
                                    'or',
                                    ['numLiteral' => null],
                                    ['numLiteral' => '']
                                ]);
                            }
                        }
                        break;
                    case 'B':
                        $record->fio = preg_replace('/\s+/', ' ', trim((string)$v));
                        break;
                    case 'C':
                        $record->age = (string)$v;
                        break;
                    case 'D':
                        $record->death_date = (string)$v;
                        break;
                    case 'E':
                        $record->rip_date = (string)$v;

                        if (preg_match('#\b(\d{4})\b#', (string)$v, $m)) {
                            $ddate = (int)$m[1];

                            if ($ddate > 1700 && $ddate < 2030) {
                                $statInfo['year1'] = min($statInfo['year1'] ?? $ddate, $ddate);
                                $statInfo['year2'] = max($statInfo['year2'] ?? $ddate, $ddate);
                            }
                        }
                        break;
                    case 'F':
                        $record->docnum = (string)$v;
                        break;
                    case 'G':
                        $record->zags = (string)$v;
                        break;
                    case 'H':
                        $record->area_num = (string)$v;
                        break;
                    case 'I':
                        $record->row_num = (string)$v;
                        break;
                    case 'J':
                        $record->rip_num = (string)$v;
                        break;
                    case 'K':
                        $record->relative_fio = (string)$v;
                        break;
                    case 'O':
                        $record->filename = strtr((string)$v, ['/' => '\\']);

                        if (!isset($filenames[$v]))
                            $filenames[$v] = [];

                        if (!in_array($nReg, $filenames[$v]))
                            $filenames[$v][] = $nReg;

                        break;
                    case 'P':
                        $record->comment = (string)$v;
                        break;
                    case 'Q':
                        if (mb_strtolower((string)$v, 'UTF-8') === "гроб")
                            $record->rip_style = 1;
                        else if (mb_strtolower((string)$v, 'UTF-8') === "урна c прахом")
                            $record->rip_style = 2;
                        else
                            $record->rip_style = 2;
                        /*else if (mb_strtolower((string)$v, 'UTF-8') === "урны")
                            $record->rip_style = 3;
                        else if (mb_strtolower((string)$v, 'UTF-8') === "урны")
                            $record->rip_style = 4;*/
                        
                        break;
                }
            }

            $query_last->andWhere([
                'numReg' => $record->numReg,
                'numLiteral' => $record->numLiteral,
                'fio' =>  $record->fio,
                'age' => $record->age,
                'death_date' => $record->death_date,
                'rip_date' => $record->rip_date,
                'docnum' => $record->docnum,
                'zags' => $record->zags,
                'area_num' => $record->area_num,
                'row_num' => $record->row_num,
                'rip_num' => $record->rip_num,
                'relative_fio' => $record->relative_fio,
                'filename' => $record->filename,
                'comment' => $record->comment,
                'rip_style' => $record->rip_style,
            ]);

            if($query_last->exists()){
                $valid = false;
                $logStr .= 'already exists: ' . $rowNum . PHP_EOL;
            }

            if (($record->fio ?? '') === '') {
                $valid = false;
                $logStr .= 'empty fio: ' . $rowNum . PHP_EOL;
            }

            if ($valid) {
                if(!$record->validate()){
                    if(!$error_print){
                        $logStr = $filepath . PHP_EOL . $logStr;
                        $error_print = true;
                    }

                    $logStr .= 'database error row: ' . $rowNum . PHP_EOL;
                }
                else{
                    $saveData[] = [
                        'book_id' => $record->book_id,
                        'numReg' => $record->numReg,
                        'numLiteral' => $record->numLiteral,
                        'fio' => $record->fio,
                        'age' => $record->age,
                        'death_date' => $record->death_date,
                        'rip_date' => $record->rip_date,
                        'docnum' => $record->docnum,
                        'zags' => $record->zags,
                        'area_num' => $record->area_num,
                        'row_num' => $record->row_num,
                        'rip_num' => $record->rip_num,
                        'relative_fio' => $record->relative_fio,
                        'filename' => $record->filename,
                        'comment' => $record->comment,
                        'rip_style' => $record->rip_style,
                        'num_crem_reg' => '',//заглушка
                        'num_crem_account' => '',
                    ];
                }
            }
            else {
                if(!$error_print){
                    $logStr = $filepath . PHP_EOL . $logStr;
                    $error_print = true;
                }
            }

            if(sizeof($saveData) >= 1000){
                \Yii::$app->db->createCommand()
                    ->batchInsert(\app\models\Record::tableName(), $saveDataNames, $saveData)
                    ->execute();

                $saveData = [];
            }
        }

        if(sizeof($saveData) > 0){
            \Yii::$app->db->createCommand()
                ->batchInsert(\app\models\Record::tableName(), $saveDataNames, $saveData)
                ->execute();

            unset($saveData);
        }

		self::updateBookIndex($bookId);

        if($updateStatus)
            $updateStatus(rtrim($logStr, PHP_EOL));

        echo $logStr;

        $statInfo['records'] = \app\models\Record::find()->andWhere(['book_id' => $bookId])->count();

        $rCount = [];
        foreach ($filenames as $fName => $list) {
            $cnt = sizeof($list);
            if (!isset($rCount[$cnt]))
                $rCount[$cnt] = 0;

            $rCount[$cnt]++;
        }

        $max_key = 0;
        $max_key_val = 0;

        foreach ($rCount as $cnt => $val) {
            if ($val > $max_key_val) {
                $max_key = $cnt;
                $max_key_val = $val;
            }
        }

        $statInfo['per_page'] = $max_key;

        return $statInfo;
    }
}
