'use strict';

var app = angular.module('dragdrop.filters', []);

app.filter('range', function() {
    return function(input, start, end) {
        start = parseInt(start);
        end = parseInt(end) + 1;
        var direction = (start <= end) ? 1 : -1;
        while (start !== end) {
            input.push(start);
            start += direction;
        }
        return input;
    };
});
