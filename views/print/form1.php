<?php

use \app\models\Record;
use \app\models\Helper;

use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * @var \Mpdf\Mpdf $mpdfObject
 */

$req = Yii::$app->request;
$docnum = $req->get('docnum', '');
$svid = "";
$number = $docnum;

$dd = explode('№', $docnum);
if (sizeof($dd) > 1) {
    $svid = $dd[0];
    $number = $dd[1];
}

$number = str_repeat('&nbsp;', 10) . $number . str_repeat('&nbsp;', 20);
$svid = str_repeat('&nbsp;', 10) . $svid . str_repeat('&nbsp;', 20);


$vidano = trim(strtr($req->get('vidano', ''), ["  " => " "]));
$vd = explode(" ", $vidano);

$normal = 'verdana';
?>

<!doctype html>
<html>
    <head>
        <style>
            <?= file_get_contents(FileHelper::normalizePath(\Yii::getAlias('@webroot/css/printer.css'))); ?>
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td class='title'>
                    <div><img width='43px' src="<?= Yii::getAlias('@webroot/img/print_logo.png') ?>"></div>
                    <div class='upper_text'> 
                        Правительство Санкт-Петербурга <br />
                        Комитет по промышленной политике,<br />
                        Инновациям и торговле Санкт-Петербурга
                        <br /><br />
                    </div>
                    <div class='upper_text bold'> 
                        Санкт-Петербургское<br />
                        Государственное Казенное Учреждение<br />
                        &laquo;Специализированная служба санкт-петербурга<br />
                        по вопросам похоронного дела&raquo;
                    </div>

                    <div> 
                        1-я Советская ул., д. 8, Санкт-Петербург, 191036 <br />
                        E-mail: info@svpd.cipit.gov.spb.ru <br />
                        https://svpd.cipit.gov.spb.ru <br />
                        Тел. (812) 241-24-24, Факс (812) 241-24-21 <br />
                        ОКПО 96728516 ОКОГУ 2300216 ОГРН 5067847213033 <br />
                        ИНН/КПП 7842340459/784201001

                        <br />
                    </div>
                    <div class='number'>№ 
                        <span class="number_ubderline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <?= $req->get('nn', '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </span> от
                        <span class="number_ubderline">&nbsp;&nbsp;&nbsp;
                            <?= date('d.m.Y', strtotime($req->get('date', ''))); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </span>
                        <br />
                    </div>
                </td>
                <td>
                    <table class="citizen">
                        <?php
                            if ((sizeof($vd) > 1) && (( mb_strlen($vd[1], 'utf8') <= 2) || (substr_count($vidano, '.')))) {
                                $string = 'Гр. ' . Helper::truncateToWidth($vidano, $normal, 8, 135, $mpdfObject);
                            } 
                            else {
                                $string = 'Гр. ' . Helper::truncateToWidth($vd[0], $normal, 8, 135, $mpdfObject);
                            }

                            echo '<tr><td class="citizen_underline">' .$string . "</td></tr>";

                            $tmp_rep = str_repeat("\u{00A0}", 4);

                            if ((sizeof($vd) > 1) && ( mb_strlen($vd[1], 'utf8') > 2) && (!substr_count($vidano, '.'))) {
                                for ($i = 1; $i < sizeof($vd); $i++) {
                                    $string = Helper::truncateToWidth($tmp_rep . $vd[$i], $normal, 8, 150, $mpdfObject);
                                    echo '<tr><td class="citizen_underline">' . $string . "</td></tr>";
                                }
                            } else {
                                for ($i = 0; $i < 3; $i++) {
                                    echo '<tr><td class="citizen_underline">' . $tmp_rep . "</td></tr>";
                                }
                            }

                            echo '<tr><td class="citizen_underline">' . $tmp_rep . "</td></tr>";
                        ?>
                    </table>
                    <br /> <br />  
                    <div style="font-size: 9pt;">СПРАВКА (Ф-1)</div>
                </td>
            <tr>
        </table>

        <div class="information">
            <div class="undertext_real">
                <?php echo "Справка" . str_repeat("\u{00A0}", 9) . "выдана" . str_repeat("\u{00A0}", 9) . 
                    "о" . str_repeat("\u{00A0}", 9) . "том," . str_repeat("\u{00A0}", 9) . "что" . 
                    str_repeat("\u{00A0}", 9) . "умерший(ая) ";
                ?>
            </div>
            <?php
                $string = $req->get('fio', '') . str_repeat("\u{00A0}", 12);

                if ($req->get('age', '')){
                    $string .= ', ' . $req->get('age', '');

                    if(intval($req->get('age', '')) . '' == $req->get('age', '')){
                        $string .= 'лет';
                    }
                }

                Helper::tablePrint('', $string, 545, 545, 7, $normal, $mpdfObject, 21);
            ?>
            <div class="hinttext">
                (фамилия, имя, отчество полностью)
            </div>
            <div>
                <div>
                    захоронен(а)
                </div>
                <?php
                    $rip_style = (($req->get('print_grob') !== null) && ($req->get('zahr', '') !== '-')) ? ', ' . $req->get('zahr', '') : '';
                    $place = $req->get('place', '') ? ', ' . $req->get('place', '') : '';
                    $zahoron = $req->get('cemetery', '') . " $rip_style $place";
                    $zahoron = trim($zahoron);
                    $zahoron = ltrim($zahoron, ',');

                    Helper::tablePrint('', $zahoron, 545, 545, 7, $normal, $mpdfObject, 4);
                ?>
            </div> 
            <div class="hinttext">
                (название кладбища, участок, ряд, место)
            </div>
            <table>
                <tr>
                    <td class="table_label">
                        дата захоронения
                    </td>
                    <td class="table_data">
                        <?php
                            $string = $req->get('rip_date', '') . ((($req->get('print_date') !== null) && ($req->get('death_date', ''))) ?
                                    ', дата смерти - ' . $req->get('death_date', '') : '');
                            echo Helper::truncateToWidth(str_repeat("\u{00A0}", 20) . $string, $normal, 7, 450, $mpdfObject);
                        ?>
                    </td>
                </tr>
            </table>
            <div class="hinttext">
                (год, месяц, число)
            </div>
            <div>
                по свидетельству о смерти <span class='information_underline'><?= $svid ?></span> № <span class='information_underline'><?= $number ?></span>                    
            </div>
            <?php
                Helper::tablePrint('место государственной регистрации смерти', $req->get('zags', ''), 330, 545, 7, $normal, $mpdfObject);
                Helper::tablePrint('ответственное лицо', $req->get('author2', ''), 445, 545, 7, $normal, $mpdfObject);
            ?>
            <div class="hinttext">
                (фамилия, имя, отчество)
            </div>
            <div>
                Основание: связка <span class="information_underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $req->get('svazka', '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> , 
                книга <span class="information_underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $req->get('book_num', '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> , стр. <span class="information_underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $req->get('page_num', '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> , п/п <span class="information_underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $req->get('pp', '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </div> 
            <?php
                if ($req->get('print_comment') !== null)
                    Helper::tablePrint('', trim($req->get('comment', '')), 545, 545, 7, $normal, $mpdfObject, 10);
            ?>
            <div class="comment_ips"> 
                Справка сформирована на основе ИПС "Поиск захоронений"
            </div>
            <div class="worker">
                Специалист по работе с архивом  
                <span class="information_underline">
                    <?php echo str_repeat("&nbsp;", 30) ?>
                </span> 
                &nbsp;&nbsp;&nbsp;(
                <span class="information_underline">
                    <?php echo Helper::truncateToWidth(str_repeat("\u{00A0}", 3) . $req->get('author', '') . str_repeat("\u{00A0}", 4), $normal, 7, 250, $mpdfObject); ?>
                </span>
                )
            </div>
        </div>
    </body>
</html>