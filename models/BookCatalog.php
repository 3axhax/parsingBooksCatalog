<?php

namespace models;

use components\Db;
use PDO;

class BookCatalog
{
    const REG_10_NUMBER = "([^\s\,;][\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[\s\,;])";
    const REG_ISBN10 = "/[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}/";
    const REG_13_NUMBER = "([^\s\,;][\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[—–−-]{0,5}[\d]{1}[\s\,;])";
    const REG_ISBN13 = "/[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}[—–−-]?[\d]{1}/";
    public $id;
    public $isbn;
    public $report = ['new'=>[],'exist'=>[]];
    public $dataFromTable;
    public $pathToReport;

    public function __construct()
    {
        $this->dataFromTable = self::getDataFromTable();
    }

    static public function getDataFromTable()
    {
        $db = Db::getConnection();
        $sql = $db->prepare('SELECT `id`,`description_ru`,`isbn`,`eancode`,`isbn2`,`isbn3`,`isbn4`,`isbn_wrong`  FROM '. self::tableName());
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

    private function get10number($str)
    {
        preg_match(self::REG_10_NUMBER, $str, $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][0]))$matches[0][0] = preg_replace('/[\s\,;]/', '', $matches[0][0]);
        return $matches;
    }
    private function get13number($str)
    {
        preg_match(self::REG_13_NUMBER, $str, $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][0]))$matches[0][0] = preg_replace('/[\s\,;]/', '', $matches[0][0]);
        return $matches;
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

    private function checkExistWrong($num, &$row)
    {
        $isbnWrong = explode(', ', $row['isbn_wrong']);
        if (!in_array($num, $isbnWrong)) $this->saveWrongIsbn($num, $row);
    }

    private function saveIsbn($isbn, &$row)
    {
        if ($row['isbn2'] == '') {
            $column = 'isbn2';
            $value = $isbn;
            $row['isbn2'] = $value;
            $this->writeNewReport('new', $row['id'], $row['eancode'], $isbn, 'isbn2');
        }
        elseif ($row['isbn3'] == ''){
            $column = 'isbn3';
            $value = $isbn;
            $row['isbn3'] = $value;
            $this->writeNewReport('new', $row['id'], $row['eancode'], $isbn, 'isbn3');
        }
        else {
            $column = 'isbn4';
            $this->writeNewReport('new', $row['id'], $row['eancode'], $isbn, 'isbn4');
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
    private function saveWrongIsbn($isbn, &$row)
    {
        $column = 'isbn_wrong';
        $this->writeNewReport('new', $row['id'], $row['eancode'], $isbn, 'isbn_wrong');
        if ($row['isbn_wrong'] == '') $value = $isbn;
        else $value = $row['isbn_wrong'].', '.$isbn;
        $row['isbn_wrong'] = $value;
        $db = Db::getConnection();
        $sql = $db->prepare('UPDATE `'.self::tableName().'` SET '.$column.' = :value WHERE `'.self::tableName().'`.`id` = :id');
        $sql->bindParam(':id', $row['id']);
        $sql->bindParam(':value', $value);
        $sql->execute();
    }

    private function checkExistIsbn($num, &$row)
    {
        $realNum = preg_replace('/[^\d]/','',$num);
        if (isset($row['isbn']) && ($row['isbn'] != '') && ($realNum == preg_replace('/[^\d]/','',$row['isbn']))) {
            $this->writeNewReport('exist', $row['id'], $row['eancode'], $num, 'isbn');
            return 'isbn';
        }
        if (isset($row['isbn2']) && ($row['isbn2'] != '') && ($realNum == preg_replace('/[^\d]/','',$row['isbn2']))) {
            $this->writeNewReport('exist', $row['id'], $row['eancode'], $num, 'isbn2');
            return 'isbn2';
        }
        if (isset($row['isbn3']) && ($row['isbn3'] != '') && ($realNum == preg_replace('/[^\d]/','',$row['isbn3']))) {
            $this->writeNewReport('exist', $row['id'], $row['eancode'], $num, 'isbn3');
            return 'isbn3';
        }
        if (isset($row['isbn4']) && ($row['isbn4'] != ''))
        {
            $isbn4 = explode(',', $row['isbn4']);
            foreach ($isbn4 as $isbn)
            {
                if ($realNum == preg_replace('/[^\d]/','',$isbn))
                {
                    $this->writeNewReport('exist', $row['id'], $row['eancode'], $num, 'isbn4');
                    return 'isbn4';
                }
            }
        }
        $this->saveIsbn($num, $row);
        return 'new';
    }

    private function findNum10(&$row)
    {
        $i = 0;
        while (($num = $this->get10number(substr($row['description_ru'], $i).' ')) && !empty($num))
        {
            if (!$this->isIsbn10Format($num[0][0])) {
                $this->checkExistWrong($num[0][0], $row);
            }
            else {
                if (!($this->checkSumEAN13('978'.$num[0][0]) || $this->checkSumEAN13('979'.$num[0][0]))) {
                    $this->checkExistWrong($num[0][0], $row);
                }
                else {
                    $this->checkExistIsbn($num[0][0], $row);
                }
            }
            $i += $num[0][1]+1;
        }
    }
    private function findNum13(&$row)
    {
        $i = 0;
        while (($num = $this->get13number(substr($row['description_ru'], $i). ' ')) && !empty($num))
        {
            if (!$this->isIsbn13Format($num[0][0])) {
                $this->checkExistWrong($num[0][0], $row);
            }
            else {
                if (!$this->checkSumEAN13($num[0][0])) {
                    $this->checkExistWrong($num[0][0], $row);
                }
                else {
                    $this->checkExistIsbn($num[0][0], $row);
                }
            }
            $i += $num[0][1]+1;
        }
    }

    public function checkTable()
    {
        foreach ($this->dataFromTable as $row)
        {
            $this->findNum10($row);
            $this->findNum13($row);
        }
    }

    private function writeNewReport($type, $id, $eancode, $result, $target)
    {
        $i = count($this->report[$type]);
        $this->report[$type][$i]['id'] = $id;
        $this->report[$type][$i]['eancode'] = $eancode;
        $this->report[$type][$i]['result'] = $result;
        $this->report[$type][$i]['target'] = $target;
    }

    public function getExcelReport()
    {
        $document = new \PHPExcel();

        $j = 0;
        foreach ($this->report as $type => $data) {

            $sheet = $document->createSheet($j);
            $j++;

            $sheet->setTitle($type);

            $columnPosition = 0;
            $startLine = 2;

            $sheet->setCellValueByColumnAndRow($columnPosition, $startLine, $type);
            $sheet->getStyleByColumnAndRow($columnPosition, $startLine)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $document->getActiveSheet()->mergeCellsByColumnAndRow($columnPosition, $startLine, $columnPosition + 3, $startLine);

            $startLine++;

            $columns = ['ID', 'eancode', 'Что нашли', 'Название поля'];

            $currentColumn = $columnPosition;

            foreach ($columns as $column) {
                $sheet->getStyleByColumnAndRow($currentColumn, $startLine)
                    ->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4abf62');

                $sheet->getStyleByColumnAndRow($currentColumn, $startLine)
                    ->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $column);

                $currentColumn++;
            }
            foreach ($data as $key => $dataItem) {
                $startLine++;
                $currentColumn = $columnPosition;

                foreach ($dataItem as $value) {

                    $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, (string)$value);
                    $currentColumn++;
                }
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($document, 'Excel5');

        $this->pathToReport = self::reportPath().'/report_'.date('d-m-Y_H-i-s').'.xls';
        $objWriter->save($this->pathToReport);
    }
}