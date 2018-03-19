<?php

namespace models;

use components\Db;
use PDO;

class BookCatalog
{
    const REG_10_NUMBER = "/[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}/";
    const REG_ISBN10 = "/[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}/";
    const REG_13_NUMBER = "/[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}[\D]{0,5}[\d]{1}/";
    const REG_ISBN13 = "/[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}-?[\d]{1}/";
    public $id;
    public $isbn;
    public $report = [];
    public $dataFromTable;
    public $pathToReport;

    public function __construct()
    {
        $this->dataFromTable = self::getDataFromTable();
    }

    static public function getDataFromTable()
    {
        $db = Db::getConnection();
        $sql = $db->prepare('SELECT `id`,`description_ru`,`isbn`,`isbn2`,`isbn3`,`isbn4`,`isbn_wrong`  FROM '. self::tableName());
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    static protected function tableName()
    {
        return 'books_catalog';
    }

    static protected function reportPath()
    {
        return 'report';
    }

    private function isIsbn10Format($str)
    {
        return preg_match(self::REG_ISBN10, $str);
    }
    private function isIsbn13Format($str)
    {
        return preg_match(self::REG_ISBN13, $str);
    }

    private function checkSumEAN13($str)
    {
        $ean = preg_replace('/[^\d]/','',$str);
        $check = 3*($ean[1] + $ean[3] + $ean[5] + $ean[7] + $ean[9] + $ean[11]) + $ean[0] + $ean[2] + $ean[4] + $ean[6] + $ean[8] + $ean[10];
        $check = $check % 10;
        if ($check != 0) $check = 10 - $check;
        if ($ean[12] != $check) return false;
        return true;
    }

    private function saveIsbn($isbn, &$row)
    {
        

        if ($row['isbn2'] == '') {
            $column = 'isbn2';
            $value = $isbn;
            $row['isbn2'] = $value;
            $this->writeNewReport($row['id'], 'Добавлен новый ISBN', 'В записе с ID = ' . $row['id'] . ' добавлен isbn: ' . $isbn.', в поле: isbn2');
        }
        elseif ($row['isbn3'] == ''){
            $column = 'isbn3';
            $value = $isbn;
            $row['isbn3'] = $value;
            $this->writeNewReport($row['id'], 'Добавлен новый ISBN', 'В записе с ID = ' . $row['id'] . ' добавлен isbn: ' . $isbn.', в поле: isbn3');
        }
        else {
            $column = 'isbn4';
            $this->writeNewReport($row['id'], 'Добавлен новый ISBN', 'В записе с ID = ' . $row['id'] . ' добавлен isbn: ' . $isbn.', в поле: isbn4');
            if ($row['isbn4'] == '') $value = $isbn;
            else $value = $row['isbn4'].', '.$isbn;
            $row['isbn4'] = $value;
        }
        $db = Db::getConnection();
        $sql = $db->prepare('UPDATE `'.self::tableName().'` SET '.$column.' = :value WHERE `'.self::tableName().'`.`id` = :id');
        $sql->bindParam(':id', $row['id']);
        $sql->bindParam(':value', $value);
        $sql->execute();
    }

    private function get10number($str)
    {
        preg_match(self::REG_10_NUMBER, $str, $matches, PREG_OFFSET_CAPTURE);
        return $matches;
    }
    private function get13number($str)
    {
        preg_match(self::REG_13_NUMBER, $str, $matches, PREG_OFFSET_CAPTURE);
        return $matches;
    }

    private function checkExistNum10($row)
    {
        $isExist = false;
        if ($num = preg_replace('/[^\d]/','',$row['description_ru']))
        {
            if (strlen($num) > 9)
            {
                if (isset($row['isbn']) && ($row['isbn'] != '') && (strpos($num, substr(preg_replace('/[^\d]/','',$row['isbn']), 3)) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn. (isbn10)');
                }
                if (isset($row['isbn2']) && ($row['isbn2'] != '') && (strpos($num, substr(preg_replace('/[^\d]/','',$row['isbn2']), 3)) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn2. (isbn10)');
                }
                if (isset($row['isbn3']) && ($row['isbn3'] != '') && (strpos($num, substr(preg_replace('/[^\d]/','',$row['isbn3']), 3)) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn3. (isbn10)');
                }
                if (isset($row['isbn4']) && ($row['isbn4'] != ''))
                {
                    $isbn4 = explode(',', $row['isbn4']);
                    foreach ($isbn4 as $isbn)
                    {
                        if (strpos($num, substr(preg_replace('/[^\d]/','',$isbn), 3)) !== false)
                        {
                            $isExist = true;
                            $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn4. (isbn10)');
                        }
                    }
                }
            }
        }
        return $isExist;
    }
    private function checkExistNum13($row)
    {
        $isExist = false;
        if ($num = preg_replace('/[^\d]/','',$row['description_ru']))
        {
            if (strlen($num) > 12)
            {
                if (isset($row['isbn']) && ($row['isbn'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn'])) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn. (isbn13)');
                }
                if (isset($row['isbn2']) && ($row['isbn2'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn2'])) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn2. (isbn13)');
                }
                if (isset($row['isbn3']) && ($row['isbn3'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn3'])) !== false))
                {
                    $isExist = true;
                    $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn3. (isbn13)');
                }
                if (isset($row['isbn4']) && ($row['isbn4'] != ''))
                {
                    $isbn4 = explode(',', $row['isbn4']);
                    foreach ($isbn4 as $isbn)
                    {
                        if (strpos($num, preg_replace('/[^\d]/','',$isbn)) !== false)
                        {
                            $isExist = true;
                            $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn4. (isbn13)');
                        }
                    }
                }
            }
        }
        return $isExist;
    }

    private function checkFindNum10($row)
    {
        if ($this->checkExistNum10($row)) return false;
        $i = 0;
        while (($num = $this->get10number(substr($row['description_ru'], $i))) && !empty($num))
        {
            if (!$this->isIsbn10Format($num[0][0])) {
                $this->writeNewReport($row['id'], 'В описании неверный формат isbn10', 'В записе с ID = ' . $row['id'] . ' встречается неверный isbn10: ' . $num[0][0]);
            }
            else
            {
                if(!$this->checkSumEAN13('978'.$num[0][0])){
                    $this->writeNewReport($row['id'], 'В описании неверная контрольная сумма EAN13', 'В записе с ID = ' . $row['id'] . ' встречается isbn10 с неверной контрольной суммой: ' . $num[0][0]);
                }
                else $this->saveIsbn('978'.$num[0][0], $row);
            }
            $i += $num[0][1]+1;
        }
    }
    private function checkFindNum13($row)
    {
        if ($this->checkExistNum13($row)) return false;
        $i = 0;
        while (($num = $this->get13number(substr($row['description_ru'], $i))) && !empty($num))
        {
            if (!$this->isIsbn13Format($num[0][0])) {
                $this->writeNewReport($row['id'], 'В описании неверный формат isbn13', 'В записе с ID = ' . $row['id'] . ' встречается неверный isbn13: ' . $num[0][0]);
            }
            else
            {
                if(!$this->checkSumEAN13($num[0][0])){
                    $this->writeNewReport($row['id'], 'В описании неверная контрольная сумма EAN13', 'В записе с ID = ' . $row['id'] . ' встречается isbn13 с неверной контрольной суммой: ' . $num[0][0]);
                }
                else $this->saveIsbn($num[0][0], $row);
            }
            $i += $num[0][1]+1;
        }
    }

    public function checkTable()
    {
        foreach ($this->dataFromTable as $row)
        {
            $this->checkFindNum10($row);
            $this->checkFindNum13($row);
        }
    }

    private function writeNewReport($id, $event, $comment)
    {
        $i = count($this->report);
        $this->report[$i]['id in DB'] = $id;
        $this->report[$i]['event'] = $event;
        $this->report[$i]['comment'] = $comment;
    }

    public function getExcelReport()
    {
        $document = new \PHPExcel();

        $sheet = $document->setActiveSheetIndex(0);

        $columnPosition = 0;
        $startLine = 2;

        $sheet->setCellValueByColumnAndRow($columnPosition, $startLine, 'Отчёт');
        $sheet->getStyleByColumnAndRow($columnPosition, $startLine)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $document->getActiveSheet()->mergeCellsByColumnAndRow($columnPosition, $startLine, $columnPosition+3, $startLine);

        $startLine++;

        $columns = ['№', 'ID записи в БД', 'Событие', 'Комментарий'];

        $data = $this->report;

        $currentColumn = $columnPosition;

        foreach ($columns as $column)
        {
            $sheet->getStyleByColumnAndRow($currentColumn, $startLine)
                ->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4abf62');

            $sheet->getStyleByColumnAndRow($currentColumn, $startLine)
                ->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $column);

            $currentColumn++;
        }
        foreach ($data as $key => $dataItem)
        {
            $startLine++;
            $currentColumn = $columnPosition;

            $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $key+1);
            $sheet->getStyleByColumnAndRow($currentColumn, $startLine)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            foreach ($dataItem as $value)
            {
                $currentColumn++;
                $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $value);
            }
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($document, 'Excel5');

        $this->pathToReport = self::reportPath().'/report_'.date('d-m-Y_H-i-s').'.xls';
        $objWriter->save($this->pathToReport);
    }
}