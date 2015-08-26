'use strict';

import DroppedBlocks from './dropped-block';

var app = angular.module('dragdrop.directives', []);

app.directive('tinyMce', [
    'CONFIG', '$interval', '$sce',
    function (config, $interval, $sce) {
        return {
            restrict: 'A',
            replace: true,
            scope: {
                id: '@dialogId',
                content: '=content'
            },
            link: function (scope, element) {
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
                    setup: function (editor) {
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
    }
]);

app.directive('editorDialog', [
    'CONFIG', '$interval', '$modal',
    function (config, $interval, $modal) {
        return {
            restrict: 'A',
            replace: false,
            scope: {
                id: '@dialogId',
                content: '=content',
                title: '=title',
                resetMessages: '&resetMessages'
            },
            link: function (scope, element) {
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
                            id: function () {
                                return $scope.id;
                            },
                            content: function () {
                                return $scope.content;
                            },
                            title: function () {
                                return $scope.title;
                            },
                            save: function () {
                                return function () {
                                    $scope.save();
                                };
                            },
                            resetMessages: function () {
                                return function () {
                                    $scope.resetMessages();
                                };
                            }
                        }
                    });

                };
            }]
        };
    }
]);

app.directive('editMenu', [
    '$location', 'CONFIG',
    function ($location, config) {
        var titles = [
            'make_attempt',
            'previous_attempts'
        ];
        var routes = [
            '/attempt',
            '/previous'
        ];

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
    }
]);

app.directive('alerts', [
    'CONFIG',
    function (config) {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                messages: '='
            },
            templateUrl: config.partialsUrl + 'directives/alerts.twig',
            link: function (scope, element) {
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
    }
]);

app.directive('tagSelect', [
    'CONFIG',
    function (config) {
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
            link: function (scope, element) {
                element
                    .mousedown(function () {
                        scope.stopAutoRefresh();
                    }
                );
                scope.selectedTag = scope.tagId;
            },
            controller: ['$scope', function ($scope) {
                $scope.$watch('selectedTag', function () {
                    if ($scope.tagId === $scope.selectedTag) {
                        return;
                    }
                    $scope.saveTag({tag: $scope.selectedTag});
                    $scope.tagId = $scope.selectedTag;
                    $scope.startAutoRefresh();
                });
            }]
        };
    }]
);

app.directive('wordBlockListItem', [
    '$timeout', 'CONFIG',
    function ($timeout, config) {
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
            link: function (scope, element) {
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
    }
]);

app.directive('attemptListItem', [
    'CONFIG',
    function (config) {
        return {
            restrict: 'A',
            replace: false,
            scope: {
                attempt: '='
            },
            templateUrl: config.partialsUrl + 'directives/attemptListItem.twig'
        };
    }
]);

app.directive('sentenceListItem', [
    '$timeout', 'CONFIG',
    function ($timeout, config) {
        return {
            restrict: 'A',
            replace: false,
            scope: {
                sentence: '=',
                deleteSentence: '&'
            },
            templateUrl: config.partialsUrl + 'directives/sentenceListItem.twig',
            link: function (scope) {
                var words = [];
                scope.sentence.wordblocks.forEach(block => {
                    words.push(block.wordblock);
                });
                scope.sentence.string = words.join(" ");
            }
        };
    }
]);

app.directive('draggableWord', [
    'CONFIG',
    function (config) {
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
    }
]);

app.directive('dragdropKey', [
    'CONFIG',
    function (config) {
        return {
            restrict: 'E',
            scope: {
                tags: '='
            },
            templateUrl: config.partialsUrl + 'directives/dragdropKey.twig',
            controller: ['$scope', function ($scope) {
                $scope.groupedTags = [];
                $scope.groupTags = function () {
                    $scope.tags.forEach((tag, i) => {
                        var type = tag.type;
                        if (!(type in $scope.groupedTags)) {
                            $scope.groupedTags[type] = {heading: tag.typeName, tags: []};
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
    }
]);

app.directive('dropAreaDock', [
    'CONFIG',
    function (config) {
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
            link: function (scope, element) {
                element.bind('drop', function ($event, ui) {
                    scope.removeWordBlock({
                        id: angular.element(ui.draggable).attr('id')
                    });
                    scope.$apply();
                });
            },
            templateUrl: config.partialsUrl + 'directives/dropareaDock.twig'
        };
    }
]);

app.directive('dropArea', [
    'CONFIG', '$interval',
    function (config, $interval) {
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
                $scope.droppedBlocks = new DroppedBlocks();

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
                    angular.element('#droparea').height(
                        angular.element('#dock-area').height() * 2
                    );
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
                    var lineHeight = maxHeight + (2 * $scope.blockMargin);

                    // work out the number of lines
                    var lines = Math.floor(
                        angular.element('#droparea').height() /
                        lineHeight
                    );
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
                    $scope.placed.wordblocks.forEach(block => {
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
                    $scope.setLoading({loading: false});
                    $scope.placed.wordblocks = $scope.droppedBlocks.getSortedBlocks();
                };
            }],
            link: function (scope, element) {

                /**
                 * calculate the top offset to which the dropped block should be snapped
                 * relative to the drop area
                 * @param topAbsolute
                 * @returns {number}
                 */
                var calculateTopOffset = function (topAbsolute) {
                    var topRelative = topAbsolute - scope.canvas.top;

                    // the line number of the block
                    var lineNum = Math.ceil((topRelative + (scope.canvas.lineHeight / 2)) / scope.canvas.lineHeight);
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
                var calculateLeftOffset = function (leftAbsolute, width) {
                    var left = leftAbsolute - scope.canvas.left;

                    // left of block is outside the canvas
                    if (left < 0) {
                        left = 0;
                    }
                    // right of block is outside the canvas
                    else if ((left + width) > scope.canvas.width) {
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
    }
]);

app.directive('attemptReportListItem', [
    'CONFIG',
    function (config) {
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
    }
]);

app.directive('reportPagination', [
    'CONFIG',
    function (config) {
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
    }
]);

app.directive('commentListItem', [
    '$timeout', 'CONFIG',
    function ($timeout, config) {
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
                        $scope.editComment(
                            $scope.comment.comment
                        );
                    }
                    $scope.startAutoRefresh();

                };
            }],
            link: function (scope, element) {
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
    }
]);
