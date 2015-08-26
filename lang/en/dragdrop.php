<?php

defined('MOODLE_INTERNAL') || die();

// general
$string['modulename'] = 'Drag and drop';
$string['modulenameplural'] = 'Drag and drop';
$string['pluginname'] = 'Drag and drop';
$string['modulename_help'] = '@todo';
$string['dragdropname'] = 'Drag and drop name';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['pluginadministration'] = 'Drag and Drop administration';
$string['word_blocks'] = 'Word blocks';
$string['drop_area'] = 'Drop area';
$string['save'] = 'Save';
$string['save_and_close'] = 'Save and close';
$string['close'] = 'Close';
$string['key'] = 'Key';
$string['back_to_attempts'] = 'Back to attempts';
$string['modified'] = 'modified';

// capabilities
$string['dragdrop:addinstance'] = 'Add a new Drag-drop activity';
$string['dragdrop:view'] = 'View a drag and drop activity';
$string['dragdrop:word_blocks'] = 'Manage drag and drop word blocks';
$string['dragdrop:sentences'] = 'Manage drag and drop sentences';
$string['dragdrop:attempts'] = 'Attempt a drag and drop activity';
$string['dragdrop:feedback_settings'] = 'Manage feedback settings';
$string['dragdrop:comment'] = 'Comment on an attempt';
$string['dragdrop:view_all_attempts'] = 'View attempts for all users';
$string['dragdrop:view_all_comments'] = 'View comments for all users';
$string['dragdrop:manage_all_attempts'] = 'Manage all attempts';
$string['dragdrop:manage_all_comments'] = 'Manage all comments';

// exceptions
$string['exception:ajax_only'] = 'AJAX requests only';
$string['exception:non_existent_partial'] = 'Non-existent partial';
$string['invalid_entity'] = 'Invalid entity: $a';

// angular js
$string['js:menu_edit_word_blocks'] = 'Word blocks';
$string['js:menu_edit_attempt_settings'] = 'Attempt settings';
$string['js:menu_report_attempts'] = 'Attempts report';
$string['js:menu_make_attempt'] = 'Attempt';
$string['js:menu_previous_attempts'] = 'Previous attempts';
$string['js:confirm_delete_word_block'] = 'Are you sure you want to delete this word block?';
$string['js:confirm_delete_comment'] = 'Are you sure you want to delete this comment?';
$string['js:confirm_attempt_submission'] = 'Are you sure you want to submit this sentence?';
$string['js:comment_deleted_successfully'] = 'Comment deleted successfully';
$string['js:word_block_deleted_successfully'] = 'Word block deleted successfully';
$string['js:confirm_delete_sentence'] = 'Are you sure you want to delete this sentence?';
$string['js:sentence_deleted_successfully'] = 'Sentence deleted successfully';
$string['js:num_attempts_reached'] = 'You have made the maximum number of attempts for this drag and drop activity';
$string['js:activity_completed'] = 'You have successfully completed this drag and drop activity';
$string['js:please_select'] = 'Please select...';
$string['js:none'] = 'None';
$string['js:correct_attempt_title'] = 'Your attempt was correct';
$string['js:incorrect_attempt_title'] = 'Your attempt was incorrect';
$string['js:reset_attempts'] = 'Are you sure you want to reset all previous attempts?';
$string['js:hint_dialog_title'] = 'Hint';

// dragdrop
$string['header'] = 'Header';
$string['footer'] = 'Footer';

// word blocks
$string['add_new_word_block'] = 'Add new word block';
$string['list_existing_word_blocks'] = 'List of existing word blocks';
$string['word_block'] = 'Word block';
$string['tags'] = 'Tags';
$string['tag'] = 'Label';
$string['no_word_blocks'] = 'No word blocks';
$string['word_block_updated_successfully'] = "Word block updated successfully";
$string['word_block_added_successfully'] = "Word block added successfully";

// sentences
$string['back_to_word_blocks'] = 'Back to word blocks';
$string['add_new_sentence'] = 'Add new sentence';
$string['list_existing_sentences'] = 'List of existing sentences';
$string['valid_sentences'] = 'Valid sentences';
$string['sentence'] = 'Sentence';
$string['no_sentences'] = 'No sentences';
$string['sentence_updated_successfully'] = "Sentence updated successfully";
$string['sentence_added_successfully'] = "Sentence added successfully";
$string['edit_sentence'] = "Edit sentence";
$string['create_sentence_instruction'] = "Create a valid sentence by dragging the word blocks into the drop area, and clicking 'save' when done.";

// sentence words
$string['sentence_words_added_successfully'] = "Sentence added successfully";
$string['sentence_words_updated_successfully'] = "Sentence updated successfully";

// attempt
$string['user_attempt_added_successfully'] = 'Attempt made successfully';
$string['no_attempts'] = 'No attempts';
$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';
$string['date_attempted'] = 'Date';
$string['sentence_submitted'] = 'Attempt submitted';
$string['previous_attempts'] = 'Previous attempts';
$string['reset_attempts'] = 'Reset attempts';
$string['maximum_attempts_reached'] = 'Attempt cannot be saved as maximum attempts have been reached.';
$string['submit_attempt'] = 'Submit attempt';
$string['submission_confirm'] = 'Are you sure you want to submit the following sentence?';
$string['attempts_reset_successfully'] = 'Attempts reset successfully';

// comments
$string['comments'] = "Comments";
$string['no_comments'] = "No comments";
$string['comment_added_successfully'] = "Comment added successfully";
$string['comment_updated_successfully'] = "Comment updated successfully";

// settings
$string['num_attempts'] = 'Number of attempts';
$string['display_labels'] = 'Display labels';
$string['instructions'] = 'Instructions';
$string['settings_updated_successfully'] = "Settings updated successfully";
$string['feedback'] = 'Feedback';
$string['attempt_settings'] = 'Attempt settings';
$string['remaining_attempts'] = 'Remaining attempts: ';
$string['feedback_correct'] = 'Feedback for a correct submission';
$string['feedback_incorrect'] = 'Feedback for an incorrect submission: attempt ';
$string['hint'] = 'Hint';

// reports
$string['student'] = 'Student';
$string['last_attempt_made'] = 'Last attempt';
$string['all_attempts'] = "All attempts";
$string['no_attempts'] = 'No attempts';
$string['searchbyuser'] = 'Search by user';
$string['user'] = 'User';
$string['numattempts'] = 'Number of attempts';
$string['lastattempt'] = 'Last attempt';
$string['completed'] = 'Completed';
$string['reset'] = 'reset';

// notifications
$string['notify:comment_added_by_tutor'] = 'The course tutor has commented on your drag and drop attempts, for activity {$a->activity}. Click <a href="{$a->url}">here</a> to view the comment.';
$string['notify:student_attempt_made'] = 'Student, {$a->studentname}, has failed to submit a correct answer on drag and drop activity, {$a->activity}. Click <a href="{$a->url}">here</a> to review.';

// logs
$string['log:comment_added_by_tutor'] = 'Comment added';
$string['log:comment_added_by_tutor_description'] = 'The user with id \'{$a->creatorid}\' added a comment to the attempts for user with id \'{$a->userid}\' for activity {$a->name}';
$string['log:student_attempt_made'] = 'Attempt made';
$string['log:student_attempt_made_description'] = 'The user with id \'{$a->userid}\' submitted an attempt on activity {$a->name}';

// mocks
$string['mock_entity_added_successfully'] = 'Mock entity added successfully';
$string['mock_entity_updated_successfully'] = 'Mock entity updated successfully';
