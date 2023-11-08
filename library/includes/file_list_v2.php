<?php

if (!defined('BB_ROOT')) {
    die(basename(__FILE__));
}

$topic_id = (int)$_GET['t'];

$sql = 'SELECT t.attach_id, t.info_hash_v2, ad.physical_filename
        FROM ' . BB_BT_TORRENTS . ' t
        LEFT JOIN ' . BB_ATTACHMENTS_DESC . ' ad
        ON t.attach_id = ad.attach_id
        WHERE t.topic_id = ' . $topic_id . '
        LIMIT 1';

$row = DB()->fetch_row($sql);

if (empty($row) || empty($row['physical_filename'])) {
    http_response_code(404);
    die('Topic id is missing');
}

if (empty($row['info_hash_v2'])) {
    http_response_code(404);
    die('Currently v2 torrents support file list displaying');
}

$file_contents = file_get_contents(get_attachments_dir() . '/' . $row['physical_filename']);

if (!$tor = \Arokettu\Bencode\Bencode::decode($file_contents, dictType: \Arokettu\Bencode\Bencode\Collection::ARRAY)) {
    return $lang['TORFILE_INVALID'];
}

$torrent = new TorrentPier\Legacy\TorrentFileList($tor);
$file_list = $torrent->fileTreeTable($tor['info']['file tree']);

$date = '';
$name = $tor['info']['name'] ?? '';
if (isset($tor['creation date']) && is_numeric($tor['creation date'])) {
  $date = date("d M Y | G:i:s T", $tor['creation date']);
}
$size = humn_size($file_list['size']);

echo "
<html>
<head>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />
<meta name=\"robots\" content=\"index, follow\">
<meta name=\"description\" content=\"File list for topic - $topic_id\">

<title>File list — $name ($size)</title>
</head>
<body>
<style>

    table {
        table-layout: auto;
        border-collapse: collapse;
        width: auto;
        margin: 20px auto;
        font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",\"Noto Sans\",Helvetica,Arial,sans-serif,\"Apple Color Emoji\",\"Segoe UI Emoji\";
    }

    th, td {
        border: 2px solid black;
    border-color: green;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;

</style>
<center>
<h2 style = \"color: black;font-family: Monospace\">Document name: $name | Date: ($date) | Size: $size</h2><hr>

<table><tr><th>Location</th><th>Size</th><th title=\"BitTorrent Merkle Root — The hash of the file, which is embedded in metafiles with BitTorrent v2 support, tracker users can extract, calculate them, also deduplicate torrents using desktop tools such as Torrent Merkle Root Reader.\">BTMR hash <sup>?</sup></th></tr>";

echo implode('', $file_list['list']);

echo '</center>
</table>
</body>
</html>';

die();
