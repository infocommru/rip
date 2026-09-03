<?php

namespace app\models;
use app\models\Record;
use app\models\CacheRecords;

class HelperCache {
    /**
     * @return void
     * @param Record $record
     */
    public static function updateSearchRecord(Record $record): void {              
		$sfb = CacheRecords::find()->query(['term' => ['record_id' => $record->id]])->one();

        if (!$sfb) {
            $sfb = new CacheRecords();
            $sfb->_id = $record->id;
            $sfb->page_punkt = 0;
        }
        /** @var CacheRecords $sfb */

        $sfb->saveData($record);
    }

    /**
     * @return void
     * @param int $cemetery
     */
    public static function deleteCemetery(int $cemetery) {
        $response = \Yii::$app->elasticsearch->post(
            [ CacheRecords::index(), '_delete_by_query' ],
            ['wait_for_completion' => 'false',],
            json_encode([
                'query' => [
                    'term' => ['cemetery_id' => $cemetery]
                ]
            ])
        );
    }

    /**
     * @return void
     * @param int $book
     */
    public static function deleteBook(int $book) {
        $response = \Yii::$app->elasticsearch->post(
            [ CacheRecords::index(), '_delete_by_query' ],
            ['wait_for_completion' => 'false',],
            json_encode([
                'query' => [
                    'term' => ['book_id' => $book]
                ]
            ])
        );
    }

     /**
     * @return void
     * @param array<Book> $books
     * @param (\Closure(): void)|null $updateStatus
     */
	public static function updateCache(array $books, ?\Closure $updateStatus = null, string $indexName = '') {
        if(!$indexName)
            $indexName = CacheRecords::index();
    
        $batchRows = CacheRecords::getDb()->createBulkCommand();
        $counter = 0;

        foreach ($books as $book) {
            $records = Record::find()
                ->andWhere(['book_id' => $book->id])
                ->andWhere(['deleted' => 0])
                ->orderBy('id')
                ->asArray()
                ->all();

            $lastPage = 'asdasd';
            $lastPagePunkt = 1;

            if($updateStatus){
                $updateStatus();
            }

            foreach ($records as $record) {
                $result = CacheRecords::convertData($record, $book);

                if ($result['page_num'] != $lastPage) {
                    $lastPage = $result['page_num'];
                    $lastPagePunkt = 1;
                }

                $page_punkt = $lastPagePunkt++;

                $batchRows->addAction([
                    'index' => [
                        '_index' => $indexName,
                        '_id'    => $result['record_id'],
                    ]
                ],
                [
                    'record_id' => $result['record_id'],
                    'cemetery_id' => $result['cemetery_id'],
                    'regnum' => $result['regnum'],

                    'fam' => $result['fam'],
                    'nam' => $result['nam'],
                    'fio_display' => $result['fio_display'],
                    'ot' => $result['ot'],

                    'age' => $result['age'],
                    'age_int' => $result['age_int'],

                    'dead_year' => $result['dead_year'],
                    'dead_month' => $result['dead_month'],
                    'dead_day' => $result['dead_day'],
                    'dead_date' => $result['dead_date'],

                    'rip_year' => $result['rip_year'],
                    'rip_month' => $result['rip_month'],
                    'rip_day' => $result['rip_day'],
                    'rip_date' => $result['rip_date'],

                    'num_crem_reg' => $result['num_crem_reg'],
                    'num_crem_account' => $result['num_crem_account'],

                    'zags' => $result['zags'],
                    'rip_style' => $result['rip_style'],

                    'unknown' => $result['unknown'],
                    'unknown_number' => $result['unknown_number'],

                    'docnum' => $result['docnum'],
                    'areanum' => $result['areanum'],
                    'rownum' => $result['rownum'],
                    'ripnum' => $result['ripnum'],

                    'relative' => $result['relative'],

                    'svazka_num' => $result['svazka_num'],
                    'book_num' => $result['book_num'],
                    'page_num' => $result['page_num'],
                    'page_punkt' => $page_punkt,

                    'comment' => $result['comment'],
                    'comment_book' => $result['comment_book'],

                    'book_id' => $result['book_id'],
                    'book_rip_style' => $result['book_rip_style'],

                    'filename' => $result['filename'],
                    'vopros' => $result['vopros'],
                    'updated_at' => $result['updated_at'],
                ]);

                if ($counter >= 5000) {
                    $response = $batchRows->execute();
                    $batchRows = CacheRecords::getDb()->createBulkCommand();
                    $counter = 0;
                }

                $counter++;
            }
        }

        if ($counter > 0) {
            $response = $batchRows->execute();
            $batchRows = CacheRecords::getDb()->createBulkCommand();
            $counter = 0;
        }
    }

    /**
    * Разбивает ФИО на составные части.
    * @param string $FIO ФИО человека
    * @return array{fam: string, nam: string, ot: string} Массив с составными частями ФИО
    */
    public static function splitFIO(string $FIO): array {
        $fullName = trim(preg_replace('/\s+/u', ' ', $FIO));

        // Фамильные части, которые могут входить в состав фамилии.
        $surnamePrefixes = [
            // Нидерландские / фламандские
            'ван',
            '’т',
            'вандер',
            'ванден',
            'ванде',
            'де',
            'ден',
            'дер',
            'те',
            'тен',
            'тер',
            'вер',
            'хет',
            'оп',
            'ин',
            'уйт',
            'уйтер',
            'тхое',
            'ту',

            // Немецкие / австрийские
            'фон',
            'цу',
            'цур',
            'фом',
            'ам',
            'аус',
            'аусм',
            'цум',

            // Французские
            'де',
            'дю',
            'дес',
            'ла',
            'ле',
            'сен',
            'сент',

            // Итальянские
            'да',
            'дал',
            'далла',
            'далл',
            'дей',
            'дегли',
            'дель',
            'делла',
            'делло',
            'делли',
            'ди',
            'ли',
            'ло',

            // Испанские
            'де',
            'дель',
            'лас',
            'лос',
            'ла',
            'ле',

            // Португальские
            'да',
            'даш',
            'де',
            'до',
            'дос',

            // Англо-кельтские
            'мак',
            'макк',
            'мк',
            'о’',
            'фитц',

            // Арабские / семитские
            'ибн',
            'бин',
            'бен',
            'бен',
            'бн',
            'сен',

            // Другие часто встречающиеся
            'аль',
            'эль',
            'иль',
            'ибн',
            'саинт',
            'сент',
        ];

        $surnameEndings = [
            'ов',
            'ев',
            'ёв',
            'ин',
            'ын',
            'их',
            'ых',
            'ский',
            'цкий',
            'ской',
            'цкой',
            'ый',
            'ий',
            'ой',
            'енко',
            'ук',
            'юк',
            'чук',
            'чак',
            'як',
            'ак',
            'ко',
            'ич',
            'ович',
            'евич',
            'ишин',
            'ышин',
            'заде',
            'ли',
            'лы',
            'лу',
            'лю',
            'оглу',
            'кызы',
            'ян',
            'ан',
            'янц',
            'енц',
            'унц',
            'уни',
            'дзе',
            'швили',
            'ури',
            'ули',
            'ани',
            'иа',
            'уа',
            'ава',
            'ая',
            'ши',
            'ели',
            'ети',
            'ати',
            'ити',
            'еску',
            'ану',
            'яну',
            'ару',
            'аш',
            'зод',
            'зода',
            'улы',
            'уулу',
            'бa',
            'ниа',
        ];

        $parts = preg_split('/\s+/u', $fullName);

        /*
        * Для классического русского ФИО:
        * Иванов Иван Петрович
        */
        if (count($parts) <= 3) {
            return [
                'fam' => $parts[0] ?? '',
                'nam' => $parts[1] ?? '',
                'ot' => $parts[2] ?? '',
            ];
        }

        $family = '';

        foreach ($parts as $index => $part) {
            $isPrefix = false;

            foreach ($surnamePrefixes as $prefix) {
                if (mb_strtolower($part) === $prefix) {
                    $family .= " $part";
                    $isPrefix = true;
                    break;
                }
            }

            if (!$isPrefix && $family !== '') {
                $family .= " $part";

                return [
                    'fam' => trim($family),
                    'nam' => $parts[0],
                    'ot' => implode(' ', array_slice($parts, $index + 1)),
                ];
            }
        }

        //Для двойных фамилий без дефиса
        //Бас Басов Алексей Михайлович
        $cleanedSurname = preg_replace('/[.,;\(\)]/', '', $parts[1]);

        foreach ($surnameEndings as $ending) {
            if (str_ends_with(mb_strtolower($cleanedSurname), $ending)) {
                return [
                    'fam' => "{$parts[0]} {$parts[1]}",
                    'nam' => $parts[2],
                    'ot' => implode(' ', array_slice($parts, 3)),
                ];
            }
        }

        /*
        * Обычный случай с 4+ словами:
        */

        return [
            'fam' => $parts[0],
            'nam' => $parts[1],
            'ot' => implode(' ', array_slice($parts, 2)),
        ];
    }
}