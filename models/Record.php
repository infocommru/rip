<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "record".
 *
 * @property int $id
 * @property int $book_id
 * @property int|null $user_id
 * @property int|null $numReg
 * @property string|null $numLiteral
 * @property string $fio
 * @property string $age
 * @property string $death_date
 * @property string $rip_date
 * @property string $docnum
 * @property string $zags
 * @property string $riper
 * @property string $area_num
 * @property string $row_num
 * @property string $rip_num
 * @property string $relative_fio
 * @property string $filename
 * @property string $comment
 * @property string $num_crem_reg
 * @property string $num_crem_account
 * @property int $rip_style
 * @property int|null $updated_at
 * @property int $vopros
 * @property int $deleted
 *
 * @property Book $book
 * @property User $user
 */
class Record extends \yii\db\ActiveRecord {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['book_id'], 'required'],
            [['book_id', 'numReg', 'rip_style', 'updated_at',
            'user_id', 'vopros'], 'integer'],
            [['comment'], 'string'],
            [['numLiteral', 'death_date', 'rip_date'], 'string', 'max' => 32],
            [['fio', 'zags', 'age', 'area_num', 'row_num', 'rip_num', 'docnum', 'num_crem_reg', 'num_crem_account'], 'string', 'max' => 128],
            [['relative_fio', 'filename'], 'string', 'max' => 256],
            [['book_id'], 'exist', 'skipOnError' => true, 'targetClass' => Book::class, 'targetAttribute' => ['book_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'book_id' => 'Книга',
            'user_id' => 'Пользователь',
            'numReg' => 'Номер записи',
            'numLiteral' => 'Номер букв.',
            'fio' => 'ФИО',
            'age' => 'Возраст',
            'death_date' => 'Дата смерти',
            'rip_date' => 'Дата захоронения',
            'docnum' => 'Номер документа ЗАГС',
            'zags' => 'ЗАГС',
            'area_num' => 'Номер участка',
            'row_num' => 'Номер ряда',
            'rip_num' => 'Номер могилы',
            'relative_fio' => 'Родственники',
            'filename' => 'Файл',
            'rip_style' => 'Захоронение',
            'updated_at' => 'Обновлено',
            'vopros' => 'Есть вопросы',
            'comment' => 'Комментарий',
            'deleted' => 'Удалено',
            'num_crem_reg' => 'Рег. № кремации',
            'num_crem_account' => '№ счета по кремации',
        ];
    }

    /**
     * Gets query for [[Book]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBook() {
        return $this->hasOne(Book::class, ['id' => 'book_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser() {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return array<int, string>
     */
    public static function ripStyleTypes() {
        $types = [
            0 => "-",
            1 => "Гроб",
            2 => "Урна",
            3 => "Урна, стена",
            4 => "Урна, земля",
        ];

        return $types;
    }
}
