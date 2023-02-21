<?php
namespace cmf\model;
use think\Model;
class OptionModel extends Model
{
    protected $name = 'option';
    protected $type = [
        'option_value' => 'array',
    ];
}
