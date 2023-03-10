<?php

namespace tree;

class Tree
{

    
    public $arr = [];

    
    public $icon = ['│', '├', '└'];
    public $nbsp = "&nbsp;";
    private $str = '';
    
    public $ret = '';

    
    public function init($arr = [])
    {
        $this->arr = $arr;
        $this->ret = '';
        return is_array($arr);
    }

    
    public function getParent($myId)
    {
        $newArr = [];
        if (!isset($this->arr[$myId]))
            return false;
        $pid = $this->arr[$myId]['parent_id'];
        $pid = $this->arr[$pid]['parent_id'];
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a['parent_id'] == $pid)
                    $newArr[$id] = $a;
            }
        }
        return $newArr;
    }

    
    public function getChild($myId)
    {
        $newArr = [];
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {

                if ($a['parent_id'] == $myId) {
                    $newArr[$id] = $a;
                }
            }
        }

        return $newArr ? $newArr : false;
    }

    
    public function getPosition($myId, &$newArr)
    {
        $a = [];
        if (!isset($this->arr[$myId]))
            return false;
        $newArr[] = $this->arr[$myId];
        $pid      = $this->arr[$myId]['parent_id'];
        if (isset($this->arr[$pid])) {
            $this->getPosition($pid, $newArr);
        }
        if (is_array($newArr)) {
            krsort($newArr);
            foreach ($newArr as $v) {
                $a[$v['id']] = $v;
            }
        }
        return $a;
    }

    
    public function getTree($myId, $str, $sid = 0, $adds = '', $str_group = '')
    {
        $number = 1;
        //一级栏目
        $child = $this->getChild($myId);

        if (is_array($child)) {
            $total = count($child);

            foreach ($child as $key => $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer   = $adds ? $adds . $j : '';
                $selected = $value['id'] == $sid ? 'selected' : '';
                $id       = 0;
                $nstr     = '';
                $parentId = $value['parent_id'];
                @extract($value);


                $parentId == 0 && $str_group ? eval("\$nstr = \"$str_group\";") : eval("\$nstr = \"$str\";");

                $this->ret .= $nstr;
                $nbsp      = $this->nbsp;
                $this->getTree($id, $str, $sid, $adds . $k . $nbsp, $str_group);
                $number++;
            }
        }
        return $this->ret;
    }

    
    public function getTreeArray($myId, $maxLevel = 0, $level = 1)
    {
        $returnArray = [];

        //一级数组
        $children = $this->getChild($myId);

        if (is_array($children)) {
            foreach ($children as $child) {
                $child['_level']           = $level;
                $returnArray[$child['id']] = $child;
                if ($maxLevel === 0 || ($maxLevel !== 0 && $maxLevel > $level)) {

                    $mLevel                                = $level + 1;
                    $returnArray[$child['id']]["children"] = $this->getTreeArray($child['id'], $maxLevel, $mLevel);
                }

            }
        }

        return $returnArray;
    }

    //TODO 优化
    private function createTree($list, $index = 'id', $pidField = 'parent_id', $childField = "child")
    {
        $tree = [];
        $list = array_column($list, null, $index);
        foreach ($list as $v) {
            if (isset($list[$v[$pidField]])) {
                $list[$v[$pidField]][$childField][] = &$list[$v[$index]];
            } else {
                $tree[] =& $list[$v[$index]];
            }
        }
        return $tree;
    }

    
    public function getTreeMulti($myId, $str, $sid = 0, $adds = '')
    {
        $number = 1;
        $child  = $this->getChild($myId);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @extract($a);
                eval("\$nstr = \"$str\";");
                $this->ret .= $nstr;
                $this->getTreeMulti($id, $str, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    
    public function getTreeCategory($myId, $str, $str2, $sid = 0, $adds = '')
    {
        $number = 1;
        $child  = $this->getChild($myId);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @extract($a);
                if (empty($html_disabled)) {
                    eval("\$nstr = \"$str\";");
                } else {
                    eval("\$nstr = \"$str2\";");
                }
                $this->ret .= $nstr;
                $this->getTreeCategory($id, $str, $str2, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    
    function getTreeView($myId, $effected_id = 'example', $str = "<span class='file'>\$name</span>", $str2 = "<span class='folder'>\$name</span>", $showlevel = 0, $style = 'filetree ', $currentlevel = 1, $recursion = FALSE)
    {
        $child = $this->getChild($myId);
        if (!defined('EFFECTED_INIT')) {
            $effected = ' id="' . $effected_id . '"';
            define('EFFECTED_INIT', 1);
        } else {
            $effected = '';
        }
        $placeholder = '<ul><li><span class="placeholder"></span></li></ul>';
        if (!$recursion)
            $this->str .= '<ul' . $effected . '  class="' . $style . '">';
        foreach ($child as $id => $a) {

            @extract($a);
            if ($showlevel > 0 && $showlevel == $currentlevel && $this->getChild($id))
                $folder = 'hasChildren'; //如设置显示层级模式@2011.07.01
            $floder_status = isset($folder) ? ' class="' . $folder . '"' : '';
            $this->str     .= $recursion ? '<ul><li' . $floder_status . ' id=\'' . $id . '\'>' : '<li' . $floder_status . ' id=\'' . $id . '\'>';
            $recursion     = FALSE;
            //判断是否为终极栏目
            if ($child == 1) {
                eval("\$nstr = \"$str2\";");
                $this->str .= $nstr;
                if ($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)) {
                    $this->getTreeView($id, $effected_id, $str, $str2, $showlevel, $style, $currentlevel + 1, TRUE);
                } elseif ($showlevel > 0 && $showlevel == $currentlevel) {
                    $this->str .= $placeholder;
                }
            } else {
                eval("\$nstr = \"$str\";");
                $this->str .= $nstr;
            }
            $this->str .= $recursion ? '</li></ul>' : '</li>';
        }
        if (!$recursion)
            $this->str .= '</ul>';
        return $this->str;
    }

    

    function getTreeViewMenu($myId, $effected_id = 'example', $str = "<span class='file'>\$name</span>", $str2 = "<span class='folder'>\$name</span>", $showlevel = 0, $ul_class = "", $li_class = "", $style = 'filetree ', $currentlevel = 1, $recursion = FALSE, $dropdown = 'hasChild')
    {
        $child = $this->getChild($myId);
        if (!defined('EFFECTED_INIT')) {
            $effected = ' id="' . $effected_id . '"';
            define('EFFECTED_INIT', 1);
        } else {
            $effected = '';
        }
        $placeholder = '<ul><li><span class="placeholder"></span></li></ul>';
        if (!$recursion) {
            $this->str .= '<ul' . $effected . '  class="' . $style . '">';
        }

        foreach ($child as $id => $a) {

            @extract($a);
            if ($showlevel > 0 && is_array($this->getChild($a['id']))) {
                $floder_status = " class='$dropdown $li_class'";
            } else {
                $floder_status = " class='$li_class'";;
            }
            $this->str .= $recursion ? "<ul class='$ul_class'><li  $floder_status id= 'menu-item-$id'>" : "<li  $floder_status   id= 'menu-item-$id'>";
            $recursion = FALSE;
            //判断是否为终极栏目
            if ($this->getChild($a['id'])) {
                eval("\$nstr = \"$str2\";");
                $this->str .= $nstr;
                if ($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)) {
                    $this->getTreeViewMenu($a['id'], $effected_id, $str, $str2, $showlevel, $ul_class, $li_class, $style, $currentlevel + 1, TRUE);
                } elseif ($showlevel > 0 && $showlevel == $currentlevel) {
                    //$this->str .= $placeholder;
                }
            } else {
                eval("\$nstr = \"$str\";");
                $this->str .= $nstr;
            }
            $this->str .= $recursion ? '</li></ul>' : '</li>';
        }
        if (!$recursion)
            $this->str .= '</ul>';
        return $this->str;
    }

    
    public function createSubJson($myId, $str = '')
    {
        $sub_cats = $this->getChild($myId);
        $n        = 0;
        if (is_array($sub_cats))
            foreach ($sub_cats as $c) {
                $data[$n]['id'] = iconv(CHARSET, 'utf-8', $c['catid']);
                if ($this->getChild($c['catid'])) {
                    $data[$n]['liclass']  = 'hasChildren';
                    $data[$n]['children'] = [['text' => '&nbsp;', 'classes' => 'placeholder']];
                    $data[$n]['classes']  = 'folder';
                    $data[$n]['text']     = iconv(CHARSET, 'utf-8', $c['catname']);
                } else {
                    if ($str) {
                        @extract(array_iconv($c, CHARSET, 'utf-8'));
                        eval("\$data[$n]['text'] = \"$str\";");
                    } else {
                        $data[$n]['text'] = iconv(CHARSET, 'utf-8', $c['catname']);
                    }
                }
                $n++;
            }
        return json_encode($data);
    }

    private function have($list, $item)
    {
        return (strpos(',,' . $list . ',', ',' . $item . ','));
    }

}

