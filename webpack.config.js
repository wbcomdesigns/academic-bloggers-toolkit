/**
 * Webpack Configuration for Academic Blogger's Toolkit
 * 
 * Builds and processes JavaScript and CSS assets for both admin and frontend
 * 
 * @package Academic_Bloggers_Toolkit
 * @since 1.0.0
 */

const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

// Environment check
const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  mode: isProduction ? 'production' : 'development',
  
  // Entry points for all JavaScript and CSS files
  entry: {
    // Admin JavaScript
    'admin/admin-main': './admin/js/src/admin-main.js',
    'admin/enhanced-admin': './admin/js/src/enhanced-admin.js',
    'admin/meta-box': './admin/js/src/meta-box.js',
    'admin/bulk-operations': './admin/js/src/bulk-operations.js',
    'admin/citation-editor': './admin/js/src/citation-editor.js',
    'admin/reference-manager': './admin/js/src/reference-manager.js',
    
    // Frontend JavaScript
    'public/frontend-main': './public/js/src/frontend-main.js',
    'public/enhanced-frontend': './public/js/src/enhanced-frontend.js',
    'public/citation-tooltips': './public/js/src/citation-tooltips.js',
    'public/footnote-handler': './public/js/src/footnote-handler.js',
    'public/reading-progress': './public/js/src/reading-progress.js',
    'public/search-widget': './public/js/src/search-widget.js',
    
    // Admin CSS
    'admin/admin-main-css': './admin/css/src/admin-main.scss',
    'admin/meta-box-css': './admin/css/src/meta-box.scss',
    'admin/reference-list-css': './admin/css/src/reference-list.scss',
    'admin/components-css': './admin/css/src/components.scss',
    
    // Frontend CSS
    'public/frontend-main-css': './public/css/src/frontend-main.scss',
    'public/academic-blog-css': './public/css/src/academic-blog.scss',
    'public/citations-css': './public/css/src/citations.scss',
    'public/bibliography-css': './public/css/src/bibliography.scss',
    'public/footnotes-css': './public/css/src/footnotes.scss',
    'public/tooltips-css': './public/css/src/tooltips.scss',
    'public/responsive-css': './public/css/src/responsive.scss'
  },

  // Output configuration
  output: {
    path: path.resolve(__dirname),
    filename: '[name].js',
    clean: false, // Don't clean dist folder to preserve other files
    assetModuleFilename: 'assets/[name][ext]'
  },

  // Module rules for processing different file types
  module: {
    rules: [
      // JavaScript processing with Babel
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets: {
                  browsers: ['> 1%', 'last 2 versions', 'ie >= 11']
                }
              }]
            ],
            plugins: [
              '@babel/plugin-transform-runtime'
            ]
          }
        }
      },

      // SCSS/CSS processing
      {
        test: /\.(scss|css)$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 2,
              sourceMap: !isProduction
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  ['autoprefixer'],
                  ...(isProduction ? [['cssnano', { preset: 'default' }]] : [])
                ]
              },
              sourceMap: !isProduction
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: !isProduction,
              sassOptions: {
                outputStyle: isProduction ? 'compressed' : 'expanded',
                includePaths: ['node_modules']
              }
            }
          }
        ]
      },

      // Font files
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        type: 'asset/resource',
        generator: {
          filename: 'assets/fonts/[name][ext]'
        }
      },

      // Images
      {
        test: /\.(png|jpg|jpeg|gif|svg)$/,
        type: 'asset/resource',
        generator: {
          filename: 'assets/images/[name][ext]'
        },
        parser: {
          dataUrlCondition: {
            maxSize: 8 * 1024 // 8kb
          }
        }
      }
    ]
  },

  // Plugins
  plugins: [
    // Extract CSS into separate files
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css'
    })
  ],

  // Optimization
  optimization: {
    minimize: isProduction,
    minimizer: [
      // JavaScript minification
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: isProduction,
            drop_debugger: isProduction
          },
          format: {
            comments: false
          }
        },
        extractComments: false
      }),
      
      // CSS minification
      new CssMinimizerPlugin({
        minimizerOptions: {
          preset: [
            'default',
            {
              discardComments: { removeAll: true }
            }
          ]
        }
      })
    ],

    // Split chunks for better caching
    splitChunks: {
      cacheGroups: {
        // Vendor libraries
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendor',
          chunks: 'all',
          enforce: true
        },
        
        // Admin common code
        adminCommon: {
          test: /admin[\\/]js[\\/]src/,
          name: 'admin/common',
          chunks: 'all',
          minChunks: 2,
          enforce: true
        },
        
        // Frontend common code
        frontendCommon: {
          test: /public[\\/]js[\\/]src/,
          name: 'public/common',
          chunks: 'all',
          minChunks: 2,
          enforce: true
        }
      }
    }
  },

  // Development server configuration (for development)
  devtool: isProduction ? false : 'source-map',

  // External dependencies (don't bundle these)
  externals: {
    'jquery': 'jQuery',
    'wp': 'wp'
  },

  // Resolve configuration
  resolve: {
    extensions: ['.js', '.jsx', '.scss', '.css'],
    alias: {
      '@admin': path.resolve(__dirname, 'admin/js/src'),
      '@public': path.resolve(__dirname, 'public/js/src'),
      '@styles': path.resolve(__dirname, 'admin/css/src'),
      '@frontend-styles': path.resolve(__dirname, 'public/css/src')
    }
  },

  // Performance hints
  performance: {
    hints: isProduction ? 'warning' : false,
    maxEntrypointSize: 512000,
    maxAssetSize: 512000
  },

  // Stats configuration
  stats: {
    colors: true,
    modules: false,
    children: false,
    chunks: false,
    chunkModules: false
  }
};

/**
 * Export different configurations for different environments
 */
if (process.env.WEBPACK_BUNDLE_ANALYZER) {
  const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
  module.exports.plugins.push(new BundleAnalyzerPlugin());
}