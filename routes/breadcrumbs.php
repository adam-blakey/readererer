<?php

use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| Breadcrumbs
|--------------------------------------------------------------------------
|
| One definition per named GET route, keyed by that route's name. The page
| header renders the trail for the current route (see
| resources/views/components/page-header.blade.php); a route without a
| definition here — the auth pages, for instance — simply shows no trail.
|
*/

Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Dashboard', route('dashboard'));
});

Breadcrumbs::for('notifications.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Notifications', route('notifications.index'));
});

Breadcrumbs::for('settings.edit', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Settings', route('settings.edit'));
});

/*
 * The generic CRUD resources rendered by resources/views/auto-entities/ all
 * share the same shape — Home ▸ Composers ▸ Rachmaninoff ▸ Edit — so register
 * their index/create/show/edit trails from one definition. Every model exposes
 * a `name` attribute, which is what the show and edit crumbs are labelled by.
 */
$auto_entity = function (string $route_prefix, string $index_label, string $create_label): void {
    Breadcrumbs::for($route_prefix.'.index', function (BreadcrumbTrail $trail) use ($route_prefix, $index_label) {
        $trail->parent('home');
        $trail->push($index_label, route($route_prefix.'.index'));
    });

    Breadcrumbs::for($route_prefix.'.create', function (BreadcrumbTrail $trail) use ($route_prefix, $create_label) {
        $trail->parent($route_prefix.'.index');
        $trail->push($create_label, route($route_prefix.'.create'));
    });

    Breadcrumbs::for($route_prefix.'.show', function (BreadcrumbTrail $trail, Model $entity) use ($route_prefix) {
        $trail->parent($route_prefix.'.index');
        $trail->push($entity->name, route($route_prefix.'.show', $entity));
    });

    Breadcrumbs::for($route_prefix.'.edit', function (BreadcrumbTrail $trail, Model $entity) use ($route_prefix) {
        $trail->parent($route_prefix.'.show', $entity);
        $trail->push('Edit', route($route_prefix.'.edit', $entity));
    });
};

$auto_entity('composers', 'Composers', 'New composer');
$auto_entity('ensembles', 'Ensembles', 'New ensemble');
$auto_entity('instrumentfamilys', 'Instrument families', 'New instrument family');
$auto_entity('pieces', 'Pieces', 'New piece');
$auto_entity('setlists', 'Setlists', 'New setlist');
$auto_entity('setupgroups', 'Setup groups', 'New setup group');
$auto_entity('terms', 'Terms', 'New term');
$auto_entity('users', 'Users', 'New user');

Breadcrumbs::for('ensembles.members', function (BreadcrumbTrail $trail, Ensemble $ensemble) {
    $trail->parent('ensembles.show', $ensemble);
    $trail->push('Members', route('ensembles.members', $ensemble));
});

Breadcrumbs::for('ensembles.seating-plan.show', function (BreadcrumbTrail $trail, Ensemble $ensemble) {
    $trail->parent('ensembles.show', $ensemble);
    $trail->push('Seating plan', route('ensembles.seating-plan.show', $ensemble));
});

Breadcrumbs::for('attendance.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Attendance updates', route('attendance.index'));
});

Breadcrumbs::for('attendance.poll', function (BreadcrumbTrail $trail, Ensemble $ensemble, Term $term) {
    $trail->parent('attendance.index');
    $trail->push($ensemble->name.': '.$term->name, route('attendance.poll', [$ensemble, $term]));
});

Breadcrumbs::for('attendance.register.index', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Attendance registers', route('attendance.register.index'));
});

Breadcrumbs::for('attendance.register.show', function (BreadcrumbTrail $trail, Ensemble $ensemble, TermDate $term_date) {
    $trail->parent('attendance.register.index');
    $trail->push($ensemble->name.': '.$term_date->date_label, route('attendance.register.show', [$ensemble, $term_date]));
});
