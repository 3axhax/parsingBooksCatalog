<?php
/**
 * Created by PhpStorm.
 * User: ТиМ
 * Date: 19.02.2018
 * Time: 22:44
 */

namespace components;


class Report
{
    static private $errorMessage = '';
    static private $countUpdate = 0;
    static private $countAdd = 0;
    static private $countError = 0;
    static private $instance;
    
    static public function instance()
    {
        if (! isset(self::$instance))
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function addCountUpdate()
    {
        if (! isset(self::$countUpdate)) self::$countUpdate = 1;
        else self::$countUpdate++;
    }
    public function getCountUpdate()
    {
        return self::$countUpdate;
    }
    public function addCountAdd()
    {
        if (! isset(self::$countAdd)) self::$countAdd = 1;
        else self::$countAdd++;
    }
    public function getCountAdd()
    {
        return self::$countAdd;
    }
    public function addCountError()
    {
        if (! isset(self::$countError)) self::$countError = 1;
        else self::$countError++;
    }
    public function getCountError()
    {
        return self::$countError;
    }
    public function getReportMessage()
    {
        $report = ($this->getCountError() == 0) ? 'Загрузка файла прошла успешно. <br>' : 'Загрузка файла прошла с ошибками. <br>';
        $report .= 'Обновлено '.$this->getCountUpdate().' записей; <br>';
        $report .= 'Добавлено '.$this->getCountAdd().' записей; <br>';
        $report .= 'Ошибок: '.$this->getCountError().'; <br>';
        $report .= self::$errorMessage;
        return $report;
    }
    public function addErrorMessage($message)
    {
        self::$errorMessage .= $message.'<br>';
    }
    public function getErrorMessage()
    {
        return self::$errorMessage;
    }
}