# Sbytes Restrict Preview for Tutor LMS

**Ask visitors to log in or register before they watch free preview lessons in Tutor LMS — then send them straight to the lesson.**

![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)
![Requires](https://img.shields.io/badge/requires-Tutor%20LMS-1E2A78)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

Tutor LMS lets you mark lessons as free previews so anyone can watch them. That is
great for marketing, but it means visitors can consume your sample content without
ever telling you who they are.

This plugin asks for a free account first. Guests see a tabbed **Log In / Sign Up**
form in place of the lesson, and once they are in they land on the exact lesson they
clicked — no dead ends, no hunting through the course again.

---

## Contents

- [What it does](#what-it-does)
- [Who is never affected](#who-is-never-affected)
- [Requirements](#requirements)
- [Installation](#installation)
- [Settings](#settings)
- [How it works](#how-it-works)
- [For developers](#for-developers)
- [FAQ](#faq)
- [Privacy](#privacy)
- [Project layout](#project-layout)
- [Contributing](#contributing)
- [License](#license)

---

## What it does

| | |
|---|---|
| **Gates preview lessons** | Visitors must be logged in before a free preview lesson will play. |
| **Asks in place** | A tabbed Log In / Sign Up form appears where the lesson was, without leaving the page. |
| **Returns them to the lesson** | After logging in or signing up, the visitor lands on the lesson they clicked. |
| **Badges the curriculum** | Optionally marks preview lessons and shows a free-lesson count on each section. |
| **Matches your theme** | Heading, message, badge wording and accent colour are all editable in Settings. |

## Who is never affected

Enrolled students, instructors and administrators keep the access they already have.
Only logged-out visitors are asked to sign in.

Lessons that are **not** marked as previews are left completely alone, so Tutor LMS's
normal enrolment rules continue to apply to them unchanged. Courses marked public are
skipped too — opening everything to everyone was a deliberate decision, and the plugin
does not second-guess it.

## Requirements

| | |
|---|---|
| WordPress | 5.8 or newer |
| PHP | 7.4 or newer |
| Plugins | [Tutor LMS](https://wordpress.org/plugins/tutor/) (active) |
| Build step | None — plain, unminified PHP, CSS and JavaScript |

## Installation

1. Install and activate **Tutor LMS** if you have not already.
2. Upload this plugin to `/wp-content/plugins/`, or install it from the Plugins screen.
3. Activate the plugin.
4. Visit **Settings → Restrict Preview** to adjust the wording, colour and options.

The gate and the badges are both on as soon as you activate, so there is nothing you
must configure first.

> **To offer sign up as well as log in**, enable **Anyone can register** under
> **Settings → General**. Without it, Tutor's registration handler will not process new
> accounts, so the Sign Up tab hides itself automatically rather than becoming a dead end.

## Settings

Everything lives on one screen: **Settings → Restrict Preview**.

| Section | Setting | Default | What it does |
|---|---|---|---|
| Behaviour | Restrict preview lessons | On | The gate itself. |
| Behaviour | Show curriculum badges | On | Lesson badge and per-section free-lesson count. |
| Behaviour | Show the Sign Up tab | On | Hidden automatically when WordPress registration is off. |
| Wording | Badge label | *Free preview* | Small label above the heading, on the prompt and the popup. |
| Wording | Curriculum lesson badge | *Free Preview* | The badge added to preview lessons in the curriculum. |
| Wording | Popup heading | *Log in to watch this lesson* | Headline inside the popup. |
| Wording | Popup message | *It's free — you'll go straight to the lesson once you're in.* | Body copy inside the popup. |
| Wording | Lesson page message | *This lesson is free to preview…* | Body copy on the locked lesson page. |
| Appearance | Accent colour | `#1E2A78` | Used by the prompt and the badges. |
| Appearance | Account page URL | *(empty)* | Optional fallback link, used only if Tutor's login form cannot be rendered. |

Clearing a text field restores its default. Defaults are translated when they are read
rather than written to the database, so changing the site language changes the wording
with it instead of freezing the activation-time language in options.

## How it works

**The gate is server-side.** Preview lesson content is replaced on `the_content` before
the page is sent, so a direct URL, a disabled-JavaScript browser or a crawler all get
the same prompt. There is no client-side hiding to defeat.

**Authentication is Tutor's.** The plugin embeds Tutor's own `[tutor_login]` and
`[tutor_student_registration_form]` shortcodes, so validation, password handling and
account creation all stay where they belong. Nothing is reimplemented.

**The redirect uses Tutor's own field.** The front-end script writes `redirect_to` into
both forms, which Tutor honours natively. A short-lived cookie is a fallback for flows
that drop that field — it is cleared on read, must resolve to a real lesson on this
site, and therefore cannot be turned into an open redirect.

**Badges are matched from real data.** Preview lessons are resolved from Tutor's preview
meta and matched to the curriculum by URL — never by scanning the rendered page for the
word "Free", which many courses never print at all.

## For developers

Two filters:

```php
/**
 * Decide who may view preview lessons.
 * Default: is_user_logged_in()
 */
add_filter( 'rptl_user_may_view_preview', function ( $allowed ) {
	// Example: also let anyone with an active subscription through.
	return $allowed || current_user_can( 'read_private_posts' );
} );

/**
 * Decide whether the plugin runs on the current request.
 * Default: singular course or lesson.
 */
add_filter( 'rptl_is_relevant_context', function ( $relevant ) {
	// Example: a curriculum rendered inside a page builder template.
	return $relevant || is_page( 'course-catalogue' );
} );
```

## FAQ

<details>
<summary><strong>Does this restrict paid or enrolled course content?</strong></summary>

No. It only affects lessons marked as free previews in Tutor LMS. Every other lesson
keeps Tutor's normal enrolment gating, untouched.
</details>

<details>
<summary><strong>Can someone bypass it by pasting the lesson URL?</strong></summary>

No. The content is replaced on the server before the page is sent, so a direct URL, a
disabled-JavaScript browser or a crawler all get the same prompt.
</details>

<details>
<summary><strong>Why is the Sign Up tab missing?</strong></summary>

WordPress registration is switched off. Turn on **Anyone can register** under
**Settings → General**. The tab is hidden rather than shown as a dead end, because
Tutor's registration handler refuses to create accounts while that setting is off.
</details>

<details>
<summary><strong>The badges are not appearing on my course page.</strong></summary>

Badges are matched to real preview lessons by their URL, and they need a link to attach
to. Themes that render the curriculum with heavily customised markup may not expose one.
The plugin never guesses from the rendered text, because many courses never display the
word "Free" at all.
</details>

<details>
<summary><strong>Does it work with page builders?</strong></summary>

The server-side gate works everywhere. If your curriculum is rendered somewhere unusual
and the assets do not load, use the `rptl_is_relevant_context` filter to force them on.
</details>

<details>
<summary><strong>What happens when I remove the plugin?</strong></summary>

Deleting it removes its single settings row and nothing else — on every site of a
network, if you run multisite. Your courses, lessons, preview flags and user accounts
are untouched, so previews go back to Tutor's default behaviour.
</details>

## Privacy

No external requests, no remote assets, and no personal data collected, stored or
transmitted. The plugin reads Tutor LMS course and lesson data already in your database
and stores one settings row. A short-lived cookie is set in the visitor's browser only
to remember which lesson they were trying to reach, so they can be returned to it after
signing in.

## Project layout

```
sbytes-restrict-preview-for-tutor-lms.php   bootstrap, constants, activation
includes/
  class-rptl-plugin.php                     wires the pieces together
  class-rptl-settings.php                   options, defaults, admin screen
  class-rptl-lessons.php                    preview/course resolution, context
  class-rptl-access.php                     the gate and the post-login redirect
  class-rptl-auth-ui.php                    locked-lesson and popup markup
  class-rptl-assets.php                     conditional CSS/JS loading
assets/css, assets/js                       front-end styles and script
languages/                                  .pot translation template
uninstall.php                               removes the settings row
```

## Contributing

Issues and pull requests are welcome at
[salma25128/sbytes-restrict-preview-for-tutor-lms](https://github.com/salma25128/sbytes-restrict-preview-for-tutor-lms).
The code follows WordPress coding standards; there is no build step, so what ships is
what you read.

## License

[GPL-2.0-or-later](LICENSE.txt).

Tutor LMS is a trademark of Themeum. This plugin is an independent, third-party
extension and is not affiliated with, endorsed by, or sponsored by Themeum.
