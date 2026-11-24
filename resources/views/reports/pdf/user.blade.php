@extends('reports.pdf.layout')

@section('content')
    <h2 dir="auto">{{ $data['user']['name'] }}@if ($data['user']['job_title']) — {{ $data['user']['job_title'] }}@endif</h2>

    <table class="cards">
        <tr>
            <td><div class="small">Productivity score</div><div class="big">{{ $data['productivity_score'] ?? '-' }}</div></td>
            <td><div class="small">Tasks completed</div><div class="big">{{ $data['tasks']['completed'] }} / {{ $data['tasks']['assigned'] }}</div></td>
            <td><div class="small">On-time rate</div><div class="big">{{ $data['tasks']['on_time_rate'] }}%</div></td>
            <td><div class="small">Meeting attendance</div><div class="big">{{ $data['meetings']['attendance_rate'] }}%</div></td>
        </tr>
    </table>

    <h2>Details</h2>
    <table>
        <tr><td>Open tasks</td><td>{{ $data['tasks']['open'] }}</td></tr>
        <tr><td>Overdue tasks</td><td>{{ $data['tasks']['overdue'] }}</td></tr>
        <tr><td>Completed in the period</td><td>{{ $data['tasks']['completed_in_period'] }}</td></tr>
        <tr><td>Files uploaded</td><td>{{ $data['files_uploaded'] }}</td></tr>
        <tr><td>Meetings attended</td><td>{{ $data['meetings']['attended'] }} / {{ $data['meetings']['invited'] }}</td></tr>
    </table>

    <h2>Completed per month</h2>
    <table>
        <tr><th>Month</th><th>Completed tasks</th></tr>
        @foreach ($data['completed_per_month'] as $row)
            <tr><td>{{ $row['month'] }}</td><td>{{ $row['completed'] }}</td></tr>
        @endforeach
    </table>

    <p class="small">Productivity score = 60% completion rate + 30% on-time rate + 10% meeting attendance.</p>
@endsection
