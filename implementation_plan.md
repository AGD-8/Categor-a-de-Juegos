# APW_Juegos — Game Review Web App

A multi-page PHP/HTML/CSS web application for reviewing, rating and discussing PS4, PS5 and Xbox games, backed by MariaDB.

## Proposed Changes

---

### Database Layer

#### [MODIFY] [database_schema.sql](file:///c:/Users/agira/Desktop/APW_Juegos/database_schema.sql)

Extend the existing schema to include:
- `users` table: `id`, `username`, `password_hash`, `avatar` (filename), `created_at`
- `games` table (expanded): add `description`, `image` (filename), `platform` (PS4/PS5/Xbox)
- `reviews` table: `id`, `game_id`, `user_id`, `rating` (1-5), `comment`, `created_at`
- `chat_messages` table: `id`, `game_id`, `user_id`, `message`, `created_at`

---

### Configuration & Shared Code

#### [NEW] config/db.php
MariaDB PDO connection (host, dbname, user, password constants).

#### [NEW] includes/auth.php
Session helpers: `isLoggedIn()`, `requireLogin()`, `getCurrentUser()`.

#### [NEW] includes/functions.php
Shared helpers: `sanitize()`, `getStarRating()`, `getAverageRating()`, image upload handler.

---

### Stylesheets

#### [NEW] assets/css/style.css
Global variables, reset, typography (Google Fonts – Inter), navbar, buttons, cards, footer.  
Dark/neutral palette with accent color. Card hover effect reveals game image in color.

#### [NEW] assets/css/auth.css
Centered form cards for login/register/profile.

#### [NEW] assets/css/games.css
Game grid with grayscale-to-color hover transition, star rating widget, platform badges.

#### [NEW] assets/css/chat.css
Chat bubble layout, message input area.

---

### Pages

#### [NEW] index.php
Home page: game grid (all platforms). Each card shows title, platform badge, year.  
**Hover effect**: image goes from grayscale → full color (CSS `filter` transition).  
Card footer has ★ average rating + **"Comentar"** button → `chat.php?game_id=X`.

#### [NEW] register.php
Form: username, password, confirm password. Validates match, hashes with `password_hash()`.

#### [NEW] login.php
Form: username + password. Verifies with `password_verify()`, starts session.

#### [NEW] logout.php
Destroys session, redirects to login.

#### [NEW] profile.php *(requires login)*
Three sections:
1. Change username
2. Change password (must provide current password first)
3. Upload avatar (jpg/png, max 2 MB, saved to `assets/uploads/avatars/`)

#### [NEW] add_game.php *(requires login)*
Form fields: name, platform (PS4/PS5/Xbox), developer, year, genre, description, cover image upload.  
Saves image to `assets/uploads/games/`.

#### [NEW] game_detail.php
Shows full game info + cover. Lists all reviews with stars & comments. Inline star-rating form + comment textarea to submit new review. Link to `chat.php?game_id=X`.

#### [NEW] reviews.php
Standalone "add review" page: select game from dropdown, 5-star interactive rating widget (CSS/JS), comment box, submit.

#### [NEW] chat.php *(requires login)*
Per-game chat. Shows game title at top. Message history (bubbles). Text input + send button. Submits via form POST, reloads page (no WebSocket needed for simplicity).

#### [NEW] navbar.php *(include)*
Responsive top navbar: logo, nav links, user avatar + username (if logged in), logout.

#### [NEW] footer.php *(include)*
Simple footer with site name and year.

---

### Assets & Images

#### [NEW] assets/uploads/games/ *(directory)*
Stores uploaded game covers.

#### [NEW] assets/uploads/avatars/ *(directory)*
Stores user avatars.

#### [NEW] assets/img/ *(directory)*
AI-generated default images: site logo, placeholder game cover, default avatar.

Pre-generated cover images for the 5 seeded games (The Last of Us 2, God of War Ragnarök, Bloodborne, Horizon Forbidden West, Ghost of Tsushima).

---

## Verification Plan

### Manual Verification (step by step)

> Run the app via a local PHP server. Navigate to `http://localhost:8000`.

1. **Register** – Go to `/register.php`, fill username/password/confirm. Should redirect to login.
2. **Login** – Go to `/login.php`, enter credentials. Should redirect to `index.php` with navbar showing username.
3. **Home / hover** – Hover over a game card. Image must animate from grayscale to color.
4. **Add Game** – Go to `/add_game.php`, fill all fields + upload an image, submit. Game must appear in `index.php`.
5. **Review** – Click "Comentar" on a game card → `chat.php`. Also test `/reviews.php` star rating widget. Submit review, verify it appears on `game_detail.php`.
6. **Chat** – Send a message in `chat.php`. Verify message appears after page reload with username and timestamp.
7. **Profile** – Go to `/profile.php`, change username, change password (enter old one first), upload avatar. Verify changes persist.
8. **Logout** – Click logout, verify session is destroyed and protected pages redirect to login.
