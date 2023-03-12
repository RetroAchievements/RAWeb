/* eslint-disable @typescript-eslint/no-var-requires */

/** @type {import('tailwindcss').Config} */
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

    extend: {
      colors: {
        bg: 'var(--bg-color)',
        'box-bg': 'var(--box-bg-color)',
        'box-shadow': 'var(--box-shadow-color)',
        embed: 'var(--embed-color)',
        'embed-highlight': 'var(--embed-highlight-color)',
        heading: 'var(--heading-color)',
        link: 'var(--link-color)',
        'link-hover': 'var(--link-hover-color)',
        'menu-link': 'var(--menu-link-color)',
        'menu-link-hover': 'var(--menu-link-hover-color)',
        text: 'var(--text-color)',
        'text-danger': 'var(--text-color-danger)',
        'text-muted': 'var(--text-color-muted)'
      },

      animation: {
        'fade-in': 'fade-in 300ms ease',
        'fade-out': 'fade-out 200ms ease'
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: 0, transform: 'translateY(1rem) scale(95%)' },
          '100%': { opacity: 1, transform: 'translateY(0) scale(100%)' }
        },
        'fade-out': {
          '0%': { opacity: 1, transform: 'scale(100%)' },
          '100%': { opacity: 0, transform: 'scale(95%)' }
        }
      },
      transition: {
        leave: 'transition ease-out duration-300'
      }
    },

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
  ],
};
