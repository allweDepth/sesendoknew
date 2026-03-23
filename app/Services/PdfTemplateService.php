<?php

class PdfTemplateService
{
  public function render(TCPDF $pdf, array $schema, array $data)
  {
    foreach ($schema as $item) {

      $field = $item['field'] ?? null; // field key
      $label = $item['label'] ?? strtoupper($field);
      $type  = $item['type']  ?? 'text';

      $value = $data[$field] ?? '';

      switch ($type) {

        case 'title':
          $pdf->SetFont('times', 'B', 14);
          $pdf->Cell(0, 6, $value, 0, 1, 'C');
          $pdf->Ln(3);
          break;

        case 'paragraph':
          $pdf->SetFont('times', '', 11);
          $pdf->MultiCell(0, 6, $value, 0, 1);
          break;

        case 'text':
        default:
          $pdf->SetFont('times', 'B', 11);
          $pdf->Cell(50, 6, $label, 0, 0);

          $pdf->SetFont('times', '', 11);
          $pdf->MultiCell(0, 6, ': ' . $value, 0, 1);
          break;
      }
    }
  }
}