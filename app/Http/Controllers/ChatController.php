<?php
namespace App\Http\Controllers;
use App\Models\{Child, Attempt};
use App\Services\{ModeDetector, TutorService};
use Illuminate\Http\Request;

class ChatController extends Controller {
    public function index() { return view('chat', ['child' => Child::find(session('child_id'))]); }

    public function start(Request $r) {
        $r->validate(['name' => 'required|string|max:30']);
        $child = Child::create(['name' => strip_tags($r->name)]);
        session(['child_id' => $child->id]);
        return redirect('/');
    }

    public function reset() { session()->forget('child_id'); return redirect('/'); }

    private function levelFor(int $xp): int { return $xp >= 15 ? 3 : ($xp >= 6 ? 2 : 1); }

    private function pickUnit(Child $child): array {
        $items = config('lessons.'.$child->level);
        $ids = array_keys($items);
        $q = Attempt::where('child_id', $child->id)->where('level', $child->level);
        $seen = (clone $q)->pluck('item_idx')->unique()->all();
        $weak = (clone $q)->where('correct', false)->latest('id')->take(5)->pluck('item_idx')->unique()->values()->all();
        $unseen = array_values(array_diff($ids, $seen)); shuffle($unseen);
        $rest = array_values(array_diff($ids, $weak, $unseen)); shuffle($rest);
        $pick = array_values(array_unique(array_map('intval', array_merge(array_slice($weak, 0, 1), $unseen, $rest))));
        $pick = array_slice($pick, 0, 3);

        $out = [];
        foreach ($pick as $id) {
            $others = array_values(array_diff($ids, [$id])); shuffle($others);
            $optIds = array_merge([$id], array_slice($others, 0, 2)); shuffle($optIds);
            $out[] = [
                'id' => $id, 'en' => $items[$id]['en'], 'bn' => $items[$id]['bn'], 'emoji' => $items[$id]['emoji'],
                'options' => array_map(fn($x) => ['id' => $x, 'emoji' => $items[$x]['emoji']], $optIds),
            ];
        }
        return ['level' => $child->level, 'items' => $out];
    }

    public function send(Request $r, ModeDetector $d, TutorService $t) {
        $start = microtime(true);
        $r->validate(['message' => 'required|string|max:300']);
        $child = Child::findOrFail(session('child_id'));
        $text = $r->message;
        $mode = $d->detect($text, $child);
        $lesson = null;

        if ($mode === 'learning' && $text === 'Teach me!') {
            $lesson = $this->pickUnit($child);
            $child->streak++;
            $out = ['reply' => "Let's learn new words!", 'bn' => 'চলো নতুন শব্দ শিখি!', 'source' => 'lesson'];
        } else {
            // Force it to be a conversation if they just typed a message
            if ($mode === 'learning') {
                $mode = 'conversation';
            }
            $out = $t->respond($child, $mode, $text);
            $child->streak = 0;
        }
        $child->mood = $mode === 'support' ? 'needs_support' : 'ok';
        $child->save();

        $child->messages()->createMany([
            ['role' => 'user', 'content' => $text, 'mode' => $mode],
            ['role' => 'assistant', 'content' => $out['reply'], 'mode' => $mode],
        ]);

        return response()->json([
            'reply' => $out['reply'], 'bn' => $out['bn'] ?? null, 'mode' => $mode,
            'level' => $child->level, 'xp' => $child->xp, 'lesson' => $lesson,
            'source' => $out['source'], 'ms' => (int) round((microtime(true) - $start) * 1000),
        ]);
    }

    public function answer(Request $r) {
        $r->validate(['level' => 'required|integer|between:1,3', 'id' => 'required|integer|min:0', 'choice' => 'required|integer|min:0']);
        $child = Child::findOrFail(session('child_id'));
        $item = config("lessons.{$r->level}.{$r->id}");
        abort_unless($item, 404);
        $ok = (int) $r->choice === (int) $r->id;

        Attempt::create(['child_id' => $child->id, 'level' => $r->level, 'item_idx' => $r->id, 'word' => $item['en'], 'correct' => $ok]);

        $old = $child->level;
        if ($ok) $child->xp++;
        $child->level = $this->levelFor($child->xp);
        $child->save();

        $good = ['Yes! Great job! 🌟', 'Wow, you got it! 🎉', 'Super! You are so smart! ⭐', 'Hooray! That is right! 🥳'];
        $fb = $ok ? $good[array_rand($good)]
                  : "Good try, {$child->name}! Listen: \"{$item['en']}\". Mistakes help our brain grow! 💛";

        return response()->json([
            'correct' => $ok, 'feedback' => $fb, 'level' => $child->level, 'xp' => $child->xp,
            'leveled_up' => $child->level > $old,
        ]);
    }
}