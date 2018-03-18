<?php

namespace models;

use components\Db;
use PDO;
use components\Report;

class Book
{
    public $id;
    public $isbn;

    static protected function tableName()
    {
        return 'books_catalog';
    }

    public function __construct()
    {

    }

    static public function getDescription()
    {
        $db = Db::getConnection();
        $sql = $db->prepare('SELECT `id`,`description_ru`,`isbn`,`isbn2`,`isbn3`,`isbn4`  FROM '. self::tableName());
        $sql->execute();
        $report = [];
        $i = 0;
        while ($row = $sql->fetch(PDO::FETCH_ASSOC))
        {
            if ($num = preg_replace('/[^\d]/','',$row['description_ru']))
            {
                if (strlen($num) > 12)
                {
                    //$report[] = $num;
                    if (isset($row['isbn']) && ($row['isbn'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn'])) !== false))
                    {
                        $report[] = 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn';
                    }
                    if (isset($row['isbn2']) && ($row['isbn2'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn2'])) !== false))
                    {
                        $report[] = 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn2';
                    }
                    if (isset($row['isbn3']) && ($row['isbn3'] != '') && (strpos($num, preg_replace('/[^\d]/','',$row['isbn3'])) !== false))
                    {
                        $report[] = 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn3';
                    }
                    if (isset($row['isbn4']) && ($row['isbn4'] != ''))
                    {
                        $isbn4 = explode(',', $row['isbn4']);
                        foreach ($isbn4 as $isbn)
                        {
                            if (strpos($num, preg_replace('/[^\d]/','',$isbn)) !== false)
                            {
                                $report[] = 'В записе с ID = '.$row['id'].' найдено совпадение с полем isbn4';
                            }
                        }
                    }
                }
            }
        }
        return $report;
    }
}