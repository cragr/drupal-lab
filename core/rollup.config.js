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
      context: 'this',
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
  //addAsset('picturefill'),
  //addAsset('jquery'),
  //addAsset('js-cookie', { unminified: 'js.cookie.js' }),
  //addAsset('jquery-form', { unminified: 'jquery.form.js' }),
  //addAsset('backbone', { unminified: 'backbone.js', minified: 'backbone-min.js' }),
  //addAsset('underscore', { unminified: 'underscore.js', minified: 'underscore-min.js', importName: 'underscore/underscore' }),

  (() => {
    const [step1, step2] = addAsset('sortable', {
      unminified: 'Sortable.js',
      importName: 'sortablejs/dist/sortable.umd',
    });

    return [
        {
        ...step1,
        output: {
          ...step2.output,
          name: 'Sortable',
        }
      }
    ];
  })(),

  (() => {
    const [step1, step2] = addAsset('popperjs', {
      unminified: 'popper.js',
    });
    // Override the plugins because popperjs needs some extra help.
    step1.plugins = [
      virtual({ entry: `
        import * as Popper from "@popperjs/core";
      `}),
      resolve(),
    ];

    return [step1, step2];
  })(),

].flat();
