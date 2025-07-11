/**
 * PostCSS Configuration for Academic Blogger's Toolkit
 * 
 * Handles CSS processing, autoprefixing, and optimization
 */

module.exports = {
  plugins: [
    // Autoprefixer for cross-browser compatibility
    require('autoprefixer')({
      overrideBrowserslist: [
        '> 1%',
        'last 2 versions',
        'ie >= 11'
      ],
      grid: true
    }),

    // CSS optimization for production
    ...(process.env.NODE_ENV === 'production' ? [
      require('cssnano')({
        preset: [
          'default',
          {
            discardComments: {
              removeAll: true
            },
            normalizeWhitespace: true,
            reduceIdents: false,
            zindex: false
          }
        ]
      })
    ] : [])
  ]
};