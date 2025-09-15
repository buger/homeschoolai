<?php

namespace App\Services\Markdown\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class InteractiveExtension implements ExtensionInterface
{
    public function configureSchema(EnvironmentBuilderInterface $environment): void
    {
        // No custom configuration needed
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Add enhanced table renderer
        $environment->addRenderer(Table::class, new EnhancedTableRenderer, 10);
    }
}

class EnhancedTableRenderer implements NodeRendererInterface
{
    /**
     * @param  Table  $node
     */
    public function render($node, ChildNodeRendererInterface $childRenderer)
    {
        if (! $node instanceof Table) {
            throw new \InvalidArgumentException('Incompatible node type: '.get_class($node));
        }

        $tableContainer = new HtmlElement('div', [
            'class' => 'enhanced-table-container',
        ]);

        $tableWrapper = new HtmlElement('div', [
            'class' => 'table-responsive',
        ]);

        $table = new HtmlElement('table', [
            'class' => 'enhanced-table',
            'data-sortable' => 'true',
        ]);

        $hasHeader = false;
        $thead = null;
        $tbody = new HtmlElement('tbody');
        $bodyRows = [];

        foreach ($node->children() as $child) {
            if ($child instanceof TableRow) {
                $isHeaderRow = $this->isHeaderRow($child);

                if ($isHeaderRow && ! $hasHeader) {
                    $thead = new HtmlElement('thead');
                    $headerRow = $this->renderTableRow($child, $childRenderer, true);
                    $thead->setContents([$headerRow]);
                    $hasHeader = true;
                } else {
                    $bodyRows[] = $this->renderTableRow($child, $childRenderer, false);
                }
            }
        }

        $tbody->setContents($bodyRows);

        // Add table elements
        $tableContents = [];
        if ($thead) {
            $tableContents[] = $thead;
        }
        $tableContents[] = $tbody;
        $table->setContents($tableContents);

        $tableWrapper->setContents([$table]);

        // Add table controls if it's sortable
        $containerContents = [];
        if ($hasHeader) {
            $controls = $this->createTableControls();
            $containerContents[] = $controls;
        }
        $containerContents[] = $tableWrapper;

        $tableContainer->setContents($containerContents);

        return $tableContainer;
    }

    private function isHeaderRow(TableRow $row): bool
    {
        // Check if any cell in the row is a header cell
        foreach ($row->children() as $cell) {
            if ($cell instanceof TableCell && $cell->getType() === TableCell::TYPE_HEADER) {
                return true;
            }
        }

        return false;
    }

    private function renderTableRow(TableRow $row, ChildNodeRendererInterface $childRenderer, bool $isHeader): HtmlElement
    {
        $tr = new HtmlElement('tr', [
            'class' => $isHeader ? 'table-header-row' : 'table-body-row',
        ]);

        $cellElements = [];
        foreach ($row->children() as $cell) {
            if ($cell instanceof TableCell) {
                $cellElement = $this->renderTableCell($cell, $childRenderer, $isHeader);
                $cellElements[] = $cellElement;
            }
        }

        $tr->setContents($cellElements);

        return $tr;
    }

    private function renderTableCell(TableCell $cell, ChildNodeRendererInterface $childRenderer, bool $forceHeader = false): HtmlElement
    {
        $isHeader = $forceHeader || $cell->getType() === TableCell::TYPE_HEADER;
        $tagName = $isHeader ? 'th' : 'td';

        $attrs = [
            'class' => $isHeader ? 'table-header-cell' : 'table-body-cell',
        ];

        if ($isHeader) {
            $attrs['data-sortable-column'] = 'true';
            $attrs['role'] = 'columnheader';
            $attrs['tabindex'] = '0';
        }

        // Add alignment if specified
        $alignment = $cell->getAlign();
        if ($alignment) {
            $attrs['style'] = 'text-align: '.$alignment;
        }

        $cellElement = new HtmlElement($tagName, $attrs);

        if ($isHeader) {
            // Add sort indicator for header cells
            $content = new HtmlElement('div', ['class' => 'table-header-content']);
            $text = new HtmlElement('span', ['class' => 'table-header-text'],
                $childRenderer->renderNodes($cell->children()));
            $sortIcon = new HtmlElement('span', ['class' => 'table-sort-icon'], '↕️');

            $content->setContents([$text, $sortIcon]);
            $cellElement->setContents([$content]);
        } else {
            $cellElement->setContents($childRenderer->renderNodes($cell->children()));
        }

        return $cellElement;
    }

    private function createTableControls(): HtmlElement
    {
        $controls = new HtmlElement('div', [
            'class' => 'table-controls',
        ]);

        $searchWrapper = new HtmlElement('div', [
            'class' => 'table-search-wrapper',
        ]);

        $searchInput = new HtmlElement('input', [
            'type' => 'text',
            'class' => 'table-search',
            'placeholder' => 'Search table...',
            'data-table-search' => 'true',
        ], '');

        $searchIcon = new HtmlElement('span', [
            'class' => 'table-search-icon',
        ], '🔍');

        $searchWrapper->setContents([$searchIcon, $searchInput]);

        $actions = new HtmlElement('div', [
            'class' => 'table-actions',
        ]);

        $resetButton = new HtmlElement('button', [
            'class' => 'table-reset-sort',
            'data-table-reset' => 'true',
        ], 'Reset Sort');

        $actions->setContents([$resetButton]);

        $controls->setContents([$searchWrapper, $actions]);

        return $controls;
    }
}
