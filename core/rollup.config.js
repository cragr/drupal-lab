import { terser } from 'rollup-plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import buble from '@rollup/plugin-buble';
import copy from 'rollup-plugin-copy';
import virtual from '@rollup/plugin-virtual';


function asset(dependency, { source = false, minified = false, name = false }) {
  const sourceFile = source || `${dependency}.js`;
  const minifiedFile = minified || sourceFile.replace(/js$/, 'min.js');
  const importDependency = name || dependency;
  return [
    {
      input: 'entry',
      output: {
        name: dependency,
        file: `assets/vendor/${dependency}/${sourceFile}`,
      },
      plugins: [
        virtual({ entry: `import "${importDependency}";` }),
        resolve(),
      ],
    },
    {
      input: `assets/vendor/${dependency}/${sourceFile}`,
      output: {
        name: dependency,
        file: `assets/vendor/${dependency}/${minifiedFile}`,
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

const files = [
  ...(() => {
    const [step1, step2] = asset('jquery');
    step1.context = 'this';
    step2.context = 'this';

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('underscore', {
      source: 'underscore.js',
      minified: 'underscore-min.js',
    });
    step1.output.format = 'iife';

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('backbone', {
      source: 'backbone.js',
      minified: 'backbone-min.js',
    });

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('sortable', {
      name: 'sortablejs',
      source: 'Sortable.js',
    });

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('popperjs', {
      name: '@popperjs/core',
    });

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('jquery-form', {
      source: 'jquery.form.js',
    });

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('js-cookie', {
      source: 'js.cookie.js',
    });

    return [step1, step2];
  })(),
  ...(() => {
    const [step1, step2] = asset('normalize-css');
    step1.plugins = [
      copy({
        src: 'node_modules/normalize.css/normalize.css',
        dest: 'assets/vendor/normalize-css/',
      })
    ];

    return [];// [step1];
  })(),
];

export default files;
