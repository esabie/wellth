<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PackingReport
{
    public const BOUQUET_SLUG = 'weekly-shot-pack';

    /**
     * Bottles of each formula inside one Wellth Bouquet.
     *
     * @var array<string, int>
     */
    public const BOUQUET_FORMULAS = [
        'Ginja' => 2,
        'Glow Getter' => 2,
        'Ruby Rush' => 2,
        'Crave Control' => 2,
        'Belly Reset' => 2,
        'ABC' => 2,
    ];

    private const HEADERS = ['Order', 'Customer', 'Phone', 'City', 'Status', 'Item', 'Quantity', 'Bottles', 'Pack'];

    /** Sage from the Wellth logo mark. */
    private const COLOR_SAGE = '607850';

    private const COLOR_SAGE_SOFT = 'E8EEE4';

    private const COLOR_MIST = 'F7F4EF';

    private const COLOR_INK = '1A1A1A';

    private const COLOR_PUNCH = 'E85D2A';

    private const COLOR_WHITE = 'FFFFFF';

    private const COLOR_GRID = 'C8C2B8';

    /**
     * @param  Builder<Order>  $orders
     */
    public function xlsx(Builder $orders): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Packing list');

        $this->writeTitle($sheet);

        $headerRow = 3;
        $sheet->fromArray(self::HEADERS, null, "A{$headerRow}");
        $this->styleHeaderRow($sheet, $headerRow);

        /** @var array<string, int> $totals */
        $totals = [];
        $row = $headerRow + 1;

        $loaded = (clone $orders)->with('items')->orderBy('paid_at')->orderBy('id')->get();

        foreach ($loaded as $order) {
            foreach ($order->items as $item) {
                $bottles = $this->bottles($item);

                $sheet->setCellValue("A{$row}", $order->uuid);
                $sheet->setCellValue("B{$row}", $order->customer_name);
                $sheet->setCellValueExplicit("C{$row}", (string) $order->customer_phone, DataType::TYPE_STRING);
                $sheet->setCellValue("D{$row}", $order->shipping_city);
                $sheet->setCellValue("E{$row}", $order->status->getLabel());
                $sheet->setCellValue("F{$row}", $this->itemLabel($item));
                $sheet->setCellValue("G{$row}", $item->quantity);
                $sheet->setCellValue("H{$row}", array_sum($bottles));
                $sheet->setCellValue("I{$row}", $this->packNote($item));

                $this->styleDataRow($sheet, $row, $row % 2 === 0);

                foreach ($bottles as $formula => $count) {
                    $totals[$formula] = ($totals[$formula] ?? 0) + $count;
                }

                $row++;
            }
        }

        $lastDataRow = $row - 1;
        $totalsStart = $row + 1;

        ksort($totals);
        $this->writeTotals($sheet, $totalsStart, $totals);

        $this->finishSheet($sheet, $headerRow, $lastDataRow, $totalsStart, count($totals));

        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'wellth-packing-');

        try {
            $writer->save($temp);

            return file_get_contents($temp) ?: '';
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($temp);
        }
    }

    private function writeTitle(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'WELLTH PACKING LIST');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => self::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SAGE],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Undelivered paid orders · Generated '.now()->timezone(config('app.timezone'))->format('j M Y, g:ia'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => self::COLOR_SAGE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SAGE_SOFT],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }

    private function styleHeaderRow(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => self::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SAGE],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => $this->gridBorders(),
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function styleDataRow(Worksheet $sheet, int $row, bool $zebra): void
    {
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => [
                'color' => ['rgb' => self::COLOR_INK],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $zebra ? self::COLOR_MIST : self::COLOR_WHITE],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => $this->gridBorders(),
        ]);
        $sheet->getStyle("G{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * @param  array<string, int>  $totals
     */
    private function writeTotals(Worksheet $sheet, int $startRow, array $totals): void
    {
        $sheet->setCellValue("A{$startRow}", 'Products');
        $sheet->setCellValue("B{$startRow}", 'Bottles to pack');
        $sheet->getStyle("A{$startRow}:B{$startRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => self::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_PUNCH],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => $this->gridBorders(),
        ]);
        $sheet->getStyle("B{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = $startRow + 1;

        foreach ($totals as $formula => $count) {
            $sheet->setCellValue("A{$row}", $formula);
            $sheet->setCellValue("B{$row}", $count);
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'font' => [
                    'color' => ['rgb' => self::COLOR_INK],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 === 0 ? self::COLOR_MIST : self::COLOR_WHITE],
                ],
                'borders' => $this->gridBorders(),
            ]);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
    }

    /**
     * @return array{allBorders: array{borderStyle: string, color: array{rgb: string}}}
     */
    private function gridBorders(): array
    {
        return [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => self::COLOR_GRID],
            ],
        ];
    }

    private function finishSheet(Worksheet $sheet, int $headerRow, int $lastDataRow, int $totalsStart, int $totalsCount): void
    {
        foreach ([
            'A' => 14,
            'B' => 18,
            'C' => 14,
            'D' => 12,
            'E' => 12,
            'F' => 18,
            'G' => 10,
            'H' => 10,
            'I' => 48,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->freezePane('A'.($headerRow + 1));

        if ($lastDataRow >= $headerRow + 1) {
            $sheet->setAutoFilter("A{$headerRow}:I{$lastDataRow}");
            $sheet->getStyle('G'.($headerRow + 1).":H{$lastDataRow}")->getNumberFormat()->setFormatCode('0');
        }

        if ($totalsCount > 0) {
            $lastTotalsRow = $totalsStart + $totalsCount;
            $sheet->getStyle('B'.($totalsStart + 1).":B{$lastTotalsRow}")->getNumberFormat()->setFormatCode('0');
        }
    }

    private function itemLabel(OrderItem $item): string
    {
        $label = $item->product_name;
        $lactose = $item->lactoseLabel();

        return $lactose === null ? $label : $label.' ('.$lactose.')';
    }

    private function packNote(OrderItem $item): string
    {
        if (! $this->isBouquet($item)) {
            return $item->quantity.' × '.$this->itemLabel($item);
        }

        return collect(self::BOUQUET_FORMULAS)
            ->map(fn (int $each, string $formula): string => ($each * $item->quantity).' × '.$formula)
            ->implode(', ');
    }

    /**
     * @return array<string, int>
     */
    private function bottles(OrderItem $item): array
    {
        if (! $this->isBouquet($item)) {
            return [$item->product_name => $item->quantity];
        }

        $bottles = [];

        foreach (self::BOUQUET_FORMULAS as $formula => $each) {
            $bottles[$formula] = $each * $item->quantity;
        }

        return $bottles;
    }

    private function isBouquet(OrderItem $item): bool
    {
        return $item->product_slug === self::BOUQUET_SLUG;
    }
}
