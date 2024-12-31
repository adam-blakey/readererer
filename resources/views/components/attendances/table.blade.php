@props(['attendances'])

<div class="table-responsive">
    <table class="table table-vcenter card-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Editing user</th>
                <th>Updated at</th>
                <th>Term date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->edit_user->name }}</td>
                    <td>{{ $attendance->updated_at }}</td>
                    <td>{{ $attendance->term_date->start_datetime }}</td>
                    <td>{{ $attendance->status_text }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
