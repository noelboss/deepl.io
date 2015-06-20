module.exports = function(grunt) {
	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		replace: {
			version: {
				src: ['./README.md','./index.html','./index.php'],
				overwrite: true,
				replacements: [
					{
						from: /@\s[0-9]{4}/g,
						to: '© <%= grunt.template.today("yyyy") %>'
					},
					{
						from: /Copyright\s[0-9]{4}/g,
						to: 'Copyright <%= grunt.template.today("yyyy") %>'
					},
					{
						from: /\–\sVersion\s[0-9]+[.]{1}[0-9]+[.]{1}[0-9]+/g,
						to: '– Version <%= pkg.version %>'
					},
					{
						from: /Current\sRelease:\s[0-9]+[.]{1}[0-9]+[.]{1}[0-9]+/g,
						to: 'Current Release: <%= pkg.version %>'
					},
					{
						from: /"Version":\s"[0-9]+[.]{1}[0-9]+[.]{1}[0-9]+"/g,
						to: '"version": "<%= pkg.version %>"'
					},
					{
						from: /\([0-9]+[.]{1}[0-9]+[.]{1}[0-9]+\)/g,
						to: '(<%= pkg.version %>)'
					}
				]
			},
			changelog: {
				src: ['./CHANGELOG.md'],
				overwrite: true,
				replacements: [
					{
						from: '## Master\n',
						to: '## Master\n\n\n## v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n'
					}
				]
			},
		},
		bump: {
			options: {
				files: [
					'package.json'
				],
				updateConfigs: ['pkg'],
				commit: true,
				commitMessage: 'Release Version %VERSION%',
				commitFiles: ['-a'], // '-a' for all files
				createTag: false,
				tagName: '%VERSION%',
				tagMessage: 'Released Version %VERSION%',
				push: false
				/*pushTo: 'upstream',*/
				/*gitDescribeOptions: '--tags --always --abbrev=1 --dirty=-d' // options to use with '$ git describe'*/
			},
		},
	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-bump');

	// Default task(s).
	grunt.registerTask('default', ['bump-only:patch', 'replace:version', 'replace:changelog',]);
	grunt.registerTask('test',    ['bump-only:patch', 'replace:version', 'replace:changelog',]);

	grunt.registerTask('patch',   [ 'bump-only:patch', 'replace:version', 'bump-commit', 'replace:changelog',]);
	grunt.registerTask('minor',   [ 'bump-only:minor', 'replace:version', 'bump-commit', 'replace:changelog',]);
	grunt.registerTask('major',   [ 'bump-only:major', 'replace:version', 'bump-commit', 'replace:changelog',]);

};
