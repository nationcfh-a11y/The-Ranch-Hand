/** Tailwind theme = the "Rustic Barn" design tokens from
 *  brand_assets/ranch-hand-design-system.md (derived per DESIGN.md's blueprint).
 *  Components reference these as semantic tokens (bg-barn, text-saddle, etc.). */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // Primary brand accent — deep blue denim (formerly barn red).
        barn:    { DEFAULT: '#2E4B7C', dark: '#1D3050', light: '#5476A8' },
        hay:     { DEFAULT: '#D9A441', dark: '#B9831F', light: '#E7C078' },
        saddle:  { DEFAULT: '#6B4226', dark: '#4A2D18', light: '#8A5C3A' },
        cream:   { DEFAULT: '#F7F1E3', 100: '#FBF7EE', 200: '#F1E7D2' },
        charcoal:{ DEFAULT: '#2E2A26', muted: '#6B645C' },
        sage:    { DEFAULT: '#6F8A5E', dark: '#566E47' },
        clay:    { DEFAULT: '#C2603F', dark: '#9E4A2E' },
        line:    '#E4D9C3',
      },
      fontFamily: {
        display: ['Bitter', 'Georgia', 'serif'],
        sans: ['"Nunito Sans"', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        '5xl': ['3.25rem', { lineHeight: '1.1' }],
        '4xl': ['2.5rem', { lineHeight: '1.15' }],
      },
      borderRadius: {
        sm: '6px', md: '10px', lg: '16px', xl: '24px',
      },
      boxShadow: {
        card: '0 4px 16px rgba(74, 45, 24, 0.10)',
        pop: '0 12px 32px rgba(74, 45, 24, 0.16)',
      },
      backgroundImage: {
        'hero-grain':
          "linear-gradient(180deg, rgba(46,42,38,0.55) 0%, rgba(46,42,38,0.35) 45%, rgba(46,42,38,0.65) 100%)",
      },
    },
  },
  plugins: [],
};
