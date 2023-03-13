<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableRow as TableRowFrameDecorator;
use Dompdf\Exception;

/**
 * Reflows table rows
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameReflower
{
    /**
     * TableRow constructor.
     * @param TableRowFrameDecorator $frame
     */
    function __construct(TableRowFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        /** @var TableRowFrameDecorator */
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        // Bail if the page is full
        if ($this->_frame->find_pageable_context()->is_full()) {
            return;
        }

        // Counters and generated content
        $this->_set_content();

        $this->_frame->position();
        $style = $this->_frame->get_style();
        $cb = $this->_frame->get_containing_block();

        foreach ($this->_frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow();

            if ($this->_frame->find_pageable_context()->is_full()) {
                //break;
            }
        }

        if ($this->_frame->find_pageable_context()->is_full()) {
            //return;
        }

        $table = TableFrameDecorator::find_parent_table($this->_frame);
        $cellmap = $table->get_cellmap();
        $style->set_used("width", $cellmap->get_frame_width($this->_frame));
        $style->set_used("height", $cellmap->get_frame_height($this->_frame));

        $this->_frame->set_position($cellmap->get_frame_position($this->_frame));

        // split parent now if the row was split
        if ($frame->_new_row !== null) {
            //TODO: walk cell map and add missing cells (append empty frame and split)
            //      AND/OR, update cell map so that existing cells +1 their rowspan?
            //      Increase the rowspan on any element with a rowspan? On the split cells?
            $frame->_new_row->split(null, true, false); // FIXME: why is this necessary, shouldn't the new row split during the next iteration?
            // Preserve the current counter values. This must be done after the
            // parent split, as counters get reset on frame reset
            //$frame->_new_row->_counters = $frame->_counters;
        }
    }

    /**
     * @throws Exception
     */
    public function get_min_max_width(): array
    {
        throw new Exception("Min/max width is undefined for table rows");
    }
}
