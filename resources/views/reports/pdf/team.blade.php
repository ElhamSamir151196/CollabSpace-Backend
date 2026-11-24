@extends('reports.pdf.layout')

@section('content')
    <h2>Teams</h2>
    <table>
        <tr>
            <th>Team</th><th>Members</th><th>Active projects</th><th>Assigned</th>
            <th>Completed</th><th>Open</th><th>Overdue</th><th>Completion</th><th>Open / member</th>
        </tr>
        @forelse ($data as $team)
            <tr>
                <td dir="auto">{{ $team['name'] }}</td>
                <td>{{ $team['members_count'] }}</td>
                <td>{{ $team['active_projects'] }}</td>
                <td>{{ $team['assigned'] }}</td>
                <td>{{ $team['completed'] }}</td>
                <td>{{ $team['open'] }}</td>
                <td>{{ $team['overdue'] }}</td>
                <td>{{ $team['completion_rate'] }}%</td>
                <td>{{ $team['open_tasks_per_member'] }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="small">No teams.</td></tr>
        @endforelse
    </table>
@endsection
