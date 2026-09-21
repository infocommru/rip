<?php

namespace app\controllers;

use app\models\Book;
use app\models\Record;
use app\models\Cemetery;
use app\models\CacheRecords;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use app\models\Helper;
use app\models\HelperCache;
use Yii;

require_once __DIR__ . '/../vendor/autoload.php';

class PrintController extends Controller {

    /**
     * @inheritDoc
     */

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // '@' означает только авторизованные пользователи
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     * @param int $record_id
     */
    public function actionIndex(int $record_id = 0): string {
        $record = null;
        $req = Yii::$app->request;
        
        if($record_id){
            $record = Record::find()
                ->andWhere(['id' => $record_id])
                ->one();
        }

        $sdata = CacheRecords::find()->query(['term' => ['record_id' => $record_id]])->one();
        $user = \app\models\User::findIdentity(\Yii::$app->user->id);

        $book = $record->book ?? null;
        $cemetery = $book->cemetery ?? null;

        // Определение способа захоронения (приоритет у книги)
        $grob = '';

        if (!empty($book->rip_style))
            $grob = Book::ripStyleTypes()[$book->rip_style] ?? '';
        elseif ($record)
            $grob = Record::ripStyleTypes()[$record->rip_style] ?? '';
        else
            $grob = Record::ripStyleTypes()[(int) $req->get('rip_style', '')] ?? '';

        // Формирование базового шаблона места захоронения
        if ($record) {
            $parts = array_filter([
                (string)$record->area_num !== '' ? "уч. {$record->area_num}" : null,
                (string)$record->row_num  !== '' ? "ряд {$record->row_num}" : null,
                (string)$record->rip_num  !== '' ? "место {$record->rip_num}" : null,
            ]);
            $zah_suffix = implode(', ', $parts);
            unset($parts);
        }
        else {
            $parts = array_filter([
                ($req->get('areanum', '') !== '' && $req->get('ext_search', '') == '1') ? "уч. {$req->get('areanum', '')}" : null,
                ($req->get('rownum', '')  !== '' && $req->get('ext_search', '') == '1') ? "ряд {$req->get('rownum', '')}" : null,
                ($req->get('ripnum', '')  !== '' && $req->get('ext_search', '') == '1') ? "место {$req->get('ripnum', '')}" : null,
            ]);
            $zah_suffix = implode(', ', $parts);
            unset($parts);
        }

        // Формирование ФИО оператора
        $user_fio = $user->middlename 
            ? $user->lastname . ' ' . mb_substr($user->firstname, 0, 1, 'utf8') . '. ' . mb_substr($user->middlename, 0, 1, 'utf8') . '.'
            : "$user->lastname $user->firstname $user->middlename";

        // Собираем данные записи
        if($record){
            $res = [
                'fio'          => $record->fio ?? '',
                'cemetery'     => $cemetery->name ?? '',
                'docnum'       => $record->docnum ?? '',
                'age'          => $record->age ?? '',
                'relative_fio' => $record->relative_fio ?? '',
                'zags'         => $record->zags ?? '',
                'comment'      => $record->comment ?? '',
                'number'       => $book->number ?? '',
                'svazka'       => $book->svazka ?? '',
                'page_num'     => $sdata->page_num ?? '',
                'regnum'       => $sdata->regnum ?? '',
                'rip_date'     => Helper::formatDate($record->rip_date ?? ''),
                'death_date'   => Helper::formatDate($record->death_date ?? ''),
                'num-crem-reg' => $record->num_crem_reg ?? '',
                'num-crem-account' => $record->num_crem_account ?? '',
            ];
        }
        else {
            $res = [
                'fio'          => preg_replace('/\s+/', ' ', trim("{$req->get('fam', '')} {$req->get('nam', '')} {$req->get('ot', '')} {$req->get('unknown_number', '')}")),
                'cemetery'     => Cemetery::find()
                                    ->select('name')
                                    ->where(['id' => (int)$req->get('cemetery', '')])
                                    ->scalar(),
                'docnum'       => $req->get('docnum', ''),
                'age'          => $req->get('age', ''),
                'relative_fio' => ($req->get('ext_search', '') == '1') ? $req->get('rel', '') : '',
                'zags'         => $req->get('zags', ''),
                'comment'      => $req->get('comment', ''),
                'number'       => "",
                'svazka'       => "",
                'page_num'     => "",
                'regnum'       => $req->get('regnum', ''),
                'rip_date'     => Helper::formatDate("{$req->get('rip_d', '')}.{$req->get('rip_m', '')}.{$req->get('rip_y', '')}"),
                'rip_y'        => $req->get('rip_y', ''),
                'death_date'   => Helper::formatDate("{$req->get('dead_d', '')}.{$req->get('dead_m', '')}.{$req->get('dead_y', '')}"),
                'num-crem-reg' => ($req->get('ext_search', '') == '1') ? $req->get('num-crem-reg', '') : '',
                'num-crem-account' => ($req->get('ext_search', '') == '1') ? $req->get('num-crem-account', '') : '',
            ];
        }

        $title = "Печать" . ($record ? ": {$cemetery->name}, {$record->fio}" : '');

        if($req->get('f2notFound', '') == '1'){
            return $this->render('form2_not_found', [
                'grob' => $grob,
                'zah_suffix' => $zah_suffix,
                'user_fio' => $user_fio,
                'res' => $res,
                'title' => $title,
            ]);
        }

        return $this->render('index', [
            'grob' => $grob,
            'zah_suffix' => $zah_suffix,
            'user_fio' => $user_fio,
            'res' => $res,
            'title' => $title,
        ]);
    }

    /**
     * @return void
     */
    public function actionForma() {
        $fname = "forma_f" . $_GET['spravka'] . '_N_' . $_GET['nn'] . date("_d_m_Y");

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $pdf = new \Mpdf\Mpdf([
            'fontDir' => array_merge($fontDirs, [
                FileHelper::normalizePath(Yii::getAlias("@app/assets/fonts")),
            ]),
            'fontdata' => array_merge($fontData, [ // lowercase letters only in font key
                'verdana' => [
                    'R' => 'Verdana.ttf',
                    'I' => 'Verdana-Italic.ttf',
                    'B' => 'Verdana-Bold.ttf',
                    'BI' => 'Verdana-BoldItalic.ttf',
                ],
            ]),
            'mode' => 'utf-8', 'format' => 'A5',
            'margin_top' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 0
        ]);

        $pdf->shrink_tables_to_fit = 1;
	    $pdf->SetDisplayMode('default');

        $htmlRender = '';
        $this->layout = false;
        $fname = "forma_f" . $_GET['spravka'] . '_N_' . $_GET['nn'] . date("_d_m_Y");
        
        if ($_GET['spravka'] == '1') {
            $htmlRender = $this->render('form1', ['mpdfObject' => $pdf]);
        } else {
            $htmlRender = $this->render('form2', ['mpdfObject' => $pdf]);
        }

        $pdf->WriteHTML($htmlRender);

        switch ($_GET['saveas']) {
            case '1':
                $pdf->Output($fname . '.pdf', \Mpdf\Output\Destination::INLINE);
                exit;
            case '2':
                $imagick = new \Imagick();
                $result = $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
                
                $imagick->setAntiAlias(true);
                $imagick->setOption('pdf:text-antialiasing', '4');
                $imagick->setOption('pdf:graphics-antialiasing', '4');
                $imagick->setResolution(400, 400);
                $imagick->readImageBlob($result . '[0]');
                $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $imagick->setImageFormat('jpeg');
                $imagick->scaleImage(1167, 0);

                header('Content-Type: image/jpeg');
                header('Content-Length: ' . strlen($imagick->getImageBlob()));
                echo $imagick->getImageBlob();
                
                $imagick->clear();
                $imagick->destroy();
                exit;
            case '3':
                $pdf->Output($fname . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
                exit;
            case '4':
                $imagick = new \Imagick();
                $result = $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
                
                $imagick->setAntiAlias(true);
                $imagick->setOption('pdf:text-antialiasing', '4');
                $imagick->setOption('pdf:graphics-antialiasing', '4');
                $imagick->setResolution(400, 400);
                $imagick->readImageBlob($result . '[0]');
                $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $imagick->setImageFormat('jpeg');
                $imagick->scaleImage(1167, 0);

                header('Content-Type: image/jpeg');
                header('Content-Length: ' . strlen($imagick->getImageBlob()));
                header('Content-Disposition: attachment; filename=' . $fname . '.jpg');
                echo $imagick->getImageBlob();
                
                $imagick->clear();
                $imagick->destroy();
                exit;
        }
    }
}
