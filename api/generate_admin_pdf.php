<?php
require_once __DIR__ . '/../vendor/mpdf/autoload.php';

$mpdf = new \Mpdf\Mpdf();

$html = '
<h1 style="background:#3a7bd5;color:white;padding:10px;">Admin Analytics Report</h1>
<p>Admin report PDF generated using mPDF.</p>
';

$mpdf->WriteHTML($html);
$mpdf->Output("admin_report.pdf", "I");
?>