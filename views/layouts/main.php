<?php
/** @var yii\web\View $this
* @var string $content 
*/
use app\static\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Url;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
    <head>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
    </head>
    <body class="d-flex flex-column h-100">
        <?php $this->beginBody() ?>

        <header id="header">
            <?php
            NavBar::begin([
                'brandLabel' => Yii::$app->name,
                'brandUrl' => Yii::$app->homeUrl,
                'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark sticky-top']
            ]);

            $items = [
                ['label' => 'Главная', 'url' => Url::to(['/site/index'])],
            ];

            if (!Yii::$app->user->isGuest) {
                $user = \app\models\User::findIdentity(Yii::$app->user->id);
                if ($user->role == 1) {
                    $items[] = ['label' => 'Кладбища',
                        'items' => [
                            ['label' => 'Кладбища', 'url' => Url::to(['/cemetery'])],
                            ['label' => 'Добавить', 'url' => Url::to(['/cemetery/create'])],
                            ['label' => 'Логи загрузки', 'url' => Url::to(['/book-upload'])]
                        ],
                    ];

                    $items[] = ['label' => 'Книги',
                        'items' => [
                            ['label' => 'Книги', 'url' => Url::to(['/book'])],
                            ['label' => 'Добавить', 'url' => Url::to(['/book/create'])],
                        ],
                    ];

                    $items[] = ['label' => 'Пользователи',
                        'items' => [
                            ['label' => 'Пользователи', 'url' => Url::to(['/user'])],
                            ['label' => 'Добавить', 'url' => Url::to(['/user/create'])],
                        ],
                    ];

                } else {

                    $items[] = ['label' => 'Книги',
                        'url' => Url::to(['/book'])
                    ];
                }
                if (($user->role != 1) && ($user->role != 4)) {
                    $items[] = ['label' => 'Поиск',
                        'url' => Url::to(['/search'])
                    ];
                } else {
                    $items[] = ['label' => 'Поиск',
                        'items' => [
                            ['label' => 'Поиск', 'url' => Url::to(['/search'])],
                            ['label' => 'Неточные данные', 'url' => Url::to(['/record/vopros'])],
                            ['label' => 'Удаленные данные', 'url' => Url::to(['/record/deleted'])],
                        ],
                    ];
                }
            }

            /** @var \app\models\User|null $user */
            $user = Yii::$app->user->identity;
            $items[] = Yii::$app->user->isGuest ? ['label' => 'Войти', 'url' => Url::to(['/site/login'])] : '<li class="nav-item">'
                    . Html::beginForm(['/site/logout'])
                    . Html::submitButton(
                            'Выйти (' . $user->username . ')',
                            ['class' => 'nav-link btn btn-link logout']
                    )
                    . Html::endForm()
                    . '</li>';

            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $items
            ]);
            NavBar::end();
            ?>
        </header>

        <main id="main" class="flex-shrink-0" role="main">
            <div class="container">
                <?php if (!empty($this->params['breadcrumbs'])): ?>
                    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
                <?php endif ?>
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </main>

        <footer id="footer" class="mt-auto py-3 bg-light">
            <div class="container">
                <div class="row text-muted">
                    <div class="col-md-6 text-center text-md-start">&copy; ИнфоКомм <?= date('Y') ?></div>
                    <div class="col-md-6 text-center text-md-end"><?php Yii::powered() ?></div>
                </div>
            </div>
        </footer>

        <?php $this->endBody() ?>

    </body>
</html>
<?php $this->endPage() ?>
