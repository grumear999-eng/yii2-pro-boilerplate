<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "order".
 * 
 * @property int $id
 * @property int $user_id
 * @property string $total_amount
 * @property int $status
 * @property bool $is_paid
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 *
 * @property User $user
 */
class Order extends ActiveRecord
{
    const STATUS_NEW = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    /**
     * {@inheritdoc}
     * Highlights standard Yii2 architecture behaviors
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'updatedByAttribute' => false, // We only care who created the order
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'total_amount'], 'required'],
            [['user_id', 'status', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['is_paid'], 'boolean'],
            [['total_amount'], 'number', 'min' => 0.01],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['is_paid'], 'default', 'value' => false],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return OrderQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new OrderQuery(get_called_class());
    }

    /**
     * Domain logic encapsulation: mark order as paid
     */
    public function markAsPaid()
    {
        // Business logic could be here (e.g., triggering an event)
        $this->is_paid = true;
        if ($this->status === self::STATUS_NEW) {
            $this->status = self::STATUS_PROCESSING;
        }
        return $this->save(false);
    }
}
