@extends('reports.pdf.layout')

@section('content')
    <table class="cards">
        <tr>
            <td><div class="small">Total</div><div class="big">{{ $data['summary']['total'] }}</div></td>
            <td><div class="small">Completed</div><div class="big">{{ $data['summary']['completed'] }}</div></td>
            <td><div class="small">Overdue</div><div class="big">{{ $data['summary']['overdue'] }}</div></td>
            <td><div class="small">On-time rate</div><div class="big">{{ $data['summary']['on_time_rate'] }}%</div></td>
            <td><div class="small">Avg. days to complete</div><div class="big">{{ $data['summary']['average_days_to_complete'] ?? '-' }}</div></td>
        </tr>
    </table>

    <h2>By status</h2>
    <table>
        <tr><th>Status</th><th>Tasks</th></tr>
        @foreach ($data['by_status'] as $row)
            <tr><td>{{ $row['status']['label'] }}</td><td>{{ $row['count'] }}</td></tr>
        @endforeach
    </table>

    <h2>By priority</h2>
    <table>
        <tr><th>Priority</th><th>Tasks</th></tr>
        @foreach ($data['by_priority'] as $row)
            <tr><td>{{ ucfirst($row['priority']) }}</td><td>{{ $row['count'] }}</td></tr>
        @endforeach
    </table>

    <h2>Completed per month</h2>
    <table>
        <tr><th>Month</th><th>Completed</th><th>On time</th><th>Late</th></tr>
        @foreach ($data['completed_per_month'] as $row)
            <tr><td>{{ $row['month'] }}</td><td>{{ $row['completed'] }}</td><td>{{ $row['on_time'] }}</td><td>{{ $row['late'] }}</td></tr>
        @endforeach
    </table>
@endsection
