<?php
$html = file_get_contents('https://www.kalibrr.id/job-board/te/developer/1', false, stream_context_create(['http' => ['header' => 'User-Agent: Mozilla/5.0']]));
file_put_contents('kalibrr_out.html', $html);
echo "Saved Kalibrr HTML (" . strlen($html) . " bytes)\n";
