@props(['ensembles'])

<div class="table-responsive">
    <table class="table table-vcenter card-table">
        <thead>
            <tr>
                <th>Ensemble name</th>
                <th>Slug</th>
                <th>Image</th>
                <th>Visible</th>
                <th>Admins</th>
                <th class="w-1"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ensembles as $ensemble)
                <tr>
                    <td>{{ $ensemble->name }}</td>
                    <td>{{ $ensemble->slug }}</td>
                    <td>
                        <img src="{{ $ensemble->image }}" alt="{{ $ensemble->name }}" class="rounded" style="width: 50px;">
                    </td>
                    <td>{{ $ensemble->visible == 1 ? 'Y' : 'N' }}</td>
                    <td>
                        @foreach ($ensemble->admins as $admin)
                            <a href="/users/{{ $admin->id }}"
                                class="text-reset">{{ $admin->name }}</a>{{ $loop->last ? '' : ',' }}
                        @endforeach
                    </td>
                    <td>
                        <a href="/ensembles/{{ $ensemble->id }}/edit">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
