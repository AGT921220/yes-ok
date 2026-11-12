<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityBlock\StoreAvailabilityBlockRequest;
use App\Models\AvailabilityBlock;
use App\Support\DayCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BlockPageController extends Controller
{
    public function index(Request $request)
    {
        $month = DayCalendar::month($request->input('month', now()->format('Y-m')));
        $selected = $request->input('date', $month->isSameMonth(now()) ? now()->toDateString() : $month->toDateString());
        if (Carbon::parse($selected)->format('Y-m') !== $month->format('Y-m')) {
            $selected = $month->toDateString();
        }

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $blocks = AvailabilityBlock::query()->orderByDesc('start_date')->get();

        return view('blocks.index', [
            'blocks' => $blocks,
            'month' => $month,
            'selected' => $selected,
            'weeks' => DayCalendar::weeks($month, collect()),
            'blocked' => DayCalendar::blockMap($blocks, $gridStart, $gridEnd),
            'duration' => null,
            'view' => 'month',
        ]);
    }

    public function store(StoreAvailabilityBlockRequest $request)
    {
        AvailabilityBlock::create($request->validated() + [
            'created_by' => $request->user()->id,
            'is_full_day' => $request->boolean('is_full_day'),
        ]);

        return redirect()
            ->route('blocks.index', ['date' => $request->input('start_date'), 'month' => substr((string) $request->input('start_date'), 0, 7)])
            ->with('success', 'Día bloqueado.');
    }

    public function destroy(AvailabilityBlock $block)
    {
        $block->delete();

        return back()->with('success', 'Bloqueo eliminado.');
    }
}
