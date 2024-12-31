@props(['users'])

<div class="table-responsive">
    <table class="table table-vcenter card-table">
        <thead>
            <tr>
                <th>Name</th>
                <th class="w-1"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>
                        <a href="/users/{{ $user->id }}/edit">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
