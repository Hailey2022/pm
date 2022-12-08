<?php










namespace think\console;

class Table
{
    const ALIGN_LEFT   = 1;
    const ALIGN_RIGHT  = 0;
    const ALIGN_CENTER = 2;

    
    protected $header = [];

    
    protected $headerAlign = 1;

    
    protected $rows = [];

    
    protected $cellAlign = 1;

    
    protected $colWidth = [];

    
    protected $style = 'default';

    
    protected $format = [
        'compact'    => [],
        'default'    => [
            'top'          => ['+', '-', '+', '+'],
            'cell'         => ['|', ' ', '|', '|'],
            'middle'       => ['+', '-', '+', '+'],
            'bottom'       => ['+', '-', '+', '+'],
            'cross-top'    => ['+', '-', '-', '+'],
            'cross-bottom' => ['+', '-', '-', '+'],
        ],
        'markdown'   => [
            'top'          => [' ', ' ', ' ', ' '],
            'cell'         => ['|', ' ', '|', '|'],
            'middle'       => ['|', '-', '|', '|'],
            'bottom'       => [' ', ' ', ' ', ' '],
            'cross-top'    => ['|', ' ', ' ', '|'],
            'cross-bottom' => ['|', ' ', ' ', '|'],
        ],
        'borderless' => [
            'top'          => ['=', '=', ' ', '='],
            'cell'         => [' ', ' ', ' ', ' '],
            'middle'       => ['=', '=', ' ', '='],
            'bottom'       => ['=', '=', ' ', '='],
            'cross-top'    => ['=', '=', ' ', '='],
            'cross-bottom' => ['=', '=', ' ', '='],
        ],
        'box'        => [
            'top'          => ['┌', '─', '┬', '┐'],
            'cell'         => ['│', ' ', '│', '│'],
            'middle'       => ['├', '─', '┼', '┤'],
            'bottom'       => ['└', '─', '┴', '┘'],
            'cross-top'    => ['├', '─', '┴', '┤'],
            'cross-bottom' => ['├', '─', '┬', '┤'],
        ],
        'box-double' => [
            'top'          => ['╔', '═', '╤', '╗'],
            'cell'         => ['║', ' ', '│', '║'],
            'middle'       => ['╠', '─', '╪', '╣'],
            'bottom'       => ['╚', '═', '╧', '╝'],
            'cross-top'    => ['╠', '═', '╧', '╣'],
            'cross-bottom' => ['╠', '═', '╤', '╣'],
        ],
    ];

    
    public function setHeader(array $header, $align = self::ALIGN_LEFT)
    {
        $this->header      = $header;
        $this->headerAlign = $align;

        $this->checkColWidth($header);
    }

    
    public function setRows(array $rows, $align = self::ALIGN_LEFT)
    {
        $this->rows      = $rows;
        $this->cellAlign = $align;

        foreach ($rows as $row) {
            $this->checkColWidth($row);
        }
    }

    
    protected function checkColWidth($row)
    {
        if (is_array($row)) {
            foreach ($row as $key => $cell) {
                if (!isset($this->colWidth[$key]) || strlen($cell) > $this->colWidth[$key]) {
                    $this->colWidth[$key] = strlen($cell);
                }
            }
        }
    }

    
    public function addRow($row, $first = false)
    {
        if ($first) {
            array_unshift($this->rows, $row);
        } else {
            $this->rows[] = $row;
        }

        $this->checkColWidth($row);
    }

    
    public function setStyle($style)
    {
        $this->style = isset($this->format[$style]) ? $style : 'default';
    }

    
    protected function renderSeparator($pos)
    {
        $style = $this->getStyle($pos);
        $array = [];

        foreach ($this->colWidth as $width) {
            $array[] = str_repeat($style[1], $width + 2);
        }

        return $style[0] . implode($style[2], $array) . $style[3] . PHP_EOL;
    }

    
    protected function renderHeader()
    {
        $style   = $this->getStyle('cell');
        $content = $this->renderSeparator('top');

        foreach ($this->header as $key => $header) {
            $array[] = ' ' . str_pad($header, $this->colWidth[$key], $style[1], $this->headerAlign);
        }

        if (!empty($array)) {
            $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

            if ($this->rows) {
                $content .= $this->renderSeparator('middle');
            }
        }

        return $content;
    }

    protected function getStyle($style)
    {
        if ($this->format[$this->style]) {
            $style = $this->format[$this->style][$style];
        } else {
            $style = [' ', ' ', ' ', ' '];
        }

        return $style;
    }

    
    public function render($dataList = [])
    {
        if ($dataList) {
            $this->setRows($dataList);
        }

        
        $content = $this->renderHeader();
        $style   = $this->getStyle('cell');

        if ($this->rows) {
            foreach ($this->rows as $row) {
                if (is_string($row) && '-' === $row) {
                    $content .= $this->renderSeparator('middle');
                } elseif (is_scalar($row)) {
                    $content .= $this->renderSeparator('cross-top');
                    $array = str_pad($row, 3 * (count($this->colWidth) - 1) + array_reduce($this->colWidth, function ($a, $b) {
                        return $a + $b;
                    }));

                    $content .= $style[0] . ' ' . $array . ' ' . $style[3] . PHP_EOL;
                    $content .= $this->renderSeparator('cross-bottom');
                } else {
                    $array = [];

                    foreach ($row as $key => $val) {
                        $array[] = ' ' . str_pad($val, $this->colWidth[$key], ' ', $this->cellAlign);
                    }

                    $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

                }
            }
        }

        $content .= $this->renderSeparator('bottom');

        return $content;
    }
}
