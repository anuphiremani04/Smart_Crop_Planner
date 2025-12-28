<?php
declare(strict_types=1);

class SimplePDF
{
    private string $title;
    private array $lines = [];
    private array $shapes = [];
    private int $pageWidth;
    private int $pageHeight;

    public function __construct(string $title = 'Report', int $pageWidth = 595, int $pageHeight = 842)
    {
        $this->title = $title;
        $this->pageWidth = $pageWidth;
        $this->pageHeight = $pageHeight;
    }

    public function addLine(string $text, int $fontSize = 12, bool $bold = false, string $color = '0 0 0'): void
    {
        $this->lines[] = [
            'text' => $text === '' ? ' ' : $text,
            'size' => max(8, min(24, $fontSize)),
            'bold' => $bold,
            'color' => $color
        ];
    }

    public function addSpacer(int $height = 8): void
    {
        $this->lines[] = [
            'text' => ' ',
            'size' => max(6, min(24, $height)),
        ];
    }

    public function addBox(float $x, float $y, float $width, float $height, string $fillColor = '0.95 0.95 0.95', string $strokeColor = '0.7 0.7 0.7', float $strokeWidth = 0.5): void
    {
        $this->shapes[] = [
            'type' => 'box',
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'fill' => $fillColor,
            'stroke' => $strokeColor,
            'strokeWidth' => $strokeWidth
        ];
    }

    public function addHeaderBox(string $text, int $fontSize = 16, string $bgColor = '0.4 0.5 0.92'): void
    {
        $this->lines[] = [
            'text' => $text,
            'size' => $fontSize,
            'bold' => true,
            'color' => '1 1 1',
            'box' => true,
            'boxColor' => $bgColor
        ];
    }

    public function addTable(array $headers, array $rows, int $fontSize = 10, bool $styled = true): void
    {
        $this->lines[] = [
            'type' => 'table',
            'headers' => $headers,
            'rows' => $rows,
            'size' => $fontSize,
            'styled' => $styled
        ];
    }

    public function output(string $filename = 'report.pdf', bool $download = true): void
    {
        $document = $this->buildDocument();

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            if ($download) {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            } else {
                header('Content-Disposition: inline; filename="' . $filename . '"');
            }
            header('Content-Length: ' . strlen($document));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        echo $document;
    }

    private function buildDocument(): string
    {
        $buffer = "%PDF-1.4\n";
        $offsets = [];
        $objectId = 1;

        $contentStream = $this->buildStream();
        $contentObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream"
        );

        $fontObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'
        );
        
        $fontBoldObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'
        );

        $pageObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 ' . $fontObject . " 0 R /Helvetica-Bold " . $fontBoldObject . " 0 R >> >> /MediaBox [0 0 "
            . $this->pageWidth . ' ' . $this->pageHeight . "] /Contents " . $contentObject . " 0 R >>"
        );

        $pagesObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            '<< /Type /Pages /Kids [' . $pageObject . " 0 R] /Count 1 >>"
        );

        $catalogObject = $this->addObject(
            $buffer,
            $offsets,
            $objectId,
            '<< /Type /Catalog /Pages ' . $pagesObject . " 0 R >>"
        );

        $xrefOffset = strlen($buffer);
        $buffer .= "xref\n0 " . $objectId . "\n0000000000 65535 f \n";
        for ($i = 1; $i < $objectId; $i++) {
            $offset = $offsets[$i] ?? 0;
            $buffer .= sprintf("%010d 00000 n \n", $offset);
        }

        $buffer .= "trailer\n<< /Size " . $objectId . " /Root " . $catalogObject . " 0 R >>\n";
        $buffer .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $buffer;
    }

    private function addObject(string &$buffer, array &$offsets, int &$objectId, string $body): int
    {
        $currentId = $objectId;
        $offsets[$currentId] = strlen($buffer);
        $buffer .= $currentId . " 0 obj\n" . $body . "\nendobj\n";
        $objectId++;

        return $currentId;
    }

    private function buildStream(): string
    {
        $startY = $this->pageHeight - 50;
        $x = 50;
        $content = "";
        
        // Draw header background
        $headerY = $startY;
        $content .= "q\n";
        $content .= "0.4 0.5 0.92 rg\n"; // Light blue gradient color
        $content .= "0 0 m\n";
        $content .= $this->pageWidth . " 0 l\n";
        $content .= $this->pageWidth . " 65 l\n";
        $content .= "0 65 l\n";
        $content .= "h f\n";
        $content .= "Q\n";
        
        // Title
        $content .= "BT\n";
        $content .= "/Helvetica-Bold 22 Tf\n";
        $content .= "1 1 1 rg\n"; // White text
        $content .= "1 0 0 1 " . $x . ' ' . ($headerY + 20) . " Tm\n";
        $content .= "(" . $this->escape($this->title) . ") Tj\n";
        $content .= "ET\n";
        
        $y = $startY - 80;

        if (empty($this->lines)) {
            $this->lines[] = ['text' => 'No data to display', 'size' => 12, 'bold' => false, 'color' => '0 0 0'];
        }

        foreach ($this->lines as $line) {
            if ($y < 60) {
                break;
            }
            
            // Handle table
            if (isset($line['type']) && $line['type'] === 'table') {
                $y = $this->drawTable($content, $x, $y, $line);
                continue;
            }
            
            // Handle header box
            if (isset($line['box']) && $line['box']) {
                $boxHeight = $line['size'] + 12;
                $boxY = $this->pageHeight - $y - $boxHeight + 5;
                $content .= "q\n";
                $content .= $line['boxColor'] . " rg\n";
                $content .= ($x - 5) . " " . $boxY . " " . ($this->pageWidth - 2*$x + 10) . " " . $boxHeight . " re\n";
                $content .= "f\n";
                $content .= "Q\n";
            }
            
            $fontWeight = isset($line['bold']) && $line['bold'] ? '/Helvetica-Bold' : '/F1';
            $color = isset($line['color']) ? $line['color'] : '0 0 0';
            
            $content .= "BT\n";
            $content .= $color . " rg\n";
            $content .= $fontWeight . ' ' . $line['size'] . " Tf\n";
            $content .= "1 0 0 1 " . $x . ' ' . $y . " Tm\n";
            $content .= "(" . $this->escape($line['text']) . ") Tj\n";
            $content .= "ET\n";
            
            $spacing = isset($line['box']) ? ($line['size'] + 15) : ($line['size'] + 8);
            $y -= $spacing;
        }

        return $content;
    }
    
    private function drawTable(string &$content, float $x, float $y, array $tableData): float
    {
        $headers = $tableData['headers'];
        $rows = $tableData['rows'];
        $fontSize = $tableData['size'];
        $styled = $tableData['styled'] ?? true;
        
        // Adjust column widths based on number of columns
        $numCols = count($headers);
        $tableWidth = $this->pageWidth - 2*$x;
        $baseWidth = $tableWidth / $numCols;
        $colWidths = array_fill(0, $numCols, $baseWidth);
        
        // Adjust specific columns if needed
        if ($numCols >= 6) {
            $colWidths[0] = 30; // # column
            $colWidths[1] = 100; // Location column
            $remaining = ($tableWidth - 130) / ($numCols - 2);
            for ($i = 2; $i < $numCols; $i++) {
                $colWidths[$i] = $remaining;
            }
        }
        
        $rowHeight = $fontSize + 8;
        $tableX = $x;
        
        // Draw table background
        if ($styled) {
            $content .= "q\n";
            $content .= "0.98 0.99 1 rg\n"; // Very light blue-gray
            $content .= $tableX . " " . ($this->pageHeight - $y - $rowHeight - 2) . " " . $tableWidth . " " . ($rowHeight * (count($rows) + 1) + 4) . " re\n";
            $content .= "f\n";
            $content .= "Q\n";
        }
        
        // Draw header background
        if ($styled) {
            $content .= "q\n";
            $content .= "0.4 0.5 0.92 rg\n"; // Blue header
            $content .= $tableX . " " . ($this->pageHeight - $y - 2) . " " . $tableWidth . " " . $rowHeight . " re\n";
            $content .= "f\n";
            $content .= "Q\n";
        }
        
        // Draw header text
        $currentX = $tableX + 8;
        $content .= "BT\n";
        $content .= "1 1 1 rg\n"; // White text
        $content .= "/Helvetica-Bold " . $fontSize . " Tf\n";
        foreach ($headers as $idx => $header) {
            $content .= "1 0 0 1 " . $currentX . " " . ($y + 2) . " Tm\n";
            $content .= "(" . $this->escape(substr($header, 0, 20)) . ") Tj\n";
            $currentX += $colWidths[$idx];
        }
        $content .= "ET\n";
        
        // Draw header bottom border
        $content .= "q\n";
        $content .= "0.2 0.3 0.6 RG\n";
        $content .= "1.5 w\n";
        $content .= $tableX . " " . ($this->pageHeight - $y - $rowHeight) . " m\n";
        $content .= ($tableX + $tableWidth) . " " . ($this->pageHeight - $y - $rowHeight) . " l\n";
        $content .= "S\n";
        $content .= "Q\n";
        
        $y -= $rowHeight;
        
        // Draw rows
        foreach ($rows as $rowIdx => $row) {
            $rowY = $y;
            $currentX = $tableX + 8;
            
            // Alternate row colors
            if ($styled && $rowIdx % 2 === 0) {
                $content .= "q\n";
                $content .= "0.95 0.97 1 rg\n"; // Very light blue
                $content .= $tableX . " " . ($this->pageHeight - $rowY - $rowHeight) . " " . $tableWidth . " " . $rowHeight . " re\n";
                $content .= "f\n";
                $content .= "Q\n";
            }
            
            $content .= "BT\n";
            $content .= "0 0 0 rg\n"; // Black text
            $content .= "/F1 " . $fontSize . " Tf\n";
            foreach ($row as $idx => $cell) {
                $content .= "1 0 0 1 " . $currentX . " " . $rowY . " Tm\n";
                $content .= "(" . $this->escape(substr((string)$cell, 0, 25)) . ") Tj\n";
                $currentX += $colWidths[$idx];
            }
            $content .= "ET\n";
            
            // Row border
            if ($styled) {
                $content .= "q\n";
                $content .= "0.85 0.85 0.85 RG\n";
                $content .= "0.3 w\n";
                $content .= $tableX . " " . ($this->pageHeight - $rowY - $rowHeight) . " m\n";
                $content .= ($tableX + $tableWidth) . " " . ($this->pageHeight - $rowY - $rowHeight) . " l\n";
                $content .= "S\n";
                $content .= "Q\n";
            }
            
            $y -= $rowHeight;
        }
        
        // Draw table border
        if ($styled) {
            $content .= "q\n";
            $content .= "0.3 0.4 0.6 RG\n";
            $content .= "1 w\n";
            $totalHeight = $rowHeight * (count($rows) + 1);
            $topY = $this->pageHeight - ($y + $rowHeight * count($rows) + 2);
            $content .= $tableX . " " . $topY . " m\n";
            $content .= ($tableX + $tableWidth) . " " . $topY . " l\n";
            $content .= ($tableX + $tableWidth) . " " . ($topY + $totalHeight) . " l\n";
            $content .= $tableX . " " . ($topY + $totalHeight) . " l\n";
            $content .= $tableX . " " . $topY . " l\n";
            $content .= "S\n";
            $content .= "Q\n";
        }
        
        return $y - 15;
    }

    private function escape(string $text): string
    {
        $text = preg_replace("/[\r\n]+/", ' ', $text);
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text ?? ''
        );
    }
}
