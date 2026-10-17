# Architecture Diagram

```mermaid
flowchart LR
  U[Child in Browser<br/>Bootstrap UI + Voice] -->|POST /chat| C[Laravel ChatController]
  C --> M[ModeDetector<br/>Rule-based]
  M --> T[TutorService]
  T -->|Last 6 msgs + Level + Weak Words| G[Groq LLM<br/>llama-3.1-8b-instant]
  G -->|JSON reply + Bangla| T
  T -.1.7s timeout.-> F[Smart Fallback]
  T --> C
  C <--> D[(MySQL via Eloquent<br/>children, messages, attempts)]
  C -->|reply, mode, level, xp| U