<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\HelperImg;
use yii\helpers\StringHelper;
use yii\web\View;

/** @var yii\web\View $this
* @var app\models\Record $model
* @var yii\widgets\ActiveForm $form
* @var bool $is_create
*/

$this->registerJsFile('assets/js/yandex_speller.js', [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

?>

<div class="record-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="container">

        <div class="row">
            <div class="col-sm-5">
                <?= $form->field($model, 'filename')->textInput(['maxlength' => true, 'readonly' => !$is_create]) ?>
            </div>
            <div class="col-sm-3">
                <label for ='select_fname'>Выбрать файл</label>
                <select id="select_fname" class="form-control"></select>
            </div>
            <?php if (!$is_create): ?>
                <div class="col-sm-2 d-flex align-items-center">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                </div>
            <?php endif; ?>

        </div>
        <div class="row">
            <div class="col-sm-2">
                <?= $form->field($model, 'numReg')->textInput() ?>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'numLiteral')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-3 parent-speller">
                <?= $form->field($model, 'fio')->textInput(['maxlength' => true]) ?>
                <div class="speller mb-2" style="color: green; cursor: pointer;"></div>
            </div>
            <div class="col-sm-1">
                <?= $form->field($model, 'age')->textInput() ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-2">
                <?= $form->field($model, 'death_date')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'rip_date')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-3">
                <?= $form->field($model, 'num_crem_reg')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-3">
                <?= $form->field($model, 'num_crem_account')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-2">
                <?= $form->field($model, 'docnum')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-2 parent-speller">
                <?= $form->field($model, 'zags')->textInput() ?>
                <div class="speller mb-2" style="color: green; cursor: pointer;"></div>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'area_num')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'row_num')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'rip_num')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4 parent-speller">
                <?= $form->field($model, 'relative_fio')->textInput(['maxlength' => true]) ?>
                <div class="speller mb-2" style="color: green; cursor: pointer;"></div>
            </div>
            <div class="col-sm-6 parent-speller">
                <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>
                <div class="speller mb-2" style="color: green; cursor: pointer;"></div>
            </div>
            <div class="col-sm-2">
                <?= $form->field($model, 'rip_style')->dropDownList(\app\models\Record::ripStyleTypes()) ?>
                <input type="hidden" id='pageNum' name='pageNum' value='1' />
            </div>
        </div>

        <?php if ($is_create): ?>
            <div class="row">
                <div class="col-sm"  >
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                </div>           
            </div>           
        <?php endif; ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>