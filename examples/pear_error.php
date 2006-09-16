<?php
require_once 'PEAR.php';
function a() {
    return b(true);
}
function b($b) {
    return c('yes', 'no', 'cancel');
}
function c($one, $two, $three) {
    return new PEAR_Error('omg!');
}
var_dump(a()->getBacktrace());
?>