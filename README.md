# Buddy - AI-Powered English Learning Chatbot for Kids

**Live URL:** https://buddy-english.up.railway.app/

Buddy is a hybrid-architecture English learning app designed for children under 7. Because young children cannot read or type well, Buddy uses a "Listen → Say → Tap" curriculum, voice recognition, and text-to-speech, along with an LLM for open conversation and emotional support.

## Features
- **Progressive Learning:** L1 (Single Words), L2 (Short Phrases), L3 (Full Sentences).
- **Child Mode Detection:** Rule-based PHP detector switches between Learning, Conversation, Engagement (games), and Support (SEL).
- **Social & Emotional Learning (SEL):** Comforts the child when sad, praises effort after mistakes, and prevents teaching burnout (switches to games after 2 units).
- **Context-Aware:** Uses an `Attempts` table to track wrong words and feeds them back into the AI prompt and future quizzes.
- **Bangla Scaffolding:** Native language toggle for text and audio, so non-English speakers can learn.
- **Latency Guarantee:** < 2 seconds. The AI is a fast Llama 3.1 model on Groq, with a hard 1.7-second timeout and a smart local fallback to prevent crashes.

## Tech Stack
- **Backend:** Laravel 11, PHP 8.2
- **Database:** MySQL (Railway)
- **Frontend:** Blade, Bootstrap 5, Vanilla JS
- **AI:** Groq API (Llama 3.1 8B Instant)
- **Voice:** Browser Web Speech API

## Local Setup
1. Clone the repo: `git clone https://github.com/Shammo-tech/kidbot-english-chatbot.git`
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Add your `GROQ_API_KEY` and MySQL database credentials to `.env`
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan serve`

## Deployment
Deployed via Railway. The `Pre-deploy Command` is set to `php artisan migrate --force` to ensure the database schema is always up to date.