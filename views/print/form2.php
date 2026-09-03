<?php

use \app\models\Helper;
use yii\helpers\FileHelper;

/**
 * @var \Mpdf\Mpdf $mpdfObject
 */

$rip_date = '';
if (isset($_GET['rip_date']))
    $rip_date = $_GET['rip_date'];
$rip_year = '';
if (preg_match("#.*?(\d\d\d\d).*?#", $rip_date, $m)) {
    $rip_year = $m[1];
}

if (isset($_GET['dead_year']))
    $rip_year = $_GET['dead_year'];

$vidano = trim(strtr("{$_GET['vidano-fam']} {$_GET['vidano-nam']} {$_GET['vidano-ot']}", ["  " => " "]));
$vd = explode(" ", $vidano);
$vd_len = mb_strlen($vidano, 'utf8');

$normal = 'verdana';
?>
<!doctype html>
<html>
    <head>
        <link rel="stylesheet" href="/assets/css/printer.css" />
    </head>
    <body>
        <table>
            <tr>
                <td class="title">
                    <div><img width='43px' src='/assets/img/print_logo.png' /></div>
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
                    </div>
                    <div class='number'>№ 
                        <span class="number_ubderline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <?= $_GET['nn'] ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </span> от
                        <span class="number_ubderline">&nbsp;&nbsp;&nbsp;
                            <?= date('d.m.Y', strtotime($_GET['date'])) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </span>
                    </div>
                </td>
                <td>
                    <table class="citizen">
                        <?php
                            if ((sizeof($vd) > 1) && (( mb_strlen($vd[1], 'utf8') <= 2) || (substr_count($vidano, '.')))) {
                                $string = 'Гр.' . Helper::truncateToWidth($vidano, $normal, 8, 135, $mpdfObject);
                            } 
                            else {
                                $string = 'Гр.' . Helper::truncateToWidth($vd[0], $normal, 8, 135, $mpdfObject);
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
                    <div style="font-size: 9pt;">СПРАВКА (Ф-2)</div>
                </td>
            </tr>
        </table>
        <div class="information" style="margin-top: 42pt;">
            <div>
                &nbsp;&nbsp;&nbsp;&nbsp;Архив по учёту захоронений Санкт-Петербургского государственного казенного учреждения<br /> «Специализированная служба Санкт-Петербурга по вопросам похоронного дела»<br /> не имеет данных 
                о захоронении
            </div>
            <?php
                Helper::tablePrint('', "{$_GET['fam']} {$_GET['nam']} {$_GET['ot']}", 545, 545, 7, $normal, $mpdfObject, 10);
            ?>
            <div class="hinttext">
                (фамилия, имя, отчество полностью)
            </div>
            <div>
                на кладбище 
                <span class="information_underline">
                    <?php echo str_repeat("&nbsp;", 7) ?><?= $_GET['cemetery'] ?><?php echo str_repeat("&nbsp;", 5) ?>
                </span>
                в 
                <span class="information_underline">
                    <?php echo str_repeat("&nbsp;", 10) ?><?= $rip_year ?><?php echo str_repeat("&nbsp;", 10) ?>
                </span> году
            </div>
            <?php
                if (isset($_GET['print_comment'])){
                    $string = trim($_GET['comment']);
                    Helper::tablePrint('', $string, 545, 545, 7, $normal, $mpdfObject, 10);
                }
            ?>
            <div class="comment_ips"> 
                Справка сформирована на основе ИПС "Поиск захоронений"
            </div>

            <div class="worker">
                Специалист по работе с архивом 
                <span class="information_underline">
                    <?php echo str_repeat("&nbsp;", 15) ?> <?php echo str_repeat("&nbsp;", 5) ?>
                </span> (
                <span class="information_underline">
                    <?php
                        $string = str_repeat("\u{00A0}", 3) . $_GET['author'] . str_repeat("\u{00A0}", 3);
                        echo Helper::truncateToWidth($string, $normal, 7, 305, $mpdfObject);
                    ?>
                </span>)
            </div>
        </div>
    </body>
</html>