<?php

namespace models;

use components\Db;
use PDO;

class Book
{
    public $id;
    public $isbn;
    public $report = [];
    public $dataFromTable;
    public $pathToReport;

    static protected function tableName()
    {
        return 'books_catalog';
    }

    public function __construct()
    {
        $this->dataFromTable = self::getDataFromTable();
    }

    static public function getDataFromTable()
    {
        $db = Db::getConnection();
        $sql = $db->prepare('SELECT `id`,`description_ru`,`isbn`,`isbn2`,`isbn3`,`isbn4`  FROM '. self::tableName());
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    private function writeNewReport($id, $event, $comment)
    {
        $i = count($this->report);
        $this->report[$i]['id in DB'] = $id;
        $this->report[$i]['event'] = $event;
        $this->report[$i]['comment'] = $comment;
    }

    public function checkExistIsbn()
    {
        foreach ($this->dataFromTable as $row)
        {
            if ($num = preg_replace('/[^\d]/','',$row['description_ru']))
            {
                if (strlen($num) > 12)
                {
                    if (isset($row['isbn']) && ($row['isbn'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn'])) !== false))
                    {
                        $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn');
                    }
                    if (isset($row['isbn2']) && ($row['isbn2'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn2'])) !== false))
                    {
                        $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn2');
                    }
                    if (isset($row['isbn3']) && ($row['isbn3'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn3'])) !== false))
                    {
                        $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn3');
                    }
                    if (isset($row['isbn4']) && ($row['isbn4'] != ''))
                    {
                        $isbn4 = explode(',', $row['isbn4']);
                        foreach ($isbn4 as $isbn)
                        {
                            if (strpos($num, preg_replace('/[^\d]/','',$isbn)) !== false)
                            {
                                $this->writeNewReport($row['id'], 'В описании известный isbn', 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn4');
                            }
                        }
                    }
                }
            }
        }
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

        $this->pathToReport = 'report/report_'.date('d-m-Y_H-i-s').'.xls';
        $objWriter->save($this->pathToReport);
    }
}