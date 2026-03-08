<?php

namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use common\models\Order;
use yii\data\ActiveDataProvider;

/**
 * OrderController implements the CRUD and custom API endpoints for Order model.
 * It demonstrates advanced REST API capabilities of Yii2.
 */
class OrderController extends ActiveController
{
    public $modelClass = Order::class;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Remove default auth to inject CORS first so preflight OPTIONS requests pass
        unset($behaviors['authenticator']);

        // Enable CORS for modern SPA/React/Vue frontends
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        // Re-add authentication using Bearer tokens (JWT/Oauth standard)
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'], // OPTIONS must not require auth
        ];

        return $behaviors;
    }

    /**
     * Customized data provider for the 'index' action.
     * Keeps controllers extremely thin using the OrderQuery scopes.
     *
     * @return ActiveDataProvider
     */
    public function actions()
    {
        $actions = parent::actions();

        // Override index action to show only orders belonging to the authenticated user
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    /**
     * Data provider preparation logic
     * @return ActiveDataProvider
     */
    public function prepareDataProvider()
    {
        // Demonstration of Thin Controllers & Custom Search Queries (using OrderQuery method 'byUser')
        $query = Order::find()->byUser(Yii::$app->user->identity->getId())->active();

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);
    }

    /**
     * Custom endpoint to pay an order
     * POST /api/orders/{id}/pay
     */
    public function actionPay($id)
    {
        /* @var $model Order */
        $model = $this->actionView($id); // reuse default view logic for handling 404s

        if ($model->user_id !== Yii::$app->user->identity->getId()) {
            throw new \yii\web\ForbiddenHttpException('You do not have permission to pay this order.');
        }

        if ($model->is_paid) {
            throw new \yii\web\BadRequestHttpException('Order is already paid.');
        }

        if ($model->markAsPaid()) {
            return [
                'success' => true,
                'message' => 'Order successfully marked as paid.',
                'order' => $model
            ];
        }

        return $model->getErrors();
    }
}
