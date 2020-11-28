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
