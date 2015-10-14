set :deploy_to, '/var/www'
set :branch,    'master'
role :app,      "#{app_name}.qa.previousnext.com.au"
set :app_path,  "#{release_path}/app"
set :port,      '11079'

namespace :govcms do
  desc "Sync the staging site."
  task :sync_staging do
    run "cd #{release_path} && composer install --prefer-dist"
    run "cd #{release_path} && bin/phing drush:init"
    run "cd #{release_path} && bin/phing database:sync"
    run "cd #{release_path} && bin/phing files:sync"
  end
end
