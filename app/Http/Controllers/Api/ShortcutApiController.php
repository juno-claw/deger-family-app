<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\FamilyList;
use App\Models\Note;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortcutApiController extends Controller
{
    /** @var array<string, string> */
    private const CATEGORY_MAP = [
        'cooking' => 'Kochen',
        'baking' => 'Backen',
        'dessert' => 'Dessert',
        'snack' => 'Snack',
        'drink' => 'Getränk',
    ];

    /** @var array<int, string> */
    private const GERMAN_DAYS = [
        0 => 'So',
        1 => 'Mo',
        2 => 'Di',
        3 => 'Mi',
        4 => 'Do',
        5 => 'Fr',
        6 => 'Sa',
    ];

    /**
     * GET /api/v1/shortcuts/einkauf?user=
     */
    public function einkauf(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        return $this->formatList($user, 'shopping', '🛒', 'Einkaufsliste');
    }

    /**
     * GET /api/v1/shortcuts/todo?user=
     */
    public function todo(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        return $this->formatList($user, 'todo', '📋', 'Todo-Liste');
    }

    /**
     * GET /api/v1/shortcuts/kalender?user=
     */
    public function kalender(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $today = Carbon::today();
        $endOfWeek = Carbon::today()->addDays(7)->endOfDay();

        $events = CalendarEvent::accessibleBy($user)
            ->whereBetween('start_at', [$today, $endOfWeek])
            ->orderBy('start_at')
            ->get();

        if ($events->isEmpty()) {
            return response()->json(['text' => '📅 Keine Termine in den nächsten 7 Tagen.']);
        }

        $lines = ['📅 Termine (nächste 7 Tage)'];

        $grouped = $events->groupBy(fn (CalendarEvent $event) => $event->start_at->toDateString());

        foreach ($grouped as $dateString => $dayEvents) {
            $date = Carbon::parse($dateString);
            $lines[] = '';
            $lines[] = $this->formatDayLabel($date).':';

            foreach ($dayEvents as $event) {
                if ($event->all_day) {
                    $lines[] = '  📌 Ganztägig: '.$event->title;
                } else {
                    $timeStr = $event->start_at->format('H:i');
                    if ($event->end_at) {
                        $timeStr .= '–'.$event->end_at->format('H:i');
                    }
                    $lines[] = '  🕐 '.$timeStr.' '.$event->title;
                }
            }
        }

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * GET /api/v1/shortcuts/notizen?user=&format=
     */
    public function notizen(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $notes = Note::accessibleBy($user)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['text' => '📝 Keine Notizen vorhanden.']);
        }

        if ($request->query('format') === 'buttons') {
            $items = $notes->map(fn (Note $note) => [
                'id' => $note->id,
                'label' => ($note->is_pinned ? '📌' : '📝').' '.$note->title,
            ])->values()->all();

            return response()->json([
                'text' => '📝 Notizen',
                'items' => $items,
            ]);
        }

        $lines = ['📝 Notizen', ''];

        foreach ($notes as $note) {
            $icon = $note->is_pinned ? '📌' : '📝';
            $lines[] = $icon.' '.$note->title;
        }

        $lines[] = '';
        $lines[] = $notes->count().' '.($notes->count() === 1 ? 'Notiz' : 'Notizen').' gesamt';

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * GET /api/v1/shortcuts/notiz/{id}?user=
     */
    public function notiz(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);

        $note = Note::accessibleBy($user)->find($id);

        if (! $note) {
            return response()->json(['text' => '⚠️ Notiz nicht gefunden.'], 404);
        }

        $lines = ['📝 '.$note->title];

        if ($note->content) {
            $lines[] = '';
            $lines[] = $note->content;
        }

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * GET /api/v1/shortcuts/rezepte?user=&format=
     */
    public function rezepte(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $recipes = Recipe::accessibleBy($user)
            ->orderByDesc('is_favorite')
            ->orderBy('title')
            ->get();

        if ($recipes->isEmpty()) {
            return response()->json(['text' => '👨‍🍳 Keine Rezepte vorhanden.']);
        }

        if ($request->query('format') === 'buttons') {
            $items = $recipes->map(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'label' => ($recipe->is_favorite ? '⭐' : '📖').' '.$recipe->title,
            ])->values()->all();

            return response()->json([
                'text' => '👨‍🍳 Rezepte',
                'items' => $items,
            ]);
        }

        $lines = ['👨‍🍳 Rezepte', ''];

        foreach ($recipes as $recipe) {
            $icon = $recipe->is_favorite ? '⭐' : '📖';
            $category = self::CATEGORY_MAP[$recipe->category] ?? $recipe->category;
            $totalTime = ($recipe->prep_time ?? 0) + ($recipe->cook_time ?? 0);
            $timeStr = $totalTime > 0 ? ', '.$totalTime.' Min.' : '';
            $lines[] = $icon.' '.$recipe->title.' ('.$category.$timeStr.')';
        }

        $lines[] = '';
        $lines[] = $recipes->count().' '.($recipes->count() === 1 ? 'Rezept' : 'Rezepte').' gesamt';

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * GET /api/v1/shortcuts/rezept/{id}?user=
     */
    public function rezept(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);

        $recipe = Recipe::accessibleBy($user)->find($id);

        if (! $recipe) {
            return response()->json(['text' => '⚠️ Rezept nicht gefunden.'], 404);
        }

        $lines = ['👨‍🍳 '.$recipe->title];

        $timeDetails = $this->formatRecipeTime($recipe);
        if ($timeDetails) {
            $lines[] = '';
            $lines[] = '⏱ '.$timeDetails;
        }

        if ($recipe->servings) {
            $lines[] = '🍽 '.$recipe->servings.' '.($recipe->servings === 1 ? 'Portion' : 'Portionen');
        }

        if ($recipe->ingredients) {
            $lines[] = '';
            $lines[] = '📋 Zutaten:';
            foreach (explode("\n", $recipe->ingredients) as $ingredient) {
                $ingredient = trim($ingredient);
                if ($ingredient !== '') {
                    $lines[] = '• '.$ingredient;
                }
            }
        }

        if ($recipe->instructions) {
            $lines[] = '';
            $lines[] = '👩‍🍳 Zubereitung:';
            $step = 1;
            foreach (explode("\n", $recipe->instructions) as $instruction) {
                $instruction = trim($instruction);
                if ($instruction !== '') {
                    $lines[] = $step.'. '.$instruction;
                    $step++;
                }
            }
        }

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * Format recipe time as a human-readable string.
     */
    private function formatRecipeTime(Recipe $recipe): ?string
    {
        $prep = $recipe->prep_time ?? 0;
        $cook = $recipe->cook_time ?? 0;
        $total = $prep + $cook;

        if ($total === 0) {
            return null;
        }

        if ($prep > 0 && $cook > 0) {
            return $total.' Min. ('.$prep.' Vorbereitung + '.$cook.' Kochen)';
        }

        if ($prep > 0) {
            return $prep.' Min. Vorbereitung';
        }

        return $cook.' Min. Kochen';
    }

    /**
     * Resolve user from ?user= query parameter.
     * Builds email as {user}@deger.family and looks it up.
     */
    private function resolveUser(Request $request): User
    {
        $username = $request->query('user');

        if (! $username || ! is_string($username)) {
            abort(400, 'Parameter "user" ist erforderlich.');
        }

        $email = strtolower($username).'@deger.family';

        $user = User::where('email', $email)->first();

        if (! $user) {
            abort(400, 'Benutzer "'.$username.'" nicht gefunden.');
        }

        return $user;
    }

    /**
     * Format a list (shopping or todo) for a given user.
     */
    private function formatList(User $user, string $type, string $emoji, string $fallbackName): JsonResponse
    {
        $list = FamilyList::accessibleBy($user)
            ->where('type', $type)
            ->latest('updated_at')
            ->with('items')
            ->first();

        if (! $list) {
            return response()->json(['text' => 'Keine '.$fallbackName.' vorhanden.']);
        }

        $items = $list->items->sortBy('is_completed');
        $total = $items->count();
        $completed = $items->where('is_completed', true)->count();

        $lines = [$emoji.' '.$list->title, ''];

        foreach ($items as $item) {
            if ($item->is_completed) {
                $lines[] = '✅ '.$item->content.' (erledigt)';
            } else {
                $lines[] = '◻️ '.$item->content;
            }
        }

        $lines[] = '';
        $lines[] = '📊 '.$completed.' von '.$total.' erledigt';

        return response()->json(['text' => implode("\n", $lines)]);
    }

    /**
     * Format a date as a German day label (Heute, Morgen, or weekday + date).
     */
    private function formatDayLabel(Carbon $date): string
    {
        $today = Carbon::today();

        if ($date->isSameDay($today)) {
            return 'Heute, '.$date->format('d.m.');
        }

        if ($date->isSameDay($today->copy()->addDay())) {
            return 'Morgen, '.$date->format('d.m.');
        }

        $dayName = self::GERMAN_DAYS[$date->dayOfWeek];

        return $dayName.', '.$date->format('d.m.');
    }
}
