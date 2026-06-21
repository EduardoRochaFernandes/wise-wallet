/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './public/**/*.php',
    './app/views/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Deliberate palette — deep pine green accent (no "VibeCode purple").
        brand: {
          50:  '#eef4f0',
          100: '#d7e7dd',
          200: '#afceba',
          300: '#80b093',
          400: '#4f8e6c',
          500: '#2f6f4f',
          600: '#1f5a3f',
          700: '#194a34',
          800: '#153c2b',
          900: '#113021',
        },
        // Semantic surface tokens driven by CSS variables (see app.css)
        paper: 'rgb(var(--paper) / <alpha-value>)',
        surface: 'rgb(var(--surface) / <alpha-value>)',
        'surface-2': 'rgb(var(--surface-2) / <alpha-value>)',
        ink: 'rgb(var(--ink) / <alpha-value>)',
        'ink-soft': 'rgb(var(--ink-soft) / <alpha-value>)',
        line: 'rgb(var(--line) / <alpha-value>)',
      },
      fontFamily: {
        // Body is a grotesque system stack — deliberately NOT Inter.
        sans: ['"Segoe UI"', 'system-ui', '-apple-system', '"Helvetica Neue"', 'Arial', 'sans-serif'],
        // Display is a serif for editorial authority.
        display: ['Georgia', '"Iowan Old Style"', '"Times New Roman"', 'serif'],
        mono: ['"Cascadia Mono"', 'Consolas', 'ui-monospace', 'monospace'],
      },
      borderRadius: {
        DEFAULT: '4px',
        sm: '3px',
        md: '5px',
        lg: '6px',
        xl: '8px',
        '2xl': '10px',
      },
      boxShadow: {
        card: '0 1px 2px rgba(26, 25, 22, 0.05)',
        pop: '0 8px 30px -12px rgba(26, 25, 22, 0.25)',
      },
      keyframes: {
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(12px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'fade-up': 'fade-up 0.5s cubic-bezier(0.22, 1, 0.36, 1) both',
      },
    },
  },
  plugins: [],
};
