@extends('reports.pdf.layout')

@section('content')
    <table class="cards">
        <tr>
            @foreach (['task_completion_rate' => 'Task Completion Rate', 'project_completion_rate' => 'Projects Completion Rate', 'team_activity_rate' => 'Teams Activity Rate'] as $key => $label)
                <td>
                    <div class="small">{{ $label }}</div>
                    <div class="big">{{ $data[$key]['value'] }}%</div>
                    @include('reports.pdf.partials.change', ['change' => $data[$key]['change']])
                </td>
            @endforeach
        </tr>
    </table>

    <h2>Task Completion Time vs Deadline</h2>
    <table>
        <tr><th>Month</th><th>Completed</th><th>On time</th><th>Late</th><th>On-time rate</th></tr>
        @foreach ($data['task_time_vs_deadline'] as $row)
            <tr>
                <td>{{ $row['month'] }}</td>
                <td>{{ $row['completed'] }}</td>
                <td>{{ $row['on_time'] }}</td>
                <td>{{ $row['late'] }}</td>
                <td>{{ $row['on_time_rate'] }}%</td>
            </tr>
        @endforeach
    </table>

    <h2>Projects Progress &amp; Worker Activity</h2>
    <table>
        <tr><th>Month</th><th>Progress</th><th>Completed tasks</th><th>Active members</th></tr>
        @foreach ($data['projects_progress'] as $index => $row)
            <tr>
                <td>{{ $row['month'] }}</td>
                <td>{{ $row['progress'] }}%</td>
                <td>{{ $data['worker_activity'][$index]['completed_tasks'] }}</td>
                <td>{{ $data['worker_activity'][$index]['active_members'] }}</td>
            </tr>
        @endforeach
    </table>
@endsection
