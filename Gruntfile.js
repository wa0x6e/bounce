module.exports = function(grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        clean: {
            coverage: ['build']
        },
        shell: {
            caketest: {
                command: '../../Console/cake test Bounce AllBounce',
                options: {
                    stdout: true
                }
            },
            coverage: {
                command: '../../Console/cake test Bounce AllBounce --configuration Test/phpunit.xml'
            }
        },
        watch: {
          scripts: {
            files: ['Model/**/*.php', 'Test/**/*.php'],
            tasks: ['caketest'],
            options: {
              nospawn: true
            }
          }
        }
    });

    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');

    grunt.registerTask('caketest', ['shell:caketest']);
    grunt.registerTask('coverage', ['clean:coverage', 'shell:coverage']);
};