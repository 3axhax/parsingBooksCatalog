<?php

use components\Report;
use models\Book;
use models\ReadXML;
use controllers\SiteController;

class BookController extends SiteController
{
    public function actionIndex()
    {
        $data = '';
        if (isset($_REQUEST['submitbutton'])) 
        {
            $data = new Book();
            $data->checkExistIsbn();
            $data->getExcelReport();
        }
        $this->setTitle('Проверить БД');
        return $this->render('books/index', ['data' => $data]);
    }
    public function actionList()
    {
        $books = Book::getBookList();
        $this->setTitle('Список книг');
        return $this->render('books/list', ['books' => $books]);
    }
    public function actionAddFile()
    {
        if ($_REQUEST)
        {
            $file = $_FILES['importfile']['tmp_name'];
            $xml = new ReadXML($file);
            $xml->getBooksFromFile();
            $ans = Report::instance()->getReportMessage();
        }
        else {$ans = true; $xml_file = '';}
        $this->setTitle('Добавить файл данных');
        return $this->render('books/add_file', ['ans' => $ans]);
    }
}