#!/bin/bash
# Academic Blogger's Toolkit - Quick Setup Script
# 
# This script automatically sets up the complete development environment
# for the Academic Blogger's Toolkit WordPress plugin.

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Header
echo "🚀 Academic Blogger's Toolkit - Quick Setup"
echo "============================================="
echo ""

# Check prerequisites
print_status "Checking prerequisites..."

if ! command_exists node; then
    print_error "Node.js is required but not installed. Please install Node.js (v14 or higher)"
    exit 1
fi

if ! command_exists npm; then
    print_error "npm is required but not installed. Please install npm"
    exit 1
fi

NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 14 ]; then
    print_error "Node.js version 14 or higher is required. Current version: $(node --version)"
    exit 1
fi

print_success "Prerequisites check passed"
echo ""

# Create project structure
print_status "Creating project directory structure..."

# Create all necessary directories
mkdir -p includes/{post-types,processors,fetchers,import-export,queries,utilities}
mkdir -p admin/{meta-boxes,pages,ajax,js/{src,dist},css/{src,dist},partials/{meta-boxes,pages,components}}
mkdir -p public/{js/{src,dist},css/{src,dist},partials/{shortcodes,widgets}}
mkdir -p templates
mkdir -p assets/{citation-styles,locales,fonts,images/{icons,screenshots}}
mkdir -p languages
mkdir -p tests/{unit,integration,js}
mkdir -p docs

print_success "Directory structure created"

# Initialize package.json if it doesn't exist
if [ ! -f package.json ]; then
    print_status "Creating package.json..."
    
    cat > package.json << 'EOF'
{
  "name": "academic-bloggers-toolkit",
  "version": "1.0.0",
  "description": "WordPress plugin for academic blogging with citation management and scholarly features",
  "keywords": ["wordpress", "plugin", "academic", "citations", "bibliography", "scholarly"],
  "author": "Academic Blogger's Toolkit Team",
  "license": "GPL-3.0-or-later",
  "private": true,
  "engines": {
    "node": ">=14.0.0",
    "npm": ">=6.0.0"
  },
  "browserslist": ["> 1%", "last 2 versions", "ie >= 11"],
  "scripts": {
    "build": "NODE_ENV=production webpack --mode=production",
    "build:dev": "NODE_ENV=development webpack --mode=development",
    "watch": "NODE_ENV=development webpack --mode=development --watch",
    "dev": "NODE_ENV=development webpack serve --mode=development",
    "clean": "rimraf admin/js/dist admin/css/dist public/js/dist public/css/dist",
    "lint:js": "eslint admin/js/src public/js/src --ext .js",
    "lint:css": "stylelint admin/css/src/**/*.scss public/css/src/**/*.scss",
    "lint": "npm run lint:js && npm run lint:css",
    "analyze": "WEBPACK_BUNDLE_ANALYZER=true npm run build",
    "test": "jest",
    "test:watch": "jest --watch",
    "format": "prettier --write admin/js/src/**/*.js public/js/src/**/*.js",
    "precommit": "lint-staged"
  }
}
EOF

    print_success "package.json created"
fi

# Install dependencies
print_status "Installing npm dependencies (this may take a few minutes)..."

npm install --save-dev \
  @babel/core@^7.22.0 \
  @babel/plugin-transform-runtime@^7.22.0 \
  @babel/preset-env@^7.22.0 \
  @babel/runtime@^7.22.0 \
  autoprefixer@^10.4.14 \
  babel-loader@^9.1.0 \
  css-loader@^6.8.0 \
  css-minimizer-webpack-plugin@^5.0.0 \
  cssnano@^6.0.0 \
  eslint@^8.42.0 \
  eslint-config-prettier@^8.8.0 \
  eslint-plugin-prettier@^4.2.1 \
  mini-css-extract-plugin@^2.7.0 \
  postcss@^8.4.24 \
  postcss-loader@^7.3.0 \
  prettier@^2.8.8 \
  rimraf@^5.0.0 \
  sass@^1.63.0 \
  sass-loader@^13.3.0 \
  stylelint@^15.7.0 \
  stylelint-config-standard-scss@^9.0.0 \
  terser-webpack-plugin@^5.3.0 \
  webpack@^5.88.0 \
  webpack-cli@^5.1.0 \
  jest@^29.5.0

npm install --save jquery@^3.7.0

print_success "Dependencies installed"

# Create webpack configuration
print_status "Creating webpack.config.js..."

cat > webpack.config.js << 'EOF'
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  mode: isProduction ? 'production' : 'development',
  
  entry: {
    'admin/admin-main': './admin/js/src/admin-main.js',
    'admin/meta-box': './admin/js/src/meta-box.js',
    'public/frontend-main': './public/js/src/frontend-main.js',
    'public/citation-tooltips': './public/js/src/citation-tooltips.js',
    'admin/admin-main-css': './admin/css/src/admin-main.scss',
    'public/frontend-main-css': './public/css/src/frontend-main.scss'
  },

  output: {
    path: path.resolve(__dirname),
    filename: '[name].js',
    clean: false
  },

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [['@babel/preset-env', { targets: { browsers: ['> 1%', 'last 2 versions'] } }]]
          }
        }
      },
      {
        test: /\.(scss|css)$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      }
    ]
  },

  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css'
    })
  ],

  optimization: {
    minimize: isProduction,
    minimizer: [new TerserPlugin(), new CssMinimizerPlugin()]
  },

  externals: {
    'jquery': 'jQuery'
  },

  devtool: isProduction ? false : 'source-map'
};
EOF

print_success "webpack.config.js created"

# Create other configuration files
print_status "Creating configuration files..."

# .babelrc
cat > .babelrc << 'EOF'
{
  "presets": [
    [
      "@babel/preset-env",
      {
        "targets": { "browsers": ["> 1%", "last 2 versions", "ie >= 11"] },
        "modules": false
      }
    ]
  ],
  "plugins": ["@babel/plugin-transform-runtime"]
}
EOF

# postcss.config.js
cat > postcss.config.js << 'EOF'
module.exports = {
  plugins: [
    require('autoprefixer')({
      overrideBrowserslist: ['> 1%', 'last 2 versions', 'ie >= 11']
    })
  ]
};
EOF

# .eslintrc.js
cat > .eslintrc.js << 'EOF'
module.exports = {
  env: { browser: true, es2021: true, jquery: true },
  extends: ['eslint:recommended'],
  parserOptions: { ecmaVersion: 2021, sourceType: 'module' },
  globals: { wp: 'readonly', jQuery: 'readonly', $: 'readonly' },
  rules: {
    'no-console': 'warn',
    'no-unused-vars': ['error', { argsIgnorePattern: '^_' }]
  }
};
EOF

# .stylelintrc.js
cat > .stylelintrc.js << 'EOF'
module.exports = {
  extends: ['stylelint-config-standard-scss'],
  rules: {
    'selector-class-pattern': '^abt-[a-z0-9\\-]+$|^wp-[a-z0-9\\-]+$|^[a-z][a-z0-9]*(-[a-z0-9]+)*$'
  }
};
EOF

# .prettierrc
cat > .prettierrc << 'EOF'
{
  "semi": true,
  "trailingComma": "es5",
  "singleQuote": true,
  "printWidth": 100,
  "tabWidth": 2
}
EOF

# .gitignore
cat > .gitignore << 'EOF'
node_modules/
admin/js/dist/
admin/css/dist/
public/js/dist/
public/css/dist/
*.min.js
*.min.css
*.map
.DS_Store
npm-debug.log*
EOF

print_success "Configuration files created"

# Create placeholder source files
print_status "Creating placeholder source files..."

# JavaScript files
cat > admin/js/src/admin-main.js << 'EOF'
/**
 * Academic Blogger's Toolkit - Admin Main JavaScript
 */
(function($) {
    'use strict';
    
    const ABT_Admin = {
        init: function() {
            console.log('ABT Admin initialized');
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Admin event handlers will go here
        }
    };
    
    $(document).ready(function() {
        ABT_Admin.init();
    });
    
})(jQuery);
EOF

cat > admin/js/src/meta-box.js << 'EOF'
/**
 * Academic Blogger's Toolkit - Meta Box JavaScript
 */
(function($) {
    'use strict';
    
    const ABT_MetaBox = {
        init: function() {
            console.log('ABT Meta Box initialized');
        }
    };
    
    $(document).ready(function() {
        ABT_MetaBox.init();
    });
    
})(jQuery);
EOF

cat > public/js/src/frontend-main.js << 'EOF'
/**
 * Academic Blogger's Toolkit - Frontend Main JavaScript
 */
(function($) {
    'use strict';
    
    const ABT_Frontend = {
        init: function() {
            console.log('ABT Frontend initialized');
        }
    };
    
    $(document).ready(function() {
        ABT_Frontend.init();
    });
    
})(jQuery);
EOF

cat > public/js/src/citation-tooltips.js << 'EOF'
/**
 * Academic Blogger's Toolkit - Citation Tooltips
 */
(function($) {
    'use strict';
    
    const ABT_CitationTooltips = {
        init: function() {
            console.log('ABT Citation Tooltips initialized');
        }
    };
    
    $(document).ready(function() {
        ABT_CitationTooltips.init();
    });
    
})(jQuery);
EOF

# SCSS files
cat > admin/css/src/admin-main.scss << 'EOF'
/**
 * Academic Blogger's Toolkit - Admin Main Styles
 */

.abt-admin-page {
  .wrap {
    margin-top: 20px;
  }
}

.abt-notice {
  margin: 15px 0;
}
EOF

cat > public/css/src/frontend-main.scss << 'EOF'
/**
 * Academic Blogger's Toolkit - Frontend Main Styles
 */

.abt-academic-post {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
  
  .abt-article-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
  }
}

.abt-citation {
  background-color: #e3f2fd;
  padding: 0 4px;
  border-radius: 3px;
  cursor: pointer;
}
EOF

print_success "Placeholder source files created"

# Create main plugin file
print_status "Creating main plugin file..."

cat > academic-bloggers-toolkit.php << 'EOF'
<?php
/**
 * Plugin Name: Academic Blogger's Toolkit
 * Plugin URI: https://github.com/your-username/academic-bloggers-toolkit
 * Description: Comprehensive toolkit for academic blogging with citation management, bibliography generation, and scholarly features.
 * Version: 1.0.0
 * Author: Academic Blogger's Toolkit Team
 * License: GPL v3 or later
 * Text Domain: academic-bloggers-toolkit
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABT_VERSION', '1.0.0');
define('ABT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class will be loaded here
 */
register_activation_hook(__FILE__, 'abt_activate_plugin');
register_deactivation_hook(__FILE__, 'abt_deactivate_plugin');

function abt_activate_plugin() {
    // Activation logic
}

function abt_deactivate_plugin() {
    // Deactivation logic
}

// Initialize plugin
add_action('plugins_loaded', function() {
    // Plugin initialization will go here
});
EOF

print_success "Main plugin file created"

# Test the build system
print_status "Testing build system..."

if npm run build > /dev/null 2>&1; then
    print_success "Build system test passed"
else
    print_warning "Build system test failed, but setup is complete"
fi

# Initialize git if not already done
if [ ! -d .git ]; then
    print_status "Initializing git repository..."
    git init
    git add .
    git commit -m "Initial commit: Academic Blogger's Toolkit setup"
    print_success "Git repository initialized"
fi

# Final instructions
echo ""
print_success "Setup completed successfully! 🎉"
echo ""
echo "📁 Project structure created"
echo "📦 Dependencies installed"
echo "⚙️  Configuration files set up"
echo "📝 Placeholder source files created"
echo "🔨 Build system configured"
echo ""
echo "🚀 Quick Start Commands:"
echo "------------------------"
echo "npm run watch          # Start development with file watching"
echo "npm run build          # Create production build"
echo "npm run lint           # Check code quality"
echo "npm test               # Run tests"
echo ""
echo "📂 Key Directories:"
echo "admin/js/src/          # Admin JavaScript source files"
echo "admin/css/src/         # Admin CSS/SCSS source files"
echo "public/js/src/         # Frontend JavaScript source files"
echo "public/css/src/        # Frontend CSS/SCSS source files"
echo ""
echo "Built files will be in:"
echo "admin/js/dist/         # Compiled admin JavaScript"
echo "admin/css/dist/        # Compiled admin CSS"
echo "public/js/dist/        # Compiled frontend JavaScript"
echo "public/css/dist/       # Compiled frontend CSS"
echo ""
echo "💡 Next Steps:"
echo "1. Run 'npm run watch' to start development"
echo "2. Edit source files in src/ directories"
echo "3. Check dist/ directories for compiled output"
echo "4. Integrate with WordPress by enqueueing the built files"
echo ""
echo "Happy coding! 🚀"