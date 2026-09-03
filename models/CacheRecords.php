<?php
namespace app\models;

use yii\elasticsearch\ActiveRecord;
use app\models\Record;
use app\models\HelperCache;
use yii\helpers\FileHelper;

/**
 * Модель для индекса cache_records в OpenSearch.
 *
 * @property int|null $_id Внутренний ID документа в Elasticsearch
 * @property int $record_id
 * @property int $cemetery_id
 * @property string|null $regnum
 * @property string|null $fam
 * @property string|null $nam
 * @property string|null $ot
 * @property string|null $fio_display
 * @property string|null $age
 * @property int|null $age_int
 * @property int|null $dead_year
 * @property int|null $dead_month
 * @property int|null $dead_day
 * @property string|null $dead_date
 * @property int|null $rip_year
 * @property int|null $rip_month
 * @property int|null $rip_day
 * @property string|null $rip_date
 * @property string|null $zags
 * @property int|null $rip_style
 * @property int|null $unknown
 * @property string|null $unknown_number
 * @property string|null $docnum
 * @property string|null $areanum
 * @property string|null $rownum
 * @property string|null $ripnum
 * @property string|null $relative
 * @property string|null $svazka_num
 * @property string|null $book_num
 * @property string|null $page_num
 * @property int $page_punkt
 * @property string|null $comment
 * @property string|null $comment_book
 * @property int $book_id
 * @property int $book_rip_style
 * @property string|null $filename
 * @property int $vopros
 * @property int|null $updated_at
 */

class CacheRecords extends ActiveRecord
{
	
    // Определяем атрибуты, которые будут храниться в OpenSearch
    public function attributes()
    {
        return [ "record_id", "cemetery_id", "regnum", "fam", "nam", "ot", "fio_display", "age", "age_int",
			"dead_year", "dead_month", "dead_day", "dead_date",
			"rip_year", "rip_month", "rip_day", "rip_date", "num_crem_reg", "num_crem_account",
		 	"zags", "rip_style", "unknown", "unknown_number", "docnum", "areanum", "rownum", "ripnum",
			"relative", "svazka_num", "book_num", "page_num", "page_punkt", "comment", "comment_book",
			"book_id", "book_rip_style", "filename", "vopros", "updated_at"];
    }

    // Имя индекса в OpenSearch (аналог таблицы в БД)
    public static function index()
    {
        return 'cache_records';
    }

    // Тип документа (для OpenSearch/Elasticsearch 7+ обычно используется '_doc')
    public static function type()
    {
        return '_doc';
    }

    // Настройка правил валидации (необязательно, но полезно)
    public function rules()
    {
        return [
            [['record_id', 'cemetery_id', 'age_int', 'dead_year', 'dead_month', 'dead_day', 
            	'rip_year', 'rip_month', 'rip_day', 'rip_style', 
            	'unknown', 'page_punkt', 'book_id', 'book_rip_style', 'vopros', 'updated_at'], 'integer'],
            [['regnum', 'unknown_number', 'svazka_num', 'book_num', 
            		'dead_date', 'rip_date'], 'string', 'max' => 32],
            [['fam', 'nam', 'ot', 'page_num'], 'string', 'max' => 64],
            [['docnum', 'fio_display', 'age', 'num_crem_reg', 'num_crem_account'], 'string', 'max' => 128],
            [['zags', 'areanum', 'rownum', 'ripnum', 'relative', 'filename'], 'string', 'max' => 256],
            [['comment', 'comment_book'], 'string'],
        ];
    }

    /**
     * @return array{day: int|null, month: int|null, year: int|null, date: string|null}
     * @param string $sDate
     */
	private static function getDate(string $sDate): array{
        $result = [
            'day' => null,
            'month' => null,
            'year' => null,
            'date' => null,
        ];

        if (preg_match("#(\d\d\d\d)#", $sDate, $m)) {
            $result['year'] = intval($m[1]);
        }

        if (!$result['year'])
            return $result;

        if (preg_match("#(\d\d?)\D(\d\d?)\D(\d\d\d\d)#", $sDate, $m)) {
            $result['day'] = intval(ltrim($m[1], '0'));
            $result['month'] = intval(ltrim($m[2], '0'));
        }

        if (preg_match("#(\d\d\d\d)\D(\d\d?)\D(\d\d?)#", $sDate, $m)) {
            $result['day'] = intval(ltrim($m[3], '0'));
            $result['month'] = intval(ltrim($m[2], '0'));
        }

        $result['date'] = $sDate;

        return $result;
    }

    /**
     * @return bool
     * @param Record $record
     */
    public function saveData(Record $record): bool{
        $values = self::convertData($record->toArray(), $record->book);

        if (empty($values)) {
            return false;
        }

        $this->setAttributes($values, false);
        return $this->save();
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public static function convertData(array $record, Book $book): array {
        $value = [];

        $value["fio_display"] = preg_replace('/\s+/', ' ', trim((string)$record['fio']));
        $value = array_merge($value, HelperCache::splitFIO($value["fio_display"]));

        $deadYearInf = self::getDate((string)$record['death_date']);
        $ripYearInf = self::getDate((string)$record['rip_date']);

        if ($record['numReg'] !== null)
            $value["regnum"] = (string)$record['numReg'];
        else
            $value["regnum"] = (string)$record['numLiteral'];

        $value["unknown_number"] = null;
        $value['unknown'] = preg_match('/(неиз|н\/)/ui', $record['fio']) ? 1 : 0;

        if (preg_match("#№\s+([\d\\/]+)#", (string)$record['fio'], $m)) {
            $value["unknown_number"] = $m[1];
        } else {
            if ($value['unknown']) {
                if (preg_match("#.*?(\d[\d\\/]+).*?#", (string)$record['fio'], $m)) {
                    $value["unknown_number"] = $m[1];
                } else {
                    if (preg_match("#.*?(\d+).*?#", (string)$record['fio'], $m)) {
                        $value["unknown_number"] = $m[1];
                    }
                }
            }
        }

        $basename = FileHelper::normalizePath((string)$record['filename']);
        $basename = pathinfo($basename, PATHINFO_FILENAME);
        $value["page_num"] = ltrim($basename, "0") ?: '0';

        $value['record_id'] = $record['id'];
        $value['cemetery_id'] = $book->cemetery_id;

        $value['age'] = (string)$record['age'];
        $value['age_int'] = ((int)$record['age'] > 200) ? null : (int)$record['age'];

        $value['dead_year'] = $deadYearInf['year'];
        $value['dead_month'] = $deadYearInf['month'];
        $value['dead_day'] = $deadYearInf['day'];
        $value['dead_date'] = $deadYearInf['date'];

        $value['rip_year'] = $ripYearInf['year'];
        $value['rip_month'] = $ripYearInf['month'];
        $value['rip_day'] = $ripYearInf['day'];
        $value['rip_date'] = $ripYearInf['date'];

        $value['num_crem_reg'] = (string)$record['num_crem_reg'];
        $value['num_crem_account'] = (string)$record['num_crem_account'];

        $value['zags'] = (string)$record['zags'];
        $value['rip_style'] = $record['rip_style'];

        $value['docnum'] = (string)$record['docnum'];
        $value['areanum'] = (string)$record['area_num'];
        $value['rownum'] = (string)$record['row_num'];
        $value['ripnum'] = (string)$record['rip_num'];

        $value['relative'] = (string)$record['relative_fio'];
        $value['svazka_num'] = $book->svazka;
        $value['book_num'] = $book->number;
        $value['comment'] = (string)$record['comment'];

        $value['comment_book'] = (string)$book->comment;
        $value['book_id'] = $book->id;
        $value['book_rip_style'] = $book->rip_style;

        $value['filename'] = (string)$record['filename'];
        $value['vopros'] = (string)$record['vopros'];
        $value['updated_at'] = $record['updated_at'];

        return $value;
    }
}