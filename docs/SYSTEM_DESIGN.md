```markdown
# System Design

## Overview
Buddy teaches English to children under 7 using a hybrid approach: deterministic lessons for reliability and speed, and an LLM for open conversation and SEL.

## Progressive Learning
- L1: Single words (Cat, Apple)
- L2: Short phrases (Red apple, My dog)
- L3: Full sentences (The cat is sleeping)
- Scoring: +1 star for a correct tap. 6 stars = Level 2, 15 stars = Level 3.

## Mode Detection
A rule-based PHP detector runs on every message (0ms latency).
- **Learning:** Triggered by the "Learn English" button.
- **Support:** Triggered by sad/angry keywords or emojis. No teaching, only comfort.
- **Engagement:** Triggered after 2 learning units in a row to prevent burnout.
- **Conversation:** Default fallback for random chat.

## Social & Emotional Learning (SEL)
- Mistakes trigger: "Mistakes help our brain grow! 💛"
- Support mode uses breathing exercises and kind words.
- Prevents nonstop teaching by switching to games.

## Context & Adaptivity
- The `Attempts` table logs every right/wrong answer.
- Weak words are saved and prioritized in future lessons (35% chance).
- Weak words are injected into the AI system prompt.

## Latency Strategy
- The curriculum takes milliseconds (no AI needed).
- AI calls use Groq (Llama 3.1 8B), the fastest LLM available.
- Hard 1.7-second timeout. If the AI is slow, a smart local fallback based on keywords responds instantly.

## Trade-offs
- Rule-based mode detection is fast but less nuanced than an LLM classifier.
- Bangla text in lessons is hand-written (100% accurate), while Bangla in chat relies on the LLM (may occasionally be imperfect).