set :app_name,      "dcomms"
set :location,      "dcomms.qa.previousnext.com.au"
set :application,   "dcomms.qa.previousnext.com.au"
set :repository,    "git@github.com:previousnext/#{app_name}.git"
set :user,          "deployer"
set :runner,        "deployer"
set :branch,        "master"
set :port,          22
set :default_stage, "staging"
set :use_sudo,      false
ssh_options[:forward_agent] = true

before "drupal:symlink_shared", "govcms:build"

namespace :govcms do
  desc "Build the GovCMS subtheme site."
  task :build do
    run "cd #{release_path} && composer install --prefer-dist"
    run "cd #{release_path} && bin/phing drush:init"
    run "cd #{release_path} && bin/phing build"
    run "cd #{release_path} && bin/phing registry:rebuild"
  end
end
