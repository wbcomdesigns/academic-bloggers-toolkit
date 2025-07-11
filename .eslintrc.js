/**
 * ESLint Configuration for Academic Blogger's Toolkit
 * 
 * Enforces code quality and consistency for JavaScript files
 */

module.exports = {
  env: {
    browser: true,
    es2021: true,
    jquery: true,
    node: true
  },
  
  extends: [
    'eslint:recommended',
    'prettier'
  ],
  
  plugins: [
    'prettier'
  ],
  
  parserOptions: {
    ecmaVersion: 2021,
    sourceType: 'module'
  },
  
  globals: {
    // WordPress globals
    wp: 'readonly',
    jQuery: 'readonly',
    $: 'readonly',
    ajaxurl: 'readonly',
    
    // Plugin globals
    abt_admin_ajax: 'readonly',
    abt_frontend_ajax: 'readonly',
    abt_metabox_ajax: 'readonly',
    
    // Common globals
    console: 'readonly',
    window: 'readonly',
    document: 'readonly'
  },
  
  rules: {
    // Prettier integration
    'prettier/prettier': 'error',
    
    // General rules
    'no-console': ['warn', { allow: ['warn', 'error'] }],
    'no-debugger': 'error',
    'no-alert': 'warn',
    'no-unused-vars': ['error', { 
      argsIgnorePattern: '^_',
      varsIgnorePattern: '^_'
    }],
    
    // Best practices
    'eqeqeq': ['error', 'always'],
    'no-eval': 'error',
    'no-implied-eval': 'error',
    'no-new-func': 'error',
    'no-script-url': 'error',
    'no-self-compare': 'error',
    'no-sequences': 'error',
    'no-throw-literal': 'error',
    'no-unmodified-loop-condition': 'error',
    'no-useless-call': 'error',
    'no-useless-concat': 'error',
    'no-useless-return': 'error',
    'no-void': 'error',
    'prefer-promise-reject-errors': 'error',
    'radix': 'error',
    'wrap-iife': ['error', 'inside'],
    'yoda': 'error',
    
    // Variables
    'no-catch-shadow': 'off',
    'no-delete-var': 'error',
    'no-label-var': 'error',
    'no-restricted-globals': ['error', 'event'],
    'no-shadow': 'error',
    'no-shadow-restricted-names': 'error',
    'no-undef': 'error',
    'no-undef-init': 'error',
    'no-use-before-define': ['error', { functions: false }],
    
    // Stylistic
    'camelcase': ['error', { properties: 'never' }],
    'consistent-this': ['error', 'self'],
    'func-names': 'off',
    'max-nested-callbacks': ['error', 4],
    'new-cap': ['error', { newIsCap: true, capIsNew: false }],
    'no-array-constructor': 'error',
    'no-lonely-if': 'error',
    'no-mixed-spaces-and-tabs': 'error',
    'no-nested-ternary': 'error',
    'no-new-object': 'error',
    'no-spaced-func': 'error',
    'no-trailing-spaces': 'error',
    'no-unneeded-ternary': 'error',
    'object-curly-spacing': ['error', 'always'],
    'one-var': ['error', 'never'],
    'operator-assignment': ['error', 'always'],
    'operator-linebreak': ['error', 'after'],
    'padded-blocks': ['error', 'never'],
    'quote-props': ['error', 'as-needed'],
    'quotes': ['error', 'single', { avoidEscape: true }],
    'semi': ['error', 'always'],
    'semi-spacing': ['error', { before: false, after: true }],
    'space-before-blocks': ['error', 'always'],
    'space-before-function-paren': ['error', 'never'],
    'space-in-parens': ['error', 'never'],
    'space-infix-ops': 'error',
    'space-unary-ops': ['error', { words: true, nonwords: false }],
    'spaced-comment': ['error', 'always'],
    
    // ES6
    'arrow-spacing': ['error', { before: true, after: true }],
    'constructor-super': 'error',
    'no-class-assign': 'error',
    'no-confusing-arrow': 'error',
    'no-const-assign': 'error',
    'no-dupe-class-members': 'error',
    'no-duplicate-imports': 'error',
    'no-new-symbol': 'error',
    'no-this-before-super': 'error',
    'no-useless-computed-key': 'error',
    'no-useless-constructor': 'error',
    'no-useless-rename': 'error',
    'no-var': 'error',
    'object-shorthand': ['error', 'always'],
    'prefer-arrow-callback': 'error',
    'prefer-const': ['error', { destructuring: 'all' }],
    'prefer-rest-params': 'error',
    'prefer-spread': 'error',
    'prefer-template': 'error',
    'rest-spread-spacing': ['error', 'never'],
    'template-curly-spacing': ['error', 'never']
  },
  
  overrides: [
    {
      // Test files
      files: ['tests/**/*.js'],
      env: {
        jest: true
      },
      rules: {
        'no-console': 'off'
      }
    },
    
    {
      // Legacy files that might not follow all rules
      files: ['admin/js/src/legacy-*.js'],
      rules: {
        'no-var': 'off',
        'prefer-arrow-callback': 'off',
        'prefer-const': 'off'
      }
    }
  ]
};