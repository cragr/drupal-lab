import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';

const def = {
  input: 'entry',
  context: 'this',
  treeshake: false,
}

const defOutput = {
  sourcemap: true,
  sourcemapExcludeSources: true,
};

export default [

  /* ES Modules */

  // once.
  /*
  [
    {
      ...def,
      output: { file: 'assets/vendor/once/once.js' },
      plugins: [
        virtual({ entry: 'import once from "once-dom";export default once;' }),
        resolve(),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/once/once.js',
      output: [
        {
          file: 'assets/vendor/once/once.min.js',
          sourcemap: true,
          name: 'once',
          format: 'iife',
        },
      ],
      plugins: [terser({
        format: { comments: false },
      })],
    }
  ],*/

  // PopperJS.
  [
    {
      ...def,
      output: { file: 'assets/vendor/popperjs/popper.js' },
      plugins: [
        virtual({ entry: 'import "@popperjs/core/dist/umd/popper";' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/popperjs/popper.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/popperjs/popper.min.js',
          format: 'iife',
          name: 'Popper',
        },
      ],
      plugins: [terser({ compress: { inline: false } })],
    }
  ],

  // SortableJS.
  [
    {
      ...def,
      output: { file: 'assets/vendor/sortable/Sortable.js' },
      plugins: [
        virtual({ entry: 'import Sortable from "sortablejs/modular/sortable.complete.esm"; export default Sortable;' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/sortable/Sortable.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/sortable/Sortable.min.js',
          format: 'iife',
          name: 'Sortable',
        },
      ],
      plugins: [terser()],
    }
  ],

  // js-cookie.
  [
    {
      ...def,
      output: { file: 'assets/vendor/js-cookie/js.cookie.js' },
      plugins: [
        virtual({ entry: 'import Cookies from "js-cookie"; export default Cookies;' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/js-cookie/js.cookie.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/js-cookie/js.cookie.min.js',
          format: 'iife',
          name: 'Cookies',
        },
      ],
      plugins: [terser()],
    }
  ],

  /* UMD source */

  // es6-promise.
  [
    {
      ...def,
      output: { file: 'assets/vendor/es6-promise/es6-promise.auto.js' },
      plugins: [
        virtual({ entry: 'import "es6-promise/dist/es6-promise.auto";' }),
        resolve(),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/es6-promise/es6-promise.auto.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/es6-promise/es6-promise.auto.min.js',
        },
      ],
      plugins: [terser({ compress: { inline: false } })],
    }
  ],
  // jquery-once.
  [
    {
      ...def,
      output: { file: 'assets/vendor/jquery-once/jquery.once.js' },
      plugins: [
        virtual({ entry: 'import "jquery-once";' }),
        resolve(),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/jquery-once/jquery.once.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/jquery-once/jquery.once.min.js',
        },
      ],
      plugins: [terser({ compress: { inline: false } })],
    }
  ],
  // jquery-form.
  [
    {
      ...def,
      output: { file: 'assets/vendor/jquery-form/jquery.form.js' },
      plugins: [
        virtual({ entry: 'import "jquery-form";' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/jquery-form/jquery.form.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/jquery-form/jquery.form.min.js',
        },
      ],
      plugins: [terser({ compress: { inline: false } })],
    }
  ],

  // underscore
  [
    {
      ...def,
      output: { file: 'assets/vendor/underscore/underscore.js' },
      plugins: [
        virtual({ entry: 'import "underscore/underscore";' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/underscore/underscore.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/underscore/underscore-min.js',
        },
      ],
      plugins: [terser()],
    }
  ],

  // Backbone
  [
    {
      ...def,
      output: { file: 'assets/vendor/backbone/backbone.js' },
      plugins: [
        virtual({ entry: 'import "backbone";' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/backbone/backbone.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/backbone/backbone-min.js',
        },
      ],
      plugins: [terser()],
    }
  ],

  // Picturefill.
  [
    {
      ...def,
      output: { file: 'assets/vendor/picturefill/picturefill.js' },
      plugins: [
        virtual({ entry: 'import "picturefill";' }),
        resolve(),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/picturefill/picturefill.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/picturefill/picturefill.min.js',
        },
      ],
      plugins: [terser()],
    },
  ],

  // jQuery
  [
    {
      ...def,
      output: { file: 'assets/vendor/jquery/jquery.js' },
      plugins: [
        virtual({ entry: 'import "jquery";' }),
        resolve()
      ],
    },
    {
      ...def,
      input: 'assets/vendor/jquery/jquery.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/jquery/jquery.min.js',
        },
      ],
      plugins: [
        terser({
          // Set compression options to be closer to jquery default output.
          compress: {
            evaluate: false,
            inline: false,
            passes: 2,
          },
        }),
      ],
    }
  ],

  /* Special cases */

  // normalize.css
  [
    {
      ...def,
      output: { file: '' },
      plugins: [
        virtual({ entry: '' }),
        resolve(),
        copy({
          targets: [
            {
              src: 'node_modules/normalize.css/normalize.css',
              dest: 'assets/vendor/normalize-css',
            }
          ],
        }),
      ],
    },
  ],

  // farbtastic.
  [
    {
      ...def,
      // The minified file is called farbtastic.js, change the name for the
      // unminified file.
      output: { file: 'assets/vendor/farbtastic/farbtastic.raw.js' },
      plugins: [
        virtual({ entry: 'import "farbtastic/farbtastic";' }),
        resolve(),
        copy({
          targets: [
            {
              src: ['marker.png', 'mask.png', 'wheel.png', 'farbtastic.css'].map(file => `node_modules/farbtastic/${file}`),
              dest: 'assets/vendor/farbtastic',
            }
          ],
        }),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/farbtastic/farbtastic.raw.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/farbtastic/farbtastic.js',
        },
      ],
      plugins: [terser({ compress: { inline: false } })],
    }
  ],

  // jquery joyride.
  [
    {
      ...def,
      // The minified file is called farbtastic.js, change the name for the
      // unminified file.
      output: { file: 'assets/vendor/jquery-joyride/jquery.joyride-2.1.raw.js' },
      plugins: [
        virtual({ entry: 'import "joyride/jquery.joyride-2.1";' }),
        resolve(),
      ],
    },
    {
      ...def,
      input: 'assets/vendor/jquery-joyride/jquery.joyride-2.1.raw.js',
      output: [
        {
          ...defOutput,
          file: 'assets/vendor/jquery-joyride/jquery.joyride-2.1.js',
        },
      ],
      plugins: [terser({
        compress: { inline: false },
        // Keep the library header documentation.
        format: { comments: /ZURB/ },
      })],
    }
  ],

].flat();
