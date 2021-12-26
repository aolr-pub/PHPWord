<?php

namespace PhpOffice\PhpWord\Writer\HTML\Element;

use PhpOffice\PhpWord\Settings;

class PreserveText extends TextRun
{
    public function write()
    {
        if (!$this->element instanceof \PhpOffice\PhpWord\Element\PreserveText) {
            return '';
        }

        $content = '';
        $content .= $this->writeOpening();
        $content .= $this->element->getText();
        $content .= $this->writeClosing();

        return $content;

    }
}
