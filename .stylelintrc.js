/**
 * Stylelint Configuration for Academic Blogger's Toolkit
 * 
 * Enforces CSS/SCSS code quality and consistency
 */

module.exports = {
  extends: [
    'stylelint-config-standard-scss'
  ],
  
  plugins: [
    'stylelint-scss'
  ],
  
  rules: {
    // General
    'at-rule-no-unknown': null,
    'scss/at-rule-no-unknown': true,
    'color-named': 'never',
    'color-no-hex': null,
    'declaration-block-no-duplicate-properties': true,
    'declaration-no-important': null,
    'font-family-name-quotes': 'always-where-required',
    'function-url-quotes': 'always',
    'max-nesting-depth': 4,
    'no-descending-specificity': null,
    'no-duplicate-selectors': true,
    'property-no-vendor-prefix': null,
    'selector-max-compound-selectors': 4,
    'selector-max-id': 1,
    'selector-no-qualifying-type': null,
    'string-quotes': 'single',
    'value-no-vendor-prefix': null,
    
    // SCSS specific
    'scss/at-extend-no-missing-placeholder': true,
    'scss/at-function-pattern': '^[a-z]+([a-z0-9-]+[a-z0-9]+)?$',
    'scss/at-import-no-partial-leading-underscore': true,
    'scss/at-import-partial-extension-blacklist': ['scss'],
    'scss/at-mixin-pattern': '^[a-z]+([a-z0-9-]+[a-z0-9]+)?$',
    'scss/dollar-variable-pattern': '^[a-z]+([a-z0-9-]+[a-z0-9]+)?$',
    'scss/percent-placeholder-pattern': '^[a-z]+([a-z0-9-]+[a-z0-9]+)?$',
    'scss/selector-no-redundant-nesting-selector': true,
    
    // WordPress specific
    'selector-class-pattern': [
      '^abt-[a-z0-9\\-]+$|^wp-[a-z0-9\\-]+$|^[a-z][a-z0-9]*(-[a-z0-9]+)*$',
      {
        message: 'Class names should be kebab-case and prefixed with "abt-" for plugin classes'
      }
    ],
    
    // Media queries
    'media-feature-name-no-vendor-prefix': true,
    
    // Colors
    'color-hex-case': 'lower',
    'color-hex-length': 'short',
    
    // Units
    'length-zero-no-unit': true,
    'number-leading-zero': 'always',
    
    // Properties
    'property-case': 'lower',
    'shorthand-property-no-redundant-values': true,
    
    // Values
    'value-keyword-case': 'lower',
    
    // Custom property patterns
    'custom-property-pattern': '^abt-[a-z0-9\\-]+$',
    
    // Comments
    'comment-whitespace-inside': 'always',
    'comment-empty-line-before': [
      'always',
      {
        except: ['first-nested'],
        ignore: ['stylelint-commands']
      }
    ]
  },
  
  ignoreFiles: [
    'node_modules/**/*',
    'vendor/**/*',
    '**/*.min.css',
    '**/dist/**/*'
  ],
  
  overrides: [
    {
      files: ['admin/css/src/**/*.scss'],
      rules: {
        // Admin-specific overrides
        'selector-max-id': 2 // Allow more IDs in admin
      }
    },
    
    {
      files: ['public/css/src/**/*.scss'],
      rules: {
        // Frontend-specific overrides
        'no-descending-specificity': null
      }
    }
  ]
};