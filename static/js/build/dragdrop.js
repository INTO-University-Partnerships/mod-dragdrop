(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var app = angular.module('dragdropApp', ['dragdrop.controllers', 'dragdrop.directives', 'dragdrop.services', 'dragdrop.filters', 'ngRoute', 'ngDragDrop', 'ui.bootstrap']);

app.constant('CONFIG', window.CONFIG);
delete window.CONFIG;

app.config(['$routeProvider', '$httpProvider', 'CONFIG', function ($routeProvider, $httpProvider, config) {
    $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    $routeProvider.when('/words/sentence', {
        templateUrl: config.partialsUrl + 'route/sentence.twig',
        controller: 'sentenceCtrl'
    }).when('/words/sentence/:id', {
        templateUrl: config.partialsUrl + 'route/sentence.twig',
        controller: 'sentenceCtrl'
    }).when('/words', {
        templateUrl: config.partialsUrl + 'route/wordBlocks.twig',
        controller: 'manageCtrl'
    }).when('/attempt', {
        templateUrl: config.partialsUrl + 'route/attempt.twig',
        controller: 'attemptCtrl'
    }).when('/previous', {
        templateUrl: config.partialsUrl + 'route/previousAttempts.twig',
        controller: 'previousAttemptsCtrl'
    }).when('/attempts', {
        templateUrl: config.partialsUrl + 'route/attemptsReport.twig',
        controller: 'attemptsReportCtrl'
    }).when('/attempts/:userid', {
        templateUrl: config.partialsUrl + 'route/previousAttempts.twig',
        controller: 'previousAttemptsCtrl'
    }).when('/settings', {
        templateUrl: config.partialsUrl + 'route/settings.twig',
        controller: 'settingsCtrl'
    }).otherwise({
        redirectTo: function redirectTo() {
            if (config.capabilities.manage_word_blocks) {
                return "/words";
            }
            return "/attempt";
        }
    });
}]);

},{}],2:[function(require,module,exports){
'use strict';

require('./angular-app');

require('./controllers');

require('./directives');

require('./services');

require('./filters');

},{"./angular-app":1,"./controllers":3,"./directives":4,"./filters":6,"./services":7}],3:[function(require,module,exports){
'use strict';

var app = angular.module('dragdrop.controllers', []);

app.controller('manageCtrl', ['$scope', 'CONFIG', '$location', function ($scope, config, $location) {
    $scope.messages = {};
    if (!config.capabilities.manage_word_blocks) {
        $location.path("/attempt");
    }
}]);

app.controller('wordBlocksCtrl', ['$scope', 'apiSrv', '$timeout', '$window', 'CONFIG', 'tagSrv', function ($scope, apiSrv, $timeout, $window, config, tagSrv) {
    $scope.blocks = [];
    $scope.newBlock = "";
    $scope.timeoutPromise = null;
    $scope.tags = [];

    $scope.getBlocks = function () {
        apiSrv.getAll('word_block').then(function (data) {
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
        tagSrv.get().then(function (data) {
            $scope.tags = data;
        }, function (error) {
            $scope.messages.error = error.errorMessage;
        });
    };

    $scope.addNewBlockDisabled = function () {
        return $scope.newBlock.length === 0;
    };

    $scope.addNewBlock = function () {
        apiSrv.post('word_block', { wordblock: $scope.newBlock }).then(function (data) {
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
        apiSrv.put('word_block', block.id, { tagid: block.tagid, wordblock: block.wordblock }).then(function (data) {
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
            apiSrv.delete('word_block', id).then(function () {
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
}]);

app.controller('sentenceListCtrl', ['$scope', 'apiSrv', '$timeout', '$window', 'CONFIG', function ($scope, apiSrv, $timeout, $window, config) {
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
            apiSrv.delete('sentence', wordid).then(function () {
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
}]);

app.controller('sentenceCtrl', ['$scope', 'apiSrv', '$routeParams', '$location', 'CONFIG', '$route', function ($scope, apiSrv, $routeParams, $location, config, $route) {
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
            } else {
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
        $scope.placed.wordblocks.forEach(function (block, i) {
            return block.position = parseInt(i) + 1;
        });
        if ($scope.id) {
            $scope.putSentence({ wordblocks: $scope.placed.wordblocks });
        } else {
            $scope.postSentence({ wordblocks: $scope.placed.wordblocks });
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
}]);

app.controller('attemptCtrl', ['$scope', 'apiSrv', 'CONFIG', '$q', '$sce', '$modal', 'tagSrv', function ($scope, apiSrv, config, $q, $sce, $modal, tagSrv) {
    $scope.blocks = [];
    $scope.placed = { wordblocks: [] };
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
            } else {
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
        tagSrv.get().then(function (data) {
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
        data.feedback.forEach(function (feedback) {
            var attempt = feedback.attempt;
            $scope.settings.feedback[attempt] = $sce.trustAsHtml(feedback.feedback);
        });
        $scope.settings.num_attempts = parseInt(data.dragdrop.num_attempts);
        $scope.settings.display_labels = Boolean(parseInt(data.dragdrop.display_labels));
    };

    $scope.getUserAttempts = function () {
        var deferred = $q.defer();
        apiSrv.getAll('user_attempt', { userid: $scope.user.id }).then(function (data) {
            $scope.prepareUserAttempts(data);
            deferred.resolve();
        }, function (error) {
            $scope.messages.error = error.errorMessage;
            deferred.reject();
        });
        return deferred.promise;
    };

    $scope.prepareUserAttempts = function (data) {
        var completed = data.some(function (attempt) {
            return parseInt(attempt.correct) === 1 && parseInt(attempt.reset) === 0;
        });
        var numUserAttempts = data.filter(function (attempt) {
            return parseInt(attempt.reset) === 0;
        }).length;
        $scope.remaining_attempts = parseInt($scope.settings.num_attempts - numUserAttempts);

        if (completed) {
            $scope.messages.success = config.messages.activity_completed;
            $scope.remaining_attempts = 0;
            $scope.locked = true;
        } else if ($scope.remaining_attempts <= 0) {
            $scope.remaining_attempts = 0;
            $scope.messages.error = config.messages.num_attempts_reached;
            $scope.locked = true;
        } else {
            $scope.locked = false;
        }
    };

    $scope.submitAttempt = function () {
        var doSubmission = function doSubmission() {
            $scope.placed.wordblocks.forEach(function (block, i) {
                return block.position = parseInt(i) + 1;
            });
            var data = { wordblocks: $scope.placed.wordblocks };
            var params = { userid: $scope.user.id };
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
        } else {
            $scope.feedbackDialog($scope.settings.feedback[attempt], config.messages.incorrect_attempt_title);
        }
        $scope.getUserAttempts();
    };

    $scope.feedbackDialog = function (_content, _title) {
        $modal.open({
            templateUrl: config.partialsUrl + 'dialogs/feedbackDialog.twig',
            controller: 'feedbackModalCtrl',
            resolve: {
                content: function content() {
                    return _content;
                },
                title: function title() {
                    return _title;
                }
            }
        });
    };

    $scope.hint = function () {
        $scope.feedbackDialog($scope.settings.hint, config.messages.hint_dialog_title);
    };

    $scope.submitDialog = function (wordblocks, _callback) {
        $modal.open({
            templateUrl: config.partialsUrl + 'dialogs/attemptDialog.twig',
            controller: 'submissionModalCtrl',
            resolve: {
                sentence: function sentence() {
                    var words = [];
                    var ids = $scope.blocks.map(function (e) {
                        return e.id;
                    });
                    wordblocks.forEach(function (block) {
                        var id = ids.indexOf(block.wordblockid);
                        words.push($scope.blocks[id].wordblock);
                    });
                    return words.join(" ");
                },
                callback: function callback() {
                    return _callback;
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
}]);

app.controller('previousAttemptsCtrl', ['$scope', 'apiSrv', 'CONFIG', '$sce', '$q', '$routeParams', '$timeout', '$window', function ($scope, apiSrv, config, $sce, $q, $routeParams, $timeout, $window) {
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
        apiSrv.getAll('user_attempt', { userid: $scope.user.id }).then(function (data) {
            data.sort($scope.sortByCreated);
            data.forEach(function (attempt) {
                return attempt.correct = parseInt(attempt.correct) === 1;
            });
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
            apiSrv.resetAttempts({ userid: $scope.user.id }).then(function (data) {
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
            } else {
                $scope.attempts.previous.push(data[i]);
            }
        }
    };

    $scope.calculateRemainingAttempts = function () {
        var numUserAttempts = $scope.attempts.previous.filter(function (block) {
            return parseInt(block.reset) === 0;
        }).length;
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
        $scope.settings.feedback.forEach(function (attempt) {
            feedback[attempt.attempt] = attempt.feedback;
        });
        $scope.setFeedbackOnAttempts($scope.attempts.previous, feedback);
        $scope.setFeedbackOnAttempts($scope.attempts.reset, feedback);
    };

    $scope.setFeedbackOnAttempts = function (attempts, feedback) {
        attempts.forEach(function (attempt) {
            var strFeedback = "";
            if (parseInt(attempt.correct) === 1) {
                $scope.remaining_attempts = 0;
                strFeedback = $sce.trustAsHtml($scope.settings.dragdrop.feedback_correct);
            } else if (attempt.attempt in feedback) {
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
}]);

app.controller('commentsCtrl', ['$scope', 'apiSrv', 'CONFIG', '$timeout', '$window', function ($scope, apiSrv, config, $timeout, $window) {
    $scope.comments = [];
    $scope.manageComments = config.capabilities.manage_comments;
    $scope.timeoutPromise = null;
    $scope.comment = "";

    $scope.getComments = function () {
        $timeout.cancel($scope.timeoutPromise);
        apiSrv.getUserComments($scope.user.id).then(function (data) {
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
            apiSrv.delete('comment', id, { userid: $scope.userid }).then(function () {
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
        apiSrv.put('comment', comment.id, { comment: comment.comment, userid: $scope.user.id }).then(function (data) {
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
}]);

app.controller('settingsCtrl', ['$scope', 'apiSrv', 'CONFIG', '$sce', '$location', function ($scope, apiSrv, config, $sce, $location) {
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
            data.feedback.forEach(function (feedback) {
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
        apiSrv.put('settings', 0, data).then(function (response) {
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
                $scope.form.feedback[x] = { raw: "", html: "" };
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
}]);

app.controller('attemptsReportCtrl', ['$scope', 'apiSrv', 'CONFIG', '$location', '$timeout', 'attemptFilterSrv', function ($scope, apiSrv, config, $location, $timeout, attemptFilterSrv) {
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
            $scope.filters.direction = $scope.filters.direction === 'ASC' ? 'DESC' : 'ASC';
        } else {
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
}]);

app.controller('feedbackModalCtrl', ['$scope', '$modalInstance', 'content', 'title', function ($scope, $modalInstance, content, title) {
    $scope.content = content;
    $scope.title = title;
    $scope.close = function () {
        $modalInstance.close();
    };
}]);

app.controller('submissionModalCtrl', ['$scope', '$modalInstance', 'sentence', 'callback', function ($scope, $modalInstance, sentence, callback) {
    $scope.sentence = sentence;
    $scope.cancel = function () {
        $modalInstance.close();
    };
    $scope.submit = function () {
        $modalInstance.close();
        callback();
    };
}]);

app.controller('editorModalCtrl', ['$scope', '$modalInstance', 'id', 'content', 'title', 'save', 'resetMessages', function ($scope, $modalInstance, id, content, title, save, resetMessages) {
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
}]);

},{}],4:[function(require,module,exports){
'use strict';

var _droppedBlock = require('./dropped-block');

var _droppedBlock2 = _interopRequireDefault(_droppedBlock);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var app = angular.module('dragdrop.directives', []);

app.directive('tinyMce', ['CONFIG', '$interval', '$sce', function (config, $interval, $sce) {
    return {
        restrict: 'A',
        replace: true,
        scope: {
            id: '@dialogId',
            content: '=content'
        },
        link: function link(scope, element) {
            element.attr('id', scope.id);
        },
        controller: ['$scope', function ($scope) {
            $scope.intervalPromise = null;
            $scope.mconfig = {
                mode: "exact",
                relative_urls: false,
                language: "en",
                content_css: config.editorCSS,
                directionality: "ltr",
                theme: "advanced",
                skin: "moodle",
                apply_source_formatting: true,
                plugins: "lists,table,style,directionality",
                gecko_spellcheck: true,
                elements: $scope.id,
                setup: function setup(editor) {
                    editor.onInit.add(function (ed) {
                        ed.selection.setContent($scope.content.html);
                    });
                    editor.onChange.add(function (ed, l) {
                        $scope.content.html = $sce.trustAsHtml(l.content);
                    });
                }
            };

            $scope.initialise = function () {
                if (jQuery('#' + $scope.id).length === 0) {
                    return;
                }
                tinyMCE.init($scope.mconfig);
                $interval.cancel($scope.intervalPromise);
            };

            $scope.intervalPromise = $interval(function () {
                $scope.initialise();
            }, 100);

            $scope.initialise();
        }]

    };
}]);

app.directive('editorDialog', ['CONFIG', '$interval', '$modal', function (config, $interval, $modal) {
    return {
        restrict: 'A',
        replace: false,
        scope: {
            id: '@dialogId',
            content: '=content',
            title: '=title',
            resetMessages: '&resetMessages'
        },
        link: function link(scope, element) {
            var textarea = element.parent().parent().find('.editor-html');
            textarea.bind('click', function () {
                scope.open();
            });
            element.bind('click', function () {
                scope.open();
            });
        },
        controller: ['$scope', function ($scope) {
            $scope.open = function (size) {
                $modal.open({
                    templateUrl: config.partialsUrl + 'dialogs/settingsDialog.twig',
                    controller: 'editorModalCtrl',
                    size: size,
                    resolve: {
                        id: function id() {
                            return $scope.id;
                        },
                        content: function content() {
                            return $scope.content;
                        },
                        title: function title() {
                            return $scope.title;
                        },
                        save: function save() {
                            return function () {
                                $scope.save();
                            };
                        },
                        resetMessages: function resetMessages() {
                            return function () {
                                $scope.resetMessages();
                            };
                        }
                    }
                });
            };
        }]
    };
}]);

app.directive('editMenu', ['$location', 'CONFIG', function ($location, config) {
    var titles = ['make_attempt', 'previous_attempts'];
    var routes = ['/attempt', '/previous'];

    if (config.capabilities.manage_word_blocks) {
        routes.push('/words');
        titles.push('edit_word_blocks');
    }
    if (config.capabilities.manage_settings) {
        routes.push('/settings');
        titles.push('edit_attempt_settings');
    }
    if (config.capabilities.view_attempts) {
        routes.push('/attempts');
        titles.push('report_attempts');
    }
    return {
        restrict: 'E',
        replace: true,
        scope: {},
        templateUrl: config.partialsUrl + 'directives/editMenu.twig',
        controller: ['$scope', function ($scope) {
            $scope.canFeedback = config.canFeedback;
            var i, count;
            $scope.menu = [];
            for (i = 0, count = routes.length; i < count; ++i) {
                var segments = $location.path().split("/");
                var active = false;
                if (routes[i] === "/" + segments[1]) {
                    active = true;
                }
                $scope.menu.push({
                    route: config.menuUrl + '#' + routes[i],
                    title: config.messages['menu_' + titles[i]],
                    active: active
                });
            }
        }]
    };
}]);

app.directive('alerts', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        replace: true,
        scope: {
            messages: '='
        },
        templateUrl: config.partialsUrl + 'directives/alerts.twig',
        link: function link(scope, element) {
            element.find('button').bind('click', function () {
                scope.$apply(function () {
                    scope.success = scope.error = scope.warning = false;
                    scope.msg = '';
                });
            });
        },
        controller: ['$scope', function ($scope) {
            $scope.success = $scope.error = $scope.warning = false;
            $scope.$watch('messages.success', function () {
                if (typeof $scope.messages.success === "string") {
                    $scope.msg = $scope.messages.success;
                    $scope.success = true;
                    $scope.error = $scope.warning = false;
                    $scope.messages = {};
                }
            }, true);
            $scope.$watch('messages.error', function () {
                if (typeof $scope.messages.error === "string") {
                    $scope.msg = $scope.messages.error;
                    $scope.error = true;
                    $scope.success = $scope.warning = false;
                    $scope.messages = {};
                }
            }, true);
            $scope.$watch('messages.warning', function () {
                if (typeof $scope.messages.warning === "string") {
                    $scope.msg = $scope.messages.warning;
                    $scope.warning = true;
                    $scope.success = $scope.error = false;
                    $scope.messages = {};
                }
            }, true);
        }]
    };
}]);

app.directive('tagSelect', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        replace: false,
        scope: {
            tags: '=',
            tagId: '=',
            saveTag: '&',
            stopAutoRefresh: '&',
            startAutoRefresh: '&'
        },
        templateUrl: config.partialsUrl + 'directives/tagSelect.twig',
        link: function link(scope, element) {
            element.mousedown(function () {
                scope.stopAutoRefresh();
            });
            scope.selectedTag = scope.tagId;
        },
        controller: ['$scope', function ($scope) {
            $scope.$watch('selectedTag', function () {
                if ($scope.tagId === $scope.selectedTag) {
                    return;
                }
                $scope.saveTag({ tag: $scope.selectedTag });
                $scope.tagId = $scope.selectedTag;
                $scope.startAutoRefresh();
            });
        }]
    };
}]);

app.directive('wordBlockListItem', ['$timeout', 'CONFIG', function ($timeout, config) {
    return {
        restrict: 'A',
        replace: false,
        scope: {
            block: '=',
            tags: '=',
            editBlock: '&',
            deleteBlock: '&',
            stopAutoRefresh: '&',
            startAutoRefresh: '&'
        },
        templateUrl: config.partialsUrl + 'directives/wordBlockListItem.twig',
        controller: ['$scope', function ($scope) {
            $scope.editing = false;

            $scope.enableEditing = function () {
                $scope.editing = true;
                $scope.stopAutoRefresh();
                $scope.oldblock = $scope.block.wordblock;
                $timeout(function () {
                    $scope.elem.focus();
                }, 1);
            };

            $scope.disableEditing = function () {
                $scope.editing = false;
                if ($scope.oldblock !== $scope.block.wordblock) {
                    $scope.editBlock({
                        wordblock: $scope.block.wordblock
                    });
                }
                $scope.startAutoRefresh();
            };

            $scope.saveTag = function (tagId) {
                $scope.block.tagid = tagId;
                $scope.editBlock({
                    wordblock: $scope.block.wordblock
                });
            };
        }],
        link: function link(scope, element) {
            scope.elem = element.find('input')[0];
            element.bind('keyup', function (event) {
                if (!scope.editing || !(event.which === 13 || event.which === 27)) {
                    return;
                }
                scope.$apply(function () {
                    scope.editing = false;
                    if (event.which === 13) {
                        scope.disableEditing();
                    } else if (event.which === 27) {
                        scope.block.wordblock = scope.oldblock;
                        scope.startAutoRefresh();
                    }
                });
            });
        }
    };
}]);

app.directive('attemptListItem', ['CONFIG', function (config) {
    return {
        restrict: 'A',
        replace: false,
        scope: {
            attempt: '='
        },
        templateUrl: config.partialsUrl + 'directives/attemptListItem.twig'
    };
}]);

app.directive('sentenceListItem', ['$timeout', 'CONFIG', function ($timeout, config) {
    return {
        restrict: 'A',
        replace: false,
        scope: {
            sentence: '=',
            deleteSentence: '&'
        },
        templateUrl: config.partialsUrl + 'directives/sentenceListItem.twig',
        link: function link(scope) {
            var words = [];
            scope.sentence.wordblocks.forEach(function (block) {
                words.push(block.wordblock);
            });
            scope.sentence.string = words.join(" ");
        }
    };
}]);

app.directive('draggableWord', ['CONFIG', function (config) {
    return {
        replace: true,
        restrict: 'E',
        scope: {
            block: '=',
            tag: '=',
            displayLabels: '='
        },
        templateUrl: config.partialsUrl + 'directives/draggableWord.twig'
    };
}]);

app.directive('dragdropKey', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        scope: {
            tags: '='
        },
        templateUrl: config.partialsUrl + 'directives/dragdropKey.twig',
        controller: ['$scope', function ($scope) {
            $scope.groupedTags = [];
            $scope.groupTags = function () {
                $scope.tags.forEach(function (tag, i) {
                    var type = tag.type;
                    if (!(type in $scope.groupedTags)) {
                        $scope.groupedTags[type] = { heading: tag.typeName, tags: [] };
                    }
                    $scope.groupedTags[type].tags.push(i);
                });
            };
            $scope.$watch('tags', function () {
                if (!$scope.tags) {
                    return;
                }
                $scope.groupTags();
            });
        }]
    };
}]);

app.directive('dropAreaDock', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        scope: {
            blocks: '=',
            loading: '=',
            displayLabels: '=',
            tags: '=',
            removeWordBlock: '&',
            rendered: '='
        },
        controller: ['$scope', function ($scope) {
            $scope.getLabelText = function (tagid) {
                for (var i in $scope.tags) {
                    if ($scope.tags[i].id === tagid) {
                        return $scope.tags[i].abbreviation;
                    }
                }
                return "";
            };
        }],
        link: function link(scope, element) {
            element.bind('drop', function ($event, ui) {
                scope.removeWordBlock({
                    id: angular.element(ui.draggable).attr('id')
                });
                scope.$apply();
            });
        },
        templateUrl: config.partialsUrl + 'directives/dropareaDock.twig'
    };
}]);

app.directive('dropArea', ['CONFIG', '$interval', function (config, $interval) {
    return {
        restrict: 'E',
        scope: {
            placed: '=',
            setLoading: '&',
            rendered: '='
        },
        controller: ['$scope', function ($scope) {

            /**
             * pixels
             * @type {number}
             */
            $scope.blockMargin = 5;

            /**
             * dropped blocks
             * @type {object}
             */
            $scope.droppedBlocks = new _droppedBlock2.default();

            /**
             * object containing properties about the drop area
             */
            $scope.canvas = null;

            /**
             * interval promises for initialising the page, and placing blocks that have been loaded
             * @type {{initialise: null, placeBlocks: null}}
             */
            $scope.intervalPromise = {
                initialise: null,
                placeBlocks: null
            };

            $scope.$on('wordRemoved', function (e, id) {
                $scope.droppedBlocks.blocks[id] = null;
                $scope.placed.wordblocks = $scope.droppedBlocks.getSortedBlocks();
            });

            $scope.$on('sentenceReset', function () {
                $scope.droppedBlocks.blocks = [];
                $scope.placed.wordblocks = $scope.droppedBlocks.getSortedBlocks();
            });

            $scope.$on('sentenceLoaded', function () {
                $scope.intervalPromise.placeBlocks = $interval(function () {
                    $scope.positionLoadedBlocks();
                }, 100);
            });

            $scope.initialiseCanvas = function () {
                if (Math.round(angular.element('#dock-area').innerHeight()) <= 2 * $scope.blockMargin) {
                    return;
                }
                $interval.cancel($scope.intervalPromise.initialise);
                angular.element('#droparea').height(angular.element('#dock-area').height() * 2);
                $scope.defineCanvas();
                $scope.droppedBlocks.setCanvas($scope.canvas);
            };

            $scope.intervalPromise.initialise = $interval(function () {
                $scope.initialiseCanvas();
            }, 100);

            /**
             * calculate properties of the drop area canvas
             */
            $scope.defineCanvas = function () {
                var maxHeight = 0;

                // find the dropped block with the greatest height
                angular.element('#dock-area').children().each(function () {
                    if (angular.element(this).height() > maxHeight) {
                        maxHeight = angular.element(this).outerHeight();
                    }
                });
                var lineHeight = maxHeight + 2 * $scope.blockMargin;

                // work out the number of lines
                var lines = Math.floor(angular.element('#droparea').height() / lineHeight);
                $scope.canvas = {
                    width: angular.element('#droparea').outerWidth(),
                    left: angular.element('#droparea').offset().left,
                    top: angular.element('#droparea').offset().top,
                    lineHeight: lineHeight,
                    lines: lines,
                    blockMargin: $scope.blockMargin
                };
            };

            /**
             * position any blocks that have been loaded in
             */
            $scope.positionLoadedBlocks = function () {

                if ($scope.canvas === null) {
                    return;
                }
                $scope.placed.wordblocks.forEach(function (block) {
                    var draggable = angular.element('#' + block.wordblockid);
                    var dropped = {
                        wordblockid: block.wordblockid,
                        height: draggable.height(),
                        width: draggable.outerWidth() + 2 * $scope.blockMargin,
                        top: parseInt(block.top),
                        left: parseInt(block.left)
                    };
                    $scope.droppedBlocks.addDroppedBlock(dropped, draggable);
                });
                $interval.cancel($scope.intervalPromise.placeBlocks);
                $scope.setLoading({ loading: false });
                $scope.placed.wordblocks = $scope.droppedBlocks.getSortedBlocks();
            };
        }],
        link: function link(scope, element) {

            /**
             * calculate the top offset to which the dropped block should be snapped
             * relative to the drop area
             * @param topAbsolute
             * @returns {number}
             */
            var calculateTopOffset = function calculateTopOffset(topAbsolute) {
                var topRelative = topAbsolute - scope.canvas.top;

                // the line number of the block
                var lineNum = Math.ceil((topRelative + scope.canvas.lineHeight / 2) / scope.canvas.lineHeight);
                if (lineNum <= 0) {
                    lineNum = 1;
                }
                if (lineNum > scope.canvas.lines) {
                    lineNum--;
                }
                return (lineNum - 1) * scope.canvas.lineHeight;
            };

            /**
             * calculate the left offset to which the dropped block should be snapped
             * the block with only be moved if any of it is outside of the drop area
             * @param leftAbsolute
             * @param width
             * @returns {number}
             */
            var calculateLeftOffset = function calculateLeftOffset(leftAbsolute, width) {
                var left = leftAbsolute - scope.canvas.left;

                // left of block is outside the canvas
                if (left < 0) {
                    left = 0;
                }
                // right of block is outside the canvas
                else if (left + width > scope.canvas.width) {
                        left = scope.canvas.width - width;
                    }
                return left;
            };

            element.bind('drop', function ($event, ui) {

                // canvas
                scope.defineCanvas();
                scope.droppedBlocks.setCanvas(scope.canvas);

                // draggable properties
                var draggable = angular.element(ui.draggable);
                var top = calculateTopOffset(draggable.offset().top);
                var left = calculateLeftOffset(draggable.offset().left, draggable.outerWidth());

                // add the dropped block, so that any collisions can be detected
                var dropped = {
                    wordblockid: draggable.attr('id'),
                    height: draggable.height(),
                    width: draggable.outerWidth() + 2 * scope.blockMargin,
                    top: top,
                    left: left
                };
                scope.droppedBlocks.addDroppedBlock(dropped, draggable);
                scope.placed.wordblocks = scope.droppedBlocks.getSortedBlocks();
                scope.$apply();
            });
        },
        templateUrl: config.partialsUrl + 'directives/droparea.twig'
    };
}]);

app.directive('attemptReportListItem', ['CONFIG', function (config) {
    return {
        restrict: 'A',
        replace: true,
        scope: {
            attempt: '=',
            messages: '=',
            viewAttempts: '&'
        },
        templateUrl: config.partialsUrl + 'directives/attemptReportListItem.twig',
        controller: ['$scope', function ($scope) {
            if (!$scope.attempt.numattempts) {
                $scope.attempt.numattempts = 0;
            }
            if (!$scope.attempt.lastattempt) {
                $scope.attempt.lastattempt_formatted = "-";
            }
        }]
    };
}]);

app.directive('reportPagination', ['CONFIG', function (config) {
    return {
        restrict: 'E',
        replace: false,
        scope: {
            perPage: '@',
            currentPage: '=',
            total: '=',
            fetchPage: '&'
        },
        templateUrl: config.partialsUrl + 'directives/pagination.twig',
        controller: ['$scope', function ($scope) {
            $scope.currentPage = 0;
            $scope.pageCount = 0;
            $scope.pages = [];
            $scope.calculatePageCount = function () {
                if ($scope.total === 0) {
                    $scope.pageCount = 1;
                } else {
                    $scope.pageCount = Math.ceil($scope.total / $scope.perPage);
                }
            };

            $scope.calculatePages = function () {
                var from, to, i;
                from = 1;
                to = $scope.pageCount;
                $scope.pages = [];
                for (i = from; i <= to; ++i) {
                    $scope.pages.push(i);
                }
            };

            $scope.$watch('currentPage', function (newVal, oldVal) {
                if (oldVal !== newVal) {
                    $scope.fetchPage($scope.currentPage);
                    $scope.calculatePages();
                }
            });

            $scope.$watch('total', function () {
                $scope.calculatePageCount();
                $scope.calculatePages();
            });

            $scope.prevPage = function () {
                if ($scope.currentPage > 0) {
                    --$scope.currentPage;
                }
            };

            $scope.prevPageDisabled = function () {
                var disabled = $scope.currentPage === 0 ? 'disabled' : '';
                return disabled;
            };

            $scope.nextPage = function () {
                if ($scope.currentPage < $scope.pageCount - 1) {
                    $scope.currentPage++;
                }
            };

            $scope.nextPageDisabled = function () {
                var disabled = $scope.currentPage === $scope.pageCount - 1 ? 'disabled' : '';
                return disabled;
            };

            $scope.pageDisabled = function (n) {
                var disabled = $scope.currentPage === n;
                return disabled;
            };

            $scope.gotoPage = function (n) {
                $scope.currentPage = n;
            };
        }]
    };
}]);

app.directive('commentListItem', ['$timeout', 'CONFIG', function ($timeout, config) {
    return {
        restrict: 'A',
        replace: false,
        scope: {
            comment: '=',
            tags: '=',
            editComment: '&',
            deleteComment: '&',
            stopAutoRefresh: '&',
            startAutoRefresh: '&',
            manage: '='
        },
        templateUrl: config.partialsUrl + 'directives/commentListItem.twig',
        controller: ['$scope', function ($scope) {
            $scope.editing = false;

            $scope.enableEditing = function () {
                $scope.editing = true;
                $scope.stopAutoRefresh();
                $scope.oldcomment = $scope.comment.comment;
                $timeout(function () {
                    $scope.elem.focus();
                }, 1);
            };

            $scope.disableEditing = function () {
                $scope.editing = false;
                if ($scope.oldcomment !== $scope.comment.comment) {
                    $scope.editComment($scope.comment.comment);
                }
                $scope.startAutoRefresh();
            };
        }],
        link: function link(scope, element) {
            scope.elem = element.find('textarea');
            element.bind('keyup', function (event) {
                if (!scope.editing || !(event.which === 27)) {
                    return;
                }
                scope.$apply(function () {
                    scope.editing = false;
                    if (event.which === 27) {
                        scope.comment.comment = scope.comment;
                        scope.startAutoRefresh();
                    }
                });
            });
        }
    };
}]);

},{"./dropped-block":5}],5:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
    value: true
});

exports.default = function () {

    /**
     * properties of the canvas
     * @type {{object}}
     */
    this.canvas = {};

    /**
     * blocks that have been dropped on to the area
     * @type {Array}
     */
    this.blocks = [];

    /**
     * blocks to place
     * @type {Array}
     */
    this.blocksToPlace = [];

    /**
     * Jquery elements corresponding to the blocks
     * @type {Array}
     */
    this.elements = [];

    /**
     * add a dropped block and position it
     * @param dropped
     * @param element
     */
    this.addDroppedBlock = function (dropped, element) {
        this.blocks[dropped.wordblockid] = dropped;
        this.elements[dropped.wordblockid] = element;
        this.positionBlock(dropped.wordblockid);
        return dropped;
    };

    /**
     * set the canvas object
     * @param canvas
     */
    this.setCanvas = function (canvas) {
        this.canvas = canvas;
    };

    /**
     * utility method
     * @param arrCloned
     * @returns {Array}
     */
    this.cloneArray = function (arrToClone) {
        var clone = [];
        for (var i in arrToClone) {
            if (arrToClone[i]) {
                clone[i] = jQuery.extend({}, arrToClone[i]);
            }
        }
        return clone;
    };

    /**
     * get all blocks ids with which a given block has collided
     * @param block
     * @returns {Array}
     */
    this.getCollisions = function (block) {

        // clone and sort
        var blocks = this.cloneArray(this.blocksToPlace);
        blocks.sort(this.sortBlocksByPosition);

        var collisions = [];
        blocks.forEach(function (compBlock) {
            if (compBlock.wordblockid === block.wordblockid) {
                return;
            }
            if (compBlock.top !== block.top) {
                return;
            }
            if (compBlock.left + compBlock.width > block.left && block.left + block.width > compBlock.left) {
                collisions.push(compBlock.wordblockid);
            }
        });
        return collisions;
    };

    /**
     * get blocks in order
     * @returns {Array}
     */
    this.getSortedBlocks = function () {

        // clone and sort
        var blocks = this.blocks.filter(function (element) {
            return element !== null;
        });
        blocks.sort(this.sortBlocksByPosition);
        return blocks;
    };

    /**
     * sorts blocks top to bottom / left to right
     * @param a
     * @param b
     * @returns {number}
     */
    this.sortBlocksByPosition = function (a, b) {
        if (a.top < b.top) {
            return -1;
        }
        if (a.top > b.top) {
            return 1;
        }
        if (a.left > b.left) {
            return 1;
        }
        if (a.left < b.left) {
            return -1;
        }
        return 0;
    };

    /**
     * position an element on the page
     * @param block
     */
    this.positionElement = function (block) {
        var element = this.elements[block.wordblockid];
        element.offset({
            top: block.top + this.canvas.top,
            left: block.left + this.canvas.left
        });
    };

    /**
     * positions elements on the page
     */
    this.positionElements = function () {
        for (var i in this.blocksToPlace) {
            if (this.blocksToPlace[i] === null) {
                continue;
            }
            this.positionElement(this.blocksToPlace[i]);
        }
    };

    /**
     * work out where the block should be positioned and resolve any collisions
     * @param id
     */
    this.positionBlock = function (id) {

        // take a clone of the blocks in case we need to cancel
        this.blocksToPlace = this.cloneArray(this.blocks);
        var block = this.blocksToPlace[id];
        var collisions = this.getCollisions(block);
        // nothing to do
        if (collisions.length === 0) {
            this.blocksToPlace = [];
            this.positionElement(block);
            return;
        }
        // resolve against first collision (the first one from the left)
        var collidedBlock = this.blocksToPlace[collisions[0]];
        if (this.intersectsOnTheRight(block, collidedBlock)) {
            this.resolveCollisionRight(block, collidedBlock);
        } else {
            this.resolveCollisionLeft(block, collidedBlock);
        }

        // position the elements
        this.positionElements();
        this.blocks = this.cloneArray(this.blocksToPlace);
        this.blocksToPlace = [];
    };

    /**
     * resolve the collision to the right and cascade
     * @param block
     * @param collidedBlock
     */
    this.resolveCollisionRight = function (block, collidedBlock) {
        var spaceOnLineRight = this.isSpaceOnLineRight(collidedBlock.left + collidedBlock.width, block.width);

        if (!spaceOnLineRight) {
            block.left = this.canvas.width + 2 * this.canvas.blockMargin - block.width;
            this.cascadeLeft(block);
        } else {
            this.moveRightOfPosition(block, collidedBlock.left + collidedBlock.width);
        }
        if (!this.cascadeRight(block)) {

            // this has failed due to lack of space. resolve to the left instead.
            this.blocksToPlace = this.cloneArray(this.blocks);
            block = this.blocksToPlace[block.wordblockid];
            this.moveLeftOfPosition(block, collidedBlock.left + collidedBlock.width);
            this.cascadeLeft(block);
        }
    };

    /**
     * resolve the collision to the left and cascade
     * @param block
     * @param collidedBlock
     */
    this.resolveCollisionLeft = function (block, collidedBlock) {
        var spaceOnLineLeft = this.isSpaceOnLineLeft(collidedBlock.left, block.width);

        if (!spaceOnLineLeft) {
            block.left = 0;
        } else {
            this.moveLeftOfPosition(block, collidedBlock.left);
        }
        if (!this.cascadeLeft(block)) {

            // this has failed due to lack of space. resolve to the right instead.
            this.blocksToPlace = this.cloneArray(this.blocks);
            block = this.blocksToPlace[block.wordblockid];
            this.cascadeRight(block);
        }
    };

    /**
     * recursive function for cascading and resolving collisions to the left
     * @param block
     * @returns {boolean}
     */
    this.cascadeLeft = function (block) {
        var _this = this;

        var collisions = this.getCollisions(block);
        if (collisions.length === 0) {
            return true;
        }
        var collidedWidth = 0;
        collisions.forEach(function (collision) {
            collidedWidth += _this.blocks[collision].width;
        });

        var line = block.top / this.canvas.lineHeight + 1;

        // first line and there is no more space
        if (line === 1 && !this.isSpaceOnLineLeft(block.left, collidedWidth)) {
            return false;
        }
        var left = block.left;
        collisions.forEach(function (collision) {
            var collidedBlock = _this.blocksToPlace[collision];
            _this.moveLeftOfPosition(collidedBlock, left);
            if (!_this.cascadeLeft(collidedBlock)) {
                return false;
            }
        });
        return true;
    };

    /**
     * recursive function for cascading and resolving collisions to the right
     * @param block
     * @returns {boolean}
     */

    this.cascadeRight = function (block) {
        var _this2 = this;

        var collisions = this.getCollisions(block);
        if (collisions.length === 0) {
            return true;
        }

        // reverse to read collisions right to left
        collisions.reverse();
        var collidedWidth = 0;
        collisions.forEach(function (collision) {
            collidedWidth += _this2.blocks[collision].width;
        });
        var line = block.top / this.canvas.lineHeight + 1;
        // last line and no more space
        if (line === this.canvas.lines && !this.isSpaceOnLineRight(block.left + block.width, collidedWidth)) {
            return false;
        }
        var left = block.left;
        collisions.forEach(function (collision) {
            var collidedBlock = _this2.blocksToPlace[collision];
            _this2.moveRightOfPosition(collidedBlock, block.width + left);
            if (!_this2.cascadeRight(collidedBlock)) {
                return false;
            }
        });
        return true;
    };

    /**
     * whether a block intersects a block with which it has collided on the right
     * @param block
     * @param collidedBlock
     * @returns {boolean}
     */
    this.intersectsOnTheRight = function (block, collidedBlock) {
        return collidedBlock.left + collidedBlock.width / 2 < block.left + block.width / 2;
    };

    /**
     * move a block to the right of the given position
     * or jump to a new line
     * @param block
     * @param position
     */
    this.moveRightOfPosition = function (block, position) {
        if (this.isSpaceOnLineRight(position, block.width)) {
            block.left = position;
        } else {
            block.left = 0;
            block.top += this.canvas.lineHeight;
        }
    };

    /**
     * move a block to the left of the given position
     * or jump to a previous line
     * @param block
     * @param position
     */
    this.moveLeftOfPosition = function (block, position) {
        if (this.isSpaceOnLineLeft(position, block.width)) {
            block.left = position - block.width;
        } else {
            block.left = this.canvas.width + 2 * this.canvas.blockMargin - block.width;
            block.top -= this.canvas.lineHeight;
        }
    };

    /**
     * whether there is space on the line, to the left
     * @param position
     * @param width
     * @returns {boolean}
     */
    this.isSpaceOnLineLeft = function (position, width) {
        return position >= width;
    };

    /**
     * whether there is space on the line, to the right
     * @param position
     * @param width
     * @returns {boolean}
     */
    this.isSpaceOnLineRight = function (position, width) {
        var availableSpace = this.canvas.width + 2 * this.canvas.blockMargin - position;
        return availableSpace >= width;
    };
};

},{}],6:[function(require,module,exports){
'use strict';

var app = angular.module('dragdrop.filters', []);

app.filter('range', function () {
    return function (input, start, end) {
        start = parseInt(start);
        end = parseInt(end) + 1;
        var direction = start <= end ? 1 : -1;
        while (start !== end) {
            input.push(start);
            start += direction;
        }
        return input;
    };
});

},{}],7:[function(require,module,exports){
'use strict';

var app = angular.module('dragdrop.services', []);

app.service('apiSrv', ['$http', '$q', 'CONFIG', function ($http, $q, config) {
    var url = config.apiUrl;

    this.getAll = function (entity, params) {
        var deferred = $q.defer();
        if (typeof params === 'undefined') {
            params = "";
        } else {
            params = "?" + jQuery.param(params);
        }
        $http.get(url + entity + "/" + params).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.get = function (entity, id) {
        var deferred = $q.defer();
        $http.get(url + entity + "/" + id).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.post = function (entity, data, params) {
        var deferred = $q.defer();
        var fullurl = url + entity + "/" + '?sesskey=' + config.sesskey;
        if (typeof params !== 'undefined') {
            fullurl += "&" + jQuery.param(params);
        }
        $http.post(fullurl, data).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.put = function (entity, id, data, params) {
        var deferred = $q.defer();
        var fullurl = url + entity + "/" + id + '?sesskey=' + config.sesskey;
        if (typeof params !== 'undefined') {
            fullurl += "&" + jQuery.param(params);
        }
        $http.put(fullurl, data).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.delete = function (entity, id, params) {
        var deferred = $q.defer();
        // workaround Moodle's JavaScript minifier breaking due to the 'delete' keyword
        var f = $http.delete;
        var fullurl = url + entity + "/" + id + '?sesskey=' + config.sesskey;
        if (typeof params !== 'undefined') {
            fullurl += "&" + jQuery.param(params);
        }
        f(fullurl).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.getAttempts = function (page, perPage, filters) {
        var deferred = $q.defer();
        var fullUrl = url + 'attempts/?limitfrom=' + page * perPage + '&limitnum=' + perPage + '&sort=' + encodeURIComponent(filters.sort) + '&direction=' + filters.direction + '&q=' + encodeURIComponent(filters.q);
        $http.get(fullUrl).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.getUser = function (userid) {
        var deferred = $q.defer();
        $http.get(url + 'user/' + userid).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.getUserComments = function (userid) {
        var deferred = $q.defer();
        $http.get(url + 'comment/?userid=' + userid).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };

    this.resetAttempts = function (data) {
        var deferred = $q.defer();
        $http.put(url + 'user_attempt/reset/', data).success(function (successData) {
            deferred.resolve(successData);
        }).error(function (errorData) {
            deferred.reject(errorData);
        });
        return deferred.promise;
    };
}]);

app.service('tagSrv', ['$http', '$q', 'CONFIG', function ($http, $q, config) {
    var url = config.tagUrl;

    this.get = function () {
        var deferred = $q.defer();
        $http.get(url).success(function (response) {
            deferred.resolve(response);
        }).error(function (errorResponse) {
            deferred.reject(errorResponse);
        });
        return deferred.promise;
    };
}]);

app.service('attemptFilterSrv', [function () {
    this.filters = {
        q: '',
        sort: 'user',
        direction: 'ASC'
    };
}]);

},{}]},{},[2]);
