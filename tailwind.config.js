/* eslint-disable @typescript-eslint/no-var-requires */

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Filament/**/*.php',
    './app/Helpers/render/*.php',
    './app/Helpers/util/*.php',
    './public/*.php',
    './resources/js/**/*.ts',
    './resources/views/**/*.blade.php',
    './storage/framework/views/*.php',
    './vendor/filament/**/*.blade.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
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
        'text-muted': 'var(--text-color-muted)',
      },

      animation: {
        'accordion-down': 'accordion-down 0.2s ease-out',
        'accordion-up': 'accordion-up 0.2s ease-out',
        'fade-in': 'fade-in 100ms ease',
        'collapse-open': 'collapse-open 200ms ease-in-out',
        tilt: 'tilt 10s infinite linear',
      },
      keyframes: {
        'accordion-down': {
          from: { height: '0' },
          to: { height: 'var(--radix-accordion-content-height)' },
        },

        'accordion-up': {
          from: { height: 'var(--radix-accordion-content-height)' },
          to: { height: '0' },
        },

        'fade-in': {
          '0%': { opacity: 0 },
          '100%': { opacity: 1 },
        },

        'collapse-open': {
          '0%': { opacity: 0, maxHeight: 0, transform: 'translateY(-0.5rem)' },
          '100%': { opacity: 1, maxHeight: 9000, transform: 'translateY(0)' },
        },

        tilt: {
          '0%, 50%, 100%': { transform: 'rotate(0deg)' },
          '25%': { transform: 'rotate(1.5deg)' },
          '75%': { transform: 'rotate(-1.5deg)' },
        },
      },
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
      // '2xl': '1536px',
    },
  },

  variants: {
    extend: {
      animation: ['motion-safe'],
    },
  },

  plugins: [
    require('tailwindcss-animate'),
    require('@tailwindcss/forms')({
      // TODO switch to global strategy as soon UI has been consolidated
      strategy: 'class', // only generate classes
    }),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),

    // Add support for `light:` and `black:` prefixes to better support light & black mode users.
    // `dark` is considered the default scheme, thus a `dark:` prefix is always implied similar to `xs:`.
    function ({ addVariant }) {
      addVariant('light', [
        '@media (prefers-color-scheme: light) { [data-scheme="system"] & }',
        '[data-scheme="light"] &',
      ]);

      addVariant('black', ['[data-scheme="black"] &']);
    },
  ],
};
