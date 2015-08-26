'use strict';

var app = angular.module('dragdrop.services', []);

app.service('apiSrv', [
    '$http', '$q', 'CONFIG',
    function ($http, $q, config) {
        var url = config.apiUrl;

        this.getAll = function (entity, params) {
            var deferred = $q.defer();
            if (typeof params === 'undefined') {
                params = "";
            }
            else {
                params = "?" + jQuery.param(params);
            }
            $http.get(url + entity + "/" + params).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };

        this.get = function (entity, id) {
            var deferred = $q.defer();
            $http.get(url + entity + "/" + id).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
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
            $http.post(fullurl, data).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
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
            $http.put(fullurl, data).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
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
            f(fullurl).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };

        this.getAttempts = function (page, perPage, filters) {
            var deferred = $q.defer();
            var fullUrl = url +
                'attempts/?limitfrom=' + (page * perPage) +
                '&limitnum=' + perPage +
                '&sort=' + encodeURIComponent(filters.sort) +
                '&direction=' + filters.direction +
                '&q=' + encodeURIComponent(filters.q);
            $http.get(fullUrl).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };

        this.getUser = function (userid) {
            var deferred = $q.defer();
            $http.get(url + 'user/' + userid).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };

        this.getUserComments = function (userid) {
            var deferred = $q.defer();
            $http.get(url + 'comment/?userid=' + userid).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };

        this.resetAttempts = function(data) {
            var deferred = $q.defer();
            $http.put(url + 'user_attempt/reset/', data).
                success(function (successData) {
                    deferred.resolve(successData);
                }).
                error(function (errorData) {
                    deferred.reject(errorData);
                });
            return deferred.promise;
        };
    }
]);

app.service('tagSrv', [
    '$http', '$q', 'CONFIG',
    function ($http, $q, config) {
        var url = config.tagUrl;

        this.get = function () {
            var deferred = $q.defer();
            $http.get(url).
                success(function (response) {
                    deferred.resolve(response);
                }).
                error(function (errorResponse) {
                    deferred.reject(errorResponse);
                });
            return deferred.promise;
        };
    }
]);

app.service('attemptFilterSrv', [
    function () {
        this.filters = {
            q: '',
            sort: 'user',
            direction: 'ASC'
        };
    }
]);

