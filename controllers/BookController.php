<?php

use models\BookCatalog;
use controllers\SiteController;

class BookController extends SiteController
{
    public function actionIndex()
    {
        $data = '';
        if (isset($_REQUEST['submitbutton'])) 
        {
            $data = new BookCatalog();
            $data->checkTable();
            $data->getExcelReport();
        }
        $this->setTitle('Проверить БД');
        return $this->render('books/index', ['data' => $data]);
    }
}