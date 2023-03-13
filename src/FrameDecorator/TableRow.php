<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;

/**
 * Decorates Frames for table row layout
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameDecorator
{
    public $_new_row = null;

    /**
     * TableRow constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->_new_row = null; // if there was a new row, well ... we messed up
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        if ($this->_new_row !== null) {
            $child->reset();
            $this->remove_child($child);
            $this->_new_row->append_child($child);
            return;
        }

        //$this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        // Truncate the box decoration at the split

        // Clear bottom decoration of original frame
        $style->margin_bottom = 0.0;
        $style->padding_bottom = 0.0;
        $style->border_bottom_width = 0.0;
        $style->border_bottom_left_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        // Clear top decoration of split frame
        $split_style->margin_top = 0.0;
        $split_style->padding_top = 0.0;
        $split_style->border_top_width = 0.0;
        $split_style->border_top_left_radius = 0.0;
        $split_style->border_top_right_radius = 0.0;
        $split_style->page_break_before = "auto";

        $split_style->text_indent = 0.0;
        $split_style->counter_reset = "none";

        $this->is_split = true;
        $split->is_split_off = true;
        $split->_already_pushed = false;

        $this->get_parent()->insert_child_after($split, $this, true);

        $this->_new_row = $split;

        // Reset top margin in case of an unforced page break
        // https://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
        $child->get_style()->margin_top = 0.0;

        // Add $child and all following siblings to the new split node
        $child->reset();
        $this->remove_child($child);
        $this->_new_row->append_child($child);

        // don't split the parent yet since we may have more columns to render
    }
}
