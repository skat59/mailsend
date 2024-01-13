module.exports = function(grunt) {
	let PACK = grunt.file.readJSON('package.json');
	require('dotenv').config();
	const sass = require('node-sass'),
		DEBUG = parseInt(process.env.DEBUG) || false,
		VERSION = process.env.VERSION == undefined ? PACK.font_version : process.env.VERSION,
		font = `localhost`,
		fontName = `LocalHost`,
		domAin = `mailsend.skat59.ru`,
		porT = `https`,
		domain_url = `${porT}://${domAin}`;
	var fs = require('fs'),
		chalk = require('chalk'),
		//PACK = grunt.file.readJSON('package.json'),
		uniqid = function () {
			if(DEBUG){
				var md5 = require('md5');
				result = md5((new Date()).getTime()).toString();
				grunt.verbose.writeln("Generate hash: " + chalk.cyan(result) + " >>> OK");
				return result;
			}
			return `v${VERSION}`;
		};
	
	String.prototype.hashCode = function() {
		var hash = 0, i, chr;
		if (this.length === 0) return hash;
		for (i = 0; i < this.length; i++) {
			chr   = this.charCodeAt(i);
			hash  = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return hash;
	};

	var gc = {
			fontvers: `${PACK.font_version}`,
			assets: "assets/templates/projectsoft",
			gosave: "site/assets/templates/projectsoft",
		},
		NpmImportPlugin = require("less-plugin-npm-import");
	
	console.log(chalk.yellow.bold(`VRESION BUILD ${VERSION}`));

	require('load-grunt-tasks')(grunt);

	grunt.registerMultiTask('robots', function() {
		let done = this.async(),
			options = this.options({}),
			domain = options.domain ? options.domain : `localhost`,
			port = options.port ? options.port : `http`,
			robotsTxt = `User-agent: *
Disallow: /assets/backup/
Disallow: /assets/cache/
Disallow: /assets/docs/
Disallow: /assets/export/
Disallow: /assets/import/
Disallow: /assets/modules/
Disallow: /assets/plugins/
Disallow: /assets/snippets/
Disallow: /assets/packages/ 
Disallow: /assets/tvs/
Disallow: /install/

Allow: /assets/cache/images/

Host: ${domain}

Sitemap: ${port}://${domain}/sitemap.xml
`;
		fs.writeFileSync(__dirname + '/site/robots.txt', robotsTxt);
		grunt.log.ok(`Write file robots.txt`);
		return done(`OK!!!`);
  	});

	require('time-grunt')(grunt);
	grunt.initConfig({
		globalConfig : gc,
		pkg : PACK,
		robots: {
			options: {
				domain: domAin,
				port: porT
			},
			default: {}
		},
		clean: {
			options: {
				force: true
			},
			all: [
				'test/',
				'tests/',
				'site/html_code.html',
				gc.gosave
			],
			favicon: [
				'site/*.ico',
				'site/*.png',
				'site/*svg',
				'site/*.xml',
				'site/*.webmanifest',
				'site/html_code.html'
			],
			build: [
				'test/',
				'tests/',
				'site/html_code.html',
				gc.gosave + "/css/",
				gc.gosave + "/images/",
				gc.gosave + "/js/",
				gc.gosave + "/tpl/",
				gc.gosave + "/*.html"
			]
		},
		realFavicon: {
			favicons: {
				src: 'src/favicon/favicon.png',
				dest: 'site/',
				options: {
					iconsPath: domain_url,
					//html: [ 'favicons.txt' ],
					design: {
						ios: {
							pictureAspect: 'backgroundAndMargin',
							backgroundColor: '#ffffff',
							margin: '14%',
							assets: {
								ios6AndPriorIcons: true,
								ios7AndLaterIcons: true,
								precomposedIcons: true,
								declareOnlyDefaultIcon: false
							}
						},
						desktopBrowser: {
							design: 'raw'
						},
						windows: {
							pictureAspect: 'noChange',
							backgroundColor: '#ffffff',
							onConflict: 'override',
							assets: {
								windows80Ie10Tile: true,
								windows10Ie11EdgeTiles: {
									small: true,
									medium: true,
									big: true,
									rectangle: true
								}
							}
						},
						androidChrome: {
							pictureAspect: 'backgroundAndMargin',
							margin: '17%',
							backgroundColor: '#ffffff',
							themeColor: '#ffffff',
							manifest: {
								display: 'standalone',
								orientation: 'notSet',
								onConflict: 'override',
								declared: true
							},
							assets: {
								legacyIcon: true,
								lowResolutionIcons: true
							}
						},
						safariPinnedTab: {
							pictureAspect: 'blackAndWhite',
							threshold: 65,
							themeColor: '#5bbad5'
						}
					},
					settings: {
						scalingAlgorithm: 'Mitchell',
						errorOnImageTooSmall: false,
						readmeFile: false,
						htmlCodeFile: true,
						// htmlCodeFile: false,
						usePathAsIs: false
					}
				}
			}
		},
		concat: {
			options: {
				separator: "\n",
			},
			appjs: {
				src: [
					'bower_components/jquery/dist/jquery.js',
					"bower_components/fancybox/src/js/core.js",
					"src/js/fancybox/media.js",
					"bower_components/fancybox/src/js/guestures.js",
					"bower_components/fancybox/src/js/slideshow.js",
					"bower_components/fancybox/src/js/fullscreen.js",
					"bower_components/fancybox/src/js/thumbs.js",
					"bower_components/fancybox/src/js/hash.js",
					"bower_components/fancybox/src/js/wheel.js",
					'bower_components/slick-carousel/slick/slick.js',
					'bower_components/js-cookie/src/js.cookie.js',
					'bower_components/jquery.cookie/jquery.cookie.js',
					'bower_components/button-visually-impaired-javascript/dist/js/bvi.js'
				],
				dest: 'test/js/app.js'
			},
			main: {
				src: [
					'src/js/main.js'
				],
				dest: 'test/js/main.js'
			}
		},
		uglify: {
			options: {
				sourceMap: false,
				compress: {
					drop_console: false
				},
				output: {
					ascii_only: true
				}
			},
			app: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'test/js/app.js',
							'test/js/main.js'
						],
						dest: '<%= globalConfig.gosave %>/js',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			}
		},
		webfont: {
			icons: {
				src: 'src/glyph/*.svg',
				dest: 'src/fonts/',
				options: {
					hashes: false,
					relativeFontPath: '@{fontpath}',
					destLess: 'src/less',
					font: font,
					types: 'ttf',
					fontFamilyName: fontName,
					stylesheets: ['less'],
					syntax: 'bootstrap',
					engine: 'node',
					autoHint: false,
					execMaxBuffer: 1024 * 200,
					htmlDemo: false,
					version: PACK.font_version,
					normalize: true,
					startCodepoint: 0xE900,
					iconsStyles: false,
					templateOptions: {
						baseClass: 'icon',
						classPrefix: 'icon-'
					},
					embed: false,
					template: 'src/font-build.template'
				}
			},
		},
		datauri: {
			default: {
				options: {
					classPrefix: '',
					variables: true,
				},
				src: 'bower_components/button-visually-impaired-javascript/dist/img/*.svg',
				dest: [
					"src/scss/base64.scss"
				]
			}
		},
		sass: {
			options: {
				implementation: sass,
				sourceMap: false
			},
			dist: {
				files: {
					'src/less/bvi.less': 'src/scss/bvi.scss'
				}
			}
		},
		less: {
			css: {
				options : {
					compress: false,
					ieCompat: false,
					plugins: [
						new NpmImportPlugin({prefix: '~'})
					],
					modifyVars: {
						'hashes': '\'' + uniqid() + '\'',
						'fontpath': '/assets/templates/projectsoft/fonts',
						'imgpath': '/assets/templates/projectsoft/images',
					}
				},
				files : {
					'test/css/main.css' : [
						'src/less/main.less'
					],
					'test/css/tinymce.css' : [
						'src/less/tinymce.less'
					],
				}
			}
		},
		autoprefixer:{
			options: {
				browsers: [
					"last 4 version"
				],
				cascade: true
			},
			css: {
				files: {
					'test/css/prefix.main.css' : [
						'test/css/main.css'
					],
					'test/css/prefix.tinymce.css' : [
						'test/css/tinymce.css'
					]
				}
			}
		},
		group_css_media_queries: {
			group: {
				files: {
					'test/css/media/main.css': ['test/css/prefix.main.css'],
					'test/css/media/tinymce.css': ['test/css/prefix.tinymce.css']
				}
			}
		},
		replace: {
			css: {
				options: {
					patterns: [
						{
							match: /\/\*.+?\*\//gs,
							replacement: ''
						},
						{
							match: /\r?\n\s+\r?\n/g,
							replacement: '\n'
						}
					]
				},
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'test/css/media/main.css'
						],
						dest: 'test/css/replace/',
						filter: 'isFile'
					},
					{
						expand: true,
						flatten : true,
						src: [
							'test/css/media/main.css'
						],
						dest: 'site/assets/templates/projectsoft/css/',
						filter: 'isFile'
					},
					{
						expand: true,
						flatten : true,
						src: [
							'test/css/media/tinymce.css'
						],
						dest: 'test/css/replace/',
						filter: 'isFile'
					},
					{
						expand: true,
						flatten : true,
						src: [
							'test/css/media/tinymce.css'
						],
						dest: 'site/assets/templates/projectsoft/css/',
						filter: 'isFile'
					}
				]
			},
		},
		cssmin: {
			options: {
				mergeIntoShorthands: false,
				roundingPrecision: -1
			},
			minify: {
				files: {
					'site/assets/templates/projectsoft/css/main.min.css' : ['test/css/replace/main.css'],
					'site/assets/templates/projectsoft/css/tinymce.min.css' : ['test/css/replace/tinymce.css']
				}
			}
		},
		imagemin: {
			options: {
				optimizationLevel: 3,
				svgoPlugins: [
					{
						removeViewBox: false
					}
				]
			},
			base: {
				files: [
					{
						expand: true,
						cwd: 'src/images', 
						src: ['**/*.{png,jpg,jpeg}'],
						dest: 'test/images/',
					},
					{
						expand: true,
						flatten : true,
						src: [
							'src/images/*.{gif,svg}'
						],
						dest: '<%= globalConfig.gosave %>/images/',
						filter: 'isFile'
					}
				]
			}
		},
		tinyimg: {
			dynamic: {
				files: [
					{
						expand: true,
						cwd: 'test/images', 
						src: ['**/*.{png,jpg,jpeg}'],
						dest: '<%= globalConfig.gosave %>/images/'
					}
				]
			}
		},
		ttf2eot: {
			default: {
				src: 'src/fonts/*.ttf',
				dest: '<%= globalConfig.gosave %>/fonts/'
			}
		},
		ttf2woff: {
			default: {
				src: 'src/fonts/*.ttf',
				dest: '<%= globalConfig.gosave %>/fonts/'
			}
		},
		ttf2woff2: {
			default: {
				src: 'src/fonts/*.ttf',
				dest: '<%= globalConfig.gosave %>/fonts/'
			}
		},
		copy: {
			fonts: {
				expand: true,
				cwd: 'src/fonts',
				src: [
					'**'
				],
				dest: '<%= globalConfig.gosave %>/fonts/',
			},
			js: {
				expand: true,
				cwd: 'test/js',
				src: [
					'**'
				],
				dest: '<%= globalConfig.gosave %>/js/',
			},
			favicons: {
				expand: true,
				cwd: 'src/favicons',
				src: [
					'**'
				],
				dest: __dirname + "/site/",
			},
			form: {
				expand: true,
				cwd: 'src/json',
				src: [
					'**'
				],
				dest: __dirname + "/site/comon/json/",
			}
		},
		pug: {
			serv: {
				options: {
					client: false,
					pretty: DEBUG ? '\t' : '\t',
					separator:  DEBUG? '\n' : '\n',
					data: function(dest, src) {
						return {
							"base": "[(site_url)]",
							"tem_path" : "/assets/templates/projectsoft",
							"img_path" : "assets/templates/projectsoft/images/",
							"site_name": "[(site_name)]",
							"hash": uniqid()
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src/pug/',
						src: [ '*.pug' ],
						dest: __dirname + '/' + '<%= globalConfig.gosave %>/',
						ext: '.html'
					}
				]
			},
			tpl: {
				options: {
					client: false,
					pretty: DEBUG ? '\t' : '\t',
					separator:  DEBUG? '\n' : '\n',
					data: function(dest, src) {
						return {
							"base": "[(site_url)]",
							"tem_path" : "/assets/templates/projectsoft",
							"img_path" : "assets/templates/projectsoft/images/",
							"site_name": "[(site_name)]",
							"hash": uniqid(),
						}
					},
				},
				files: [
					{
						expand: true,
						dest: __dirname + '/<%= globalConfig.gosave %>/tpl/',
						cwd:  __dirname + '/src/pug/tpl/',
						src: '*.pug',
						ext: '.html'
					}
				]
			}
		}
	});
	grunt.registerTask('default',   ["clean:all", "realFavicon", "robots", "concat", "uglify", "webfont", "ttf2woff", "ttf2woff2", "imagemin", "tinyimg", "datauri", "sass", "less", "autoprefixer", "group_css_media_queries", "replace", "cssmin", "copy", "pug"]);
	grunt.registerTask('favicon',   ["clean:favicon", "realFavicon"]);
	grunt.registerTask('build',	    ["clean:build", "robots", "concat", "uglify", "imagemin", "tinyimg", "datauri", "sass", "less", "autoprefixer", "group_css_media_queries", "replace", "cssmin", "copy", "pug"]);
}
