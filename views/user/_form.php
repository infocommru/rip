<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'firstname')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'lastname')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'middlename')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'position')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'role')->dropDownList(User::roleList()) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
