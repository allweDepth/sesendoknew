<?php

require_once __DIR__.'/../../vendor/tecnickcom/tcpdf/tcpdf.php';

class TableRowsPdf extends TCPDF
{
    protected function getHtmlDomArray($html)
    {
        $dom = parent::getHtmlDomArray($html);
        // TCPDF renders repeated headers recursively inside the active row transaction.
        if (str_contains($html, '</tablehead>')) return $dom;

        foreach ($dom as $index => $node) {
            if ($node['value'] !== 'tr' || empty($node['opening']) || !empty($node['thead'])
                || isset($node['attribute']['nobr'])) {
                continue;
            }

            $table = $dom[$node['parent']];
            if (!empty($table['attribute']['nested'])) continue;

            // Explicit page-break rules take precedence, including on a whole block.
            $ancestor = $node['parent'];
            while ($ancestor && !isset($dom[$ancestor]['attribute']['nobr'])) {
                $ancestor = $dom[$ancestor]['parent'];
            }
            if (!$ancestor) $dom[$index]['attribute']['nobr'] = 'true';
        }
        return $dom;
    }

    public function _destroy($destroyall=false,$preserve_objcopy=false)
    {
        // TCPDF transactions may destroy a clone before its destructor runs.
        if (!isset($this->file_id)) return;
        parent::_destroy($destroyall,$preserve_objcopy);
    }
}
