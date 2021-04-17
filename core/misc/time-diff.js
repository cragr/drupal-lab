/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.timestampAsTimeDiff = {};
  Drupal.dateFormatter = {};
  Drupal.behaviors.timestampAsTimeDiff = {
    attach: function attach(context) {
      Drupal.timestampAsTimeDiff.allIntervals = Object.keys(Drupal.dateFormatter.intervals);
      var elements = once('time-diff', 'time.js-time-diff', context);
      $(elements).each(function (index, $timeElement) {
        Drupal.timestampAsTimeDiff.showTimeDiff($timeElement);
      });
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        var elements = once.remove('time-diff', 'time.js-time-diff', context);
        $(elements).each(function (index, $timeElement) {
          clearInterval($timeElement.timer);
        });
      }
    }
  };

  Drupal.timestampAsTimeDiff.showTimeDiff = function ($timeElement) {
    var timestamp = new Date($($timeElement).attr('datetime')).getTime();
    var timeDiffSettings = JSON.parse($($timeElement).attr('data-drupal-time-diff'));
    var now = Date.now();
    var options = {
      granularity: timeDiffSettings.granularity
    };
    var timeDiff;
    var format;

    if (timestamp > now) {
      timeDiff = Drupal.dateFormatter.formatDiff(now, timestamp, options);
      format = timeDiffSettings.format.future;
    } else {
      timeDiff = Drupal.dateFormatter.formatDiff(timestamp, now, options);
      format = timeDiffSettings.format.past;
    }

    $($timeElement).text(Drupal.t(format, {
      '@interval': timeDiff.formatted
    }));

    if (timeDiffSettings.refresh > 0) {
      var refreshInterval = Drupal.timestampAsTimeDiff.refreshInterval(timeDiff.value, timeDiffSettings.refresh, timeDiffSettings.granularity);
      $timeElement.timer = setTimeout(Drupal.timestampAsTimeDiff.showTimeDiff, refreshInterval * 1000, $timeElement);
    }
  };

  Drupal.timestampAsTimeDiff.refreshInterval = function (value, refresh, granularity) {
    var units = Object.keys(value);
    var unitsCount = units.length;
    var lastUnit = units.pop();

    if (lastUnit !== 'second') {
      if (unitsCount === granularity) {
        $.each(Drupal.dateFormatter.intervals, function (interval, duration) {
          if (interval === lastUnit) {
            refresh = refresh < duration ? duration : refresh;
            return false;
          }
        });
        return refresh;
      }

      var lastIntervalIndex = Drupal.timestampAsTimeDiff.allIntervals.indexOf(lastUnit);
      var nextInterval = Drupal.timestampAsTimeDiff.allIntervals[lastIntervalIndex + 1];
      refresh = Drupal.dateFormatter.intervals[nextInterval];
    }

    return refresh;
  };

  Drupal.dateFormatter.formatDiff = function (from, to, options) {
    options = options || {};
    options = $.extend({
      granularity: 2,
      strict: true
    }, options);

    if (options.strict && from > to) {
      return {
        formatted: Drupal.t('0 seconds'),
        value: {
          second: 0
        }
      };
    }

    var output = [];
    var value = {};
    var units;
    var _options = options,
        granularity = _options.granularity;
    var diff = Math.round(Math.abs(to - from) / 1000);
    $.each(Drupal.dateFormatter.intervals, function (interval, duration) {
      units = Math.floor(diff / duration);

      if (units > 0) {
        diff %= units * duration;

        switch (interval) {
          case 'year':
            output.push(Drupal.formatPlural(units, '1 year', '@count years'));
            break;

          case 'month':
            output.push(Drupal.formatPlural(units, '1 month', '@count months'));
            break;

          case 'week':
            output.push(Drupal.formatPlural(units, '1 week', '@count weeks'));
            break;

          case 'day':
            output.push(Drupal.formatPlural(units, '1 day', '@count days'));
            break;

          case 'hour':
            output.push(Drupal.formatPlural(units, '1 hour', '@count hours'));
            break;

          case 'minute':
            output.push(Drupal.formatPlural(units, '1 minute', '@count minutes'));
            break;

          default:
            output.push(Drupal.formatPlural(units, '1 second', '@count seconds'));
        }

        value[interval] = units;
        granularity -= 1;

        if (granularity <= 0) {
          return false;
        }
      } else if (output.length > 0) {
        return false;
      }
    });

    if (output.length === 0) {
      return {
        formatted: Drupal.t('0 seconds'),
        value: {
          second: 0
        }
      };
    }

    return {
      formatted: output.join(' '),
      value: value
    };
  };

  Drupal.dateFormatter.intervals = {
    year: 31536000,
    month: 2592000,
    week: 604800,
    day: 86400,
    hour: 3600,
    minute: 60,
    second: 1
  };
})(jQuery, Drupal);