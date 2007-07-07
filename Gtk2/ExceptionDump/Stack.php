<?php
require_once 'Gtk2/ExceptionDump/StackModel.php';
require_once 'Gtk2/VarDump.php';
require_once 'Gtk2/VarDump/ColTreeView.php';

/**
* Stack trace list for an exception.
* Tree with the trace lines in top level, and the parameters
* in second level.
* Double-click an entry, and Gtk2_VarDump will be started.
* Drop an entry in your editor to open the file.
*
* @category Gtk2
* @package  Gtk2_ExceptionDump
* @author   Christian Weiske <cweiske@php.net>
* @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
* @version  CVS: $Id$
* @link     http://pear.php.net/package/Gtk2_ExceptionDump
*/
class Gtk2_ExceptionDump_Stack extends Gtk2_VarDump_ColTreeView
{
    /**
    * Creates the stack trace list.
    *
    * @param mixed $exception Exception or PEAR_Error
    */
    public function __construct($exception = null)
    {
        parent::__construct();

        $this->set_model(new Gtk2_ExceptionDump_StackModel());
        $this->build();

        if ($exception !== null) {
            $this->setException($exception);
        }
    }



    /**
    * Sets up the columns and DnD thingies.
    */
    protected function build()
    {
        $this->createColumns(array('#', 'Function', 'Location'));
        $this->connect_after('event', array($this, 'clickedTree'));
        $this->set_events(Gdk::_2BUTTON_PRESS);

        $this->drag_source_set(
            Gdk::BUTTON1_MASK,
            array(),
            Gdk::ACTION_COPY | Gdk::ACTION_MOVE
        );
        $this->drag_source_add_uri_targets();
        $this->connect('drag-begin'   , array($this, 'onDragBegin'));
        $this->connect('drag-data-get', array($this, 'onGetDragData'));
    }



    /**
    * Sets and displays the exception.
    *
    * @param mixed $exception  Exception or PEAR_Error
    * @param int   $nOmitLines Number of stack lines to supress
    */
    public function setException($exception, $nOmitLines = 0)
    {
        $this->get_model()->setException($exception, $nOmitLines);
    }//public function setException($exception, $nOmitLines = 0)



    /**
    * The tree has been double-clicked. This causes Gtk2_VarDump
    * to be opened with the selected stack trace entry.
    *
    * @param GtkTreeView $tree  The tree which has been clicked
    * @param GdkEvent    $event The event data for the click event
    */
    public function clickedTree($tree, $event)
    {
        if ($event->type !== Gdk::_2BUTTON_PRESS) {
            return;
        }

        list($model, $arSelected) = $tree->get_selection()->get_selected_rows();
        if ($arSelected === null) {
            return;
        }

        $path = implode(':', $arSelected[0]);

        if ($event->button == 1) {
            //left mouse button
            $iter = $this->get_model()->get_iter($path);
            $var  = $this->get_model()->get_value($iter, 3);
            Gtk2_VarDump::display($var, $this->get_model()->get_value($iter, 1));
        }
    }//public function clickedTree($tree, $event)



    /**
    * Returns the data at the end of a DnD operation
    * that shall go into the target application.
    * Sets the file to drop.
    *
    * @param GtkWidget    $widget    Widget the drag begin with
    * @param mixed        $context   Drag context object
    * @param GtkSelection $selection Selection object
    * @param mixed        $info      Info object
    * @param int          $time      Time the drag started
    */
    public function onGetDragData($widget, $context, $selection, $info, $time)
    {
        list($model, $iter) = $widget->get_selection()->get_selected();
        if (!$iter) {
            $context->drag_abort($time);
            return;
        }

        $step = $model->get_value($iter, 3);
        $file = $step['file'] . "\r\n";

        $selection->set(
            //GdkAtom type,
            Gdk::atom_intern('text/uri-list'),
            //gint format,	format (number of bits in a unit)
            8,
            //const guchar *data,pointer to the data (will be copied)
            $file,
            //gint length, length of the data
            strlen($file)
        );
    }//function onGetDragData($widget, $context, $selection, $info, $time)



    /**
    * When a drag begins, we want to set DnD icon to reflect
    * the contents of the row being dragged.
    *
    * @param GtkWidget $widget  Widget the drag begin with
    * @param mixed     $context Drag context object
    */
    public function onDragBegin($widget, $context)
    {
        list($model, $iter) = $this->get_selection()->get_selected();
        if (!$iter) {
            $context->drag_abort($time);
            return;
        }
        /*
        $this->drag_source_set_icon(
            GdkColormap::get_system(),
            //FIXME: create pixmap with contents that really will be dropped
            $this->create_row_drag_icon(
                $this->get_model()->get_path($iter)
            )
        );*/
    }//public function onDragBegin($widget, $context)

}//class Gtk2_ExceptionDump_Stack extends Gtk2_VarDump_ColTreeView
?>