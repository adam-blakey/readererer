@servers(['test_server' => '-A ' . $unix_username . '@' . $server_domain . ' -p ' . $server_port])

@setup
    /*
    |--------------------------------------------------------------------------
    | Variables we take branch and domain form envoy command
    |--------------------------------------------------------------------------
    */
    $root = $unix_username;
    $repository = 'git@gitlab.com:adam.blakey/readererer.git';
    $venv = '/home/'.$unix_username.'/nodevenv/public_html/'.$domain.'/20/bin/activate';

    /*
    |--------------------------------------------------------------------------
    | Fixed
    |--------------------------------------------------------------------------
    */
    $app_dir = '/home/' . $root . '/public_html/' . $domain ;
    $resources_dir = '/resources/' . $domain .'/';
    $keep = 4;

    function logMessage($message){return "echo '\033[32m" . $message . "\033[0m';\n";}
@endsetup

@story('deploy', ['on' => 'test_server'])
    git_pull
    artisan_down
    run_npm_install
    compiling_assets
    run_composer
    optimize_and_migrate
    artisan_up
    application_deployed
@endstory

@task('git_pull')
    {{ logMessage('🐛  Pulling changes...') }}
    cd {{ $app_dir }}
    git fetch origin {{ $branch }}
    git fetch --tags
    git reset --hard origin/{{ $branch }}
@endtask

@task('artisan_down')
    {{ logMessage('🔒  Putting Application into maintenance mode...') }}
    cd {{ $app_dir }}
    php artisan down
@endtask

@task('run_npm_install')
    {{ logMessage('📦  Running npm...') }}
    cd {{ $app_dir }}
    @if (!empty($venv))
        source {{ $venv }}
    @endif
    npm install
@endtask

@task('compiling_assets')
    {{ logMessage('🌅  Compiling assets...') }}
    cd {{ $app_dir }}
    @if (!empty($venv))
        source {{ $venv }}
    @endif
    npm install vite
    npm run build
@endtask

@task('run_composer')
    {{ logMessage('🚚  Running Composer...') }}
    cd {{ $app_dir }}
    composer install --no-interaction --no-dev --prefer-dist
@endtask

@task('optimize_and_migrate')
    {{ logMessage('✨  Optimizing installation and migrating database...') }}
    cd {{ $app_dir }}
    php artisan clear-compiled --env=production
    php artisan optimize --env=production
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache

    php artisan migrate --force
    php artisan cache:clear
    php artisan queue:restart
@endtask

@task('artisan_up')
    {{ logMessage('🔓  Bringing Application out of maintenance mode...') }}
    cd {{ $app_dir }}
    php artisan up
@endtask

@task('application_deployed')
    {{ logMessage('🚀  Application deployed!') }}
@endtask
