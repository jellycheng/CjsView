<?php
echo view()->make('common.header', array_except(get_defined_vars(), array('__data', '__path')));

echo $tpl_share_jelly1 . PHP_EOL;

?>
views/page/index.php

<?php
    echo view()->make('common.footer', array_except(get_defined_vars(), array('__data', '__path')));
?>