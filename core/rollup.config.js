import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import buble from '@rollup/plugin-buble';
import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';


function addAsset(dependency, { unminified = false, minified = false, importName = false } = {}) {
  const sourceFile = unminified || `${dependency}.js`;
  const minifiedFile = minified || sourceFile.replace(/js$/, 'min.js');
  const nameImport = importName || dependency;
  if (!dependency) { return [[], []]; }
  return [
    {
      input: 'entry',
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
  ]
}

export default [
  addAsset('backbone', { unminified: 'backbone.js', minified: 'backbone-min.js' }),
  addAsset('sortable', { unminified: 'Sortable.js', importName: 'sortablejs' }),
  addAsset('jquery-form', { unminified: 'jquery.form.js' }),
  addAsset('js-cookie', { unminified: 'js.cookie.js' }),

  (() => {
    const [step1, step2] = addAsset('jquery');
    step1.context = 'this';
    step2.context = 'this';

    return [step1, step2];
  })(),

  (() => {
    const [step1, step2] = addAsset('underscore', {
      unminified: 'underscore.js',
      minified: 'underscore-min.js',
    });
    step1.output.format = 'iife';

    return [step1, step2];
  })(),

  (() => {
    const [step1, step2] = addAsset('popperjs', {
      importName: '@popperjs/core',
    });

    return [step1, step2];
  })(),

  (() => {
    const [step1, step2] = addAsset('normalize-css');
    step1.plugins = [
      copy({
        src: 'node_modules/normalize.css/normalize.css',
        dest: 'assets/vendor/normalize-css/',
      })
    ];

    return [];// [step1];
  })(),
].flat();
