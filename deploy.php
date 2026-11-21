<?php

namespace Deployer;

require 'recipe/common.php';

host('site')
    ->setHostname((string) getenv('DEPLOY_SSH_HOST'))
    ->setPort((int) getenv('DEPLOY_SSH_PORT'))
    ->setRemoteUser((string) getenv('DEPLOY_SSH_USER'))
    ->setIdentityFile('~/.ssh/deploy')
    ->setSshArguments(['-o StrictHostKeyChecking=no'])
    ->set('deploy_path', fn () => run('echo $HOME').'/domains/'.getenv('APP_DOMAIN'));

set('keep_releases', 3);
set('shared_dirs', ['storage']);
set('shared_files', ['.env']);
set('fresh_database', getenv('FRESH_DATABASE') === 'true');

task('readererer:check_layout', function () {
    if (! test('[ -s {{deploy_path}}/shared/.env ]')) {
        throw error('No {{deploy_path}}/shared/.env. Move the site to the release layout (see "Moving dev or qa to Deployer" in .github/DEPLOYMENTS.md), then deploy again.');
    }
});

task('deploy:update_code', function () {
    upload(__DIR__.'/', '{{release_path}}', ['progress_bar' => false, 'options' => [
        '--exclude=/.git', '--exclude=/.github', '--exclude=/docs', '--exclude=/site', '--exclude=/tests', '--exclude=/node_modules',
    ]]);
});

task('readererer:check_app_key', function () {
    cd('{{release_path}}');
    run(<<<'SH'
        php -r 'require $argv[1]; $key = Dotenv\Dotenv::createArrayBacked(".")->load()["APP_KEY"] ?? ""; if (str_starts_with($key, "base64:")) $key = base64_decode(substr($key, 7)); if (! Illuminate\Encryption\Encrypter::supported($key, "aes-256-cbc")) { fwrite(STDERR, "APP_KEY in shared/.env is missing or invalid. In the current release run php artisan config:clear and php artisan key:generate --force (or restore the old key), then deploy again.\n"); exit(1); }' vendor/autoload.php
        SH);
});

task('readererer:migrate', function () {
    cd('{{release_path}}');
    run('php artisan down --retry=5 --refresh=5');
    run(get('fresh_database') ? 'php artisan migrate:fresh --seed --force' : 'php artisan migrate --force');
});

task('readererer:cache', function () {
    cd('{{release_path}}');
    run('ln -sfn ../storage/app/public public/storage');
    run('php artisan optimize');
    run('php artisan event:cache');
});

task('readererer:up', function () {
    if (! test('[ -f {{deploy_path}}/shared/storage/framework/down ]')) {
        return;
    }
    cd('{{release_or_current_path}}');
    run('php artisan up');
    run('php artisan queue:restart');
});

task('deploy', [
    'deploy:info',
    'readererer:check_layout',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'readererer:check_app_key',
    'readererer:migrate',
    'readererer:cache',
    'deploy:symlink',
    'readererer:up',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
]);

after('deploy:failed', 'readererer:up');
after('deploy:failed', 'deploy:unlock');
