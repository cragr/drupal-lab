import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import buble from '@rollup/plugin-buble';
//import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';


function addAsset(dependency, { unminified = false, minified = false, importName = false } = {}, callback = false) {
  const sourceFile = unminified || `${dependency}.js`;
  const minifiedFile = minified || sourceFile.replace(/js$/, 'min.js');
  const nameImport = importName || dependency;
  if (!dependency) { return [[], []]; }
  const steps = [
    {
      input: 'entry',
      context: 'this',
      treeshake: false,
      output: {
        name: dependency,
        file: `assets/vendor/${dependency}/${sourceFile}`,
      },
      plugins: [
        virtual({ entry: `import "${nameImport}";` }),
        resolve(),
      ],
    },
    {
      input: `assets/vendor/${dependency}/${sourceFile}`,
      context: 'this',
      treeshake: false,
      output: {
        name: dependency,
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
  addAsset('picturefill'),
  addAsset('jquery'),
  addAsset('js-cookie', { unminified: 'js.cookie.js' }),
  addAsset('jquery-form', { unminified: 'jquery.form.js' }),
  addAsset('backbone', { unminified: 'backbone.js', minified: 'backbone-min.js' }),
  addAsset('underscore', { unminified: 'underscore.js', minified: 'underscore-min.js', importName: 'underscore/underscore' }),
  addAsset('popperjs', { unminified: 'popper.js', importName: '@popperjs/core/dist/umd/popper' }, ([step1, step2]) => {
    // Prevent leaking variables.
    step2.output.format = 'iife';
    return [step1, step2];
  }),

  addAsset('sortable', { unminified: 'Sortable.js', importName: 'sortablejs/dist/sortable.umd' }, ([step1, step2]) => [
    {
      ...step1,
      output: {
        // Sortable is already minified, no need to reminify it.
        ...step2.output,
        name: 'Sortable',
        sourcemap: false,
      }
    }
  ]),

].flat();
