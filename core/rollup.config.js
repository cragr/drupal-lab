import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import buble from '@rollup/plugin-buble';
//import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';


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
        buble({ transforms: { modules: false } }),
        terser({
          format: { comments: 'some' },
        }),
      ],
    }
  ];
  return callback && callback(steps) || steps;
}

export default [
  // ok.
  addAsset('picturefill'),
  // ok.
  addAsset('backbone', { unminified: 'backbone.js', minified: 'backbone-min.js' }),
  addAsset('underscore', { unminified: 'underscore.js', minified: 'underscore-min.js', 'name': 'underscore/underscore' }),

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
