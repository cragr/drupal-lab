import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import buble from '@rollup/plugin-buble';
//import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';

const def = {
  input: 'entry',
  context: 'this',
  treeshake: false,
}

export default [

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
          file: 'assets/vendor/sortable/Sortable.min.js',
          sourcemap: true,
          format: 'iife',
          name: 'Sortable',
        },
      ],
      plugins: [terser({
        //compress: { inline: false },
        format: { comments: false },
      })],
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
          file: 'assets/vendor/jquery-form/jquery.form.min.js',
          sourcemap: true,
        },
      ],
      plugins: [terser({
        compress: { inline: false },
        format: { comments: false },
      })],
    }
  ],

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
          file: 'assets/vendor/popperjs/popper.min.js',
          sourcemap: true,
          format: 'iife',
          name: 'Popper',
        },
      ],
      plugins: [terser({
        compress: { inline: false },
        format: { comments: false },
      })],
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
          file: 'assets/vendor/js-cookie/js.cookie.min.js',
          sourcemap: true,
          format: 'iife',
          name: 'Cookies',
        },
      ],
      plugins: [terser({ format: { comments: false } })],
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
          file: 'assets/vendor/underscore/underscore-min.js',
          sourcemap: true,
        },
      ],
      plugins: [terser({ format: { comments: false } })],
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
          file: 'assets/vendor/backbone/backbone-min.js',
          sourcemap: true,
        },
      ],
      plugins: [terser({ format: { comments: false } })],
    }
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
          file: 'assets/vendor/jquery/jquery.min.js',
          sourcemap: true,
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
          format: { comments: false },
        }),
      ],
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
          file: 'assets/vendor/picturefill/picturefill.min.js',
          sourcemap: true,
        },
      ],
      plugins: [terser({ format: { comments: false } })],
    },
  ],

].flat();


function addAsset(dependency, opts = {}, callback = false) {
  const moduleName = opts['name'] || dependency;
  const sourceFile = opts.unminified || `${dependency}.js`;
  const minifiedFile = opts.minified || sourceFile.replace(/js$/, 'min.js');
  const nameImport = opts['import'] || dependency;
  if (!dependency) { return [[], []]; }
  const virtualModule = [
    `import "${nameImport}";`,
    opts['export'] ? `export default ${opts.exportName || moduleName};` : '',
  ]
  const steps = [
    {
      input: 'entry',
      context: 'this',
      treeshake: false,
      output: {
        name: moduleName,
        file: `assets/vendor/${dependency}/${sourceFile}`,
        plugins: [
          buble({ transforms: { modules: false } }),
          terser({
            format: { comments: 'some' },
          }),
        ],
      },
      plugins: [
        virtual({ entry: virtualModule.join("\n") }),
        resolve(),
      ],
    },
    {
      input: `assets/vendor/${dependency}/${sourceFile}`,
      context: 'this',
      treeshake: false,
      output: {
        name: moduleName,
        file: `assets/vendor/${dependency}/${minifiedFile}`,
        sourcemap: true,
      },
      plugins: [
      ],
    }
  ];
  return callback && callback(steps) || steps;
}

const files = [
  // ok.
  addAsset('picturefill'),
  // ok.
  addAsset('backbone', { unminified: 'backbone.js', minified: 'backbone-min.js' }),
  addAsset('underscore', { unminified: 'underscore.js', minified: 'underscore-min.js', 'import': 'underscore/underscore', 'name': '_' }, ([step1, step2]) => {
    step1.output.format = 'es';
    step2.output.format = 'iife';

    return [step1, step2];
  }),

  addAsset('jquery', { 'import': 'jquery/dist/jquery.js', 'name': 'jQuery' }, ([step1, step2]) => {
    // Prevent leaking variables.
    step2.output.format = 'iife';
    return [step1, step2];
  }),
  // ok.
  addAsset('js-cookie', { unminified: 'js.cookie.js', 'import': 'js-cookie', 'name': 'Cookies', 'export': true }, ([step1, step2]) => {
    // Prevent leaking variables.
    step2.output.format = 'iife';
    return [step1, step2];
  }),
  addAsset('popperjs', { unminified: 'popper.js', 'import': '@popperjs/core/dist/umd/popper', 'name': 'Popper' }, ([step1, step2]) => {
    // Prevent leaking variables.
    step2.output.format = 'iife';
    return [step1, step2];
  }),
  addAsset('jquery-form', { unminified: 'jquery.form.js', 'import': 'jquery-form/dist/jquery.form.min.js', 'export': 'jqueryForm' },  ([step1, step2]) =>  [
    {
      ...step1,
      output: {
        // Sortable is already minified, no need to reminify it.
        ...step2.output,
        sourcemap: false,
      }
    }
  ]),

  addAsset('sortable', { unminified: 'Sortable.js', 'import': 'sortablejs/dist/sortable.umd', 'name': 'Sortable' }, ([step1, step2]) => [
    {
      ...step1,
      output: {
        // Sortable is already minified, no need to reminify it.
        ...step2.output,
        sourcemap: false,
      }
    }
  ]),

].flat();
