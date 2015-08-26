<?php
function xmldb_dragdrop_install() {
    require_once(__DIR__ . '/../src/wordblock_tags.php');
    $tags = json_decode(file_get_contents(__DIR__ . '/tags.json'));
    $src = new wordblock_tags();
    foreach ((array) $tags as $type => $tag) {
        $src->create_tags($tag, $type);
    }
}