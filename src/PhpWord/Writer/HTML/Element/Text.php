<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer\HTML\Element;

use PhpOffice\PhpWord\Element\TrackChange;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\Writer\HTML\Style\Font as FontStyleWriter;
use PhpOffice\PhpWord\Writer\HTML\Style\Paragraph as ParagraphStyleWriter;

/**
 * Text element HTML writer
 *
 * @since 0.10.0
 */
class Text extends AbstractElement
{
    /**
     * for list item
     * @var string
     */
    protected $additionalClass = '';

    /**
     * Text written after opening
     *
     * @var string
     */
    private $openingText = '';

    /**
     * Text written before closing
     *
     * @var string
     */
    private $closingText = '';

    /**
     * Opening tags
     *
     * @var string
     */
    private $openingTags = '';

    /**
     * Closing tag
     *
     * @var string
     */
    private $closingTags = '';

    /**
     * Write text
     *
     * @return string
     */
    public function write()
    {
        /** @var \PhpOffice\PhpWord\Element\Text $element Type hint */
        $element = $this->element;
//        $this->getFontStyle();

        $content = '';
        $content .= $this->writeOpening();
        $content .= $this->openingText;
        $content .= $this->openingTags;
        $content .= $this->processTextWithFont();
        $content .= $this->closingTags;
        $content .= $this->closingText;
        $content .= $this->writeClosing();

        return $content;
    }

    /**
     * Set opening text.
     *
     * @param string $value
     */
    public function setOpeningText($value)
    {
        $this->openingText = $value;
    }

    /**
     * Set closing text.
     *
     * @param string $value
     */
    public function setClosingText($value)
    {
        $this->closingText = $value;
    }

    /**
     * Write opening
     *
     * @return string
     */
    protected function writeOpening()
    {
        $content = '';
        if (!$this->withoutP) {
            $style = '';
            if (method_exists($this->element, 'getParagraphStyle')) {
//                $style = $this->getParagraphStyle();
                $class = $this->getParagraphClass();
            }

            $id = ' id="' . $this->element->getElementIndex() . '"';
            $content .= "<p{$id}{$style}{$class}>";
        }

        //open track change tag
        $content .= $this->writeTrackChangeOpening();

        return $content;
    }

    /**
     * Write ending
     *
     * @return string
     */
    protected function writeClosing()
    {
        $content = '';

        //close track change tag
        $content .= $this->writeTrackChangeClosing();

        if (!$this->withoutP) {
            if (Settings::isOutputEscapingEnabled()) {
                $content .= $this->escaper->escapeHtml($this->closingText);
            } else {
                $content .= $this->closingText;
            }

            $content .= '</p>' . PHP_EOL;
        }

        return $content;
    }

    /**
     * writes the track change opening tag
     *
     * @return string the HTML, an empty string if no track change information
     */
    private function writeTrackChangeOpening()
    {
        $changed = $this->element->getTrackChange();
        if ($changed == null) {
            return '';
        }

        $content = '';
        if (($changed->getChangeType() == TrackChange::INSERTED)) {
            $content .= '<ins data-phpword-prop=\'';
        } elseif ($changed->getChangeType() == TrackChange::DELETED) {
            $content .= '<del data-phpword-prop=\'';
        }

        $changedProp = array('changed' => array('author'=> $changed->getAuthor(), 'id'    => $this->element->getElementId()));
        if ($changed->getDate() != null) {
            $changedProp['changed']['date'] = $changed->getDate()->format('Y-m-d\TH:i:s\Z');
        }
        $content .= json_encode($changedProp);
        $content .= '\' ';
        $content .= 'title="' . $changed->getAuthor();
        if ($changed->getDate() != null) {
            $dateUser = $changed->getDate()->format('Y-m-d H:i:s');
            $content .= ' - ' . $dateUser;
        }
        $content .= '">';

        return $content;
    }

    /**
     * writes the track change closing tag
     *
     * @return string the HTML, an empty string if no track change information
     */
    private function writeTrackChangeClosing()
    {
        $changed = $this->element->getTrackChange();
        if ($changed == null) {
            return '';
        }

        $content = '';
        if (($changed->getChangeType() == TrackChange::INSERTED)) {
            $content .= '</ins>';
        } elseif ($changed->getChangeType() == TrackChange::DELETED) {
            $content .= '</del>';
        }

        return $content;
    }

    /**
     * Write paragraph style
     *
     * @return string
     */
    private function getParagraphStyle()
    {
        /** @var \PhpOffice\PhpWord\Element\Text $element Type hint */
        $element = $this->element;
        $style = '';
        if (!method_exists($element, 'getParagraphStyle')) {
            return $style;
        }

        $paragraphStyle = $element->getParagraphStyle();
        $pStyleIsObject = ($paragraphStyle instanceof Paragraph);
        if ($pStyleIsObject) {
            $styleWriter = new ParagraphStyleWriter($paragraphStyle);
            $style = $styleWriter->write();
        } elseif (is_string($paragraphStyle)) {
            $style = $paragraphStyle;
        }
        if ($style) {
            $attribute = $pStyleIsObject ? 'style' : 'class';
            $style = " {$attribute}=\"{$style}\"";
        }

        return $style;
    }

    public function getParagraphClass()
    {
        $element = $this->element;
        $paragraphStyle = $element->getParagraphStyle();
        $pStyleIsObject = ($paragraphStyle instanceof Paragraph);
        $classes = [];
        if ($pStyleIsObject && !empty($paragraphStyle->getStyleName())) {
            $classes[] = $paragraphStyle->getStyleName();
        }

        if (!empty($this->additionalClass)) {
            $classes[] = $this->additionalClass;
        }
        return ' class="' . implode(' ', $classes) . '"';
    }

    private function processTextWithFont()
    {
        $text = '';
        if (Settings::isOutputEscapingEnabled()) {
            $text = $this->escaper->escapeHtml($this->element->getText());
        } else {
            $text = $this->element->getText();
        }

        $fontStyle = $this->element->getfontStyle();
        if (!$fontStyle instanceof Font) {
            return $text;
        }

        if ($fontStyle->isItalic()) {
            $text = sprintf("<i>%s</i>", $text);
        }

        if ($fontStyle->isBold()) {
            $text = sprintf("<b>%s</b>", $text);
        }

        if ($fontStyle->isSuperScript()) {
            $text = sprintf("<sup>%s</sup>", $text);
        }

        if ($fontStyle->isSubScript()) {
            $text = sprintf("<sub>%s</sub>", $text);
        }

        return $text;
    }

    /**
     * Get font style.
     */
    private function getFontStyle()
    {
        /** @var \PhpOffice\PhpWord\Element\Text $element Type hint */
        $element = $this->element;
        $style = '';
        $fontStyle = $element->getFontStyle();
        $fStyleIsObject = ($fontStyle instanceof Font);
        if ($fStyleIsObject) {
            $styleWriter = new FontStyleWriter($fontStyle);
            $style = $styleWriter->write();
        } elseif (is_string($fontStyle)) {
            $style = $fontStyle;
        }
        if ($style) {
            $attribute = $fStyleIsObject ? 'style' : 'class';
            $this->openingTags = "<span {$attribute}=\"{$style}\">";
            $this->closingTags = '</span>';
        }
    }
}
