<?php
namespace App\Services;
use App\Models\Child;

class ModeDetector {
    const SUPPORT = ['sad','cry','crying','scared','afraid','angry','mad','hate','hurt','lonely','tired','sick','upset','worried','nobody','miss mom','miss dad','too hard',"can't","cant"];
    const BORED   = ['bored','boring','idk','stop','later','no more',"i don't know",'dont know','joke','game','riddle','sing','song'];
    const LEARN   = ['teach','learn','quiz','word','spell','abc','letter','color','colour','number','animal','what is','how do you say'];
    const CHAT    = ['hi','hello','hey','how are you','my name','i like','i love','my dog','my cat','my mom','my dad','play','story','friend'];

    private function has(string $t, array $words): bool {
        foreach ($words as $w) {
            if (preg_match('/\b'.preg_quote($w,'/').'\b/i', $t)) return true;
        }
        return false;
    }

    public function detect(string $text, Child $child): string {
        $t = trim($text);
        if ($this->has($t, self::SUPPORT)) return 'support';
        if ($this->has($t, self::BORED) || mb_strlen($t) <= 1) return 'engagement';
        if ($child->streak >= 2) return 'engagement'; // Don't teach nonstop
        if ($this->has($t, self::LEARN)) return 'learning';

        $last = $child->messages()->where('role','assistant')->latest('id')->value('content');
        $answering = $last && str_contains($last,'?') && str_word_count($t) <= 4;
        if ($answering && !str_contains($last, 'cat make')) return 'learning';
        if ($this->has($t, self::CHAT)) return 'conversation';
        return $last ? 'learning' : 'conversation';
    }
}