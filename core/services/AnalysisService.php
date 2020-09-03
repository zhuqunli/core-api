<?php

namespace app\core\services;

use app\core\helpers\AnalysisHelper;
use app\core\models\Category;
use app\core\models\Record;
use app\core\types\DirectionType;
use Yii;
use yii\base\BaseObject;
use yiier\helpers\DateHelper;
use yiier\helpers\Setup;

class AnalysisService extends BaseObject
{
    public function getRecordOverviewByDate(array $date): array
    {
        $conditions = [];
        if (count($date) == 2) {
            $conditions = ['between', 'date', $date[0], $date[1]];
        }
        $userId = \Yii::$app->user->id;
        $sum = Record::find()
            ->where(['user_id' => $userId, 'direction' => DirectionType::INCOME])
            ->andWhere($conditions)
            ->sum('amount_cent');
        $items['income'] = $sum ? (float)Setup::toYuan($sum) : 0;

        $sum = Record::find()
            ->where(['user_id' => $userId, 'direction' => DirectionType::EXPENSE])
            ->andWhere($conditions)
            ->sum('amount_cent');
        $items['expense'] = $sum ? (float)Setup::toYuan($sum) : 0;

        $items['surplus'] = (float)bcadd($items['income'], $items['expense'], 2);

        return $items;
    }

    public function getCategoryStatisticalData(array $date, int $transactionType)
    {
        $conditions = [];
        $items = [];
        if (count($date) == 2) {
            $conditions = ['between', 'date', $date[0], $date[1]];
        }
        $userId = \Yii::$app->user->id;

        $baseConditions = ['user_id' => $userId, 'transaction_type' => $transactionType];
        $categories = Category::find()->where($baseConditions)->asArray()->all();

        foreach ($categories as $key => $category) {
            $items[$key]['x'] = $category['name'];
            $sum = Record::find()
                ->where($baseConditions)
                ->andWhere(['category_id' => $category['id']])
                ->andWhere($conditions)
                ->sum('amount_cent');
            $items[$key]['y'] = $sum ? (float)Setup::toYuan($sum) : 0;
        }

        return $items;
    }


    /**
     * @param $key
     * @return array
     * @throws \Exception
     */
    public static function getDateRange($key): array
    {
        $formatter = Yii::$app->formatter;
        $date = [];
        switch ($key) {
            case AnalysisHelper::TODAY:
                $date = [DateHelper::beginTimestamp(), DateHelper::endTimestamp()];
                break;
            case AnalysisHelper::YESTERDAY:
                $time = strtotime('-1 day');
                $date = [DateHelper::beginTimestamp($time), DateHelper::endTimestamp($time)];
                break;
            case AnalysisHelper::CURRENT_MONTH:
                $time = $formatter->asDatetime('now', 'php:01-m-Y');
                $date = [DateHelper::beginTimestamp($time), DateHelper::endTimestamp()];
                break;
        }

        return array_map(function ($i) use ($formatter) {
            return $formatter->asDatetime($i, 'php:Y-m-d');
        }, $date);
    }
}