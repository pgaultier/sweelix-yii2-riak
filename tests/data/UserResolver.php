<?php

namespace sweelix\yii2\nosql\tests\data;

use sweelix\yii2\nosql\riak\ResolverInterface;

class UserResolver implements ResolverInterface
{
    /**
     * Returns the ActiveRecord whose has been inserted older.
     *
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ResolverInterface::resolve()
     */
    public function resolve($models)
    {
        $ret = null;
        $format = 'Y-m-d\TH:i:sP';
        foreach ($models as $model) {
            $model instanceof User;

            if ($ret === null) {
                $ret = $model;
            } else {
                $date1 = \DateTime::createFromFormat($format, $model->userDateCreate);
                $date2 = \DateTime::createFromFormat($format, $ret->userDateCreate);
                if ($date1 > $date2) {
                    $ret = $model;
                }
            }

        }
        return $ret;
    }
}
