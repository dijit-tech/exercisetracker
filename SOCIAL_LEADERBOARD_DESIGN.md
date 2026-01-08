# Social Leaderboard Design - Rocketship Progress Visualization

## Concept: Vertical Tower Competition

```
                🚀                      🚀
                █                       █
                █                       █
    🚀          █                       █
    █           █                       █
    █           █                       █          🚀
    █           █                       █          █
    █           █                       █          █
    █           █                       █          █
  ━━█━━       ━━█━━                   ━━█━━      ━━█━━
  User1       User2                   User3      User4
  (15 days)   (18 days)               (18 days)  (12 days)
```

## Visual Elements

### Each User Column Contains:
1. **Rocket 🚀** at the top (animated bounce if active today)
2. **Colored blocks** stacked vertically
   - Each block = 1 day of goal completion
   - Color intensity based on completion percentage that day:
     - 🟩 Dark Green = 100%
     - 🟨 Light Green = 67-99%
     - 🟧 Yellow = 34-66%
     - 🟥 Red = 1-33%
     - ⬜ Gray = 0% (missed day)
3. **Base platform** with username and total score
4. **Hover effect** shows date and % completion for each block

## Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│  🏆 Goal Achievement Leaderboard - January 2026         │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │
│                                                          │
│   ┌────┐  ┌────┐  ┌────┐  ┌────┐  ┌────┐             │
│   │ 🚀 │  │ 🚀 │  │ 🚀 │  │ 🚀 │  │ 🚀 │             │
│   │▓▓▓▓│  │▓▓▓▓│  │████│  │▓▓▓▓│  │████│             │
│   │████│  │▓▓▓▓│  │▓▓▓▓│  │▓▓▓▓│  │▓▓▓▓│             │
│   │▓▓▓▓│  │████│  │▓▓▓▓│  │████│  │▓▓▓▓│             │
│   │████│  │▓▓▓▓│  │████│  │▓▓▓▓│  │████│             │
│   │▓▓▓▓│  │████│  │▓▓▓▓│  │████│  │▓▓▓▓│             │
│   │████│  │▓▓▓▓│  │████│  │▓▓▓▓│  │████│             │
│   │▓▓▓▓│  │████│  │▓▓▓▓│  │████│  │▓▓▓▓│             │
│   │████│  │▓▓▓▓│  │████│  │▓▓▓▓│  │▓▓▓▓│             │
│   │▓▓▓▓│  │████│  │▓▓▓▓│  │████│  │████│             │
│   │████│  │▓▓▓▓│  │████│  │▓▓▓▓│  │▓▓▓▓│             │
│   └────┘  └────┘  └────┘  └────┘  └────┘             │
│   Alice   Bob     Carol   Dave    Eve                 │
│   320pts  415pts  400pts  385pts  350pts              │
│   #4      #1      #2      #3      #5                  │
└─────────────────────────────────────────────────────────┘
```

## Implementation Details

### HTML Structure:
```html
<div class="leaderboard-container">
  <div class="user-tower" data-user-id="1">
    <div class="rocket">🚀</div>
    <div class="tower-blocks">
      <div class="block percent-100" title="Jan 7: 4/4 goals (100%)"></div>
      <div class="block percent-67" title="Jan 6: 3/4 goals (75%)"></div>
      <div class="block percent-100" title="Jan 5: 4/4 goals (100%)"></div>
      <!-- ... more blocks ... -->
    </div>
    <div class="tower-base">
      <div class="username">Alice</div>
      <div class="score">320 pts</div>
      <div class="rank">#4</div>
    </div>
  </div>
  <!-- More user towers... -->
</div>
```

### CSS:
```css
.leaderboard-container {
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 30px;
  padding: 40px;
  overflow-x: auto;
}

.user-tower {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 80px;
}

.rocket {
  font-size: 2em;
  animation: bounce 2s infinite;
  margin-bottom: 10px;
}

.tower-blocks {
  display: flex;
  flex-direction: column-reverse; /* Builds from bottom up */
  gap: 2px;
  min-height: 50px;
}

.block {
  width: 60px;
  height: 15px;
  border-radius: 3px;
  transition: all 0.3s;
}

.block:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.tower-base {
  margin-top: 15px;
  text-align: center;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 10px;
  width: 100%;
}
```

## Scoring System
- 100% completion day = 10 points
- 67-99% completion = 7 points
- 34-66% completion = 5 points
- 1-33% completion = 2 points
- 0% completion = 0 points

Current month total determines ranking.

## Interactive Features
1. **Click user tower** → Navigate to their profile/goals
2. **Hover block** → Tooltip shows date and completion details
3. **Animated rocket** → Bounces when user completes goal today
4. **Rank badges** → 🥇 #1, 🥈 #2, 🥉 #3
5. **Month selector** → Compare different months

## Alternative: Horizontal Race Track

```
🏁━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━🏆
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━🚀 Bob (415 pts)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━🚀 Carol (400 pts)
━━━━━━━━━━━━━━━━━━━━━━━━━━━🚀 Dave (385 pts)
━━━━━━━━━━━━━━━━━━━━━━━━🚀 Eve (350 pts)
━━━━━━━━━━━━━━━━━━━━━━🚀 Alice (320 pts)
START
```

Each track segment is colored by completion %.

---

**Which visualization would you prefer?**
1. **Vertical Towers** (Minecraft-style building up)
2. **Horizontal Race Track** (Side-scrolling progress race)

Both show:
- Individual user progress
- Social comparison
- Daily completion blocks with color coding
- Competitive ranking
