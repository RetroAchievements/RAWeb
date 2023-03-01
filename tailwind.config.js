/* eslint-disable @typescript-eslint/no-var-requires */

module.exports = {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    //  legacy
    './app_legacy/Helpers/render/*.php',
    './app_legacy/Helpers/util/*.php',
    './public/*.php',
  ],

  corePlugins: {
    // turn off preflight (reset/normalize)
    // https://tailwindcss.com/docs/preflight#disabling-preflight
    // preflight: false,
  },

  theme: {
    container: {
      center: true,
    },
    // extend: {
    //   fontFamily: {
    //     sans: ['Verdana', ...defaultTheme.fontFamily.sans],
    //   },
    // },
    fontSize: {
      '2xs': '.70rem',
      xs: '.75rem',
      sm: '.875rem',
      base: '1rem',
      lg: '1.125rem',
      xl: '1.25rem',
      '2xl': '1.5rem',
      '3xl': '1.875rem',
      '4xl': '2.25rem',
      '5xl': '3rem',
      '6xl': '4rem',
      '7xl': '5rem',
    },
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      '2xl': '1536px',
    },
    extend: {
      colors: {
        raTheme: {
          scrollthumb: 'var(--link-color)',
          scrolltrack: 'var(--text-color-muted)'
        }
      }
    }
  },

  plugins: [
    require('@tailwindcss/forms')({
      // TODO switch to global strategy as soon UI has been consolidated
      strategy: 'class', // only generate classes
    }),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/line-clamp'),
    require('tailwind-scrollbar'),
  ],
};
