<?php
// mPDF PDF GENERATION (Colored Theme)
require_once __DIR__ . '/../vendor/mpdf/autoload.php';

$mpdf = new \Mpdf\Mpdf();

// HTML content (styled)
$html = '
<h1 style="background:#3a7bd5;color:white;padding:10px;">Farmer Report</h1>
<p>This is a styled PDF report. Full layout will appear once mPDF is installed.</p>
';

$mpdf->WriteHTML($html);
$mpdf->Output("farmer_report.pdf", "I");
?>