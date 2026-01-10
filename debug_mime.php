<?php
// Zip containing test.txt
$zipContent = base64_decode('UEsDBBQAAAAIAAAAIQA8Wl0jCwAAAAwAAAAIAAAAdGVzdC50eHR0ZXN0IGNvbnRlbnRQSwECFAAUAAAACAAAACEAPFpdIwsAAAAMAAAACAAAAAAAAAABACAAAAAAAAAAdGVzdC50eHRQSwUGAAAAAAEAAQAzAAAANAAAAAAA');
$zipFile = tempnam(sys_get_temp_dir(), 'debug_zip');
file_put_contents($zipFile, $zipContent);

$finfo = new finfo(FILEINFO_MIME_TYPE);
echo "MIME TYPE: [" . $finfo->file($zipFile) . "]" . PHP_EOL;

unlink($zipFile);
