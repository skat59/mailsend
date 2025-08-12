module.exports = function(grunt) {
	let PACK = grunt.file.readJSON('package.json');
	require('dotenv').config();
	const DEBUG = parseInt(process.env.DEBUG) || false,
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
			let md5 = require('md5');
			result = md5(`v${VERSION}`).toString();// (new Date()).getTime()
			grunt.verbose.writeln("Generate hash: " + chalk.cyan(result) + " >>> OK");
			return result;
		};
	String.prototype.hashCode = function() {
		let hash = 0, i, chr;
		if (this.length === 0) return hash;
		for (i = 0; i < this.length; i++) {
			chr   = this.charCodeAt(i);
			hash  = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return hash;
	};

	const hash = uniqid();

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
		concat: {
			options: {
				separator: "\n",
			},
			appjs: {
				src: [
					"src/js/app.js",
				],
				dest: 'test/js/app.js'
			},
			main: {
				src: [
					'src/js/main.js'
				],
				dest: 'test/js/main.js'
			},
			datatables: {
				src: [
					'bower_components/pdfmake/build/pdfmake.js',
					'bower_components/jszip/dist/jszip.js',
					'bower_components/pdfmake/build/vfs_fonts.js',
					'bower_components/datatables.net/js/dataTables.js',
					'bower_components/datatables.net-buttons/js/dataTables.buttons.js',
					'bower_components/datatables.net-buttons/js/buttons.html5.js',
					'bower_components/datatables.net-bs/js/dataTables.bootstrap.js'
				],
				dest: 'site/assets/modules/MailSend/js/datatables.js'
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
					},
					{
						expand: true,
						flatten : true,
						src: [
							'site/assets/modules/MailSend/js/datatables.js'
						],
						dest: 'site/assets/modules/MailSend/js',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			},
			mod: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'site/assets/modules/MailSend/js/main.js'
						],
						dest: 'site/assets/modules/MailSend/js',
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
		less: {
			css: {
				options : {
					compress: false,
					ieCompat: false,
					plugins: [
						new NpmImportPlugin({prefix: '~'})
					],
					modifyVars: {
						'hashes': '\'' + hash + '\'',
						'fontpath': '/assets/templates/projectsoft/fonts',
						'imgpath': '/assets/templates/projectsoft/images',
					}
				},
				files : {
					'test/css/main.css' : [
						'src/less/main.less',
						//'src/less/plugins/prism.css'
					],
					'test/css/tinymce.css' : [
						'src/less/tinymce.less'
					],
					'site/assets/modules/MailSend/css/main.css' : [
						'bower_components/datatables.net-bs/css/dataTables.bootstrap.css',
						'bower_components/datatables.net-buttons-bs/css/buttons.bootstrap.css',
						'bower_components/datatables.net-select-bs/css/select.bootstrap.css',
						'site/assets/modules/MailSend/css/main.less'
					]
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
					],
					'site/assets/modules/MailSend/css/main.css' : [
						'site/assets/modules/MailSend/css/main.css'
					]
				}
			}
		},
		group_css_media_queries: {
			group: {
				files: {
					'test/css/media/main.css': ['test/css/prefix.main.css'],
					'test/css/media/tinymce.css': ['test/css/prefix.tinymce.css'],
					'site/assets/modules/MailSend/css/main.css': ['site/assets/modules/MailSend/css/main.css']
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
					'site/assets/templates/projectsoft/css/tinymce.min.css' : ['test/css/replace/tinymce.css'],
					'site/assets/modules/MailSend/css/main.min.css': ['site/assets/modules/MailSend/css/main.css']
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
							'src/images/*.{svg}'
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
				dest: "site/",
			},
			form: {
				expand: true,
				cwd: 'src/json',
				src: [
					'**'
				],
				dest: "site/comon/json/",
			},
			css_htaccess: {
				expand: true,
				cwd: 'src/copy/',
				src: [
					'.*'
				],
				dest: "site/assets/templates/projectsoft/css/",
			},
			js_htaccess: {
				expand: true,
				cwd: 'src/copy/',
				src: [
					'.*'
				],
				dest: "site/assets/templates/projectsoft/js/",
			}
		},
		pug: {
			serv: {
				options: {
					client: false,
					pretty: DEBUG ? '\t' : '',
					separator:  DEBUG? '\n' : '',
					data: function(dest, src) {
						return {
							"base": "[(site_url)]",
							"tem_path" : "/assets/templates/projectsoft",
							"img_path" : "assets/templates/projectsoft/images/",
							"site_name": "[(site_name)]",
							"hash": hash
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: 'src/pug/',
						src: [ '*.pug' ],
						dest: '<%= globalConfig.gosave %>/',
						ext: '.html'
					}
				]
			},
			tpl: {
				options: {
					client: false,
					pretty: DEBUG ? '\t' : '',
					separator:  DEBUG? '\n' : '',
					data: function(dest, src) {
						return {
							"base": "[(site_url)]",
							"tem_path" : "/assets/templates/projectsoft",
							"img_path" : "assets/templates/projectsoft/images/",
							"site_name": "[(site_name)]",
							"hash": hash,
						}
					},
				},
				files: [
					{
						expand: true,
						dest: '<%= globalConfig.gosave %>/tpl/',
						cwd:  'src/pug/tpl/',
						src: '*.pug',
						ext: '.html'
					}
				]
			}
		}
	});
	grunt.registerTask('default',   [
		"clean:all",
		"robots",
		"concat",
		"uglify",
		"webfont",
		"ttf2woff",
		"ttf2woff2",
		"imagemin",
		"tinyimg",
		"less",
		"autoprefixer",
		"group_css_media_queries",
		"replace",
		"cssmin",
		"copy",
		"pug"
	]);
	grunt.registerTask('build',	    [
		"clean:build",
		"robots",
		"concat",
		"uglify",
		"imagemin",
		"tinyimg",
		"less",
		"autoprefixer",
		"group_css_media_queries",
		"replace",
		"cssmin",
		"copy",
		"pug"
	]);
}
