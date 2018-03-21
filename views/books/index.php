<?php
?>
<h3><?= static::$title ?></h3>
<form action="/" method="post" class="form-horizontal" enctype="multipart/form-data">
    <div class="">
        <button type="submit" class="btn btn-success" name="submitbutton">Проверить <span class="glyphicon glyphicon-check"></button>
    </div>
</form>
<?php if(is_object($data) && isset($data->pathToReport)):?>
    <br><a href="<?= $data->pathToReport ?>" class="btn btn-success">Загрузить последний отчет <span class="glyphicon glyphicon-download-alt"></a>
<?php endif;?>
<br>
<!--<pre>
    <?php
/*    if(is_object($data)) print_r($data->report);
    */?>
</pre>-->