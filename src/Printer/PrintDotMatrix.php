<?php namespace Klb\Core\Printer;

use Mike42\Escpos\Printer;

/**
 * Class PrintDotMatrix
 *
 * @package Klb\Core\Printer
 */
class PrintDotMatrix extends Printer
{
    /**
     * @param int $font
     */
    public function setSmallFont($font = Printer::FONT_A)
    {
        self::validateInteger($font, 0, 2, __FUNCTION__);
        $this->connector->write(self::ESC . "g" . chr($font));
    }

    /**
     * @param array $text
     * @param $length
     * @param int $splitInto
     * @param null $extraColumnWidth
     * @return array
     */
    public function textColumns(array $text, $length, $splitInto = 1, $extraColumnWidth = null)
    {
        $splity = floor($length / $splitInto);
        $mod = $length % $splity;
        $columns = [];
        if ($extraColumnWidth === null) {
            $extraColumnWidth = $splitInto;
        }
        $max = 0;
        for ($i = 0; $i < $splitInto; $i++) {
            $columns[$i] = [
                'max'  => ($extraColumnWidth - 1 === $i) ? $splity + $mod : $splity,
                'text' => $text[$i],
            ];
            if (count($text[$i]) > $max) {
                $max = count($text[$i]);
            }
        }
        $str = [];
        for ($i = 0; $i < $max; $i++) {
            $word = "";
            foreach ($columns as $columnIndex => $column) {
                $pad = STR_PAD_RIGHT;
                if (empty($column['text'][$i])) {
                    $column['text'][$i] = '';
                }
                if (is_array($column['text'][$i])) {
                    list($wording, $pad) = each($column['text'][$i]);
                } else {
                    $wording = $column['text'][$i];
                }
                $word .= str_pad($wording, $column['max'], ' ', $pad);
            }
            $str[$i] = $word;
        }

        return $str;
    }

    /**
     * @param array $text
     * @param $length
     * @param int $splitInto
     * @param null $extraColumnWidth
     * @return int
     */
    public function textColumnsPrint(array $text, $length, $splitInto = 1, $extraColumnWidth = null)
    {
        $newLine = "\r\n";
        $counter = 1;
        foreach ($this->textColumns($text, $length, $splitInto, $extraColumnWidth) as $word) {
            $this->text($word . $newLine);
            $counter++;
        }

        return $counter;
    }

}
