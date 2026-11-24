{{-- "+3 pts" / "-4 pts" next to a rate. Expects $change (int). --}}
@if ($change > 0)
    <span class="up">+{{ $change }} pts</span>
@elseif ($change < 0)
    <span class="down">{{ $change }} pts</span>
@else
    <span class="small">no change</span>
@endif
