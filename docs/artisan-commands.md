# Full Artisan Command List

Generated from `php artisan list --raw`. This is the complete list of every Artisan command available in this installation, including Laravel framework and package commands.

```text
about                            Display basic information about your application
clear-compiled                   Remove the compiled class file
completion                       Dump the shell completion script
db                               Start a new database CLI session
docs                             Access the Laravel documentation
down                             Put the application into maintenance / demo mode
env                              Display the current framework environment
help                             Display help for a command
inspire                          Display an inspiring quote
list                             List commands
migrate                          Run the database migrations
optimize                         Cache framework bootstrap, configuration, and metadata to increase performance
pail                             Tails the application logs
reload                           Reload running services
serve                            Serve the application on the PHP development server
test                             Run the application tests
tinker                           Interact with your application
up                               Bring the application out of maintenance mode
app:export-api-json              Export the documented API endpoints to a Postman collection JSON file.
app:init-module                  Create a full module scaffold with model, repository, requests, resource, DTO, controller, policy, and documentation case
app:init-sub-module              Create a sub-resource scaffold inside an existing module
app:init-test                    Run a module documentation case and store its real API response in the documentation database
auth:clear-resets                Flush expired password reset tokens
boost:add-skill                  Add skills from a remote GitHub repository
boost:install                    
boost:list-skills                List all available skills in the current project
boost:mcp                        Starts Laravel Boost (usually from mcp.json)
boost:update                     Update the Laravel Boost guidelines & skills to the latest guidance
cache:clear                      Flush the application cache
cache:forget                     Remove an item from the cache
cache:prune-stale-tags           Prune stale cache tags from the cache (Redis only)
channel:list                     List all registered private broadcast channels
config:cache                     Create a cache file for faster configuration loading
config:clear                     Remove the configuration cache file
config:publish                   Publish configuration files to your application
config:show                      Display all of the values for a given configuration file or key
db:monitor                       Monitor the number of connections on the specified database
db:seed                          Seed the database with records
db:show                          Display information about the given database
db:table                         Display information about the given database table
db:wipe                          Drop all tables, views, and types
env:decrypt                      Decrypt an environment file
env:encrypt                      Encrypt an environment file
event:cache                      Discover and cache the application's events and listeners
event:clear                      Clear all cached events and listeners
event:list                       List the application's events and listeners
ide-helper:eloquent              Add \Eloquent helper to \Eloquent\Model
ide-helper:generate              Generate a new IDE Helper file.
ide-helper:meta                  Generate metadata for PhpStorm
ide-helper:models                Generate autocompletion for models
install:api                      Create an API routes file and install Laravel Sanctum or Laravel Passport
install:broadcasting             Create a broadcasting channel routes file
key:generate                     Set the application key
lang:publish                     Publish all language files that are available for customization
make:cache-table                 Create a migration for the cache database table
make:cast                        Create a new custom Eloquent cast class
make:channel                     Create a new channel class
make:class                       Create a new class
make:command                     Create a new Artisan command
make:component                   Create a new view component class
make:config                      Create a new configuration file
make:controller                  Create a new controller class
make:dto                         Generate a new DTO class file
make:enum                        Create a new enum
make:event                       Create a new event class
make:exception                   Create a new custom exception class
make:export                      Create a new export class
make:factory                     Create a new model factory
make:import                      Create a new import class
make:interface                   Create a new interface
make:job                         Create a new job class
make:job-middleware              Create a new job middleware class
make:listener                    Create a new event listener class
make:mail                        Create a new email class
make:mcp-app-resource            Create a new MCP app resource class and linked view
make:mcp-prompt                  Create a new MCP prompt class
make:mcp-resource                Create a new MCP resource class
make:mcp-server                  Create a new MCP server class
make:mcp-tool                    Create a new MCP tool class
make:middleware                  Create a new HTTP middleware class
make:migration                   Create a new migration file
make:model                       Create a new Eloquent model class
make:notification                Create a new notification class
make:notifications-table         Create a migration for the notifications table
make:observer                    Create a new observer class
make:policy                      Create a new policy class
make:provider                    Create a new service provider class
make:queue-batches-table         Create a migration for the batches database table
make:queue-failed-table          Create a migration for the failed queue jobs database table
make:queue-table                 Create a migration for the queue jobs database table
make:request                     Create a new form request class
make:resource                    Create a new resource
make:rule                        Create a new validation rule
make:scope                       Create a new scope class
make:seeder                      Create a new seeder class
make:session-table               Create a migration for the session database table
make:test                        Create a new test class
make:test-case                   Create the documentation cases file for a module
make:trait                       Create a new trait
make:view                        Create a new view
mcp:inspector                    Open the MCP Inspector tool to debug and test MCP Servers
mcp:start                        Start the MCP Server for a given handle
migrate:fresh                    Drop all tables and re-run all migrations
migrate:install                  Create the migration repository
migrate:refresh                  Reset and re-run all migrations
migrate:reset                    Rollback all database migrations
migrate:rollback                 Rollback the last database migration
migrate:status                   Show the status of each migration
model:prune                      Prune models that are no longer needed
model:show                       Show information about an Eloquent model
module:composer-update           update autoload of composer.json file
module:delete                    Delete a module from the application
module:disable                   Disable an array of modules.
module:dump                      Dump-autoload the specified module or for all module.
module:enable                    Enable the specified module.
module:install                   Install the specified module by given package name (vendor/name).
module:lang                      Check missing language keys in the specified module.
module:list                      Show list of all modules.
module:list-commands             List all commands in the specified module(s)
module:make                      Create a new module.
module:make-action               Create a new action class for the specified module.
module:make-cast                 Create a new Eloquent cast class for the specified module.
module:make-channel              Create a new channel class for the specified module.
module:make-class                Create a new class
module:make-command              Generate new Artisan command for the specified module.
module:make-component            Create a new component-class for the specified module.
module:make-component-view       Create a new component-view for the specified module.
module:make-controller           Generate new restful controller for the specified module.
module:make-enum                 Create a new enum class for the specified module.
module:make-event                Create a new event class for the specified module
module:make-event-provider       Create a new event service provider class for the specified module.
module:make-exception            Create a new exception class for the specified module.
module:make-factory              Create a new model factory for the specified module.
module:make-helper               Create a new helper class for the specified module.
module:make-inertia-component    Create a new Inertia component for the specified module.
module:make-inertia-page         Create a new Inertia page for the specified module.
module:make-interface            Create a new interface class for the specified module.
module:make-job                  Create a new job class for the specified module
module:make-listener             Create a new event listener class for the specified module
module:make-mail                 Create a new email class for the specified module
module:make-middleware           Create a new middleware class for the specified module.
module:make-migration            Create a new migration for the specified module.
module:make-model                Create a new model for the specified module.
module:make-notification         Create a new notification class for the specified module.
module:make-observer             Create a new observer for the specified module.
module:make-policy               Create a new policy class for the specified module.
module:make-provider             Create a new service provider class for the specified module.
module:make-replacement          Create a new replacement key command class for stubs.
module:make-repository           Create a new repository class for the specified module.
module:make-request              Create a new form request class for the specified module.
module:make-resource             Create a new resource class for the specified module.
module:make-rule                 Create a new validation rule for the specified module.
module:make-scope                Create a new scope class for the specified module.
module:make-seed                 Create a new seeder for the specified module.
module:make-service              Create a new service class for the specified module.
module:make-test                 Create a new test class for the specified module.
module:make-trait                Create a new trait class for the specified module.
module:make-view                 Create a new view for the specified module.
module:migrate                   Migrate the migrations from the specified module or from all modules.
module:migrate-fresh             Reset all database tables and re-run the modules migrations.
module:migrate-refresh           Rollback & re-migrate the modules migrations.
module:migrate-reset             Reset the modules migrations.
module:migrate-rollback          Rollback the modules migrations.
module:migrate-status            Status for all module migrations
module:model-show                Show information about an Eloquent model in modules
module:prune                     Prune models by module that are no longer needed
module:publish                   Publish a module's assets to the application
module:publish-config            Publish a module's config files to the application
module:publish-inertia           Publish the Inertia app.js to resources/js, configured to resolve pages from all modules.
module:publish-migration         Publish a module's migrations to the application
module:publish-translation       Publish a module's translations to the application
module:route-provider            Create a new route service provider for the specified module.
module:seed                      Run database seeder from the specified module or from all modules.
module:setup                     Setting up modules folders for first use.
module:unuse                     Forget the used module with module:use
module:update                    Update dependencies for the specified module or for all modules.
module:update-phpunit-coverage   Update phpunit.xml source/include path with enabled modules
module:use                       Use the specified module.
module:v6:migrate                Migrate laravel-modules v5 modules statuses to v6.
optimize:clear                   Remove the cached bootstrap files
package:discover                 Rebuild the cached package manifest
queue:clear                      Delete all of the jobs from the specified queue
queue:failed                     List all of the failed queue jobs
queue:flush                      Flush all of the failed queue jobs
queue:forget                     Delete a failed queue job
queue:listen                     Listen to a given queue
queue:monitor                    Monitor the size of the specified queues
queue:pause                      Pause job processing for a specific queue
queue:prune-batches              Prune stale entries from the batches database
queue:prune-failed               Prune stale entries from the failed jobs table
queue:restart                    Restart queue worker daemons after their current job
queue:resume                     Resume job processing for a paused queue
queue:retry                      Retry a failed queue job
queue:retry-batch                Retry the failed jobs for a batch
queue:work                       Start processing jobs on the queue as a daemon
reverb:install                   Install the Reverb dependencies
reverb:restart                   Restart the Reverb server
reverb:start                     Start the Reverb server
roster:scan                      Detect packages, stacks, frameworks, agents, and approaches in use and output as JSON
route:cache                      Create a route cache file for faster route registration
route:clear                      Remove the route cache file
route:list                       List all registered routes
sail:add                         Add a service to an existing Sail installation
sail:install                     Install Laravel Sail's default Docker Compose file
sail:publish                     Publish the Laravel Sail Docker files
sanctum:prune-expired            Prune tokens expired for more than specified number of hours
schedule:clear-cache             Delete the cached mutex files created by scheduler
schedule:interrupt               Interrupt the current schedule run
schedule:list                    List all scheduled tasks
schedule:run                     Run the scheduled commands
schedule:test                    Run a scheduled command
schedule:work                    Start the schedule worker
schema:dump                      Dump the given database schema
scribe:config:diff               Dump your changed config to the console. Use this when posting bug reports
scribe:generate                  Generate API documentation from your Laravel routes.
scribe:strategy                  Create a new strategy class.
security:api-key                 Manage API client keys — create, rotate, renew, list, or revoke
storage:link                     Create the symbolic links configured for the application
storage:unlink                   Delete existing symbolic links configured for the application
stub:publish                     Publish all stubs that are available for customization
telescope:clear                  Delete all Telescope data from storage
telescope:install                Install all of the Telescope resources
telescope:list                   List Telescope entries
telescope:pause                  Pause all Telescope watchers
telescope:prune                  Prune stale entries from the Telescope database
telescope:publish                Publish all of the Telescope resources
telescope:resume                 Unpause all Telescope watchers
telescope:show                   Show a Telescope entry with full batch context
vendor:publish                   Publish any publishable assets from vendor packages
view:cache                       Compile all of the application's Blade templates
view:clear                       Clear all compiled view files
```
