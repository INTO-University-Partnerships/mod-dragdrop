'use strict';

var app = angular.module('dragdrop.controllers', []);

app.controller('manageCtrl', [
    '$scope', 'CONFIG', '$location',
    function ($scope, config, $location) {
        $scope.messages = {};
        if (!config.capabilities.manage_word_blocks) {
            $location.path("/attempt");
        }
    }
]);

app.controller('wordBlocksCtrl', [
    '$scope', 'apiSrv', '$timeout', '$window', 'CONFIG', 'tagSrv',
    function ($scope, apiSrv, $timeout, $window, config, tagSrv) {
        $scope.blocks = [];
        $scope.newBlock = "";
        $scope.timeoutPromise = null;
        $scope.tags = [];

        $scope.getBlocks = function () {
            apiSrv.getAll('word_block').
                then(function (data) {
                    $scope.blocks = data;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                }).finally(function () {
                    $scope.timeoutPromise = $timeout(function () {
                        $scope.getBlocks();
                    }, 10000);
                });
        };

        $scope.getTags = function () {
            tagSrv.get().
                then(function (data) {
                    $scope.tags = data;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                });
        };

        $scope.addNewBlockDisabled = function () {
            return $scope.newBlock.length === 0;
        };

        $scope.addNewBlock = function () {
            apiSrv.post('word_block', {wordblock: $scope.newBlock}).
                then(function (data) {
                    $scope.messages.success = data.successMessage;
                    $scope.newBlock = "";
                    $scope.getBlocks();
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                    $scope.getBlocks();
                });
        };

        $scope.editBlock = function (block) {
            $timeout.cancel($scope.timeoutPromise);
            apiSrv.put('word_block', block.id, {tagid: block.tagid, wordblock: block.wordblock}).
                then(function (data) {
                    $scope.messages.success = data.successMessage;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                });
        };

        $scope.deleteBlock = function (id) {
            $timeout.cancel($scope.timeoutPromise);
            $timeout(function () {
                if (!$window.confirm(config.messages.confirm_delete_word_block)) {
                    $scope.getBlocks();
                    return;
                }
                apiSrv.delete('word_block', id).
                    then(function () {
                        $scope.messages.success = config.messages.word_block_deleted_successfully;
                        $scope.getBlocks();
                    }, function (error) {
                        $scope.messages.error = error.errorMessage;
                        $scope.getBlocks();
                    });
            }, 1);
        };

        $scope.stopAutoRefresh = function () {
            $timeout.cancel($scope.timeoutPromise);
        };

        $scope.startAutoRefresh = function () {
            $scope.getBlocks();
        };

        $scope.$on('$destroy', function () {
            $timeout.cancel($scope.timeoutPromise);
        });

        $scope.getBlocks();
        $scope.getTags();
    }
]);

app.controller('sentenceListCtrl', [
    '$scope', 'apiSrv', '$timeout', '$window', 'CONFIG',
    function ($scope, apiSrv, $timeout, $window, config) {
        $scope.sentences = [];
        $scope.timeoutPromise = null;

        $scope.getSentences = function () {
            apiSrv.getAll('sentence').then(function (data) {
                $scope.sentences = data;
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            }).finally(function () {
                $scope.timeoutPromise = $timeout(function () {
                    $scope.getSentences();
                }, 10000);
            });
        };

        $scope.deleteSentence = function (wordid) {
            $timeout.cancel($scope.timeoutPromise);
            $timeout(function () {
                if (!$window.confirm(config.messages.confirm_delete_sentence)) {
                    $scope.getSentences();
                    return;
                }
                apiSrv.delete('sentence', wordid).
                    then(function () {
                        $scope.messages.success = config.messages.sentence_deleted_successfully;
                        $scope.getSentences();
                    }, function (error) {
                        $scope.messages.error = error.errorMessage;
                        $scope.getSentences();
                    });
            }, 1);
        };

        $scope.$on('$destroy', function () {
            $timeout.cancel($scope.timeoutPromise);
        });

        $scope.getSentences();
    }
]);

app.controller('sentenceCtrl', [
    '$scope', 'apiSrv', '$routeParams', '$location', 'CONFIG', '$route',
    function ($scope, apiSrv, $routeParams, $location, config, $route) {
        if (!config.capabilities.manage_sentences) {
            $location.path("/attempt");
        }
        $scope.id = $routeParams.id ? $routeParams.id : 0;
        $scope.blocks = [];
        $scope.placed = {};
        $scope.messages = {};
        $scope.loading = true;
        $scope.locked = true;
        $scope.rendered = {};

        $scope.getWordBlocks = function (sentenceid) {
            apiSrv.getAll('word_block').then(function (data) {
                $scope.blocks = data;
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            }).finally(function () {
                if (sentenceid) {
                    $scope.$evalAsync(function () {
                        $scope.getSentence(sentenceid);
                    });
                }
                else {
                    $scope.loading = false;
                }
            });
        };

        $scope.getSentence = function (sentenceid) {
            apiSrv.get('sentence_words', sentenceid).then(function (data) {
                $scope.placed.wordblocks = data.wordblocks;
                $scope.$broadcast('sentenceLoaded');
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            });
        };

        $scope.removeWordBlock = function (id) {
            for (var i in $scope.placed.wordblocks) {
                if ($scope.placed.wordblocks[i].wordblockid === id) {
                    $scope.placed.wordblocks.splice(i, 1);
                    $scope.$broadcast('wordRemoved', id);
                    return;
                }
            }
        };

        $scope.setLoading = function ($value) {
            $scope.loading = $value;
        };

        $scope.saveSentence = function () {
            if ($scope.placed.wordblocks.length === 0) {
                return;
            }
            $scope.placed.wordblocks.forEach((block, i) => block.position = parseInt(i) + 1);
            if ($scope.id) {
                $scope.putSentence({wordblocks: $scope.placed.wordblocks});
            }
            else {
                $scope.postSentence({wordblocks: $scope.placed.wordblocks});
            }
        };

        $scope.postSentence = function (data) {
            $scope.locked = true;
            apiSrv.post('sentence_words', data).then(function (response) {
                $scope.messages.success = response.successMessage;
                $scope.id = response.sentence.id;
                $location.path("/words/sentence/" + response.sentence.id);
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            }).finally(function () {
                $scope.locked = false;
            });
        };

        $scope.putSentence = function (data) {
            $scope.locked = true;
            apiSrv.put('sentence_words', $scope.id, data).then(function (response) {
                $scope.messages.success = response.successMessage;
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            }).finally(function () {
                $scope.locked = false;
            });
        };

        $scope.reset = function () {
            $scope.getWordBlocks();
            $scope.$broadcast('sentenceReset');
        };

        var lastRoute = $route.current.originalPath;
        $scope.$on('$locationChangeSuccess', function () {
            if (typeof $route.current.originalPath === 'undefined') {
                return;
            }
            if ($route.current.originalPath.indexOf(lastRoute) === 0) {
                $route.current = lastRoute;
            }
        });

        $scope.close = function () {
            $location.path('#words');
        };

        $scope.$watch('placed.wordblocks', function () {
            $scope.locked = !($scope.placed.wordblocks && $scope.placed.wordblocks.length > 0);

        });

        $scope.getWordBlocks($scope.id);
    }
]);

app.controller('attemptCtrl', [
    '$scope', 'apiSrv', 'CONFIG', '$q', '$sce', '$modal', 'tagSrv',
    function ($scope, apiSrv, config, $q, $sce, $modal, tagSrv) {
        $scope.blocks = [];
        $scope.placed = {wordblocks: []};
        $scope.messages = {};
        $scope.tags = [];
        $scope.settings = {
            instruction: "",
            feedback: {},
            hint: "",
            num_attempts: 0,
            feedback_correct: ""
        };
        $scope.remaining_attempts = 0;
        $scope.user = {};
        $scope.rendered = {};
        $scope.loading = true;
        $scope.locked = true;

        $scope.getWordBlocks = function () {
            var deferred = $q.defer();
            apiSrv.getAll('word_block').then(function (data) {

                // shuffle the data
                data.sort(function () {
                    return 0.5 - Math.random();
                });
                $scope.blocks = data;
                deferred.resolve();

            }, function (error) {
                deferred.reject();
                $scope.messages.error = error.errorMessage;
            });
            return deferred.promise;
        };

        $scope.removeWordBlock = function (id) {
            for (var i in $scope.placed.wordblocks) {
                if ($scope.placed.wordblocks[i].wordblockid === id) {
                    $scope.placed.wordblocks.splice(i, 1);
                    $scope.$broadcast('wordRemoved', id);
                    return;
                }
            }
        };

        $scope.getSettings = function () {
            var deferred = $q.defer();
            apiSrv.get('settings', config.instanceid).then(function (data) {
                $scope.prepareSettings(data, deferred);
                if ($scope.settings.display_labels) {
                    $scope.getTags().then(function () {
                        deferred.resolve();
                    });
                }
                else {
                    deferred.resolve();
                }
            }, function (error) {
                $scope.messages.error = error.errorMessage;
                deferred.reject();
            });
            return deferred.promise;
        };

        $scope.getTags = function () {
            var deferred = $q.defer();
            tagSrv.get().
                then(function (data) {
                    $scope.tags = data;
                    deferred.resolve();
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                    deferred.reject();
                });
            return deferred.promise;
        };

        $scope.getUser = function () {
            var deferred = $q.defer();
            apiSrv.getUser(0).then(function (data) {
                deferred.resolve();
                $scope.user = data;
            });
            return deferred.promise;
        };

        $scope.prepareSettings = function (data) {
            $scope.settings.instruction = $sce.trustAsHtml(data.dragdrop.instruction);
            $scope.settings.hint = $sce.trustAsHtml(data.dragdrop.hint);
            $scope.settings.feedback_correct = $sce.trustAsHtml(data.dragdrop.feedback_correct);
            data.feedback.forEach(feedback => {
                var attempt = feedback.attempt;
                $scope.settings.feedback[attempt] = $sce.trustAsHtml(feedback.feedback);
            });
            $scope.settings.num_attempts = parseInt(data.dragdrop.num_attempts);
            $scope.settings.display_labels = Boolean(parseInt(data.dragdrop.display_labels));
        };

        $scope.getUserAttempts = function () {
            var deferred = $q.defer();
            apiSrv.getAll('user_attempt', {userid: $scope.user.id}).then(function (data) {
                $scope.prepareUserAttempts(data);
                deferred.resolve();
            }, function (error) {
                $scope.messages.error = error.errorMessage;
                deferred.reject();
            });
            return deferred.promise;
        };

        $scope.prepareUserAttempts = function (data) {
            var completed = data.some(attempt => parseInt(attempt.correct) === 1 && parseInt(attempt.reset) === 0);
            var numUserAttempts = data.filter(attempt => parseInt(attempt.reset) === 0).length;
            $scope.remaining_attempts = parseInt($scope.settings.num_attempts - numUserAttempts);

            if (completed) {
                $scope.messages.success = config.messages.activity_completed;
                $scope.remaining_attempts = 0;
                $scope.locked = true;
            }
            else if ($scope.remaining_attempts <= 0) {
                $scope.remaining_attempts = 0;
                $scope.messages.error = config.messages.num_attempts_reached;
                $scope.locked = true;
            }
            else {
                $scope.locked = false;
            }
        };

        $scope.submitAttempt = function () {
            var doSubmission = function () {
                $scope.placed.wordblocks.forEach((block, i) => block.position = parseInt(i) + 1);
                var data = {wordblocks: $scope.placed.wordblocks};
                var params = {userid: $scope.user.id};
                apiSrv.post('user_attempt', data, params).then(function (response) {
                    $scope.setFeedback(parseInt(response.correct), parseInt(response.contributing_attempts));
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                });
            };
            $scope.submitDialog($scope.placed.wordblocks, doSubmission);
        };

        $scope.setFeedback = function (correct, attempt) {
            if (correct) {
                $scope.feedbackDialog($scope.settings.feedback_correct, config.messages.correct_attempt_title);
            }
            else {
                $scope.feedbackDialog($scope.settings.feedback[attempt], config.messages.incorrect_attempt_title);
            }
            $scope.getUserAttempts();
        };

        $scope.feedbackDialog = function (content, title) {
            $modal.open({
                templateUrl: config.partialsUrl + 'dialogs/feedbackDialog.twig',
                controller: 'feedbackModalCtrl',
                resolve: {
                    content: function () {
                        return content;
                    },
                    title: function () {
                        return title;
                    }
                }
            });
        };

        $scope.hint = function () {
            $scope.feedbackDialog($scope.settings.hint, config.messages.hint_dialog_title);
        };

        $scope.submitDialog = function (wordblocks, callback) {
            $modal.open({
                templateUrl: config.partialsUrl + 'dialogs/attemptDialog.twig',
                controller: 'submissionModalCtrl',
                resolve: {
                    sentence: function () {
                        var words = [];
                        var ids = $scope.blocks.map(function (e) {
                            return e.id;
                        });
                        wordblocks.forEach(block => {
                            var id = ids.indexOf(block.wordblockid);
                            words.push($scope.blocks[id].wordblock);
                        });
                        return words.join(" ");
                    },
                    callback: function () {
                        return callback;
                    }
                }
            });
        };

        $scope.reset = function () {
            $scope.getWordBlocks();
            $scope.$broadcast('sentenceReset');
        };

        $scope.init = function () {
            $scope.getUser().then(function () {
                $scope.getSettings().then(function () {
                    $scope.getWordBlocks().then(function () {
                        $scope.getUserAttempts().then(function () {
                            $scope.loading = false;
                        });
                    });
                });
            });
        };
        $scope.init();
    }
]);

app.controller('previousAttemptsCtrl', [
    '$scope', 'apiSrv', 'CONFIG', '$sce', '$q', '$routeParams', '$timeout', '$window',
    function ($scope, apiSrv, config, $sce, $q, $routeParams, $timeout, $window) {
        $scope.messages = {};
        $scope.attempts = {
            previous: [],
            reset: []
        };
        $scope.loading = true;
        $scope.timeoutPromise = null;
        $scope.userid = $routeParams.userid ? $routeParams.userid : 0;
        $scope.reporting = $scope.userid !== 0;
        $scope.user = {};
        $scope.remaining_attempts = 0;
        $scope.settings = {};
        $scope.manageAttempts = config.capabilities.manage_attempts;

        $scope.getAttemptsForUser = function () {
            $timeout.cancel($scope.timeoutPromise);
            var deferred = $q.defer();
            apiSrv.getAll('user_attempt', {userid: $scope.user.id}).then(function (data) {
                data.sort($scope.sortByCreated);
                data.forEach(attempt => attempt.correct = parseInt(attempt.correct) === 1);
                $scope.groupAttempts(data);
                deferred.resolve();
            }, function (error) {
                $scope.messages.error = error.errorMessage;
                deferred.reject();
            }).finally(function () {
                $scope.prepareFeedback();
                $scope.calculateRemainingAttempts();
                $scope.loading = false;
                $scope.timeoutPromise = $timeout(function () {
                    $scope.getAttemptsForUser($scope.user.id);
                }, 10000);
            });
            return deferred.promise;
        };

        $scope.reset = function () {
            $timeout.cancel($scope.timeoutPromise);
            $timeout(function () {
                if (!$window.confirm(config.messages.reset_attempts)) {
                    $scope.getAttemptsForUser();
                    return;
                }
                apiSrv.resetAttempts({userid: $scope.user.id}).
                    then(function (data) {
                        $scope.messages.success = data.success;
                        $scope.getAttemptsForUser();
                    }, function (error) {
                        $scope.messages.error = error.errorMessage;
                        $scope.getAttemptsForUser();
                    });
            }, 1);
        };

        $scope.groupAttempts = function (data) {
            $scope.attempts.previous = [];
            $scope.attempts.reset = [];
            for (var i in data) {
                if (Boolean(parseInt(data[i].reset))) {
                    $scope.attempts.reset.push(data[i]);
                }
                else {
                    $scope.attempts.previous.push(data[i]);
                }
            }
        };

        $scope.calculateRemainingAttempts = function () {
            var numUserAttempts = $scope.attempts.previous.filter(block => parseInt(block.reset) === 0).length;
            $scope.remaining_attempts = parseInt($scope.settings.dragdrop.num_attempts) - numUserAttempts;
        };

        $scope.getUser = function (userid) {
            var deferred = $q.defer();
            apiSrv.getUser(userid).then(function (data) {
                $scope.user = data;
                deferred.resolve();
            });
            return deferred.promise;
        };

        $scope.getSettings = function () {
            var deferred = $q.defer();
            apiSrv.get('settings', config.instanceid).then(function (data) {
                $scope.settings = data;
                deferred.resolve();
            }, function (error) {
                $scope.messages.error = error.errorMessage;
                deferred.reject();
            });
            return deferred.promise;
        };

        $scope.prepareFeedback = function () {
            var feedback = {};
            $scope.settings.feedback.forEach(attempt => {
                feedback[attempt.attempt] = attempt.feedback;
            });
            $scope.setFeedbackOnAttempts($scope.attempts.previous, feedback);
            $scope.setFeedbackOnAttempts($scope.attempts.reset, feedback);
        };

        $scope.setFeedbackOnAttempts = function (attempts, feedback) {
            attempts.forEach(attempt => {
                var strFeedback = "";
                if (parseInt(attempt.correct) === 1) {
                    $scope.remaining_attempts = 0;
                    strFeedback = $sce.trustAsHtml($scope.settings.dragdrop.feedback_correct);
                }
                else if (attempt.attempt in feedback) {
                    strFeedback = $sce.trustAsHtml(feedback[attempt.attempt]);
                }
                attempt.feedback = strFeedback;
            });
        };

        $scope.$on('$destroy', function () {
            $timeout.cancel($scope.timeoutPromise);
        });

        $scope.sortByCreated = function (a, b) {
            if (a.timecreated > b.timecreated) {
                return -1;
            }
            if (a.timecreated < b.timecreated) {
                return 1;
            }
            return 0;
        };

    }
]);

app.controller('commentsCtrl', [
    '$scope', 'apiSrv', 'CONFIG', '$timeout', '$window',
    function ($scope, apiSrv, config, $timeout, $window) {
        $scope.comments = [];
        $scope.manageComments = config.capabilities.manage_comments;
        $scope.timeoutPromise = null;
        $scope.comment = "";

        $scope.getComments = function () {
            $timeout.cancel($scope.timeoutPromise);
            apiSrv.getUserComments($scope.user.id).
                then(function (data) {
                    $scope.comments = data;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                }).finally(function () {
                    $scope.timeoutPromise = $timeout(function () {
                        $scope.getComments();
                    }, 10000);
                });
        };

        $scope.$on('$destroy', function () {
            $timeout.cancel($scope.timeoutPromise);
        });

        $scope.addComment = function () {
            apiSrv.post('comment', {
                userid: $scope.user.id,
                comment: $scope.comment
            }).then(function (data) {
                $scope.messages.success = data.successMessage;
                $scope.getComments();
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            });
        };

        $scope.deleteComment = function (id) {
            $timeout.cancel($scope.timeoutPromise);
            $timeout(function () {
                if (!$window.confirm(config.messages.confirm_delete_comment)) {
                    $scope.getComments();
                    return;
                }
                apiSrv.delete('comment', id, {userid: $scope.userid}).
                    then(function () {
                        $scope.messages.success = config.messages.comment_deleted_successfully;
                        $scope.getComments();
                    }, function (error) {
                        $scope.messages.error = error.errorMessage;
                        $scope.getComments();
                    });
            }, 1);
        };

        $scope.editComment = function (comment) {
            $timeout.cancel($scope.timeoutPromise);
            apiSrv.put('comment', comment.id, {comment: comment.comment, userid: $scope.user.id}).
                then(function (data) {
                    $scope.messages.success = data.successMessage;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                });
        };

        $scope.init = function () {
            $scope.getUser($scope.userid).then(function () {
                $scope.getSettings().then(function () {
                    $scope.getAttemptsForUser();
                });
                $scope.getComments();
            });
        };

        $scope.stopAutoRefresh = function () {
            $timeout.cancel($scope.timeoutPromise);
        };

        $scope.startAutoRefresh = function () {
            $scope.getComments();
        };

        $scope.init();

    }
]);

app.controller('settingsCtrl', [
    '$scope', 'apiSrv', 'CONFIG', '$sce', '$location',
    function ($scope, apiSrv, config, $sce, $location) {
        if (!config.capabilities.manage_settings) {
            $location.path("/attempt");
        }
        $scope.messages = {};
        $scope.form = {
            num_attempts: 0,
            display_labels: false,
            instruction: {},
            hint: {},
            feedback_correct: {},
            feedback: {}
        };

        $scope.getSettings = function () {
            apiSrv.get('settings', config.instanceid).then(function (data) {
                $scope.form.num_attempts = parseInt(data.dragdrop.num_attempts);
                $scope.form.display_labels = Boolean(parseInt(data.dragdrop.display_labels));
                $scope.form.instruction.html = $sce.trustAsHtml(data.dragdrop.instruction);
                $scope.form.instruction.raw = data.dragdrop.instruction;
                $scope.form.hint.html = $sce.trustAsHtml(data.dragdrop.hint);
                $scope.form.hint.raw = data.dragdrop.hint;
                $scope.form.feedback_correct.html = $sce.trustAsHtml(data.dragdrop.feedback_correct);
                $scope.form.feedback_correct.raw = data.dragdrop.feedback_correct;
                data.feedback.forEach(feedback => {
                    var attempt = feedback.attempt;
                    $scope.form.feedback[attempt] = {
                        html: $sce.trustAsHtml(feedback.feedback),
                        raw: feedback.feedback
                    };
                });
                $scope.initialiseFeedback();
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            });
        };

        $scope.saveSettings = function (data) {
            apiSrv.put('settings', 0, data).
                then(function (response) {
                    $scope.messages.success = response.successMessage;
                }, function (error) {
                    $scope.messages.error = error.errorMessage;
                    $scope.getFeedback();
                }).finally(function () {
                    $scope.getSettings();
                });
        };

        $scope.initialiseFeedback = function () {
            for (var x = 1; x <= $scope.form.num_attempts; x++) {
                if (!(x in $scope.form.feedback)) {
                    $scope.form.feedback[x] = {raw: "", html: ""};
                }
                $scope.watchFeedback(x);
            }
            for (var i in $scope.form.feedback) {
                if (i > $scope.form.num_attempts) {
                    delete $scope.form.feedback[i];
                }
            }
        };

        $scope.watchFeedback = function (num) {
            $scope.$watch('form.feedback[' + num + '].html', function () {
                if (typeof $scope.form.feedback[num] === 'undefined') {
                    return;
                }
                var feedback = $sce.getTrustedHtml($scope.form.feedback[num].html);
                if (feedback !== $scope.form.feedback[num].raw) {
                    $scope.form.feedback[num].raw = feedback;
                    $scope.saveSettings({
                        feedback: {
                            attempt: parseInt(num),
                            html: feedback
                        }
                    });
                }
            });
        };

        $scope.displayLabelsChanged = function () {
            $scope.initialiseFeedback();
            $scope.saveSettings({
                display_labels: $scope.form.display_labels
            });
        };

        $scope.attemptsChanged = function () {
            $scope.initialiseFeedback();
            $scope.saveSettings({
                num_attempts: $scope.form.num_attempts
            });
        };

        $scope.$watch('form.instruction.html', function () {
            var instruction = $sce.getTrustedHtml($scope.form.instruction.html);
            if (instruction !== $scope.form.instruction.raw) {
                $scope.form.instruction.raw = instruction;
                $scope.saveSettings({
                    instruction: instruction
                });
            }
        });

        $scope.$watch('form.hint.html', function () {
            var hint = $sce.getTrustedHtml($scope.form.hint.html);
            if (hint !== $scope.form.hint.raw) {
                $scope.form.hint.raw = hint;
                $scope.saveSettings({
                    hint: hint
                });
            }
        });

        $scope.$watch('form.feedback_correct.html', function () {
            var feedback = $sce.getTrustedHtml($scope.form.feedback_correct.html);
            if (feedback !== $scope.form.feedback_correct.raw) {
                $scope.form.feedback_correct.raw = feedback;
                $scope.saveSettings({
                    feedback_correct: feedback
                });
            }
        });

        $scope.resetMessages = function () {
            $scope.messages.success = "";
            $scope.messages.error = "";
        };
        $scope.getSettings();

    }
]);

app.controller('attemptsReportCtrl', [
    '$scope', 'apiSrv', 'CONFIG', '$location', '$timeout', 'attemptFilterSrv',
    function ($scope, apiSrv, config, $location, $timeout, attemptFilterSrv) {
        if (!config.capabilities.view_attempts) {
            $location.path("/attempt");
        }
        $scope.messages = {};
        $scope.attempts = [];
        $scope.currentPage = 0;
        $scope.total = null;
        $scope.perPage = 10;
        $scope.timeoutPromise = null;
        $scope.filters = attemptFilterSrv.filters;
        $scope.prevFilterQ = attemptFilterSrv.filters.q;
        $scope.sortDefaults = {
            user: 'ASC',
            lastattempt: 'DESC',
            numattempts: 'DESC',
            completed: 'DESC'
        };

        $scope.getPageOfAttempts = function (page) {
            $timeout.cancel($scope.timeoutPromise);
            apiSrv.getAttempts(page, $scope.perPage, $scope.filters).then(function (data) {
                $scope.attempts = data.attempts;
                $scope.total = data.total;
            }, function (error) {
                $scope.messages.error = error.errorMessage;
            }).finally(function () {
                $scope.timeoutPromise = $timeout(function () {
                    $scope.getPageOfAttempts($scope.currentPage);
                }, 10000);
            });
        };

        $scope.filterQChanged = function () {
            $timeout.cancel($scope.timeoutPromise);
            $scope.timeoutPromise = $timeout(function () {
                if ($scope.prevFilterQ !== $scope.filters.q) {
                    $scope.prevFilterQ = $scope.filters.q;
                    $scope.filterChanged();
                } else {
                    $scope.timeoutPromise = $timeout(function () {
                        $scope.getPageOfAttempts($scope.currentPage);
                    }, 10000);
                }
            }, 1000);
        };

        $scope.sortAttempts = function (sort) {
            $timeout.cancel($scope.timeoutPromise);
            if ($scope.filters.sort === sort) {
                $scope.filters.direction = ($scope.filters.direction === 'ASC') ? 'DESC' : 'ASC';
            }
            else {
                $scope.filters.direction = $scope.sortDefaults[sort];
            }
            $scope.filters.sort = sort;
            $scope.getPageOfAttempts($scope.currentPage);
        };

        $scope.filterChanged = function () {
            $scope.currentPage = 0;
            $scope.getPageOfAttempts($scope.currentPage);
        };

        $scope.$on('$destroy', function () {
            $timeout.cancel($scope.timeoutPromise);
        });

        $scope.viewAttempts = function (userid) {
            $location.path("/attempts/" + userid);
        };

        $scope.getPageOfAttempts($scope.currentPage);
    }
]);

app.controller('feedbackModalCtrl', [
    '$scope', '$modalInstance', 'content', 'title',
    function ($scope, $modalInstance, content, title) {
        $scope.content = content;
        $scope.title = title;
        $scope.close = function () {
            $modalInstance.close();
        };
    }
]);

app.controller('submissionModalCtrl', [
    '$scope', '$modalInstance', 'sentence', 'callback',
    function ($scope, $modalInstance, sentence, callback) {
        $scope.sentence = sentence;
        $scope.cancel = function () {
            $modalInstance.close();
        };
        $scope.submit = function () {
            $modalInstance.close();
            callback();
        };
    }
]);

app.controller('editorModalCtrl', [
    '$scope', '$modalInstance', 'id', 'content', 'title', 'save', 'resetMessages',
    function ($scope, $modalInstance, id, content, title, save, resetMessages) {
        $scope.id = id;
        $scope.content = content;
        $scope.title = title;
        $scope.save = save;
        $scope.resetMessages = resetMessages;
        $scope.oldContent = content.html;

        $scope.ok = function () {
            $modalInstance.close();
        };

        $scope.cancel = function () {
            $scope.resetMessages();
            $scope.content.html = $scope.oldContent;
            $modalInstance.dismiss('cancel');
        };
    }
]);
