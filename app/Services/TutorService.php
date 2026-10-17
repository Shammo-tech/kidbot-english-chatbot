<?php
namespace App\Services;
use App\Models\Child;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TutorService {
    public function respond(Child $child, string $mode, string $text): array {
        $history = $child->messages()->latest('id')->take(6)->get()->reverse()
            ->map(fn($m) => ['role'=>$m->role,'content'=>$m->content])->values()->all();

        $messages = array_merge(
            [['role'=>'system','content'=>$this->system($child,$mode)]],
            $history,
            [['role'=>'user','content'=>$text]]
        );

        try {
            $res = Http::withToken(config('services.groq.key'))
                ->connectTimeout(1)->timeout(1.7)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 200,
                    'response_format' => ['type'=>'json_object'],
                ]);
            if ($res->successful()) {
                $data = json_decode($res->json('choices.0.message.content'), true);
                if (!empty($data['reply'])) {
                    return ['reply'=>$data['reply'], 'bn'=>$data['bn'] ?? null, 'correct'=>false, 'source'=>'llm'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Groq failed: '.$e->getMessage());
        }
        
        return ['reply'=>$this->fallback($mode,$child,$text), 'bn'=>null, 'correct'=>false, 'source'=>'fallback'];
    }

    private function fallback(string $mode, Child $c, string $text): string {
        $t = strtolower($text);
        
        if (str_contains($t, 'meow')) return "Yes! A cat says meow! You are so smart! 🐱";
        if (str_contains($t, 'woof') || str_contains($t, 'dog')) return "Woof woof! Dogs are so much fun! 🐶";
        if (str_contains($t, 'joke')) return "Why did the cookie go to the doctor? Because it was feeling crummy! 🍪😂";
        if (str_contains($t, 'hello') || str_contains($t, 'hi')) return "Hello {$c->name}! I am so happy to see you! 🌟";
        if (str_contains($t, 'how are you')) return "I am feeling great, thank you for asking! How are you? 😊";
        
        if (str_contains($t, 'hide and seek') || str_contains($t, 'game')) return "Hide and seek is such a fun game! You must be really fast! 🏃‍♂️💨";
        if (str_contains($t, 'outside')) return "I love the outdoors! It is so nice to play outside. 🌳☀️";
        if (str_contains($t, 'color')) return "Wow, that is a beautiful color! 🎨 What is your favorite animal?";
        if (str_contains($t, 'play')) return "Playing is so much fun! What is your favorite game? 🎮";
        if (str_contains($t, 'like')) return "That sounds awesome! Tell me more about why you like it. 😊";
        
        if ($mode === 'support') {
            $options = [
                "It's okay to feel that way, {$c->name}. Let's breathe in... and out. I'm here with you. 💛",
                "I am here for you. Do you want to take a deep breath together? 🫂"
            ];
            return $options[array_rand($options)];
        }
        
        if ($mode === 'engagement') {
            $options = [
                "Let's play! What sound does a dog make? 🐶",
                "I spy with my little eye... something BLUE! 🔵 Can you find it?",
                "Riddle time! What has hands but cannot clap? A clock! ⏰"
            ];
            return $options[array_rand($options)];
        }
        
        if ($mode === 'conversation') {
            $options = [
                "That is so cool, {$c->name}! Tell me more! 🌟",
                "Wow! I love hearing about that. What else do you like? 😊",
                "You are doing great! What do you want to learn today? 🚀"
            ];
            return $options[array_rand($options)];
        }

        return "Great try! Let's say it together: A, B, C! 🌟";
    }

    private function system(Child $c, string $mode): string {
        $weak = \App\Models\Attempt::where('child_id',$c->id)->where('correct',false)->latest('id')->take(5)->pluck('word')->unique()->implode(', ') ?: 'none yet';
        
        $prompt = "You are Buddy, a kind, playful English-teacher friend for a child under 7.\n";
        $prompt .= "Child: {$c->name}. Level: L{$c->level}. Stars earned: {$c->xp}. Current mode: {$mode}.\n";
        $prompt .= "Words the child found hard recently (weave them in gently): {$weak}.\n\n";
        $prompt .= "RULES: Max 2 short sentences (under 25 words). Very simple words. Use 1 emoji. Never scold.\n";
        $prompt .= "LEVELS: L1 = single words. L2 = short phrases. L3 = full sentences.\n";
        $prompt .= "MODES:\n";
        $prompt .= "- learning: teach ONE small thing, ask ONE tiny question.\n";
        $prompt .= "- conversation: chat warmly about their day. Do NOT lecture.\n";
        $prompt .= "- engagement: be fun: a riddle, animal-sound game, or joke.\n";
        $prompt .= "- support: name the feeling, comfort them. NO lesson.\n";
        $prompt .= "SEL: Help the child name feelings, praise effort, build confidence.\n";
        $prompt .= "The child speaks Bangla and is learning English: use only very common English words.\n";
        $prompt .= "Reply with JSON only: {\"reply\":\"<simple English>\",\"bn\":\"<the same message in simple Bangla>\",\"correct\":null}";

        return $prompt;
    }
}