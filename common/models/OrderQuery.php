<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Order]].
 * Provides custom query scopes to keep controllers extremely clean.
 *
 * @see Order
 */
class OrderQuery extends ActiveQuery
{
    /**
     * Filter orders by active status.
     * @return $this
     */
    public function active()
    {
        return $this->andWhere('[[status]]=1');
    }

    /**
     * Find only unpaid orders.
     * @return $this
     */
    public function unpaid()
    {
        return $this->andWhere(['is_paid' => false]);
    }

    /**
     * Filter orders by specific user ID.
     * @param int $userId
     * @return $this
     */
    public function byUser($userId)
    {
        return $this->andWhere(['user_id' => $userId]);
    }

    /**
     * {@inheritdoc}
     * @return Order[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Order|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
