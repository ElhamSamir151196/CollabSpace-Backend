@extends('reports.pdf.layout')

@section('content')
    <table class="cards">
        <tr>
            @foreach (['total' => 'Total', 'active' => 'Active', 'delayed' => 'Delayed', 'completed' => 'Completed'] as $key => $label)
                <td><div class="small">{{ $label }}</div><div class="big">{{ $data['summary'][$key] }}</div></td>
            @endforeach
            <td><div class="small">Completion rate</div><div class="big">{{ $data['summary']['completion_rate'] }}%</div></td>
        </tr>
    </table>

    <h2>Projects</h2>
    <table>
        <tr><th>Project</th><th>Status</th><th>Manager</th><th>Deadline</th><th>Progress</th><th>Tasks</th><th>Overdue tasks</th></tr>
        @forelse ($data['projects'] as $project)
            <tr>
                <td dir="auto">{{ $project['name'] }}</td>
                <td>{{ $project['status']['label'] }}</td>
                <td dir="auto">{{ $project['manager']['name'] ?? '-' }}</td>
                <td>{{ $project['deadline'] }}@if ($project['is_overdue']) <span class="down">(late)</span>@endif</td>
                <td>{{ $project['progress'] }}%</td>
                <td>{{ $project['tasks_completed'] }} / {{ $project['tasks_total'] }}</td>
                <td>{{ $project['tasks_overdue'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="small">No projects.</td></tr>
        @endforelse
    </table>
@endsection
