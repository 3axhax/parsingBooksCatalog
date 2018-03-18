<?php
?>
<h3><?= static::$title ?></h3>
<form action="/" method="post" class="form-horizontal" enctype="multipart/form-data">
    <div class="">
        <button type="submit" class="btn btn-success" name="submitbutton">Проверить <span class="glyphicon glyphicon-check"></button>
    </div>
</form>
<pre>
    <?php
    //echo substr('12345678901234567', 10, 13);
    echo "<br>";
    print_r(\models\Book::getDescription());
    ?>
</pre>