<?php

namespace filsh\yii2\user\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use yii\filters\VerbFilter;
use filsh\yii2\user\models\User;

/**
 * AdminController implements the CRUD actions for User model.
 */
class AdminController extends Controller
{
    /**
     * Get view path based on module property
     *
     * @return string
     */
    public function getViewPath()
    {
        return Yii::$app->getModule('user')->viewPath ? rtrim(Yii::$app->getModule('user')->viewPath, '/\\') . DIRECTORY_SEPARATOR . $this->id : parent::getViewPath();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // check for admin permission in web requests. console requests should not throw the exception
        if (!Yii::$app->request->isConsoleRequest && !Yii::$app->user->can('admin')) {
            throw new HttpException(403, 'You are not allowed to perform this action.');
        }

        parent::init();
    }

    /**
     * Lists all User models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        /** @var \filsh\yii2\user\models\search\UserSearch $searchModel */
        $searchModel = Yii::$app->getModule('user')->model('UserSearch');
        $dataProvider = $searchModel->search($_GET);

        return $this->render('index', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single User model.
     *
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        /** @var \filsh\yii2\user\models\User $user */
        /** @var \filsh\yii2\user\models\Profile $profile */
        $user = Yii::$app->getModule('user')->model('User');
        $user->setScenario(User::SCENARIO_ADMIN);
        $profile = Yii::$app->getModule('user')->model('Profile');

        if ($user->load($_POST) && $user->validate() && $profile->load($_POST) and $profile->validate()) {
            $user->save();
            $profile->setUser($user->id)->save(false);
            return $this->redirect(['view', 'id' => $user->id]);
        } else {
            return $this->render('create', [
                        'user' => $user,
                        'profile' => $profile,
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $user = $this->findModel($id);
        $user->setScenario(User::SCENARIO_ADMIN);
        $profile = $user->profile;

        if ($user->load($_POST) && $user->validate() && $profile->load($_POST) and $profile->validate()) {
            $user->save();
            $profile->save();
            return $this->redirect(['view', 'id' => $user->id]);
        } else {
            return $this->render('update', [
                        'user' => $user,
                        'profile' => $profile,
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        // delete profile first to handle foreign key constraint
        $user = $this->findModel($id);
        $profile = $user->profile;
        $profile->delete();
        $user->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param string $id
     * @return \filsh\yii2\user\models\User the loaded model
     * @throws HttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $user = Yii::$app->getModule('user')->model('User');
        if (($model = $user::findOne($id)) !== null) {
            return $model;
        } else {
            throw new HttpException(404, 'The requested page does not exist.');
        }
    }
}
