/* cSpell:disable */
/**
 * The testScenarios object is for testing a wide range of jQuery UI position
 * configuration options. The object properties are:
 * {
 *   - How the `of:` option will be used. This option determines the element the
 *     positioned element will attach to. This can be a selector, window, a
 *     jQuery object, or a vanilla JS element.
 *     - `my`: Sets the 'my' option for position().
 *     - `at`: Sets the 'at' option for position().
 *     - `x`: The expected X position of the element being positioned.
 *     - `y`: The expected Y position of the element being positioned.
 * }
 * This covers every possible combination of `my:` and `at:` using fixed amounts
 * (left, right, center, top, bottom), with additional scenarios that include
 * offsets.
 */
/* cSpell:disable */
const testScenarios = {
  window: {
    centerbottomcenterbottom: {
      at: 'center bottom',
      my: 'center bottom',
      x: 38.5,
      y: 77,
    },
    centerbottomcentercenter: {
      at: 'center center',
      my: 'center bottom',
      x: 38.5,
      y: 77,
    },
    centerbottomcentertop: {
      at: 'center top',
      my: 'center bottom',
      x: 38.5,
      y: -76.984375,
    },
    centerbottomleftbottom: {
      at: 'left bottom',
      my: 'center bottom',
      x: -38.5,
      y: 77,
    },
    centerbottomleftcenter: {
      at: 'left center',
      my: 'center bottom',
      x: -38.5,
      y: 77,
    },
    centerbottomlefttop: {
      at: 'left top',
      my: 'center bottom',
      x: -38.5,
      y: -76.984375,
    },
    centerbottomrightbottom: {
      at: 'right bottom',
      my: 'center bottom',
      x: 38.5,
      y: 77,
    },
    centerbottomrightcenter: {
      at: 'right center',
      my: 'center bottom',
      x: 38.5,
      y: 77,
    },
    centerbottomrightminus80bottomminus40: {
      at: 'right-80 bottom-40',
      my: 'center bottom',
      x: 118.5,
      y: 117,
    },
    centerbottomrighttop: {
      at: 'right top',
      my: 'center bottom',
      x: 38.5,
      y: -76.984375,
    },
    centerminus40topplus40leftplus20ptop: {
      at: 'left+20 top',
      my: 'center-40 top+40',
      x: -58.5,
      y: 40,
    },
    centerplus10perpbottomcenterminus10pertop: {
      at: 'center+110 top',
      my: 'center+150 bottom',
      x: -221.5,
      y: -76.984375,
    },
    centerplus20ptopplus20pcenterbottom: {
      at: 'center bottom',
      my: 'center+100 top-200',
      x: -61.5,
      y: 200,
    },
    centerplus40topminus15pcentercenterplus40: {
      at: 'center center+40',
      my: 'center+40 top+15',
      x: -1.5,
      y: -55,
    },
    centerplus80bottomminus90leftbottom: {
      at: 'left bottom',
      my: 'center+80 bottom-90',
      x: 41.5,
      y: 167,
    },
    centertopcenterbottom: {
      at: 'center bottom',
      my: 'center top',
      x: 38.5,
      y: 0,
    },
    centertopcentercenter: {
      at: 'center center',
      my: 'center top',
      x: 38.5,
      y: 0,
    },
    centertopcenterplus20ptopplus20p: {
      at: 'center+70 top+60',
      my: 'center top',
      x: -31.5,
      y: 60,
    },
    centertopcentertop: { at: 'center top', my: 'center top', x: 38.5, y: 0 },
    centertopleftbottom: {
      at: 'left bottom',
      my: 'center top',
      x: -38.5,
      y: 0,
    },
    centertopleftcenter: {
      at: 'left center',
      my: 'center top',
      x: -38.5,
      y: 0,
    },
    centertoplefttop: { at: 'left top', my: 'center top', x: -38.5, y: 0 },
    centertoprightbottom: {
      at: 'right bottom',
      my: 'center top',
      x: 38.5,
      y: 0,
    },
    centertoprightcenter: {
      at: 'right center',
      my: 'center top',
      x: 38.5,
      y: 0,
    },
    centertoprighttop: { at: 'right top', my: 'center top', x: 38.5, y: 0 },
    leftbottomcenterbottom: {
      at: 'center bottom',
      my: 'left bottom',
      x: 0,
      y: 77,
    },
    leftbottomcentercenter: {
      at: 'center center',
      my: 'left bottom',
      x: 0,
      y: 77,
    },
    leftbottomcentertop: {
      at: 'center top',
      my: 'left bottom',
      x: 0,
      y: -76.984375,
    },
    leftbottomleftbottom: { at: 'left bottom', my: 'left bottom', x: 0, y: 77 },
    leftbottomleftcenter: { at: 'left center', my: 'left bottom', x: 0, y: 77 },
    leftbottomlefttop: {
      at: 'left top',
      my: 'left bottom',
      x: 0,
      y: -76.984375,
    },
    leftbottomrightbottom: {
      at: 'right bottom',
      my: 'left bottom',
      x: 0,
      y: 77,
    },
    leftbottomrightcenter: {
      at: 'right center',
      my: 'left bottom',
      x: 0,
      y: 77,
    },
    leftbottomrighttop: {
      at: 'right top',
      my: 'left bottom',
      x: 0,
      y: -76.984375,
    },
    leftcentercenterbottom: {
      at: 'center bottom',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcentercentercenter: {
      at: 'center center',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcentercentertop: {
      at: 'center top',
      my: 'left center',
      x: 0,
      y: -38.484375,
    },
    leftcenterleftbottom: {
      at: 'left bottom',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcenterleftcenter: {
      at: 'left center',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcenterlefttop: {
      at: 'left top',
      my: 'left center',
      x: 0,
      y: -38.484375,
    },
    leftcenterrightbottom: {
      at: 'right bottom',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcenterrightcenter: {
      at: 'right center',
      my: 'left center',
      x: 0,
      y: 38.5,
    },
    leftcenterrighttop: {
      at: 'right top',
      my: 'left center',
      x: 0,
      y: -38.484375,
    },
    lefttopcenterbottom: { at: 'center bottom', my: 'left top', x: 0, y: 0 },
    lefttopcentercenter: { at: 'center center', my: 'left top', x: 0, y: 0 },
    lefttopcentertop: { at: 'center top', my: 'left top', x: 0, y: 0 },
    lefttopleftbottom: { at: 'left bottom', my: 'left top', x: 0, y: 0 },
    lefttopleftcenter: { at: 'left center', my: 'left top', x: 0, y: 0 },
    lefttoplefttop: { at: 'left top', my: 'left top', x: 0, y: 0 },
    lefttoprightbottom: { at: 'right bottom', my: 'left top', x: 0, y: 0 },
    lefttoprightcenter: { at: 'right center', my: 'left top', x: 0, y: 0 },
    lefttoprighttop: { at: 'right top', my: 'left top', x: 0, y: 0 },
    rightbottomcenterbottom: {
      at: 'center bottom',
      my: 'right bottom',
      x: 77,
      y: 77,
    },
    rightbottomcentercenter: {
      at: 'center center',
      my: 'right bottom',
      x: 77,
      y: 77,
    },
    rightbottomcentertop: {
      at: 'center top',
      my: 'right bottom',
      x: 77,
      y: -76.984375,
    },
    rightbottomleftbottom: {
      at: 'left bottom',
      my: 'right bottom',
      x: -77,
      y: 77,
    },
    rightbottomleftcenter: {
      at: 'left center',
      my: 'right bottom',
      x: -77,
      y: 77,
    },
    rightbottomlefttop: {
      at: 'left top',
      my: 'right bottom',
      x: -77,
      y: -76.984375,
    },
    rightbottomrightbottom: {
      at: 'right bottom',
      my: 'right bottom',
      x: 77,
      y: 77,
    },
    rightbottomrightcenter: {
      at: 'right center',
      my: 'right bottom',
      x: 77,
      y: 77,
    },
    rightbottomrighttop: {
      at: 'right top',
      my: 'right bottom',
      x: 77,
      y: -76.984375,
    },
    rightcentercenterbottom: {
      at: 'center bottom',
      my: 'right center',
      x: 77,
      y: 38.5,
    },
    rightcentercentercenter: {
      at: 'center center',
      my: 'right center',
      x: 77,
      y: 38.5,
    },
    rightcentercentertop: {
      at: 'center top',
      my: 'right center',
      x: 77,
      y: -38.484375,
    },
    rightcenterleftbottom: {
      at: 'left bottom',
      my: 'right center',
      x: -77,
      y: 38.5,
    },
    rightcenterleftcenter: {
      at: 'left center',
      my: 'right center',
      x: -77,
      y: 38.5,
    },
    rightcenterlefttop: {
      at: 'left top',
      my: 'right center',
      x: -77,
      y: -38.484375,
    },
    rightcenterrightbottom: {
      at: 'right bottom',
      my: 'right center',
      x: 77,
      y: 38.5,
    },
    rightcenterrightcenter: {
      at: 'right center',
      my: 'right center',
      x: 77,
      y: 38.5,
    },
    rightcenterrighttop: {
      at: 'right top',
      my: 'right center',
      x: 77,
      y: -38.484375,
    },
    righttopcenterbottom: { at: 'center bottom', my: 'right top', x: 77, y: 0 },
    righttopcentercenter: { at: 'center center', my: 'right top', x: 77, y: 0 },
    righttopcentertop: { at: 'center top', my: 'right top', x: 77, y: 0 },
    righttopleftbottom: { at: 'left bottom', my: 'right top', x: -77, y: 0 },
    righttopleftcenter: { at: 'left center', my: 'right top', x: -77, y: 0 },
    righttoplefttop: { at: 'left top', my: 'right top', x: -77, y: 0 },
    righttoprightbottom: { at: 'right bottom', my: 'right top', x: 77, y: 0 },
    righttoprightcenter: { at: 'right center', my: 'right top', x: 77, y: 0 },
    righttoprighttop: { at: 'right top', my: 'right top', x: 77, y: 0 },
  },
  selector: {
    centerbottomcenterbottom: {
      at: 'center bottom',
      my: 'center bottom',
      x: 62.5,
      y: 125,
    },
    centerbottomcentercenter: {
      at: 'center center',
      my: 'center bottom',
      x: 62.5,
      y: 24,
    },
    centerbottomcentertop: {
      at: 'center top',
      my: 'center bottom',
      x: 62.5,
      y: -77,
    },
    centerbottomleftbottom: {
      at: 'left bottom',
      my: 'center bottom',
      x: -38.5,
      y: 125,
    },
    centerbottomleftcenter: {
      at: 'left center',
      my: 'center bottom',
      x: -38.5,
      y: 24,
    },
    centerbottomlefttop: {
      at: 'left top',
      my: 'center bottom',
      x: -38.5,
      y: -77,
    },
    centerbottomrightbottom: {
      at: 'right bottom',
      my: 'center bottom',
      x: 163.5,
      y: 125,
    },
    centerbottomrightcenter: {
      at: 'right center',
      my: 'center bottom',
      x: 163.5,
      y: 24,
    },
    centerbottomrightplus40bottomminus40: {
      at: 'right+40 bottom-40',
      my: 'center bottom',
      x: 203.5,
      y: 85,
    },
    centerbottomrighttop: {
      at: 'right top',
      my: 'center bottom',
      x: 163.5,
      y: -77,
    },
    centerminus40topplus40leftminus20ptop: {
      at: 'left-20% top',
      my: 'center-40 top+40',
      x: -118.890625,
      y: 40,
    },
    centerplus10perpbottomcenterminus10pertop: {
      at: 'center-20% top',
      my: 'center+20% bottom',
      x: 37.5,
      y: -77,
    },
    centerplus40bottomminus40leftbottom: {
      at: 'left bottom',
      my: 'center+40 bottom-40',
      x: 1.5,
      y: 85,
    },
    centerplus40topminus15pcentercenterplus40: {
      at: 'center center+40',
      my: 'center+40 top-15%',
      x: 102.5,
      y: 129.4375,
    },
    centertopcenterbottom: {
      at: 'center bottom',
      my: 'center top',
      x: 62.5,
      y: 202,
    },
    centertopcentercenter: {
      at: 'center center',
      my: 'center top',
      x: 62.5,
      y: 101,
    },
    centertopcenterplus20ptopplus20p: {
      at: 'center+20% top+20%',
      my: 'center top',
      x: 102.890625,
      y: 40.390625,
    },
    centertopcentertop: { at: 'center top', my: 'center top', x: 62.5, y: 0 },
    centertopleftbottom: {
      at: 'left bottom',
      my: 'center top',
      x: -38.5,
      y: 202,
    },
    centertopleftcenter: {
      at: 'left center',
      my: 'center top',
      x: -38.5,
      y: 101,
    },
    centertoplefttop: { at: 'left top', my: 'center top', x: -38.5, y: 0 },
    centertoprightbottom: {
      at: 'right bottom',
      my: 'center top',
      x: 163.5,
      y: 202,
    },
    centertoprightcenter: {
      at: 'right center',
      my: 'center top',
      x: 163.5,
      y: 101,
    },
    centertoprighttop: { at: 'right top', my: 'center top', x: 163.5, y: 0 },
    leftbottomcenterbottom: {
      at: 'center bottom',
      my: 'left bottom',
      x: 101,
      y: 125,
    },
    leftbottomcentercenter: {
      at: 'center center',
      my: 'left bottom',
      x: 101,
      y: 24,
    },
    leftbottomcentertop: {
      at: 'center top',
      my: 'left bottom',
      x: 101,
      y: -77,
    },
    leftbottomleftbottom: {
      at: 'left bottom',
      my: 'left bottom',
      x: 0,
      y: 125,
    },
    leftbottomleftcenter: { at: 'left center', my: 'left bottom', x: 0, y: 24 },
    leftbottomlefttop: { at: 'left top', my: 'left bottom', x: 0, y: -77 },
    leftbottomrightbottom: {
      at: 'right bottom',
      my: 'left bottom',
      x: 202,
      y: 125,
    },
    leftbottomrightcenter: {
      at: 'right center',
      my: 'left bottom',
      x: 202,
      y: 24,
    },
    leftbottomrighttop: { at: 'right top', my: 'left bottom', x: 202, y: -77 },
    leftcentercenterbottom: {
      at: 'center bottom',
      my: 'left center',
      x: 101,
      y: 163.5,
    },
    leftcentercentercenter: {
      at: 'center center',
      my: 'left center',
      x: 101,
      y: 62.5,
    },
    leftcentercentertop: {
      at: 'center top',
      my: 'left center',
      x: 101,
      y: -38.5,
    },
    leftcenterleftbottom: {
      at: 'left bottom',
      my: 'left center',
      x: 0,
      y: 163.5,
    },
    leftcenterleftcenter: {
      at: 'left center',
      my: 'left center',
      x: 0,
      y: 62.5,
    },
    leftcenterlefttop: { at: 'left top', my: 'left center', x: 0, y: -38.5 },
    leftcenterrightbottom: {
      at: 'right bottom',
      my: 'left center',
      x: 202,
      y: 163.5,
    },
    leftcenterrightcenter: {
      at: 'right center',
      my: 'left center',
      x: 202,
      y: 62.5,
    },
    leftcenterrighttop: {
      at: 'right top',
      my: 'left center',
      x: 202,
      y: -38.5,
    },
    lefttopcenterbottom: {
      at: 'center bottom',
      my: 'left top',
      x: 101,
      y: 202,
    },
    lefttopcentercenter: {
      at: 'center center',
      my: 'left top',
      x: 101,
      y: 101,
    },
    lefttopcentertop: { at: 'center top', my: 'left top', x: 101, y: 0 },
    lefttopleftbottom: { at: 'left bottom', my: 'left top', x: 0, y: 202 },
    lefttopleftcenter: { at: 'left center', my: 'left top', x: 0, y: 101 },
    lefttoplefttop: { at: 'left top', my: 'left top', x: 0, y: 0 },
    lefttoprightbottom: { at: 'right bottom', my: 'left top', x: 202, y: 202 },
    lefttoprightcenter: { at: 'right center', my: 'left top', x: 202, y: 101 },
    lefttoprighttop: { at: 'right top', my: 'left top', x: 202, y: 0 },
    rightbottomcenterbottom: {
      at: 'center bottom',
      my: 'right bottom',
      x: 24,
      y: 125,
    },
    rightbottomcentercenter: {
      at: 'center center',
      my: 'right bottom',
      x: 24,
      y: 24,
    },
    rightbottomcentertop: {
      at: 'center top',
      my: 'right bottom',
      x: 24,
      y: -77,
    },
    rightbottomleftbottom: {
      at: 'left bottom',
      my: 'right bottom',
      x: -77,
      y: 125,
    },
    rightbottomleftcenter: {
      at: 'left center',
      my: 'right bottom',
      x: -77,
      y: 24,
    },
    rightbottomlefttop: { at: 'left top', my: 'right bottom', x: -77, y: -77 },
    rightbottomrightbottom: {
      at: 'right bottom',
      my: 'right bottom',
      x: 125,
      y: 125,
    },
    rightbottomrightcenter: {
      at: 'right center',
      my: 'right bottom',
      x: 125,
      y: 24,
    },
    rightbottomrighttop: {
      at: 'right top',
      my: 'right bottom',
      x: 125,
      y: -77,
    },
    rightcentercenterbottom: {
      at: 'center bottom',
      my: 'right center',
      x: 24,
      y: 163.5,
    },
    rightcentercentercenter: {
      at: 'center center',
      my: 'right center',
      x: 24,
      y: 62.5,
    },
    rightcentercentertop: {
      at: 'center top',
      my: 'right center',
      x: 24,
      y: -38.5,
    },
    rightcenterleftbottom: {
      at: 'left bottom',
      my: 'right center',
      x: -77,
      y: 163.5,
    },
    rightcenterleftcenter: {
      at: 'left center',
      my: 'right center',
      x: -77,
      y: 62.5,
    },
    rightcenterlefttop: {
      at: 'left top',
      my: 'right center',
      x: -77,
      y: -38.5,
    },
    rightcenterrightbottom: {
      at: 'right bottom',
      my: 'right center',
      x: 125,
      y: 163.5,
    },
    rightcenterrightcenter: {
      at: 'right center',
      my: 'right center',
      x: 125,
      y: 62.5,
    },
    rightcenterrighttop: {
      at: 'right top',
      my: 'right center',
      x: 125,
      y: -38.5,
    },
    righttopcenterbottom: {
      at: 'center bottom',
      my: 'right top',
      x: 24,
      y: 202,
    },
    righttopcentercenter: {
      at: 'center center',
      my: 'right top',
      x: 24,
      y: 101,
    },
    righttopcentertop: { at: 'center top', my: 'right top', x: 24, y: 0 },
    righttopleftbottom: { at: 'left bottom', my: 'right top', x: -77, y: 202 },
    righttopleftcenter: { at: 'left center', my: 'right top', x: -77, y: 101 },
    righttoplefttop: { at: 'left top', my: 'right top', x: -77, y: 0 },
    righttoprightbottom: {
      at: 'right bottom',
      my: 'right top',
      x: 125,
      y: 202,
    },
    righttoprightcenter: {
      at: 'right center',
      my: 'right top',
      x: 125,
      y: 101,
    },
    righttoprighttop: { at: 'right top', my: 'right top', x: 125, y: 0 },
  },
};
/* cSpell:enable */

// Testing `of:` using jQuery or vanilla JS elements can use the same test
// scenarios and expected values as those using a selector.
testScenarios.jQuery = testScenarios.selector;
testScenarios.element = testScenarios.selector;

module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'position Shim Test')
        .waitForElementVisible(
          'input[name="modules[position_shim_test][enable]"]',
          1000,
        )
        .click('input[name="modules[position_shim_test][enable]"]')
        .click('input[type="submit"]');
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'test position': (browser) => {
    browser
      .resizeWindow(1200, 600)
      .drupalRelativeURL('/position-shim-test')
      .waitForElementPresent('#position-reference-1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (testIterations, done) {
          const $ = jQuery;
          const toReturn = {};

          /**
           * Confirms a coordinate is acceptably close to the expected value.
           *
           * @param {number} actual
           *  The actual coordinate value.
           * @param {number} expected
           *  The expected coordinate value.
           * @return {boolean|boolean}
           *  True if the actual is within 3px of the expected.
           */
          const withinRange = (actual, expected) => {
            return actual <= expected + 3 && actual >= expected - 3;
          };

          /**
           * Parses a jQuery UI position config string for `at:` or `my:`.
           *
           * A position config string can contain both alignment and offset
           * configuration. This string is parsed and returned as an object that
           * separates horizontal and vertical alignment and their respective
           * offsets into distinct object properties.
           *
           * @param {string}offset
           *   Offset configuration in jQuery UI Position format.
           * @param {element} element
           *   The element being positioned.
           * @return {{horizontal: (*|string), verticalOffset: number, vertical: (*|string), horizontalOffset: number}}
           *   The horizontal and vertical alignment and offset values for the element.
           */
          const parseOffset = (offset, element) => {
            const rhorizontal = /left|center|right/;
            const rvertical = /top|center|bottom/;
            const roffset = /[+-]\d+(\.[\d]+)?%?/;
            const rposition = /^\w+/;
            const rpercent = /%$/;
            let positions = offset.split(' ');
            if (positions.length === 1) {
              if (rhorizontal.test(positions[0])) {
                positions.push('center');
              } else if (rvertical.test(positions[0])) {
                positions = ['center'].concat(positions);
              }
            }

            const horizontalOffset = roffset.exec(positions[0]);
            const verticalOffset = roffset.exec(positions[1]);
            positions = positions.map((pos) => rposition.exec(pos)[0]);

            return {
              horizontalOffset: horizontalOffset
                ? parseFloat(horizontalOffset[0]) *
                  (rpercent.test(horizontalOffset[0])
                    ? element.offsetWidth / 100
                    : 1)
                : 0,
              verticalOffset: verticalOffset
                ? parseFloat(verticalOffset[0]) *
                  (rpercent.test(verticalOffset[0])
                    ? element.offsetWidth / 100
                    : 1)
                : 0,
              horizontal: positions[0],
              vertical: positions[1],
            };
          };

          /**
           * Checks the position of an element.
           *
           * The position values of an element are based on their distance
           * relative to the element their being positioned against.
           *
           * @param {jQuery} tip
           *  The element being positioned.
           * @param {Object} options
           *  The position options.
           * @param {string} attachToKey
           *  The type of element being attached to.
           * @param {string} idKey
           *   The unique id of the element indicating the use case scenario.
           *
           * @return {Promise}
           *   Default resolve after all but the final iteration, which returns
           *   a Nightwatch test completion promise.
           */
          const checkPosition = (tip, options, attachToKey, idKey, key) =>
            new Promise((resolve) => {
              setTimeout(() => {
                const box = tip[0].getBoundingClientRect();
                let { x, y } = box;
                const originalX = x;
                // If the tip is attaching to the window, X and Y are measured
                // based on their distance from the closest window boundary.
                if (attachToKey === 'window') {
                  const atOffsets = parseOffset(options.at, tip[0]);
                  if (atOffsets.horizontal === 'center') {
                    x = Drupal.hasOwnProperty('PopperInstances')
                      ? $(window).outerWidth() / 2 - x
                      : document.documentElement.clientWidth / 2 - x;
                  } else if (atOffsets.horizontal === 'right') {
                    x = document.documentElement.clientWidth - x;
                  }
                  if (atOffsets.vertical === 'center') {
                    y = document.documentElement.clientHeight / 2 - y;
                  } else if (atOffsets.vertical === 'bottom') {
                    y = document.documentElement.clientHeight - y;
                  } else {
                    y += window.pageYOffset;
                  }
                } else {
                  // Measure the distance of the tip from the reference element.
                  const refRect = document
                    .querySelector('#position-reference-1')
                    .getBoundingClientRect();
                  const refX = refRect.x;
                  const refY = refRect.y;
                  x -= refX;
                  y -= refY;
                }
                if (!withinRange(x, options.x) || !withinRange(y, options.y)) {
                  toReturn[
                    idKey
                  ] = `${idKey} EXPECTED x:${options.x} y:${options.y} ACTUAL x:${x} y:${y}`;
                } else {
                  toReturn[idKey] = true;
                }

                // Remove the tip after checking position so it does not impact
                // the coordinates of tips added in the next iteration.
                tip.remove();

                // There are 313 scenarios. Complete the test after the final
                // scenario completes.
                if (Object.keys(toReturn).length === 313) {
                  done(toReturn);
                }
                resolve();
              }, 25);
            });

          const attachScenarios = {
            selector: '#position-reference-1',
            window,
            jQuery: $('#position-reference-1'),
            element: document.querySelector('#position-reference-1'),
          };

          // Loop through testScenarios and attachScenarios to get config for a
          // positioned tip.
          (async function iterate() {
            const attachToKeys = Object.keys(attachScenarios);
            for (let i = 0; i < attachToKeys.length; i++) {
              const attachToKey = attachToKeys[i];
              const scenarios = Object.keys(testIterations[attachToKey]);
              for (let j = 0; j < scenarios.length; j++) {
                const key = scenarios[j];
                const options = testIterations[attachToKey][key];
                options.of = attachScenarios[attachToKey];
                options.collision = 'none';
                const idKey = `${attachToKey}${key}`;

                // eslint-disable-next-line no-await-in-loop
                const tip = await new Promise((resolve) => {
                  const addedTip = $(
                    `<div class="test-tip"  style="position:${
                      attachToKey === 'window' ? 'fixed' : 'absolute'
                    }" id="${idKey}">${idKey}</div>`,
                  ).appendTo('main');
                  addedTip.position(options);
                  setTimeout(() => {
                    resolve(addedTip);
                  });
                });
                // eslint-disable-next-line no-await-in-loop
                await checkPosition(tip, options, attachToKey, idKey, key);
              }
            }
          })();
        },
        [testScenarios],
        (result) => {
          console.log(result);
          Object.keys(result.value).forEach((item) => {
            browser.assert.equal(
              result.value[item],
              true,
              `expected position: ${item}`,
            );
          });
        },
      );
  },
};
