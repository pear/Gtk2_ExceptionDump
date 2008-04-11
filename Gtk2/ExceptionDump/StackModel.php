<?php

/**
* Stack trace list model.
* Is used as model for Gtk2_ExceptionDump_Stack class
*
* @category Gtk2
* @package  Gtk2_ExceptionDump
* @author   Christian Weiske <cweiske@php.net>
* @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
* @version  CVS: $Id$
* @link     http://pear.php.net/package/Gtk2_ExceptionDump
*/
class Gtk2_ExceptionDump_StackModel extends GtkTreeStore
{
    /**
    * Creates the stack trace tree model.
    *
    * @param mixed $exception Exception or PEAR_Error
    */
    public function __construct($exception = null)
    {
        parent::__construct(
            Gobject::TYPE_LONG, Gobject::TYPE_STRING,
            Gobject::TYPE_STRING, Gobject::TYPE_PHP_VALUE
        );

        if ($exception !== null) {
            $this->setException($exception);
        }
    }//public function __construct($exception = null)



    /**
    * Sets and displays the exception.
    *
    * @param mixed $exception  Exception or PEAR_Error
    * @param int   $nOmitLines Number of stack lines to supress
    */
    public function setException($exception, $nOmitLines = 0)
    {
        $this->clear();
        /* PEAR_Error->getBacktrace(): arrays with this elements:
        [1]=>
        array(4) {
            ["file"]=>
            string(61) "/data/php-gtk/two/Gtk2_ExceptionDump/pear_error.phpw"
            ["line"]=>
            int(7)
            ["function"]=>
            string(1) "c"
            ["args"]=>
            array(3) {
            [0]=>
            string(3) "yes"
            [1]=>
            string(2) "no"
            [2]=>
            string(6) "cancel"
            }
        } */

        /* Exception->getTrace(): array with elements a la:
        [0]=>
        array(4) {
            ["file"]=>
            string(60) "/data/php-gtk/two/Gtk2_ExceptionDump/examples/exception.phpw"
            ["line"]=>
            int(7)
            ["function"]=>
            string(1) "c"
            ["args"]=>
            array(3) {
            [0]=>
            string(3) "yes"
            [1]=>
            string(2) "no"
            [2]=>
            string(6) "cancel"
            }
        }*/
        $func = $exception instanceof PEAR_Error ? 'getBacktrace' : 'getTrace';

        $trace = $exception->$func();
        foreach ($trace as $id => $step) {
            if ($nOmitLines > 0) {
                $nOmitLines--; continue;
            }

            if (!isset($step['args']) || count($step['args']) == 0) {
                $function = $step['function'] . '()';
            } else {
                $function = $step['function'] . '(...)';
            }

            if (isset($step['class'])) {
                if ($step['class'] == $step['function']) {
                    $function = 'new ' . $function;
                } else {
                    $function = $step['class'] . $step['type'] . $function;
                }
            }

            if (isset($step['file'])) {
                $file = basename($step['file']) . '#' . $step['line']
                      . '   ' . dirname($step['file']);
            } else {
                $file = 'unknown';
            }

            $parent = $this->append(null, array(
                $id,
                $function,
                $file,
                $step
            ));

            $num = 0;
            if (isset($step['args'])) {
                foreach ($step['args'] as $arg) {
                    $this->append($parent, array(
                        $num++,
                        gettype($arg),
                        (string)$arg,
                        $arg
                    ));
                }
            }
        }

    }//public function setException($exception, $nOmitLines = 0)

}//class Gtk2_ExceptionDump_StackModel extends GtkTreeStore
?>