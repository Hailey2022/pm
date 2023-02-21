<?php
namespace think\model;
use think\Model;
class Pivot extends Model
{
    public $parent;
    protected $autoWriteTimestamp = false;
    public function __construct($data = [], Model $parent = null, $table = '')
    {
        $this->parent = $parent;
        if (is_null($this->name)) {
            $this->name = $table;
        }
        parent::__construct($data);
    }
}
