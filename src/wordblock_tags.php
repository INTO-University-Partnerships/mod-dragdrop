<?php
defined('MOODLE_INTERNAL') || die();

class wordblock_tags {

    /**
     * a part of speech
     */
    const SPEECH = 1;

    /**
     * a clause
     */
    const CLAUSE = 2;

    /**
     * all types
     * @var array
     */
    private static $_types = array(
        'speech' => self::SPEECH,
        'clause' => self::CLAUSE
    );


    /**
     * writes tags to the database of a given type
     * @param $tags
     * @param $type
     */
    public function create_tags($tags, $type) {
        global $DB;

        $existing = $DB->get_records_menu('dragdrop_tag', array(), '', 'id, name');

        $transaction = $DB->start_delegated_transaction();
        foreach ($tags as $tag) {
            $tag->name = trim(strtolower($tag->name));
            if (in_array($tag->name, $existing)) {
                continue;
            }
            $tag->abbreviation = trim(strtolower($tag->abbreviation));
            $tag->timecreated = time();
            $tag->timemodified = time();
            $tag->type = self::$_types[$type];
            $DB->insert_record('dragdrop_tag', $tag);
        }
        $transaction->allow_commit();
    }

    /**
     * get all tags
     */
    public function get_all() {
        global $DB;
        $records = $DB->get_records('dragdrop_tag');
        $tags = array();
        foreach ($records as $record) {
            if (!in_array($record->type, self::$_types)) {
                continue;
            }
            $type = array_search($record->type, self::$_types);
            $tag = new \stdClass();
            $tag->id = $record->id;
            $tag->type = $record->type;
            $tag->typename = ucfirst($type);
            $tag->name = ucfirst($record->name);
            $tag->abbreviation = $record->abbreviation;
            $tags[] = $tag;
        }
        return $tags;
    }
}