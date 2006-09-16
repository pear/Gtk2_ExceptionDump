<?php
/**
*   Information box with icon and the exception message.
*
*   @author Christian Weiske <cweiske@php.net>
*/
class Gtk2_ExceptionDump_InfoBox extends GtkHBox
{
    /**
    *   Creates a new InfoBox and sets the exception.
    *
    *   @param mixed    $exception  Exception or PEAR_Error
    */
    public function __construct($exception = null)
    {
        parent::__construct();
        $this->build();
        if ($exception !== null) {
            $this->setException($exception);
        }
    }//public function __construct($exception = null)



    /**
    *   Sets up the child widgets.
    */
    protected function build()
    {
        $stockalign = new GtkAlignment(0, 0, 0, 0);
        $stockalign->add(GtkImage::new_from_stock(Gtk::STOCK_DIALOG_ERROR, Gtk::ICON_SIZE_DIALOG));
        $this->pack_start($stockalign, false, true);


        $this->expander = new GtkExpander('');

        $this->message = new GtkLabel();
        $this->expander->set_label_widget($this->message);
        $this->message->set_selectable(true);
        $this->message->set_line_wrap(true);

        $this->userinfo = new GtkLabel();
        $this->userinfo->set_selectable(true);
        $this->userinfo->set_line_wrap(true);
        //FIXME: add scrolled window
        $this->expander->add($this->userinfo);

        $this->pack_start($this->expander);
    }//protected function build()



    /**
    *   Sets and displays the exception.
    *
    *   @param mixed    $exception  Exception or PEAR_Error
    */
    public function setException($exception)
    {
        //works on PEAR_Error and Exception
        $code = $exception->getCode();
        if ($code !== null) {
            $code = ' (Code #' . $code . ')';
        }

        $this->message->set_label($exception->getMessage() . $code);
        if ($exception instanceof PEAR_Error) {
            $this->userinfo->set_label($exception->getUserInfo());
        } else {
            $this->userinfo->set_label('');
        }
    }//public function setException($exception)



    /**
    *   Explicitely sets a message to display, not an exception.
    *   Can be used to tell the user that no exception occured,
    *   but a normal variable has been passed.
    *
    *   @param string $message  The message to display
    *   @param string $userinfo User information text that is display
    *                           when expanding the label.
    */
    public function setMessage($message, $userinfo = '')
    {
        $this->message->set_label($message);
        $this->userinfo->set_label($userinfo);
    }//public function setMessage($message, $userinfo = '')

}//class Gtk2_ExceptionDump_InfoBox extends GtkHBox
?>