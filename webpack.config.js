const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';
    
    return {
        entry: {
            // Admin JavaScript files
            'admin/js/dist/admin-main': './admin/js/src/admin-main.js',
            'admin/js/dist/meta-box': './admin/js/src/meta-box.js',
            'admin/js/dist/reference-manager': './admin/js/src/reference-manager.js',
            'admin/js/dist/citation-editor': './admin/js/src/citation-editor.js',
            'admin/js/dist/bulk-operations': './admin/js/src/bulk-operations.js',
            
            // Public JavaScript files
            'public/js/dist/frontend-main': './public/js/src/frontend-main.js',
            'public/js/dist/citation-tooltips': './public/js/src/citation-tooltips.js',
            'public/js/dist/footnote-handler': './public/js/src/footnote-handler.js',
            'public/js/dist/search-widget': './public/js/src/search-widget.js',
            'public/js/dist/reading-progress': './public/js/src/reading-progress.js',
            
            // CSS files
            'admin/css/dist/admin-main': './admin/css/src/admin-main.scss',
            'admin/css/dist/meta-box': './admin/css/src/meta-box.scss',
            'admin/css/dist/reference-list': './admin/css/src/reference-list.scss',
            'admin/css/dist/components': './admin/css/src/components.scss',
            
            'public/css/dist/frontend-main': './public/css/src/frontend-main.scss',
            'public/css/dist/academic-blog': './public/css/src/academic-blog.scss',
            'public/css/dist/citations': './public/css/src/citations.scss',
            'public/css/dist/bibliography': './public/css/src/bibliography.scss',
            'public/css/dist/footnotes': './public/css/src/footnotes.scss',
            'public/css/dist/tooltips': './public/css/src/tooltips.scss',
            'public/css/dist/responsive': './public/css/src/responsive.scss'
        },
        
        output: {
            path: path.resolve(__dirname),
            filename: '[name].js',
            clean: false // Don't clean all files, only JS files
        },
        
        module: {
            rules: [
                // JavaScript processing
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                [
                                    '@babel/preset-env',
                                    {
                                        targets: {
                                            browsers: ['extends @wordpress/browserslist-config']
                                        },
                                        modules: false
                                    }
                                ]
                            ]
                        }
                    }
                },
                
                // SCSS/CSS processing
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: !isProduction,
                                importLoaders: 1
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: !isProduction,
                                sassOptions: {
                                    outputStyle: isProduction ? 'compressed' : 'expanded',
                                    precision: 6
                                }
                            }
                        }
                    ]
                },
                
                // CSS processing
                {
                    test: /\.css$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: !isProduction
                            }
                        }
                    ]
                },
                
                // Asset processing
                {
                    test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf|otf)$/,
                    type: 'asset/resource',
                    generator: {
                        filename: 'assets/[name][ext]'
                    }
                }
            ]
        },
        
        plugins: [
            new MiniCssExtractPlugin({
                filename: '[name].css',
                chunkFilename: '[id].css'
            })
        ],
        
        resolve: {
            extensions: ['.js', '.scss', '.css'],
            alias: {
                '@admin': path.resolve(__dirname, 'admin'),
                '@public': path.resolve(__dirname, 'public'),
                '@includes': path.resolve(__dirname, 'includes'),
                '@assets': path.resolve(__dirname, 'assets')
            }
        },
        
        externals: {
            jquery: 'jQuery',
            wp: 'wp'
        },
        
        optimization: {
            splitChunks: {
                cacheGroups: {
                    // Admin common chunks
                    adminVendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'admin/js/dist/vendor',
                        chunks: chunk => chunk.name && chunk.name.includes('admin/js/dist/'),
                        enforce: true
                    },
                    
                    // Public common chunks
                    publicVendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'public/js/dist/vendor',
                        chunks: chunk => chunk.name && chunk.name.includes('public/js/dist/'),
                        enforce: true
                    }
                }
            }
        },
        
        devtool: isProduction ? false : 'source-map',
        
        performance: {
            hints: isProduction ? 'warning' : false,
            maxEntrypointSize: 500000,
            maxAssetSize: 500000
        },
        
        stats: {
            colors: true,
            modules: false,
            chunks: false,
            chunkModules: false
        },
        
        watchOptions: {
            aggregateTimeout: 300,
            poll: 1000,
            ignored: [
                /node_modules/,
                /tests/,
                /vendor/
            ]
        }
    };
};