<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDressCodeRequest;
use App\Models\DressCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DressCodeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DressCode::class, 'dressCode');
    }

    public function index()
    {
        $query = DressCode::query();
        $query->when(request('with_trashed'), fn ($query) => $query->withTrashed());
        $dress_codes = $query->autosort()->paginate(per_page())->withQueryString();

        return view('auto-entities.index', [
            'entities' => $dress_codes,
            'page_name' => __('Dress codes'),
            'page_subname' => __('Dress codes overview'),
            'create_entity' => [
                'route' => 'dresscodes.create',
                'name' => __('dress code'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('auto-entities.form', [
            'page_name' => __('Dress codes'),
            'page_subname' => __('Create new dress code'),
            'update' => false,
            'fields' => get_create_fields(new DressCode),
            'form_route' => route('dresscodes.store'),
        ]);
    }

    public function store(StoreDressCodeRequest $request): RedirectResponse
    {
        $dress_code = DressCode::create($request->validated());

        return to_route('dresscodes.show', $dress_code);
    }

    public function show(DressCode $dressCode)
    {
        return view('auto-entities.show', [
            'entity' => $dressCode,
            'page_name' => __('Dress codes'),
            'page_subname' => __('Dress code :name', ['name' => $dressCode->name]),
            'edit_route' => 'dresscodes.edit',
            'destroy_route' => 'dresscodes.destroy',
            'restore_route' => 'dresscodes.restore',
        ]);
    }

    public function edit(DressCode $dressCode): View
    {
        return view('auto-entities.form', [
            'page_name' => __('Dress codes'),
            'page_subname' => __('Update dress code'),
            'update' => true,
            'fields' => get_create_fields($dressCode),
            'form_route' => route('dresscodes.update', $dressCode),
        ]);
    }

    public function update(StoreDressCodeRequest $request, DressCode $dressCode): RedirectResponse
    {
        $dressCode->update($request->validated());

        return to_route('dresscodes.show', $dressCode);
    }

    public function destroy(DressCode $dressCode)
    {
        $dressCode->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(DressCode $dressCode)
    {
        $dressCode->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
