'use strict';

var app = angular.module('dragdropApp', [
    'dragdrop.controllers',
    'dragdrop.directives',
    'dragdrop.services',
    'dragdrop.filters',
    'ngRoute',
    'ngDragDrop',
    'ui.bootstrap'
]);

app.constant('CONFIG', window.CONFIG);
delete window.CONFIG;

app.config([
    '$routeProvider', '$httpProvider', 'CONFIG',
    function ($routeProvider, $httpProvider, config) {
        $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        $routeProvider.
            when('/words/sentence', {
                templateUrl: config.partialsUrl + 'route/sentence.twig',
                controller: 'sentenceCtrl'
            }).
            when('/words/sentence/:id', {
                templateUrl: config.partialsUrl + 'route/sentence.twig',
                controller: 'sentenceCtrl'
            }).
            when('/words', {
                templateUrl: config.partialsUrl + 'route/wordBlocks.twig',
                controller: 'manageCtrl'
            }).
            when('/attempt', {
                templateUrl: config.partialsUrl + 'route/attempt.twig',
                controller: 'attemptCtrl'
            }).
            when('/previous', {
                templateUrl: config.partialsUrl + 'route/previousAttempts.twig',
                controller: 'previousAttemptsCtrl'
            }).
            when('/attempts', {
                templateUrl: config.partialsUrl + 'route/attemptsReport.twig',
                controller: 'attemptsReportCtrl'
            }).
            when('/attempts/:userid', {
                templateUrl: config.partialsUrl + 'route/previousAttempts.twig',
                controller: 'previousAttemptsCtrl'
            }).
            when('/settings', {
                templateUrl: config.partialsUrl + 'route/settings.twig',
                controller: 'settingsCtrl'
            }).
            otherwise({
                redirectTo: function () {
                    if (config.capabilities.manage_word_blocks) {
                        return "/words";
                    }
                    return "/attempt";
                }
            });
    }
]);
