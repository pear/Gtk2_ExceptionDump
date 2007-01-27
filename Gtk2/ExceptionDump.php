<?php
require_once 'Gtk2/ExceptionDump/InfoBox.php';
require_once 'Gtk2/ExceptionDump/Stack.php';

/**
*   Displays an Exception in a GtkWindow.
*   Supports PHP Exceptions and PEAR_Error objects.
*
*   Simple example:
*   $error = new PEAR_Error();
*   Gtk2_ExceptionDump::display($error);
*
*   Second example:
*   Gtk2_ExceptionDump::setupExceptionHandler();
*   throw new Exception('Exception!');
*
*   Third example:
*   Gtk2_ExceptionDump::setupPearErrorHandler();
*   PEAR::raiserError('Error!');
*
*   Fourth example:
*   Gtk2_ExceptionDump::setupPhpErrorHandler();
*   trigger_error('Oops! A php error', E_USER_ERROR);
*
*   @author Christian Weiske <cweiske@php.net>
*/
class Gtk2_ExceptionDump extends GtkWindow
{
    /**
    *   The exception that is shown.
    *   PEAR_Error or Exception.
    *   @var mixed
    */
    protected $exception = null;



    /**
    *   Creates a new ExceptionDump window.
    *   You still need to show it and run the main loop.
    *
    *   The window can be closed, quitting the main loop and
    *   continuing running the program OR quitting the script
    *   with exit status 253
    *
    *   @param mixed    $exception      Exception or PEAR_Error object
    *   @param string   $title          The title for the window
    */
    public function __construct($exception, $title = null)
    {
        parent::__construct();
        $this->buildDialog();
        if ($exception !== null) {
            $this->setException($exception, $title);
        }
    }//public function __construct($exception, $title = null)



    /**
    *   Creates a new ExceptionDump window, displays it
    *   and starts its own main loop.
    *   The window can be closed, quitting the main loop and
    *   continuing running the program OR quitting the script
    *   with exit status 253
    *
    *   @param mixed    $exception      Exception or PEAR_Error object
    *   @param string   $title          (optional) The title for the window
    */
    public static function display($exception, $title = null)
    {
        $ed = new Gtk2_ExceptionDump($exception, $title);
        $ed->show_all();
        Gtk::main();
    }//public static function display($exception = null, $title = null)



    /**
    *   Sets up the PHP exception handler to call
    *   Gtk2_ExceptionDump::display() if an exception occurs.
    *
    *   This is the safest way to handle *all* uncaught exceptions
    *   with Gtk2_ExceptionDump. It handles "real" exceptions only,
    *   not PEAR_Errors.
    *
    *   Note that the "continue" button doesn't work anymore, since
    *   the exceptions are handled out of all user php code.
    */
    public static function setupExceptionHandler()
    {
        set_exception_handler(array('Gtk2_ExceptionDump', 'display'));
    }//public static function setupExceptionHandler()



    /**
    *   Sets up the PEAR Exception handler to call
    *   Gtk2_ExceptionDump::display() if an PEAR_Error occurs.
    *
    *   While this catches *all* PEAR_Errors, it also catches
    *   the ones that are handled by php scripts.
    */
    public static function setupPearErrorHandler()
    {
        require_once 'PEAR.php';
        PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array('Gtk2_ExceptionDump', 'display'));
    }//public static function setupPearErrorHandler()



    /**
    *   Sets the php error handler to use Gtk2_ExceptionDump.
    *   All catchable PHP errors are displayed here.
    *
    *   Not all errors are handled, only the ones
    *   defined by error_reporting.
    */
    public static function setupPhpErrorHandler()
    {
        set_error_handler(array('Gtk2_ExceptionDump', 'handlePhpError'), error_reporting());
    }//public static function setupPhpErrorHandler()



    /**
    *   Sets up that all errors/exceptions are handled with
    *   Gtk2_ExceptionDump.
    *   Calls setupExceptionHandler() and setupPearErrorHandler()
    *   internally.
    */
    public static function setupAllHandlers()
    {
        self::setupExceptionHandler();
        self::setupPearErrorHandler();
        self::setupPhpErrorHandler();
    }//public static function setupAllHandlers()



    /**
    *   Use Gtk2_ExceptionDump to display a PHP error.
    *
    *   @param int      $errno      Contains the level of the error raised
    *   @param string   $errstr     Error message
    *   @param string   $errfile    Filename that the error was raised in
    *   @param int      $errline    Line number the error was raised at
    *   @param array    $errcontext Array that points to the active symbol
    *                                table at the point the error occurred. In
    *                                other words, errcontext will contain an
    *                                array of every variable that existed in
    *                                the scope the error was triggered in.
    */
    public static function handlePhpError($errno, $errstr, $errfile = null, $errline = null , $errcontext = array())
    {
        //this occurs when using @ to silence errors
        if (error_reporting() == 0) {
            return;
        }

        $errorNames = array(
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT'
        );
        Gtk2_ExceptionDump::display(
            new Exception($errstr . "\r\n\r\n" . $errfile . '#' . $errline, $errno),
            'PHP Error: ' . $errorNames[$errno]
        );
    }//public static function handlePhpError($errno, $errstr, $errfile = null, $errline = null , $errcontext = array())



    /**
    *   Set the exception object.
    *
    *   @param mixed    $exception  The exception to display
    */
    public function setException($exception, $title = null)
    {
        if ($exception instanceof PEAR_Error || $exception instanceof Exception) {
            $this->exception = $exception;
            $this->stack->setException($exception);
            $this->infobox->setException($exception);
            $this->setTitle($exception, $title);
        } else {
            $this->buildError($exception);
            $this->set_title('No error occured');
        }

    }//public function setException($exception, $title = null)



    /**
    *   Sets the title of the window.
    *
    *   @param string   $title      Window title to use
    */
    protected function setTitle($exception, $title)
    {
        if ($title === null) {
            $class = get_class($exception);
            if ($exception instanceof PEAR_Error) {
                if ($class == 'PEAR_Error') {
                    $title = 'A PEAR_Error was thrown';
                } else {
                    $title = 'A "' . $class . '" PEAR_Error was thrown';
                }
            } else {
                if ($class == 'Exception') {
                    $title = 'An Exception was thrown' ;
                } else {
                    $title = 'An "' . $class . '" exception was thrown' ;
                }
            }
        }
        $this->set_title($title);
    }//protected function setTitle($exception, $title)



    /**
    *   Creates the dialog widgets.
    */
    protected function buildDialog()
    {
        $this->destroySignal = $this->connect_simple('destroy', array($this, 'onQuit'));
        $this->set_default_size(700, 300);

        $this->infobox = new Gtk2_ExceptionDump_InfoBox();

        $this->stack = new Gtk2_ExceptionDump_Stack();
        $scrStack = new GtkScrolledWindow();
        $scrStack->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
        $scrStack->add($this->stack);

        $vbox = new GtkVBox();
        $vbox->pack_start($this->infobox, false);
        $vbox->pack_start($scrStack);

        $hbox = new GtkHbox();
        $vbox->pack_end($hbox, false);

        $hbox->pack_start($this->buildActionButtonBox(), false);
        $hbox->pack_end($this->buildContinuationButtonBox(), false);

        $this->add($vbox);
    }//protected function buildDialog($title)



    /**
    *   Returns the box with the Execute/Continue buttons.
    *
    *   @return GtkButtonBox    The button box.
    */
    protected function buildContinuationButtonBox()
    {
        $buttons = new GtkHButtonBox();
        $buttons->set_layout(Gtk::BUTTONBOX_END);

        $quit     = GtkButton::new_from_stock(Gtk::STOCK_QUIT);
        $quit->connect_simple('clicked', array($this, 'onQuit'));
        $quit->set_flags($quit->flags() + Gtk::CAN_DEFAULT);
        $buttons->add($quit);

        //FIXME: register own stock icon
        $continue = GtkButton::new_from_stock(Gtk::STOCK_EXECUTE);
        $continue->set_label('Con_tinue');
        $continue->connect_simple('clicked', array($this, 'onContinue'));
        $buttons->add($continue);

        return $buttons;
    }//protected function buildContinuationButtonBox()



    /**
    *   Returns the box with the Copy button.
    *
    *   @return GtkButtonBox    The button box.
    */
    protected function buildActionButtonBox()
    {
        $buttons = new GtkHButtonBox();
        $buttons->set_layout(Gtk::BUTTONBOX_END);

        $copy    = GtkButton::new_from_stock(Gtk::STOCK_COPY);
        $copy->connect_simple('clicked', array($this, 'onCopy'));
        $buttons->add($copy);

        return $buttons;
    }//protected function buildActionButtonBox()



    /**
    *   Creates the error message that the object isn't an
    *   Exception or a PEAR_Error.
    *
    *   @param mixed    $exception  Some variable that isn't an Exception
    *                                or a PEAR_Error object
    */
    protected function buildError($exception)
    {
        $this->infobox->setMessage(
            'Gtk2_ExceptionDump has been called with'
            . ' an variable of type "' . gettype($exception) . '".' . "\n"
            . 'It cannot be displayed since it is not'
            . ' an Exception or an PEAR_Error object.',

            'Usually, this occurs when the programmer didn\'t check' . "\n"
            . 'what type of variable is passed to Gtk2_ExceptionDump.' . "\n"
            . 'File a bug report'
        );

        $this->stack->setException(new Exception(), 3);
    }//protected function buildError($exception)



    /**
    *   Quits the php script with exit status 253.
    */
    public function onQuit()
    {
        exit(253);
    }//public function onQuit()



    /**
    *   Closes the window, quits the main loop
    *   and continues normal script execution.
    */
    public function onContinue()
    {
        //block the destroy signal, since it would call onQuit()
        $this->block($this->destroySignal);
        $this->destroy();
        //close this level
        Gtk::main_quit();
    }//public function onContinue()



    /**
    *   Copies the exception/error as string to the clipboard.
    */
    public function onCopy()
    {
        $cb = GtkClipboard::get(Gdk::atom_intern('CLIPBOARD', false));
        $cb->set_text(self::getExceptionAsString($this->exception));
    }//public function onCopy()



    /**
    *   Generates a string representation of the exception.
    *
    *   @param mixed    $exception  The exception to convert
    */
    protected static function getExceptionAsString($exception)
    {
        $s = array(
            get_class($exception),
            'Message:',
            ' ' . $exception->getMessage()
        );
        if ($exception instanceof PEAR_Error) {
            $s[] = 'User info';
            $s[] = ' ' . $exception->getUserInfo();
        }

        $func = $exception instanceof PEAR_Error ? 'getBacktrace' : 'getTrace';
        $trace = $exception->$func();
        $s[] = 'Backtrace:';
        foreach ($trace as $id => $step) {
            if (count($step['args']) == 0) {
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
                $file = $step['file'] . '#' . $step['line'];
            } else {
                $file = 'unknown';
            }

            $s[] = ' ' . $function. ' at ' . $file;


            foreach ($step['args'] as $arg) {
                if (gettype($arg) == 'object') {
                    $arg = get_class($arg) . ' - ' . (string)$arg;
                } else {
                    $arg = gettype($arg) . '(' . $arg . ')';
                }
                $s[] .= '  ' . $arg;
            }
        }

        $s[] = '';//newline

        return implode("\r\n", $s);
    }//protected static function getExceptionAsString($exception)

}//class Gtk2_ExceptionDump extends GtkWindow
?>