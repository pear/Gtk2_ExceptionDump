<?php
require_once 'PEAR.php';
function a() {
    return b(true);
}
function b($b) {
    return c('yes', 'no', 'cancel');
}
function c($one, $two, $three) {
    throw new Exception('omg! an exception.');
    return true;
}

try {
    a();
} catch (Exception $e) {
    var_dump($e->getTrace());
}
?>